<script lang="ts">

    let {
        percent = 0,
        speed = null,
        eta = null,
        status = 'downloading',
        isDownloading = false,
    }: {
        percent?: number;
        speed?: string | null;
        eta?: string | null;
        status?: string;
        isDownloading?: boolean;
    } = $props();

    const statusColors = {
        pending: 'bg-gray-500',
        downloading: 'bg-blue-500',
        done: 'bg-green-500',
        error: 'bg-red-500',
    };

    const color = $derived(statusColors[status as keyof typeof statusColors] || 'bg-blue-500');
</script>

{#if isDownloading}
    <div class="mt-6 space-y-2">
        <div class="flex justify-between text-sm">
            <span class="text-gray-700 dark:text-gray-300 capitalize">{status}...</span>
            <span class="text-gray-700 dark:text-gray-300">{percent}%</span>
        </div>
        
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
            <div
                class="{color} h-4 transition-all duration-300 ease-out"
                style="width: {percent}%"
            ></div>
        </div>

        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400">
            {#if speed}
                <span>Speed: {speed}</span>
            {/if}
            {#if eta}
                <span>ETA: {eta}</span>
            {/if}
        </div>
    </div>
{/if}
