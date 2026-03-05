<script lang="ts">
    import Button from '$lib/Button.svelte';

    let {
        value = '',
        disabled = false,
        onInput,
        onPaste,
    }: {
        value?: string;
        disabled?: boolean;
        onInput?: (value: string) => void;
        onPaste?: () => void;
    } = $props();

    function handleInput(event: Event) {
        const target = event.target as HTMLInputElement;
        onInput?.(target.value);
    }

    async function handlePasteButton(event: Event) {
        event.preventDefault();
        await onPaste?.();
    }
</script>

<div class="flex gap-2">
    <input
        type="text"
        value={value}
        {disabled}
        oninput={handleInput}
        onpaste={(e) => {
            // Allow native paste event to process natively,
            // we no longer auto-trigger so the app won't spam reload loops
        }}
        onkeydown={(e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                onPaste?.();
            }
        }}
        placeholder="Paste URL video, playlist, or channel here... (or press Ctrl+V)"
        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg 
               focus:outline-none focus:ring-2 focus:ring-blue-500 
               dark:bg-gray-800 dark:text-white"
    />
    <Button 
        variant="outline" 
        onclick={handlePasteButton}
        disabled={disabled}
    >
        Paste
    </Button>
</div>
