<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import AdminLayout from '../layouts/AdminLayout.svelte';

    interface Download {
        id: number;
        url: string;
        title: string | null;
        platform: string | null;
        type: 'video' | 'audio' | 'playlist';
        format: string | null;
        file_path: string | null;
        file_size: number | null;
        status: 'pending' | 'downloading' | 'done' | 'error';
        error_msg: string | null;
        created_at: string;
        updated_at: string;
    }

    export let downloads: Download[] = [];

    let isClearing = false;

    function clearHistory() {
        if (!confirm('Apakah kamu yakin ingin menghapus seluruh riwayat unduhan?\n(Hanya menghapus riwayat, tidak akan menghapus berkas file aslinya)')) {
            return;
        }

        isClearing = true;
        router.delete('/history', {
            onFinish: () => {
                isClearing = false;
            }
        });
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

    function getStatusColor(status: string): string {
        switch (status) {
            case 'done':
                return 'text-green-600 dark:text-green-400';
            case 'downloading':
                return 'text-blue-600 dark:text-blue-400';
            case 'error':
                return 'text-red-600 dark:text-red-400';
            default:
                return 'text-gray-600 dark:text-gray-400';
        }
    }

    function getTypeIcon(type: string): string {
        switch (type) {
            case 'video':
                return '🎬';
            case 'audio':
                return '🎵';
            case 'playlist':
                return '📋';
            default:
                return '📄';
        }
    }
</script>

<svelte:head>
    <title>Download History - appsDLP</title>
</svelte:head>

<AdminLayout>
    <div class="max-w-6xl mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        📋 Download History
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        View all your past downloads
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    {#if downloads.length > 0}
                        <button
                            onclick={clearHistory}
                            disabled={isClearing}
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                        >
                            {isClearing ? 'Clearing...' : '🗑️ Clear History'}
                        </button>
                    {/if}
                </div>
            </div>
        </div>

        <!-- Downloads Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            {#if downloads.length === 0}
                <div class="text-center py-16">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                        No downloads yet
                    </h3>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Start downloading videos, audio, or playlists!
                    </p>
                </div>
            {:else}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Title
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Platform
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Format
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Size
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            {#each downloads as download (download.id)}
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-2xl">
                                        {getTypeIcon(download.type)}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="max-w-xs">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {download.title || 'Unknown'}
                                            </div>
                                            <div class="text-xs text-gray-500 truncate">
                                                {download.url}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {download.platform || '-'}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {download.format || '-'}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {formatFileSize(download.file_size)}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize
                                            {download.status === 'done' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ''}
                                            {download.status === 'downloading' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : ''}
                                            {download.status === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : ''}
                                            {download.status === 'pending' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' : ''}
                                        ">
                                            {download.status}
                                        </span>
                                        {#if download.error_msg}
                                            <div class="mt-1 text-xs text-red-600 dark:text-red-400 max-w-xs truncate">
                                                {download.error_msg}
                                            </div>
                                        {/if}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {formatDate(download.created_at)}
                                    </td>
                                </tr>
                            {/each}
                        </tbody>
                    </table>
                </div>
            {/if}
        </div>
    </div>
</AdminLayout>
