@props(['items'])

<nav class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.1em] text-slate-400 mb-2 overflow-x-auto no-scrollbar">
    @foreach($items as $item)
        @if(!$loop->first)
            <i class="fas fa-chevron-right text-[7px] opacity-40 mx-0.5 flex-shrink-0"></i>
        @endif

        @if(isset($item['url']) && $item['url'])
            <a href="{{ $item['url'] }}" class="hover:text-blue-600 transition-colors whitespace-nowrap flex-shrink-0">
                {{ $item['label'] }}
            </a>
        @else
            <span class="whitespace-nowrap flex-shrink-0">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
