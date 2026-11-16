{{-- Conceptos vigentes asignados al colaborador (sólo lectura). --}}
<div x-show="activeTab === '{{ $tab }}'" x-cloak>
    <div class="bg-white rounded-lg shadow-sm border border-slate-100">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-700">{{ $title }}</h3>
        </div>
        @forelse ($rows as $assignment)
            @php
                $amount = (float) $assignment->amount;
                $formatted = match ($assignment->unit) {
                    'UF' => rtrim(rtrim(number_format($amount, 4, ',', '.'), '0'), ',') . ' UF',
                    'UTM' => rtrim(rtrim(number_format($amount, 4, ',', '.'), '0'), ',') . ' UTM',
                    'PERCENTAGE' => rtrim(rtrim(number_format($amount, 2, ',', '.'), '0'), ',') . ' %',
                    default => '$ ' . number_format($amount, 0, ',', '.'),
                };
            @endphp
            <div class="flex items-center gap-4 px-5 py-4 border-b border-slate-50 last:border-b-0">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-700 truncate">{{ $assignment->item->name }}</p>
                    <p class="text-xs text-slate-400">
                        {{ $assignment->item->type_label }}
                        @if ($assignment->total_installments)
                            · Cuotas descontadas: {{ (int) $assignment->current_installment }} de
                            {{ $assignment->total_installments }}
                        @endif
                    </p>
                </div>
                <p class="text-sm font-bold text-slate-900 whitespace-nowrap">{{ $formatted }}</p>
            </div>
        @empty
            <div class="py-12 text-center">
                <p class="text-sm font-semibold text-slate-500">{{ $emptyTitle }}</p>
                <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">{{ $emptyText }}</p>
            </div>
        @endforelse
    </div>
</div>
