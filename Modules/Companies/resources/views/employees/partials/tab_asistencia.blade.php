{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- TAB: ASISTENCIA                                                 --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'asistencia'" x-cloak x-data="{
    isModalOpen: false,
    modalTitle: 'Registrar Marca / Inasistencia',
    date: '',
    status: 'ausente',
    isJustified: false,
    justificationType: 'inasistencia_injustificada',
    justificationReason: '',
    attendanceId: null,

    openAddModal() {
        this.modalTitle = 'Registrar Marca / Inasistencia';
        this.date = '';
        this.status = 'ausente';
        this.isJustified = false;
        this.justificationType = 'inasistencia_injustificada';
        this.justificationReason = '';
        this.attendanceId = null;
        this.isModalOpen = true;
    },

    openEditModal(record) {
        this.modalTitle = 'Editar Marca / Inasistencia';
        this.date = record.date;
        this.status = record.status;
        this.isJustified = record.is_justified;
        this.justificationType = record.justification_type || 'inasistencia_injustificada';
        this.justificationReason = record.justification_reason || '';
        this.attendanceId = record.id;
        this.isModalOpen = true;
    }
}" x-effect="
    if (status === 'licencia') {
        isJustified = true;
        justificationType = 'licencia_medica';
    } else if (status === 'ausente' && justificationType === 'licencia_medica') {
        justificationType = 'inasistencia_injustificada';
    }
">

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
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Registro de Marcas</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ $attendances->count() }} {{ Str::plural('registro', $attendances->count()) }} cargados</p>
            </div>
            <button type="button" @click="openAddModal()"
                class="flex items-center gap-2 px-3 py-1.5 bg-slate-900 text-white hover:bg-slate-800 text-xs font-bold rounded-xl transition-all shadow-sm">
                <i class="fas fa-plus text-[10px]"></i>
                Registrar Marca / Inasistencia
            </button>
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
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Justificación / Motivo</th>
                                <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide text-right">Acciones</th>
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

                                {{-- Justificación --}}
                                <td class="px-4 py-3 text-slate-600 text-xs">
                                    @if ($record->status === 'ausente' || $record->status === 'licencia')
                                        @if ($record->is_justified)
                                            <div class="flex flex-col gap-0.5">
                                                <span class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                                                    <i class="fas fa-circle-check text-[10px]"></i>
                                                    {{ \Modules\Employees\Models\AttendanceRecord::$justificationTypes[$record->justification_type] ?? 'Justificado' }}
                                                </span>
                                                @if ($record->justification_reason)
                                                    <span class="text-slate-400 italic">"{{ $record->justification_reason }}"</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 font-semibold text-rose-600">
                                                <i class="fas fa-circle-xmark text-[10px]"></i>
                                                Injustificada (Descuenta)
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>

                                {{-- Acciones --}}
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        {{-- Edit Button --}}
                                        <button type="button" @click="openEditModal({
                                            id: {{ $record->id }},
                                            date: '{{ $record->date->format('Y-m-d') }}',
                                            status: '{{ $record->status }}',
                                            is_justified: {{ $record->is_justified ? 'true' : 'false' }},
                                            justification_type: '{{ $record->justification_type }}',
                                            justification_reason: '{{ addslashes($record->justification_reason) }}'
                                        })" class="w-8 h-8 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-600 hover:text-slate-900 border border-slate-200 transition-colors">
                                            <i class="fas fa-pencil text-xs"></i>
                                        </button>

                                        {{-- Delete Button --}}
                                        <form method="POST" action="{{ route('companies.employees.attendance.destroy', [$company, $employee, $record]) }}" class="inline-block" onsubmit="return confirm('¿Eliminar este registro de asistencia/ausencia?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-8 h-8 rounded-lg bg-slate-50 hover:bg-rose-50 flex items-center justify-center text-slate-500 hover:text-rose-600 border border-slate-200 hover:border-rose-200 transition-colors">
                                                <i class="fas fa-trash text-xs"></i>
                                            </button>
                                        </form>
                                    </div>
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
                <p class="text-xs text-slate-400 mt-1 max-w-xs font-medium">
                    No hay registros de marcas o ausencias cargados para este colaborador aún.
                </p>
            </div>
        @endforelse
    </div>

    {{-- ── MODAL: REGISTRAR / EDITAR AUSENCIA O MARCA ────────────────────── --}}
    <div x-show="isModalOpen" style="display: none;" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="isModalOpen" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="isModalOpen" @click.away="isModalOpen = false"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-visible rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 w-full sm:max-w-lg">
                    
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                            <i class="fas fa-clock text-blue-500 text-sm"></i>
                            <span x-text="modalTitle"></span>
                        </h3>
                        <button @click="isModalOpen = false" type="button"
                            class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('companies.employees.attendance.store', [$company, $employee]) }}" class="p-6">
                        @csrf
                        
                        <div class="space-y-4">
                            {{-- Fecha --}}
                            <div>
                                <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                                    Fecha <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="date" x-model="date" required
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all">
                            </div>

                            {{-- Estado / Tipo de Marca --}}
                            <div>
                                <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                                    Estado de Asistencia <span class="text-red-500">*</span>
                                </label>
                                <select name="status" x-model="status" required
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all">
                                    <option value="asistio">Asistió</option>
                                    <option value="atraso">Atraso</option>
                                    <option value="ausente">Ausente</option>
                                    <option value="licencia">Licencia Médica</option>
                                    <option value="vacaciones">Vacaciones</option>
                                </select>
                            </div>

                            {{-- Sección de Justificación (sólo para ausente) --}}
                            <div x-show="status === 'ausente'" class="bg-slate-50 p-4 rounded-xl border border-slate-100 flex items-center justify-between" x-transition>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">¿Inasistencia Justificada?</h4>
                                    <p class="text-[10px] text-slate-400 font-medium mt-0.5">Define si tiene permiso autorizado</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_justified" x-model="isJustified" class="sr-only peer">
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            {{-- Tipo de Justificación (para ausencias justificadas o licencias) --}}
                            <div x-show="status === 'licencia' || (status === 'ausente' && isJustified)" x-transition>
                                <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                                    Tipo de Licencia / Justificación
                                </label>
                                <select name="justification_type" x-model="justificationType"
                                    class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all">
                                    <template x-if="status === 'licencia'">
                                        <option value="licencia_medica">Licencia Médica (Descuenta)</option>
                                    </template>
                                    <template x-if="status === 'ausente'">
                                        <option value="permiso_con_goce">Permiso con Goce de Sueldo (No descuenta)</option>
                                    </template>
                                    <template x-if="status === 'ausente'">
                                        <option value="permiso_sin_goce">Permiso sin Goce de Sueldo (Descuenta)</option>
                                    </template>
                                    <template x-if="status === 'ausente'">
                                        <option value="accidente_trabajo">Accidente del Trabajo (Descuenta)</option>
                                    </template>
                                    <template x-if="status === 'ausente'">
                                        <option value="suspension_disciplinaria">Sanción/Suspensión Disciplinaria (Descuenta)</option>
                                    </template>
                                </select>
                            </div>

                            {{-- Hidden para inasistencia injustificada --}}
                            <div x-show="status === 'ausente' && !isJustified">
                                <input type="hidden" name="justification_type" value="inasistencia_injustificada">
                            </div>

                            {{-- Detalles / Observación --}}
                            <div x-show="['ausente', 'licencia'].includes(status)" x-transition>
                                <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                                    Detalle / Observaciones
                                </label>
                                <textarea name="justification_reason" x-model="justificationReason" rows="3"
                                    placeholder="Indique folio de licencia, motivo detallado del permiso, etc."
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="isModalOpen = false"
                                class="px-4 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-50 transition-all shadow-sm">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="flex items-center gap-2 px-5 py-2 bg-slate-900 text-white text-xs font-black rounded-xl hover:bg-slate-800 active:scale-95 transition-all shadow-sm">
                                <i class="fas fa-floppy-disk text-xs"></i>
                                Guardar Registro
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
