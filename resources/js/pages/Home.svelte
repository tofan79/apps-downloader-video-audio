<script lang="ts">
    import { router, Link } from '@inertiajs/svelte';
    import UrlInput from '$components/UrlInput.svelte';
    import MetaPreview from '$components/MetaPreview.svelte';
    import FormatPicker from '$components/FormatPicker.svelte';
    import CookiesToggle from '$components/CookiesToggle.svelte';
    import DownloadButton from '$components/DownloadButton.svelte';
    import ProgressBar from '$components/ProgressBar.svelte';
    import Button from '$lib/Button.svelte';

    export let stats: {
        total: number;
        video: number;
        audio: number;
        playlist: number;
    } = { total: 0, video: 0, audio: 0, playlist: 0 };
    export let files: Array<any> = [];
    export let downloads: Array<any> = [];

    let url = '';
    let hasCookies = false;
    let type: 'video' | 'audio' | 'playlist' = 'video';
    let mediaType: 'video' | 'audio' = 'video';
    let format = 'best';
    let isDownloading = false;
    let jobId: string | null = null;
    let progress = 0;
    let speed: string | null = null;
    let eta: string | null = null;
    let status = 'pending';
    let error: string | null = null;
    let downloadedFilePath = '';
    let selectedItems: number[] = [];

    // Metadata
    let metadata: {
        id: string | null;
        title: string;
        thumbnail: string | null;
        duration: number;
        platform: string;
        is_playlist: boolean;
        playlist_count: number;
        entries: Array<any>;
    } | null = null;

    let fetchingMetadata = false;
    let showMetadata = false;

    let pollInterval: number | null = null;

    // Check if cookies exist on mount
    async function checkCookiesExist() {
        try {
            const response = await fetch('/cookies/check', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') ?? '',
                },
            });

            if (response.ok) {
                const data = await response.json();
                hasCookies = data.exists;
            }
        } catch (err) {
            console.error('Failed to check cookies:', err);
        }
    }

    // Check cookies when component mounts
    checkCookiesExist();

    async function handleUrlInput(value: string) {
        url = value;
        error = null;
        showMetadata = false;
        metadata = null;
    }

    async function handlePaste() {
        try {
            const text = await navigator.clipboard.readText();
            url = text.trim();
            error = null;
            showMetadata = false;
            metadata = null;
            mediaType = 'video';

            console.log('Pasted URL:', url);

            if (url && url.includes('http')) {
                await fetchMetadata();
            }
        } catch (err) {
            console.error('Failed to paste:', err);
            error =
                'Failed to access clipboard. Please paste manually using Ctrl+V';
        }
    }

    async function fetchMetadata() {
        if (!url) return;

        fetchingMetadata = true;
        error = null;

        try {
            const response = await fetch('/fetch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') ?? '',
                },
                body: JSON.stringify({
                    url,
                    use_cookies: hasCookies,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to fetch metadata');
            }

            metadata = data;
            type = data.is_playlist
                ? 'playlist'
                : data.duration > 0
                  ? 'video'
                  : 'audio';
            mediaType = type === 'audio' ? 'audio' : 'video';

            // Auto-select all items if it is a playlist
            if (data.is_playlist && data.entries) {
                selectedItems = data.entries.map((e: any) => e.index);
            } else {
                selectedItems = [];
            }

            showMetadata = true;
        } catch (err: any) {
            error = err.message;
            showMetadata = false;
            metadata = null;
        } finally {
            fetchingMetadata = false;
        }
    }

    function handleFormatSelect(selectedFormat: string) {
        format = selectedFormat;
    }

    async function handleCookiesUpload(file: File) {
        const formData = new FormData();
        formData.append('cookies', file);

        const response = await fetch('/cookies', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Failed to upload cookies');
        }

        hasCookies = true;

        // Re-fetch metadata with cookies if URL is present
        if (url) {
            await fetchMetadata();
        }
    }

    async function startDownload() {
        if (!url || !metadata) return;

        isDownloading = true;
        error = null;
        downloadedFilePath = '';

        try {
            const response = await fetch('/download', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') ?? '',
                },
                body: JSON.stringify({
                    url,
                    title: metadata.title,
                    platform: metadata.platform,
                    type: type === 'playlist' ? 'playlist' : mediaType,
                    media_type: mediaType,
                    format,
                    use_cookies: hasCookies,
                    playlist_items:
                        type === 'playlist' ? selectedItems.join(',') : null,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to start download');
            }

            jobId = data.job_id;
            startProgressPolling();
        } catch (err: any) {
            error = err.message;
            isDownloading = false;
        }
    }

    function startProgressPolling() {
        if (!jobId) return;

        pollInterval = window.setInterval(async () => {
            try {
                const response = await fetch(`/progress/${jobId}`);
                const data = await response.json();

                if (response.ok) {
                    progress = data.percent;
                    speed = data.speed;
                    eta = data.eta;
                    status = data.status;

                    if (status === 'done' || status === 'error') {
                        stopProgressPolling();
                        isDownloading = false;

                        if (status === 'done') {
                            downloadedFilePath = '/path/to/downloaded/file'; // Will be updated from backend

                            // Reload stats, files, and history real-time
                            router.reload({
                                only: ['stats', 'downloads', 'files'],
                            });
                        }
                    }
                }
            } catch (err) {
                console.error('Failed to fetch progress:', err);
            }
        }, 1000);
    }

    function stopProgressPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    $: if (!isDownloading && jobId) {
        // Reset for new download
        jobId = null;
        progress = 0;
        speed = null;
        eta = null;
        status = 'pending';
    }

    // --- History & File Manager Functions ---
    let isDeleting: string | null = null;
    let isClearing = false;

    function formatFileSize(bytes: number | null): string {
        if (!bytes) return 'Unknown';
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        return `${size.toFixed(1)} ${units[unitIndex]}`;
    }

    function formatDate(dateString: string): string {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function getTypeIcon(type: string, ext: string = '') {
        if (type === 'video') return '🎬';
        if (type === 'audio') return '🎵';
        if (type === 'playlist') return '📋';
        if (['jpg', 'jpeg', 'png', 'webp'].includes(ext.toLowerCase()))
            return '🖼️';
        return '📄';
    }

    function deleteFile(path: string) {
        if (!confirm(`Hapus file ini?\n\n${path}`)) return;
        isDeleting = path;
        router.post(
            '/files/delete',
            { path },
            {
                onFinish: () => {
                    isDeleting = null;
                },
            },
        );
    }

    function clearHistory() {
        if (
            !confirm(
                'Apakah kamu yakin ingin menghapus seluruh riwayat unduhan?',
            )
        )
            return;
        isClearing = true;
        router.delete('/history', {
            onFinish: () => {
                isClearing = false;
            },
        });
    }
</script>

<svelte:head>
    <title>Dashboard - Apps-Downloader-Video-Audio</title>
</svelte:head>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-12 font-sans">
    <!-- Top Nav / Header -->
    <header
        class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm sticky top-0 z-10 mb-8"
    >
        <div
            class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between"
        >
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold tracking-tight shadow-md"
                >
                    AD
                </div>
                <h1
                    class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400"
                >
                    Apps-Downloader-Video-Audio
                </h1>
            </div>
            <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                Single Page Dashboard
            </div>
        </div>
    </header>

    <div class="max-w-6xl mx-auto space-y-8 px-4">
        <!-- Dashboard Stats -->
        <div>
            <h1
                class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white mb-6"
            >
                Dashboard
            </h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1 -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <h3
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            Total Downloads
                        </h3>
                        <svg
                            class="w-4 h-4 text-gray-500 dark:text-gray-400"
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            ><path
                                d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"
                            /><polyline points="7 10 12 15 17 10" /><line
                                x1="12"
                                x2="12"
                                y1="15"
                                y2="3"
                            /></svg
                        >
                    </div>
                    <p
                        class="text-2xl font-bold text-gray-900 dark:text-white mt-2"
                    >
                        {stats?.total || 0}
                    </p>
                </div>
                <!-- Card 2 -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <h3
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            Video Unduhan
                        </h3>
                        <div class="text-xl">🎬</div>
                    </div>
                    <p
                        class="text-2xl font-bold text-gray-900 dark:text-white mt-2"
                    >
                        {stats?.video || 0}
                    </p>
                </div>
                <!-- Card 3 -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <h3
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            Audio Unduhan
                        </h3>
                        <div class="text-xl">🎵</div>
                    </div>
                    <p
                        class="text-2xl font-bold text-gray-900 dark:text-white mt-2"
                    >
                        {stats?.audio || 0}
                    </p>
                </div>
                <!-- Card 4 -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <h3
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            Playlist Unduhan
                        </h3>
                        <div class="text-xl">📋</div>
                    </div>
                    <p
                        class="text-2xl font-bold text-gray-900 dark:text-white mt-2"
                    >
                        {stats?.playlist || 0}
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 lg:p-8"
        >
            <div class="mb-6">
                <h2
                    class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white"
                >
                    Unduh Baru
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Paste URL dari platform yang didukung ke dalam kotak di
                    bawah ini.
                </p>
            </div>
            <!-- URL Input -->
            <UrlInput
                value={url}
                disabled={isDownloading}
                onInput={handleUrlInput}
                onPaste={handlePaste}
            />

            <!-- Error Message -->
            {#if error}
                <div
                    class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg"
                >
                    <p class="text-sm text-red-600 dark:text-red-400">
                        {error}
                    </p>
                </div>
            {/if}

            <!-- Fetching Metadata Loading -->
            {#if fetchingMetadata}
                <div class="mt-4 flex items-center justify-center py-8">
                    <svg
                        class="animate-spin h-8 w-8 text-blue-600"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        />
                    </svg>
                    <span class="ml-3 text-gray-700 dark:text-gray-300"
                        >Fetching metadata...</span
                    >
                </div>
            {/if}

            <!-- Metadata Preview -->
            {#if showMetadata && metadata}
                <MetaPreview
                    title={metadata.title}
                    thumbnail={metadata.thumbnail}
                    duration={metadata.duration}
                    platform={metadata.platform}
                    isPlaylist={metadata.is_playlist}
                    playlistCount={metadata.playlist_count}
                    entries={metadata.entries}
                    {selectedItems}
                    onSelectionChange={(items) => (selectedItems = items)}
                />
            {/if}

            {#if showMetadata}
                <!-- Cookies Toggle -->
                <div class="mt-4">
                    <CookiesToggle
                        {hasCookies}
                        disabled={isDownloading}
                        onUpload={handleCookiesUpload}
                    />
                </div>

                <!-- Media Type Toggle -->
                <div class="mt-6 space-y-2">
                    <p
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Mode Download
                    </p>
                    <div class="flex gap-2">
                        <Button
                            variant={mediaType === 'video'
                                ? 'default'
                                : 'outline'}
                            onclick={() => {
                                mediaType = 'video';
                                handleFormatSelect('');
                            }}
                            disabled={isDownloading}
                        >
                            Video
                        </Button>
                        <Button
                            variant={mediaType === 'audio'
                                ? 'default'
                                : 'outline'}
                            onclick={() => {
                                mediaType = 'audio';
                                handleFormatSelect('');
                            }}
                            disabled={isDownloading}
                        >
                            Audio
                        </Button>
                    </div>
                </div>

                <!-- Format Picker -->
                <div class="mt-4">
                    <FormatPicker
                        type={mediaType}
                        {format}
                        disabled={isDownloading}
                        onSelect={handleFormatSelect}
                    />
                </div>

                <!-- Download Button -->
                <DownloadButton
                    disabled={!url ||
                        !metadata ||
                        (type === 'playlist' && selectedItems.length === 0)}
                    {isDownloading}
                    onClick={startDownload}
                />

                <!-- Progress Bar -->
                <ProgressBar
                    percent={progress}
                    {speed}
                    {eta}
                    {status}
                    {isDownloading}
                />
            {/if}
        </div>

        <!-- FILE MANAGER SECTION -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 lg:p-8"
        >
            <div class="mb-6">
                <h2
                    class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white"
                >
                    📁 File Manager
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Kelola file unduhan yang tersimpan secara lokal.
                </p>
            </div>

            <div
                class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                {#if files.length === 0}
                    <div
                        class="text-center py-16 bg-gray-50 dark:bg-gray-800/50"
                    >
                        <div class="text-5xl mb-4">📭</div>
                        <h3
                            class="text-lg font-medium text-gray-900 dark:text-white"
                        >
                            Folder Masih Kosong
                        </h3>
                        <p class="text-gray-500">
                            Belum ada file yang berhasil di-download.
                        </p>
                    </div>
                {:else}
                    <div class="overflow-x-auto max-h-[400px]">
                        <table
                            class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 relative"
                        >
                            <thead
                                class="bg-gray-50 dark:bg-gray-900 sticky top-0 z-10 shadow-sm"
                            >
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >Nama File</th
                                    >
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >Tipe</th
                                    >
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >Ukuran</th
                                    >
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >Diubah Pada</th
                                    >
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"
                                        >Aksi</th
                                    >
                                </tr>
                            </thead>
                            <tbody
                                class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                            >
                                {#each files as file}
                                    <tr
                                        class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                    >
                                        <td class="px-6 py-4">
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <div class="text-2xl">
                                                    {getTypeIcon(
                                                        file.type,
                                                        file.ext,
                                                    )}
                                                </div>
                                                <div>
                                                    <div
                                                        class="text-sm font-medium text-gray-900 dark:text-white max-w-sm truncate"
                                                        title={file.name}
                                                    >
                                                        {file.name}
                                                    </div>
                                                    <div
                                                        class="text-xs text-gray-500 dark:text-gray-400"
                                                    >
                                                        {file.path}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 capitalize"
                                        >
                                            {file.type}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            {formatFileSize(file.size)}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            {file.last_modified}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                        >
                                            <div
                                                class="flex items-center justify-end gap-2"
                                            >
                                                <a
                                                    href={`/files/download?path=${encodeURIComponent(file.path)}`}
                                                    download
                                                    class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400 px-3 py-1 rounded bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 transition"
                                                >
                                                    Ke PC
                                                </a>
                                                <button
                                                    onclick={() =>
                                                        deleteFile(file.path)}
                                                    disabled={isDeleting ===
                                                        file.path}
                                                    class="text-red-600 hover:text-red-900 dark:hover:text-red-400 px-3 py-1 rounded bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 transition disabled:opacity-50"
                                                >
                                                    {isDeleting === file.path
                                                        ? '...'
                                                        : 'Hapus'}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                {/if}
            </div>
        </div>

        <!-- HISTORY SECTION -->
        <div
            class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 lg:p-8"
        >
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h2
                        class="text-xl font-semibold tracking-tight text-gray-900 dark:text-white"
                    >
                        📋 Riwayat Unduhan
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        History dari proses download sebelumnya.
                    </p>
                </div>
                {#if downloads.length > 0}
                    <button
                        onclick={clearHistory}
                        disabled={isClearing}
                        class="px-3 py-1.5 text-sm bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400 rounded-md hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors disabled:opacity-50 font-medium"
                    >
                        {isClearing ? 'Clearing...' : '🗑️ Bersihkan Riwayat'}
                    </button>
                {/if}
            </div>

            <div
                class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
            >
                {#if downloads.length === 0}
                    <div
                        class="text-center py-16 bg-gray-50 dark:bg-gray-800/50"
                    >
                        <svg
                            class="mx-auto h-12 w-12 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"
                            />
                        </svg>
                        <h3
                            class="mt-4 text-sm font-medium text-gray-900 dark:text-white"
                        >
                            Tidak ada riwayat
                        </h3>
                    </div>
                {:else}
                    <div class="overflow-x-auto max-h-[400px]">
                        <table
                            class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 relative"
                        >
                            <thead
                                class="bg-gray-50 dark:bg-gray-900 sticky top-0 z-10 shadow-sm"
                            >
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >Info</th
                                    >
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >Format & Ukuran</th
                                    >
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >Status</th
                                    >
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                        >Tanggal</th
                                    >
                                </tr>
                            </thead>
                            <tbody
                                class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                            >
                                {#each downloads as download (download.id)}
                                    <tr
                                        class="hover:bg-gray-50 dark:hover:bg-gray-900/50"
                                    >
                                        <td class="px-6 py-4">
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <div class="text-2xl">
                                                    {getTypeIcon(download.type)}
                                                </div>
                                                <div class="max-w-xs">
                                                    <div
                                                        class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                                        title={download.title}
                                                    >
                                                        {download.title ||
                                                            'Unknown'}
                                                    </div>
                                                    <a
                                                        href={download.url}
                                                        target="_blank"
                                                        class="text-xs text-blue-500 hover:underline truncate inline-block w-full"
                                                    >
                                                        {download.platform ||
                                                            'Link'}
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                                        >
                                            <div class="font-medium">
                                                {download.format || '-'}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {formatFileSize(
                                                    download.file_size,
                                                )}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium capitalize
                                                {download.status === 'done'
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                                    : ''}
                                                {download.status ===
                                                'downloading'
                                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                                                    : ''}
                                                {download.status === 'error'
                                                    ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                                    : ''}
                                                {download.status === 'pending'
                                                    ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                                                    : ''}
                                            "
                                            >
                                                {download.status}
                                            </span>
                                            {#if download.error_msg}
                                                <div
                                                    class="mt-1 text-xs text-red-600 max-w-xs truncate"
                                                    title={download.error_msg}
                                                >
                                                    {download.error_msg}
                                                </div>
                                            {/if}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400"
                                        >
                                            {formatDate(download.updated_at)}
                                        </td>
                                    </tr>
                                {/each}
                            </tbody>
                        </table>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>
