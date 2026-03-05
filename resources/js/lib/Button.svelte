<script lang="ts">
    let {
        variant = 'default',
        size = 'md',
        disabled = false,
        class: className = '',
        children,
        onclick,
    }: {
        variant?: 'default' | 'outline';
        size?: 'sm' | 'md' | 'lg';
        disabled?: boolean;
        class?: string;
        children?: any;
        onclick?: (event: MouseEvent) => void;
    } = $props();

    const baseClasses = 'inline-flex items-center justify-center rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none';
    
    const variants = {
        default: 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-700',
        outline: 'border border-gray-300 dark:border-gray-700 bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-900 dark:text-white',
    };

    const sizes = {
        sm: 'h-9 px-3 text-xs',
        md: 'h-10 px-4 py-2',
        lg: 'h-11 px-8 text-lg',
    };

    const classes = $derived(`${baseClasses} ${variants[variant]} ${sizes[size]} ${className}`);
</script>

<button 
    type="button"
    {disabled}
    class={classes}
    onclick={onclick}
>
    {@render children?.()}
</button>
