<script lang="ts">
    let {
        title = '',
        thumbnail = null,
        duration = 0,
        platform = '',
        isPlaylist = false,
        playlistCount = 0,
        entries = [],
        selectedItems = [],
        onSelectionChange,
    }: {
        title?: string;
        thumbnail?: string | null;
        duration?: number;
        platform?: string;
        isPlaylist?: boolean;
        playlistCount?: number;
        selectedItems?: number[];
        onSelectionChange?: (items: number[]) => void;
        entries?: Array<{
            index: number;
            title: string;
            thumbnail: string | null;
            duration: number;
        }>;
    } = $props();

    function formatDuration(seconds: number): string {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
</script>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mt-4">
    <div class="flex gap-4">
        {#if thumbnail}
            <img 
                src={thumbnail} 
                alt={title}
                class="w-48 h-36 object-cover rounded-lg"
            />
        {/if}
        
        <div class="flex-1">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                {title}
            </h3>
            
            <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                    </svg>
                    {platform}
                </span>
                
                {#if !isPlaylist}
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                        </svg>
                        {formatDuration(duration)}
                    </span>
                {:else}
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                        </svg>
                        {playlistCount} videos
                    </span>
                {/if}
            </div>
        </div>
    </div>

    {#if isPlaylist && entries.length > 0}
        <div class="mt-4 border-t dark:border-gray-700 pt-4">
            <div class="flex items-center justify-between mb-2">
                <h4 class="font-medium text-gray-900 dark:text-white">
                    Playlist Items ({selectedItems.length}/{entries.length} selected)
                </h4>
                <button 
                    type="button"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                    onclick={() => {
                        if (selectedItems.length === entries.length) {
                            onSelectionChange?.([]);
                        } else {
                            onSelectionChange?.(entries.map(e => e.index));
                        }
                    }}
                >
                    {selectedItems.length === entries.length ? 'Deselect All' : 'Select All'}
                </button>
            </div>
            <div class="max-h-60 overflow-y-auto space-y-2">
                {#each entries as entry (entry.index)}
                    <label class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-900 rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <input 
                            type="checkbox" 
                            class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                            checked={selectedItems.includes(entry.index)}
                            onchange={(e) => {
                                const isChecked = (e.target as HTMLInputElement).checked;
                                if (isChecked) {
                                    onSelectionChange?.([...selectedItems, entry.index].sort((a,b) => a-b));
                                } else {
                                    onSelectionChange?.(selectedItems.filter(i => i !== entry.index));
                                }
                            }}
                        />
                        {#if entry.thumbnail}
                            <img 
                                src={entry.thumbnail} 
                                alt={entry.title}
                                class="w-20 h-12 object-cover rounded"
                            />
                        {/if}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900 dark:text-white truncate">
                                {entry.index}. {entry.title}
                            </p>
                            <p class="text-xs text-gray-500">
                                {formatDuration(entry.duration)}
                            </p>
                        </div>
                    </label>
                {/each}
            </div>
        </div>
    {/if}
</div>
