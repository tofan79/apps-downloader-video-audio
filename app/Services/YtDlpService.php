<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class YtDlpService
{
    protected string $ytdlpPath;
    protected string $downloadPath;
    protected string $cookiesPath;

    public function __construct()
    {
        $this->ytdlpPath = config('apps_dlp.ytdlp_path', '/usr/local/bin/yt-dlp');
        $this->downloadPath = config('apps_dlp.download_path', storage_path('downloads'));
        $this->cookiesPath = config('apps_dlp.cookies_path', storage_path('cookies/cookies.txt'));
    }

    /**
     * Fetch metadata from URL
     */
    public function fetchMetadata(string $url, bool $useCookies = false): array
    {
        $command = [
            $this->ytdlpPath,
            '--dump-single-json',
            '--no-download',
            '--ignore-errors',
            '--no-warnings',
            '--quiet',
            '--flat-playlist',
        ];

        if ($useCookies && file_exists($this->cookiesPath)) {
            $command[] = '--cookies';
            $command[] = $this->cookiesPath;
        }

        $command[] = $url;

        $process = new Process($command);
        $process->setTimeout(30);
        $process->run();

        // Even with --ignore-errors, yt-dlp might return non-zero exit code if some videos in a playlist error out.
        // We evaluate success by checking if we received a valid JSON output.
        $output = $process->getOutput();
        $data = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
            Log::error('Failed to fetch metadata (Invalid JSON): ' . $process->getErrorOutput());
            throw new \Exception('Failed to fetch metadata. Please make sure the URL is accessible.');
        }

        return [
            'id' => $data['id'] ?? null,
            'title' => $data['title'] ?? 'Unknown',
            'thumbnail' => $data['thumbnail'] ?? null,
            'duration' => $data['duration'] ?? 0,
            'platform' => $data['extractor_key'] ?? 'Unknown',
            'is_playlist' => $data['_type'] === 'playlist',
            'playlist_count' => $data['playlist_count'] ?? 0,
            'entries' => $this->extractPlaylistEntries($data),
        ];
    }

    /**
     * Download single video
     */
    public function downloadVideo(
        string $url,
        string $format,
        int $downloadId,
        bool $useCookies = false
    ): Process {
        $outputPath = $this->downloadPath . "/video/%(title)s [{$format}].%(ext)s";
        $command = [
            $this->ytdlpPath,
            '--ignore-errors',
            '--no-warnings',
        ];

        if ($useCookies && file_exists($this->cookiesPath)) {
            $command[] = '--cookies';
            $command[] = $this->cookiesPath;
        }

        // Format selection based on quality
        switch ($format) {
            case 'best':
                $command[] = '-f';
                $command[] = 'bestvideo[ext=mp4][vcodec^=avc1]+bestaudio[ext=m4a]/bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best';
                break;
            case 'x_format':
                $command[] = '-f';
                $command[] = 'bv*[vcodec^=avc1]+ba[acodec^=mp4a]/b[ext=mp4]';
                break;
            case '1080p':
                $command[] = '-f';
                $command[] = 'bestvideo[height<=1080][ext=mp4]+bestaudio[ext=m4a]/bestvideo[height<=1080]+bestaudio/best[height<=1080]';
                break;
            case '720p':
                $command[] = '-f';
                $command[] = 'bestvideo[height<=720][ext=mp4]+bestaudio[ext=m4a]/bestvideo[height<=720]+bestaudio/best[height<=720]';
                break;
            case '480p':
                $command[] = '-f';
                $command[] = 'bestvideo[height<=480][ext=mp4]+bestaudio[ext=m4a]/bestvideo[height<=480]+bestaudio/best[height<=480]';
                break;
            case '360p':
                $command[] = '-f';
                $command[] = 'bestvideo[height<=360][ext=mp4]+bestaudio[ext=m4a]/bestvideo[height<=360]+bestaudio/best[height<=360]';
                break;
        }

        $command[] = '--merge-output-format';
        $command[] = 'mp4';
        $command[] = '-o';
        $command[] = $outputPath;
        $command[] = '--progress';
        $command[] = '--newline';
        $command[] = $url;

        $process = new Process($command);
        $process->setTimeout(null); // No timeout for downloads
        
        return $process;
    }

    /**
     * Download single audio
     */
    public function downloadAudio(
        string $url,
        string $format,
        int $downloadId,
        bool $useCookies = false
    ): Process {
        $outputPath = $this->downloadPath . "/audio/%(title)s [{$format}].%(ext)s";
        $command = [
            $this->ytdlpPath, 
            '-x',
            '--ignore-errors',
            '--no-warnings',
        ];

        if ($useCookies && file_exists($this->cookiesPath)) {
            $command[] = '--cookies';
            $command[] = $this->cookiesPath;
        }

        // Audio format selection
        switch ($format) {
            case 'mp3_320':
                $command[] = '--audio-format';
                $command[] = 'mp3';
                $command[] = '--audio-quality';
                $command[] = '0'; // 320kbps
                break;
            case 'mp3_192':
                $command[] = '--audio-format';
                $command[] = 'mp3';
                $command[] = '--audio-quality';
                $command[] = '2'; // 192kbps
                break;
            case 'aac':
                $command[] = '--audio-format';
                $command[] = 'aac';
                break;
            case 'flac':
                $command[] = '--audio-format';
                $command[] = 'flac';
                break;
        }

        $command[] = '-o';
        $command[] = $outputPath;
        $command[] = '--progress';
        $command[] = '--newline';
        $command[] = $url;

        $process = new Process($command);
        $process->setTimeout(null);
        
        return $process;
    }

    /**
     * Download playlist
     */
    public function downloadPlaylist(
        string $url,
        string $type,
        string $format,
        int $downloadId,
        bool $useCookies = false,
        ?string $playlistItems = null
    ): Process {
        $baseDir = $type === 'video' ? 'video' : 'audio';
        
        if ($type === 'video') {
            $outputPath = $this->downloadPath . "/video/%(playlist_title)s/%(playlist_index)02d - %(title)s [{$format}].%(ext)s";
        } else {
            $outputPath = $this->downloadPath . "/audio/%(playlist_title)s/%(playlist_index)02d - %(title)s [{$format}].%(ext)s";
        }

        $command = [
            $this->ytdlpPath,
            '--ignore-errors',
            '--no-warnings',
        ];

        if ($useCookies && file_exists($this->cookiesPath)) {
            $command[] = '--cookies';
            $command[] = $this->cookiesPath;
        }

        if ($type === 'video') {
            switch ($format) {
                case 'best':
                    $command[] = '-f';
                    $command[] = 'bestvideo[ext=mp4][vcodec^=avc1]+bestaudio[ext=m4a]/bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best';
                    break;
                case 'x_format':
                    $command[] = '-f';
                    $command[] = 'bv*[vcodec^=avc1]+ba[acodec^=mp4a]/b[ext=mp4]';
                    break;
                case '1080p':
                    $command[] = '-f';
                    $command[] = 'bestvideo[height<=1080][ext=mp4]+bestaudio[ext=m4a]/bestvideo[height<=1080]+bestaudio/best[height<=1080]';
                    break;
                case '720p':
                    $command[] = '-f';
                    $command[] = 'bestvideo[height<=720][ext=mp4]+bestaudio[ext=m4a]/bestvideo[height<=720]+bestaudio/best[height<=720]';
                    break;
                case '480p':
                    $command[] = '-f';
                    $command[] = 'bestvideo[height<=480][ext=mp4]+bestaudio[ext=m4a]/bestvideo[height<=480]+bestaudio/best[height<=480]';
                    break;
                case '360p':
                    $command[] = '-f';
                    $command[] = 'bestvideo[height<=360][ext=mp4]+bestaudio[ext=m4a]/bestvideo[height<=360]+bestaudio/best[height<=360]';
                    break;
                default:
                    $command[] = '-f';
                    $command[] = 'bestvideo[height<=1080]+bestaudio/best[height<=1080]';
            }
            $command[] = '--merge-output-format';
            $command[] = 'mp4';
        } else {
            $command[] = '-x';
            switch ($format) {
                case 'mp3_320':
                    $command[] = '--audio-format';
                    $command[] = 'mp3';
                    $command[] = '--audio-quality';
                    $command[] = '0';
                    break;
                case 'mp3_192':
                    $command[] = '--audio-format';
                    $command[] = 'mp3';
                    $command[] = '--audio-quality';
                    $command[] = '2';
                    break;
                case 'aac':
                    $command[] = '--audio-format';
                    $command[] = 'aac';
                    break;
                case 'flac':
                    $command[] = '--audio-format';
                    $command[] = 'flac';
                    break;
                default:
                    $command[] = '--audio-format';
                    $command[] = 'mp3';
                    $command[] = '--audio-quality';
                    $command[] = '0';
            }
        }

        if ($playlistItems) {
            $command[] = '-I';
            $command[] = $playlistItems;
        }

        $command[] = '-o';
        $command[] = $outputPath;
        $command[] = '--yes-playlist';
        $command[] = '--progress';
        $command[] = '--newline';
        $command[] = $url;

        $process = new Process($command);
        $process->setTimeout(null);
        
        return $process;
    }

    /**
     * Update yt-dlp to latest version
     */
    public function updateYtDlp(): bool
    {
        $command = [$this->ytdlpPath, '-U'];
        $process = new Process($command);
        $process->setTimeout(120);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Extract playlist entries from metadata
     */
    private function extractPlaylistEntries(array $data): array
    {
        $entries = [];
        
        if (isset($data['entries']) && is_array($data['entries'])) {
            foreach ($data['entries'] as $index => $entry) {
                $entries[] = [
                    'index' => $index + 1,
                    'id' => $entry['id'] ?? null,
                    'title' => $entry['title'] ?? 'Unknown',
                    'thumbnail' => $entry['thumbnail'] ?? null,
                    'duration' => $entry['duration'] ?? 0,
                ];
            }
        }

        return $entries;
    }
}
