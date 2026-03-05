<script lang="ts">
    import Button from '$lib/Button.svelte';

    let {
        disabled = false,
        isDownloading = false,
        onClick,
    }: {
        disabled?: boolean;
        isDownloading?: boolean;
        onClick?: () => void;
    } = $props();
</script>

<Button
    onclick={onClick}
    disabled={disabled || isDownloading}
    size="lg"
    class="w-full mt-6"
>
    {#if isDownloading}
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
        </svg>
        Downloading...
    {:else}
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"/>
        </svg>
        Download
    {/if}
</Button>
