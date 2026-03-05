<script lang="ts">
    import Button from '$lib/Button.svelte';

    let {
        type = 'video',
        format = '',
        disabled = false,
        onSelect,
    }: {
        type?: 'video' | 'audio';
        format?: string;
        disabled?: boolean;
        onSelect?: (format: string) => void;
    } = $props();

    const videoFormats = [
        { value: 'best', label: 'Best Quality' },
        { value: 'x_format', label: 'X/IG Compatible' },
        { value: '1080p', label: '1080p Full HD' },
        { value: '720p', label: '720p HD' },
        { value: '480p', label: '480p SD' },
        { value: '360p', label: '360p' },
    ];

    const audioFormats = [
        { value: 'mp3_320', label: 'MP3 320kbps' },
        { value: 'mp3_192', label: 'MP3 192kbps' },
        { value: 'aac', label: 'AAC' },
        { value: 'flac', label: 'FLAC Lossless' },
    ];

    const formats = $derived(type === 'video' ? videoFormats : audioFormats);


    $effect(() => {
        if (formats.length > 0 && !format) {
            onSelect?.(formats[0].value);
        }
    });
</script>

<div class="space-y-2">
    <p class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        Format
    </p>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2">
        {#each formats as f (f.value)}
            <Button
                variant={format === f.value ? 'default' : 'outline'}
                onclick={() => {
                    console.log('Format selected:', f.value);
                    onSelect?.(f.value);
                }}
                disabled={disabled}
                class="w-full"
            >
                {f.label}
            </Button>
        {/each}
    </div>
</div>
