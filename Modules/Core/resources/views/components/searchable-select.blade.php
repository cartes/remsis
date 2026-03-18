@props(['name', 'label', 'value' => '', 'placeholder' => 'Buscar...', 'endpoint', 'initialLabel' => ''])

<div x-data="{
    open: false,
    search: '',
    options: [],
    selectedId: @js($value),
    selectedLabel: @js($initialLabel),
    loading: false,

    init() {
        this.$watch('search', value => {
            if (value.length >= 2) {
                this.fetchOptions();
            }
        });
    },

    async fetchOptions() {
        this.loading = true;
        try {
            const response = await fetch(`${@js($endpoint)}?query=${encodeURIComponent(this.search)}`);
            this.options = await response.json();
        } catch (e) {
            console.error('Error fetching options:', e);
        } finally {
            this.loading = false;
        }
    },

    select(option) {
        this.selectedId = option.id;
        this.selectedLabel = `${option.code} - ${option.name}`;
        this.open = false;
        this.search = '';
        this.options = [];
        
        // Dispatch event for parent integration if needed
        this.$dispatch('input', this.selectedId);
    },

    clear() {
        this.selectedId = '';
        this.selectedLabel = '';
        this.open = false;
    }
}" class="relative w-full" @click.away="open = false">
    @if($label)
        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">{{ $label }}</label>
    @endif

    <div class="relative">
        <input type="hidden" name="{{ $name }}" x-model="selectedId">
        
        <div @click="open = !open" 
             class="w-full px-3 py-2 border rounded-lg text-sm bg-white cursor-pointer flex justify-between items-center transition-all"
             :class="open ? 'border-blue-500 ring-2 ring-blue-100' : 'border-gray-200'">
            <span x-text="selectedLabel || @js($placeholder)" :class="selectedLabel ? 'text-gray-900' : 'text-gray-400'"></span>
            <i class="fas fa-chevron-down text-gray-400 text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
        </div>

        <div x-show="open" 
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden"
             x-cloak>
            
            <div class="p-2 border-b border-gray-100 bg-gray-50">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" 
                           x-model.debounce.300ms="search"
                           placeholder="Buscar por código o nombre..."
                           class="w-full pl-8 pr-3 py-1.5 bg-white border border-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 outline-none"
                           @click.stop>
                </div>
            </div>

            <div class="max-h-60 overflow-y-auto p-1">
                <template x-if="loading">
                    <div class="p-3 text-center text-gray-400">
                        <i class="fas fa-spinner fa-spin mr-2"></i> Cargando...
                    </div>
                </template>

                <template x-if="!loading && options.length === 0 && search.length >= 2">
                    <div class="p-3 text-center text-gray-400 text-xs italic">
                        No se encontraron resultados
                    </div>
                </template>

                <template x-if="!loading && search.length < 2 && options.length === 0">
                    <div class="p-3 text-center text-gray-400 text-xs">
                        Escribe al menos 2 caracteres para buscar
                    </div>
                </template>

                <template x-for="option in options" :key="option.id">
                    <div @click="select(option)" 
                         class="px-3 py-2 hover:bg-blue-50 cursor-pointer rounded-lg transition-colors group">
                        <div class="flex items-center gap-2">
                            <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-1.5 py-0.5 rounded" x-text="option.code"></span>
                            <span class="text-xs text-gray-700 font-medium group-hover:text-blue-900" x-text="option.name"></span>
                        </div>
                    </div>
                </template>
            </div>

            <template x-if="selectedId">
                <div @click="clear()" class="p-2 border-t border-gray-100 text-center">
                    <button type="button" class="text-[10px] font-bold text-red-500 uppercase hover:text-red-700 transition-colors">
                        <i class="fas fa-times-circle mr-1"></i> Limpiar selección
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>
