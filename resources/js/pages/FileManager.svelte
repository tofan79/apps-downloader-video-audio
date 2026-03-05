<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import AdminLayout from '../layouts/AdminLayout.svelte';

    export let files: Array<{
        name: string;
        path: string;
        type: string;
        size: number;
        ext: string;
        last_modified: string;
    }> = [];

    let isDeleting: string | null = null;

    function formatFileSize(bytes: number): string {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return `${size.toFixed(1)} ${units[unitIndex]}`;
    }

    function deleteFile(path: string) {
        if (!confirm(`Hapus file ini?\n\n${path}`)) return;
        
        isDeleting = path;
        router.post('/files/delete', { path }, {
            onFinish: () => {
                isDeleting = null;
            }
        });
    }

    function getTypeIcon(type: string, ext: string) {
        if (type === 'video') return '🎬';
        if (type === 'audio') return '🎵';
        if (['jpg', 'jpeg', 'png', 'webp'].includes(ext.toLowerCase())) return '🖼️';
        return '📄';
    }
</script>

<svelte:head>
    <title>File Manager - appsDLP</title>
</svelte:head>

<AdminLayout>
    <div class="max-w-6xl mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                📁 File Manager
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Kelola file unduhan yang tersimpan secara lokal.
            </p>
        </div>

        {#if $page.props.errors?.message}
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{$page.props.errors.message}</span>
            </div>
        {/if}

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            {#if files.length === 0}
                <div class="text-center py-16">
                    <div class="text-5xl mb-4">📭</div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Folder Masih Kosong</h3>
                    <p class="text-gray-500">Belum ada file yang berhasil di-download.</p>
                </div>
            {:else}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama File</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ukuran</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Diubah Pada</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            {#each files as file}
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="text-2xl">{getTypeIcon(file.type, file.ext)}</div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white max-w-sm truncate" title={file.name}>
                                                    {file.name}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {file.path}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 capitalize">
                                        {file.type}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {formatFileSize(file.size)}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {file.last_modified}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a 
                                                href={`/files/download?path=${encodeURIComponent(file.path)}`}
                                                download
                                                class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400 px-3 py-1 rounded bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 transition"
                                            >
                                                Download ke PC
                                            </a>
                                            <button 
                                                onclick={() => deleteFile(file.path)}
                                                disabled={isDeleting === file.path}
                                                class="text-red-600 hover:text-red-900 dark:hover:text-red-400 px-3 py-1 rounded bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 transition disabled:opacity-50"
                                            >
                                                {isDeleting === file.path ? '...' : 'Hapus'}
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
</AdminLayout>
