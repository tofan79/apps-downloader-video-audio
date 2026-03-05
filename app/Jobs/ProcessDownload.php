<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Services\YtDlpService;
use Illuminate\Support\Facades\Log;

class ProcessDownload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0; // unlimited timeout for long downloads

    protected string $jobId;
    protected int $downloadId;
    protected array $validated;

    public function __construct(string $jobId, int $downloadId, array $validated)
    {
        $this->jobId = $jobId;
        $this->downloadId = $downloadId;
        $this->validated = $validated;
    }

    public function handle(YtDlpService $ytdlpService): void
    {
        try {
            // Update status to downloading
            DB::table('downloads')
                ->where('id', $this->downloadId)
                ->update(['status' => 'downloading', 'updated_at' => now()]);

            DB::table('download_progress')
                ->where('job_id', $this->jobId)
                ->update(['status' => 'downloading', 'updated_at' => now()]);

            // Get appropriate process based on type
            $process = match ($this->validated['type']) {
                'video', 'audio' => $this->validated['media_type'] === 'video'
                    ? $ytdlpService->downloadVideo(
                        $this->validated['url'],
                        $this->validated['format'],
                        $this->downloadId,
                        $this->validated['use_cookies'] ?? false
                    )
                    : $ytdlpService->downloadAudio(
                        $this->validated['url'],
                        $this->validated['format'],
                        $this->downloadId,
                        $this->validated['use_cookies'] ?? false
                    ),
                'playlist' => $ytdlpService->downloadPlaylist(
                    $this->validated['url'],
                    $this->validated['media_type'],
                    $this->validated['format'],
                    $this->downloadId,
                    $this->validated['use_cookies'] ?? false,
                    $this->validated['playlist_items'] ?? null
                ),
            };

            // Read output and update progress real-time
            $buffer = '';
            $process->run(function ($type, $output) use (&$buffer) {
                $buffer .= $output;
                
                // Parse yt-dlp progress
                if (preg_match('/\[download\]\s+(\d+\.?\d*)%/', $output, $matches)) {
                    $percent = (int) floatval($matches[1]);
                    
                    DB::table('download_progress')
                        ->where('job_id', $this->jobId)
                        ->update([
                            'percent' => $percent,
                            'updated_at' => now(),
                        ]);
                }
                
                if (preg_match('/at\s+([\d.]+\s*\w\/s)/', $output, $matches)) {
                    DB::table('download_progress')
                        ->where('job_id', $this->jobId)
                        ->update([
                            'speed' => $matches[1],
                            'updated_at' => now(),
                        ]);
                }
            });

            // With --ignore-errors, yt-dlp might return exit code 1 if a single video in a playlist fails, 
            // but we still want to consider the overall job as "done" rather than a total error.
            if ($process->isSuccessful() || $process->getExitCode() === 1 || $process->getExitCode() === 101) {
                // Check if any file was actually downloaded
                $fileInfo = $this->getLastDownloadedFileInfo($this->validated['media_type']);
                
                if (empty($fileInfo['path'])) {
                    // It means yt-dlp completed (or errored out) but no file was generated.
                    // This is a failure.
                    $errorMsg = $process->getErrorOutput();
                    if (empty($errorMsg)) {
                        $errorMsg = mb_substr(trim($buffer), -250); // Get the last part of standard output if error output is empty
                    }

                    DB::table('downloads')
                        ->where('id', $this->downloadId)
                        ->update([
                            'status' => 'error',
                            'error_msg' => 'Gagal mengunduh file. ' . $errorMsg,
                            'updated_at' => now(),
                        ]);

                    DB::table('download_progress')
                        ->where('job_id', $this->jobId)
                        ->update([
                            'status' => 'error',
                            'updated_at' => now(),
                        ]);
                } else {
                    // Update as done
                    DB::table('downloads')
                        ->where('id', $this->downloadId)
                        ->update([
                            'status' => 'done',
                            'file_path' => $fileInfo['path'],
                            'file_size' => $fileInfo['size'],
                            'updated_at' => now(),
                        ]);

                    DB::table('download_progress')
                        ->where('job_id', $this->jobId)
                        ->update([
                            'percent' => 100,
                            'status' => 'done',
                            'updated_at' => now(),
                        ]);
                }
            } else {
                // Update as error
                DB::table('downloads')
                    ->where('id', $this->downloadId)
                    ->update([
                        'status' => 'error',
                        'error_msg' => $process->getErrorOutput(),
                        'updated_at' => now(),
                    ]);

                DB::table('download_progress')
                    ->where('job_id', $this->jobId)
                    ->update([
                        'status' => 'error',
                        'updated_at' => now(),
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('ProcessDownload Job error: ' . $e->getMessage());
            
            DB::table('downloads')
                ->where('id', $this->downloadId)
                ->update([
                    'status' => 'error',
                    'error_msg' => $e->getMessage(),
                    'updated_at' => now(),
                ]);

            DB::table('download_progress')
                ->where('job_id', $this->jobId)
                ->update([
                    'status' => 'error',
                    'updated_at' => now(),
                ]);
        }
    }

    private function getLastDownloadedFileInfo(string $mediaType): array
    {
        $basePath = config('apps_dlp.download_path') . '/' . $mediaType;
        $files = glob($basePath . '/*');
        
        if (empty($files)) {
            return ['path' => null, 'size' => null];
        }

        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $lastPath = $files[0];
        $size = 0;

        if (is_dir($lastPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($lastPath, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } else {
            $size = filesize($lastPath);
        }

        return ['path' => $lastPath, 'size' => $size];
    }
}
