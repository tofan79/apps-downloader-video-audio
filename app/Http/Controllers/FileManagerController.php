<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;

class FileManagerController extends Controller
{
    public function index()
    {
        $downloadPath = config('apps_dlp.download_path', storage_path('downloads'));
        
        // Ensure path exists
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

        return Inertia::render('FileManager', [
            'files' => $files,
        ]);
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string'
        ]);

        $downloadPath = config('apps_dlp.download_path', storage_path('downloads'));
        // For security, prevent path traversal
        $safePath = str_replace(['..', '..\\'], '', $validated['path']);
        $fullPath = rtrim($downloadPath, '/') . '/' . ltrim($safePath, '/');

        if (File::exists($fullPath)) {
            File::delete($fullPath);
            
            // Sync with history
            DB::table('downloads')->where('file_path', $fullPath)->delete();
            
            return redirect()->back()->with('success', 'File deleted successfully.');
        }

        return redirect()->back()->withErrors(['message' => 'File not found.']);
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'path' => 'required|string'
        ]);

        $downloadPath = config('apps_dlp.download_path', storage_path('downloads'));
        $safePath = str_replace(['..', '..\\'], '', $validated['path']);
        $fullPath = rtrim($downloadPath, '/') . '/' . ltrim($safePath, '/');

        if (File::exists($fullPath)) {
            return response()->download($fullPath);
        }

        return redirect()->back()->withErrors(['message' => 'File not found.']);
    }
}
