{{-- Partial: pestaña bloqueada "Próximamente" --}}
<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="p-12 flex flex-col items-center justify-center text-center">

        <div class="relative mb-5">
            <div class="w-20 h-20 rounded-2xl bg-slate-100 flex items-center justify-center">
                <svg class="w-9 h-9 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $icon }}"/>
                </svg>
            </div>
            <div class="absolute -bottom-1 -right-1 w-7 h-7 bg-amber-400 rounded-full flex items-center justify-center ring-2 ring-white">
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
        </div>

        <span class="text-xs font-bold text-amber-600 bg-amber-50 border border-amber-200 px-3 py-1 rounded-full uppercase tracking-widest mb-3">
            Próximamente
        </span>
        <p class="text-base font-bold text-slate-700">{{ $title }}</p>
        <p class="text-sm text-slate-400 mt-2 max-w-sm leading-relaxed">{{ $desc }}</p>

        <div class="mt-6 flex items-center gap-2 text-xs text-slate-400">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Esta funcionalidad estará disponible en una próxima versión de Remsis
        </div>

    </div>
</div>
