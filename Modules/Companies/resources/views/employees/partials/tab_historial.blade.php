{{--
    PESTAÑA: HISTORIAL
    Línea de tiempo de auditoría del colaborador.
    Requiere: $employee->logs (cargado con logs.user), $fieldLabels
--}}
<div x-show="tab === 'historial'" x-cloak
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 translate-y-1"
    x-transition:enter-end="opacity-100 translate-y-0">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between mb-8">
        <h2 class="text-base font-black text-slate-800 flex items-center gap-2">
            <i class="fas fa-clock-rotate-left text-slate-500 text-sm"></i>
            Historial del Colaborador
        </h2>
        <span class="text-xs text-slate-400 font-semibold bg-slate-100 px-2.5 py-1 rounded-full">
            {{ $employee->logs->count() }} {{ $employee->logs->count() === 1 ? 'registro' : 'registros' }}
        </span>
    </div>

    @forelse($employee->logs as $log)

        @php
            $typeConfig = [
                'creacion'     => ['dot' => 'bg-emerald-500', 'icon' => 'fa-user-plus',       'badge' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
                'remuneracion' => ['dot' => 'bg-violet-500',  'icon' => 'fa-money-bill-wave', 'badge' => 'bg-violet-100 text-violet-700 border-violet-200'],
                'contrato'     => ['dot' => 'bg-blue-500',    'icon' => 'fa-file-contract',   'badge' => 'bg-blue-100 text-blue-700 border-blue-200'],
                'ausentismo'   => ['dot' => 'bg-amber-500',   'icon' => 'fa-calendar-xmark',  'badge' => 'bg-amber-100 text-amber-700 border-amber-200'],
                'sistema'      => ['dot' => 'bg-slate-400',   'icon' => 'fa-gear',            'badge' => 'bg-slate-100 text-slate-500 border-slate-200'],
                'auditoria'    => ['dot' => 'bg-slate-500',   'icon' => 'fa-pen-to-square',   'badge' => 'bg-slate-100 text-slate-600 border-slate-200'],
            ];
            $cfg   = $typeConfig[$log->type] ?? $typeConfig['auditoria'];
            $label = \Modules\Employees\Models\EmployeeLog::$typeLabels[$log->type] ?? 'Auditoría';
        @endphp

        <div class="flex gap-4">

            {{-- Línea vertical + punto de color --}}
            <div class="flex flex-col items-center flex-shrink-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center ring-4 ring-slate-50 {{ $cfg['dot'] }}">
                    <i class="fas {{ $cfg['icon'] }} text-white text-[11px]"></i>
                </div>
                @unless($loop->last)
                    <div class="w-0.5 flex-1 bg-slate-200 my-1 min-h-[1.5rem]"></div>
                @endunless
            </div>

            {{-- Tarjeta del evento --}}
            <div class="flex-1 pb-6">
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow">

                    {{-- Cabecera: badge + fecha --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $cfg['badge'] }}">
                            {{ $label }}
                        </span>
                        <span class="text-[11px] text-slate-400 font-semibold flex items-center gap-1">
                            <i class="fas fa-clock opacity-60 text-[9px]"></i>
                            {{ $log->created_at->format('d/m/Y') }}
                            <span class="text-slate-200 mx-0.5">·</span>
                            {{ $log->created_at->format('H:i') }} hrs
                        </span>
                    </div>

                    {{-- Descripción --}}
                    <p class="text-sm text-slate-700 font-semibold">{{ $log->description }}</p>

                    {{-- Detalle expandible (antes / después) --}}
                    @if($log->old_values || $log->new_values)
                        <div x-data="{ openDetail: false }" class="mt-3">
                            <button @click="openDetail = !openDetail" type="button"
                                class="flex items-center gap-1 text-[11px] font-bold text-slate-400 hover:text-slate-700 transition-colors group">
                                <i class="fas fa-code-compare text-[10px] transition-colors"></i>
                                <span x-text="openDetail ? 'Ocultar detalle' : 'Ver detalle del cambio'"></span>
                                <i class="fas fa-chevron-down text-[9px] transition-transform duration-200"
                                    :class="openDetail ? 'rotate-180' : ''"></i>
                            </button>

                            <div x-show="openDetail"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">

                                @if($log->old_values)
                                    <div class="bg-red-50 border border-red-100 rounded-lg p-3">
                                        <p class="font-black text-red-500 uppercase tracking-widest text-[9px] mb-2 flex items-center gap-1">
                                            <i class="fas fa-minus-circle"></i> Antes
                                        </p>
                                        @foreach($log->old_values as $campo => $valor)
                                            <p class="text-slate-600 mb-0.5">
                                                <span class="font-bold">{{ $campo }}:</span>
                                                {{ is_null($valor) ? '—' : $valor }}
                                            </p>
                                        @endforeach
                                    </div>
                                @endif

                                @if($log->new_values)
                                    <div class="bg-emerald-50 border border-emerald-100 rounded-lg p-3">
                                        <p class="font-black text-emerald-600 uppercase tracking-widest text-[9px] mb-2 flex items-center gap-1">
                                            <i class="fas fa-plus-circle"></i> Después
                                        </p>
                                        @foreach($log->new_values as $campo => $valor)
                                            <p class="text-slate-600 mb-0.5">
                                                <span class="font-bold">{{ $campo }}:</span>
                                                {{ is_null($valor) ? '—' : $valor }}
                                            </p>
                                        @endforeach
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endif

                    {{-- Pie: usuario responsable --}}
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-1.5 text-[11px] text-slate-400 font-semibold">
                        <i class="fas fa-user-shield text-[10px]"></i>
                        <span>{{ $log->user->name ?? 'Sistema' }}</span>
                    </div>

                </div>
            </div>

        </div>

    @empty

        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4 shadow-inner">
                <i class="fas fa-clock-rotate-left text-2xl text-slate-300"></i>
            </div>
            <h3 class="text-sm font-black text-slate-700 mb-1">Sin registros aún</h3>
            <p class="text-xs text-slate-400 max-w-xs leading-relaxed">
                Aún no hay registros en el historial de este colaborador.
                Los cambios quedarán registrados automáticamente a partir de ahora.
            </p>
        </div>

    @endforelse

</div>
