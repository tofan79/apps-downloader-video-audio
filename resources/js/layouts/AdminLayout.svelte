<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { Link } from '@inertiajs/svelte';

    let { children } = $props();

    // Svelte 5 reactive to get current path from page store if available,
    // or we can just use window.location.pathname. We'll use page.url
    let currentPath = $derived($page.url);
</script>

<div class="min-h-screen bg-gray-50 flex flex-col md:flex-row dark:bg-gray-900 font-sans">
    <!-- Sidebar -->
    <aside class="w-full md:w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 md:min-h-screen flex-shrink-0">
        <div class="h-full px-3 py-6 flex flex-col">
            <div class="mb-8 px-4 flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-xl shadow-lg">
                    DLP
                </div>
                <h2 class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">
                    appsDLP
                </h2>
            </div>
            
            <nav class="flex-1 space-y-2">
                <Link
                    href="/"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {currentPath === '/' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 shadow-sm font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/50 dark:hover:text-white'}"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Download</span>
                </Link>

                <Link
                    href="/history"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {currentPath === '/history' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 shadow-sm font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/50 dark:hover:text-white'}"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Riwayat</span>
                </Link>

                <Link
                    href="/files"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 {currentPath === '/files' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 shadow-sm font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700/50 dark:hover:text-white'}"
                >
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    <span>File Manager</span>
                </Link>
            </nav>

            <div class="mt-auto px-4 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center text-white text-sm font-bold shadow-md">
                    U
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">User Panel</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Local Download</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-x-hidden flex flex-col h-full bg-gray-50 dark:bg-gray-900 shadow-inner">
        <div class="p-4 md:p-8 flex-1 overflow-y-auto">
            {@render children()}
        </div>
    </main>
</div>
