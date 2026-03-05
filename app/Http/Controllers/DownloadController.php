<?php

namespace App\Http\Controllers;

use App\Services\YtDlpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Symfony\Component\Process\Process;
use App\Jobs\ProcessDownload;

class DownloadController extends Controller
{
    protected YtDlpService $ytdlpService;

    public function __construct(YtDlpService $ytdlpService)
    {
        $this->ytdlpService = $ytdlpService;
    }

    /**
     * Show home page
     */
    public function index()
    {
        $stats = [
            'total' => DB::table('downloads')->where('status', 'done')->count(),
            'video' => DB::table('downloads')->where('status', 'done')->where('type', 'video')->count(),
            'audio' => DB::table('downloads')->where('status', 'done')->where('type', 'audio')->count(),
            'playlist' => DB::table('downloads')->where('status', 'done')->where('type', 'playlist')->count(),
        ];

        // Fetch History
        $downloads = DB::table('downloads')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // Fetch Files
        $downloadPath = config('apps_dlp.download_path', storage_path('downloads'));
        if (!File::exists($downloadPath)) {
            File::makeDirectory($downloadPath, 0755, true);
        }

        $directories = ['video', 'audio'];
        $files = [];

        foreach ($directories as $dir) {
            $path = $downloadPath . '/' . $dir;
            if (File::exists($path)) {
                $allFiles = File::allFiles($path);
                foreach ($allFiles as $file) {
                    $files[] = [
                        'name' => $file->getFilename(),
                        'path' => $dir . '/' . $file->getRelativePathname(),
                        'type' => $dir,
                        'size' => $file->getSize(),
                        'ext' => $file->getExtension(),
                        'last_modified' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
            }
        }

        // Sort by newest first
        usort($files, function($a, $b) {
            return strtotime($b['last_modified']) - strtotime($a['last_modified']);
        });

        return Inertia::render('Home', [
            'stats' => $stats,
            'downloads' => $downloads,
            'files' => $files,
        ]);
    }

    /**
     * Fetch metadata from URL
     */
    public function fetch(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'use_cookies' => 'boolean',
        ]);

        try {
            $metadata = $this->ytdlpService->fetchMetadata(
                $validated['url'],
                $validated['use_cookies'] ?? false
            );

            return response()->json($metadata);
        } catch (\Exception $e) {
            Log::error('Fetch metadata error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Start download
     */
    public function download(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'title' => 'nullable|string',
            'platform' => 'nullable|string',
            'type' => 'required|in:video,audio,playlist',
            'media_type' => 'required|in:video,audio',
            'format' => 'required|string',
            'use_cookies' => 'boolean',
            'playlist_items' => 'nullable|string',
        ]);

        try {
            // Create download record
            $download = DB::table('downloads')->insertGetId([
                'url' => $validated['url'],
                'title' => $validated['title'] ?? 'Unknown',
                'platform' => $validated['platform'] ?? 'Unknown',
                'type' => $validated['type'],
                'format' => $validated['format'],
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $jobId = 'dl_' . $download;

            // Update progress record
            DB::table('download_progress')->insert([
                'job_id' => $jobId,
                'percent' => 0,
                'status' => 'pending',
                'updated_at' => now(),
            ]);

            // Dispatch download process to background queue
            ProcessDownload::dispatch($jobId, $download, $validated);

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'download_id' => $download,
            ]);
        } catch (\Exception $e) {
            Log::error('Download start error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get download progress
     */
    public function progress(string $jobId)
    {
        $progress = DB::table('download_progress')
            ->where('job_id', $jobId)
            ->first();

        if (!$progress) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json([
            'percent' => (int) $progress->percent,
            'speed' => $progress->speed,
            'eta' => $progress->eta,
            'status' => $progress->status,
        ]);
    }

    /**
     * Upload cookies file
     */
    public function uploadCookies(Request $request)
    {
        $validated = $request->validate([
            'cookies' => 'required|file|mimes:txt',
        ]);

        try {
            $cookiesPath = config('apps_dlp.cookies_path');
            $directory = dirname($cookiesPath);
            
            // Ensure directory exists
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Get the uploaded file
            $uploadedFile = $request->file('cookies');
            
            // Move the file directly to the target location
            $uploadedFile->move($directory, basename($cookiesPath));

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Cookies upload error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Check if cookies file exists
     */
    public function checkCookies()
    {
        $cookiesPath = config('apps_dlp.cookies_path');
        $exists = file_exists($cookiesPath);

        return response()->json(['exists' => $exists]);
    }

    /**
     * Show download history
     */
    public function history()
    {
        $downloads = DB::table('downloads')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('History', [
            'downloads' => $downloads,
        ]);
    }

    /**
     * Clear download history
     */
    public function clearHistory()
    {
        DB::table('downloads')->truncate();
        DB::table('download_progress')->truncate();

        // Sync with files
        $downloadPath = config('apps_dlp.download_path', storage_path('downloads'));
        $directories = ['video', 'audio'];
        foreach ($directories as $dir) {
            $path = $downloadPath . '/' . $dir;
            if (File::exists($path)) {
                File::cleanDirectory($path);
            }
        }

        return redirect()->back();
    }
}
