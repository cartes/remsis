{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- TAB: ASISTENCIA                                                 --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'asistencia'" x-cloak>

    @php
        $attendances      = $employee->attendances ?? collect();
        $totalOvertime    = $attendances->sum('overtime_minutes');
        $totalAbsences    = $attendances->where('status', 'ausente')->count();
        $totalDelayMins   = $attendances->sum('delay_minutes');

        $overtimeH = intdiv($totalOvertime, 60);
        $overtimeM = $totalOvertime % 60;
        $overtimeLabel = $totalOvertime > 0
            ? ($overtimeH > 0 ? "{$overtimeH}h {$overtimeM}m" : "{$overtimeM}m")
            : '0m';
    @endphp

    {{-- ── Bento Box: tarjetas de resumen ────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

        {{-- Horas Extras --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex items-center gap-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.8"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Horas Extras</p>
                <p class="text-2xl font-bold text-slate-900 leading-tight">{{ $overtimeLabel }}</p>
            </div>
        </div>

        {{-- Días Ausente --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex items-center gap-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="1.8"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6 18 18 6M6 6l12 12" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Días Ausente</p>
                <p class="text-2xl font-bold text-slate-900 leading-tight">{{ $totalAbsences }}</p>
            </div>
        </div>

        {{-- Minutos de Atraso --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 flex items-center gap-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.8"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v4l2.5 2.5M12 3a9 9 0 1 1 0 18A9 9 0 0 1 12 3Z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Min. de Atraso</p>
                <p class="text-2xl font-bold text-slate-900 leading-tight">{{ number_format($totalDelayMins) }}</p>
            </div>
        </div>
    </div>

    {{-- ── Tabla de Marcas ────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Registro de Marcas</h3>
            <span class="text-xs text-slate-400">{{ $attendances->count() }} {{ Str::plural('registro', $attendances->count()) }}</span>
        </div>

        @forelse($attendances as $record)
            @if ($loop->first)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left">
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Fecha</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Turno</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Entrada</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Salida</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Horas Extra</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
            @endif

                            <tr class="hover:bg-slate-50/60 transition-colors">
                                {{-- Fecha --}}
                                <td class="px-4 py-3 text-slate-700 font-medium">
                                    {{ $record->date->translatedFormat('d M Y') }}
                                </td>

                                {{-- Turno --}}
                                <td class="px-4 py-3 text-slate-500">
                                    {{ $record->scheduled_shift ?? '—' }}
                                </td>

                                {{-- Entrada --}}
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $record->clock_in ? \Carbon\Carbon::parse($record->clock_in)->format('H:i') : '—' }}
                                </td>

                                {{-- Salida --}}
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $record->clock_out ? \Carbon\Carbon::parse($record->clock_out)->format('H:i') : '—' }}
                                </td>

                                {{-- Horas Extra --}}
                                <td class="px-4 py-3">
                                    @if ($record->overtime_minutes > 0)
                                        <span class="text-emerald-600 font-semibold">
                                            {{ $record->formatted_overtime }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Estado Badge --}}
                                <td class="px-4 py-3">
                                    @php
                                        $badgeClasses = match ($record->status) {
                                            'asistio'    => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                            'ausente'    => 'bg-red-50 text-red-700 ring-red-600/20',
                                            'atraso'     => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                            'licencia'   => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                            'vacaciones' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
                                            default      => 'bg-slate-100 text-slate-600 ring-slate-500/20',
                                        };
                                        $badgeLabel = \Modules\Employees\Models\AttendanceRecord::$statusLabels[$record->status] ?? $record->status;
                                    @endphp
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $badgeClasses }}">
                                        {{ $badgeLabel }}
                                    </span>
                                </td>
                            </tr>

            @if ($loop->last)
                        </tbody>
                    </table>
                </div>
            @endif

        @empty
            {{-- Empty State --}}
            <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-slate-700">Sin registros de asistencia</p>
                <p class="text-xs text-slate-400 mt-1 max-w-xs">
                    No hay registros de asistencia para este colaborador aún.
                </p>
            </div>
        @endforelse
    </div>

</div>
