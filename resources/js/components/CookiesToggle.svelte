<script lang="ts">
    import Button from '$lib/Button.svelte';

    let {
        hasCookies = false,
        disabled = false,
        onUpload,
    }: {
        hasCookies?: boolean;
        disabled?: boolean;
        onUpload?: (file: File) => void;
    } = $props();

    let uploading = $state(false);

    async function handleFileChange(event: Event) {
        const target = event.target as HTMLInputElement;
        const file = target.files?.[0];
        
        console.log('File selected:', file);
        
        if (!file) return;

        uploading = true;
        try {
            await onUpload?.(file);
        } finally {
            uploading = false;
            target.value = '';
        }
    }
</script>

<div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
    <div class="flex flex-col gap-1">
        <h3 class="text-sm font-medium text-gray-900 dark:text-white">
            YouTube Cookies
        </h3>
        {#if hasCookies}
            <p class="text-sm text-green-600 dark:text-green-400 font-medium">
                ✅ Cookie Aktif (Siap untuk unduh video private/membership)
            </p>
        {:else}
            <p class="text-xs text-gray-600 dark:text-gray-400">
                Belum ada cookie. Upload <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">cookies.txt</code> untuk akses video private.
            </p>
        {/if}
    </div>

    {#if !hasCookies}
        <div class="relative shrink-0">
            <input
                type="file"
                id="cookies-upload"
                accept=".txt"
                onchange={handleFileChange}
                disabled={uploading || disabled}
                class="hidden"
            />
            <Button
                variant="outline"
                size="sm"
                onclick={() => document.getElementById('cookies-upload')?.click()}
                disabled={uploading || disabled}
            >
                {uploading ? 'Uploading...' : 'Upload cookies.txt'}
            </Button>
        </div>
    {/if}
</div>
