{{--
    PESTAÑA: LICENCIAS MÉDICAS (DS 3 de 1984) Y SUBSIDIO POR INCAPACIDAD LABORAL (DFL 44 de 1978)
    Requiere: $medicalLeaveSummary, $employee->medicalLeaves
--}}
@php
    use Modules\Employees\Models\MedicalLeave;

    $leaveErrors = $errors->getBag('leave');
    $clp = fn ($amount) => '$'.number_format((float) $amount, 0, ',', '.');
    $leaveStatusClasses = [
        'in_process' => 'bg-amber-50 text-amber-700 border-amber-200',
        'authorized' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'reduced' => 'bg-blue-50 text-blue-700 border-blue-200',
        'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
        'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $sourceLabels = ['liquidacion' => 'liquidación', 'subsidio' => 'subsidio', 'contrato' => 'contrato'];
@endphp
<div x-show="tab === 'licencias'" x-cloak x-data="{ resolving: null }">

    <div class="mb-6">
        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Licencias médicas</h2>
        <p class="text-xs text-slate-400 mt-0.5">
            Los días de licencia son corridos y no los paga la empresa: los cubre el subsidio de la entidad pagadora.
            El monto que se muestra es una estimación para conciliar con ella.
        </p>
    </div>

    @if ($leaveErrors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($leaveErrors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach ([
            ['Licencias '.now()->year, $medicalLeaveSummary['leaves'], 'registradas', 'text-slate-900'],
            ['Días de licencia', $medicalLeaveSummary['days'], 'días corridos en '.now()->year, 'text-blue-700'],
            ['En trámite', $medicalLeaveSummary['in_process'], 'sin resolución', 'text-amber-700'],
            ['Subsidio estimado', $clp($medicalLeaveSummary['subsidy']), 'licencias de '.now()->year, 'text-emerald-700'],
        ] as [$label, $value, $hint, $class])
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ $label }}</p>
                <p class="text-2xl font-black mt-1 {{ $class }}">{{ $value }}</p>
                <p class="text-[11px] text-slate-400">{{ $hint }}</p>
            </div>
        @endforeach
    </div>

    @can('manageEmployees', $company)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
            <h3 class="text-sm font-bold text-slate-800 mb-1">Registrar licencia</h3>
            <p class="text-xs text-slate-500 mb-4">Queda en trámite, marca sus días en la asistencia y estima el subsidio.</p>
            <form method="POST" action="{{ route('companies.employees.medical-leaves.store', [$company, $employee]) }}"
                class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Folio *</label>
                    <input type="text" name="folio" required maxlength="30" value="{{ old('folio') }}" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo *</label>
                    <select name="type" required class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach (MedicalLeave::TYPE_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', MedicalLeave::TYPE_COMMON) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Inicio del reposo *</label>
                    <input type="date" name="start_date" required value="{{ old('start_date') }}" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Días de reposo *</label>
                    <input type="number" name="days" required min="1" max="365" value="{{ old('days') }}" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div class="col-span-2 lg:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Observación</label>
                    <input type="text" name="notes" maxlength="500" value="{{ old('notes') }}" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div class="col-span-2 lg:col-span-1 flex items-end">
                    <label class="flex items-start gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="is_continuation" value="1" @checked(old('is_continuation')) class="mt-0.5 rounded border-slate-300">
                        <span>Continúa la licencia anterior sin interrupción, por el mismo diagnóstico</span>
                    </label>
                </div>
                <div class="col-span-2 lg:col-span-4 flex items-center justify-between gap-4">
                    <p class="text-[11px] text-slate-400">
                        En enfermedad común de hasta 10 días el subsidio se paga desde el cuarto día; las licencias continuas se suman para ese límite.
                    </p>
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">Registrar</button>
                </div>
            </form>
        </div>
    @endcan

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <th class="px-5 py-3.5 text-left">Licencia</th>
                    <th class="px-5 py-3.5 text-left">Reposo</th>
                    <th class="px-5 py-3.5 text-left">Entidad pagadora</th>
                    <th class="px-5 py-3.5 text-right">Subsidio estimado</th>
                    <th class="px-5 py-3.5 text-center">Estado</th>
                    <th class="px-5 py-3.5 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($employee->medicalLeaves as $leave)
                    <tr class="align-top">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-800">Folio {{ $leave->folio }}</p>
                            <p class="text-xs text-slate-500">{{ $leave->type_label }}</p>
                            @if ($leave->previous)
                                <p class="text-[11px] text-slate-400 mt-0.5">Continúa el folio {{ $leave->previous->folio }}</p>
                            @endif
                            @if ($leave->notes)
                                <p class="text-xs text-slate-600 mt-0.5">{{ $leave->notes }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <p class="font-semibold text-slate-800">{{ $leave->start_date->format('d/m/Y') }} – {{ $leave->end_date->format('d/m/Y') }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $leave->days }} {{ $leave->days === 1 ? 'día' : 'días' }}
                                @if ($leave->status === MedicalLeave::STATUS_REDUCED)
                                    · autorizados {{ $leave->authorized_days }}
                                @endif
                            </p>
                        </td>
                        <td class="px-5 py-4 text-xs text-slate-600">{{ $leave->payer_label }}</td>
                        <td class="px-5 py-4 text-right">
                            @if ($leave->status === MedicalLeave::STATUS_CANCELLED)
                                <span class="text-slate-400">—</span>
                            @elseif ($leave->subsidy_daily_amount === null)
                                <p class="text-xs text-slate-500">Sin base de cálculo</p>
                                <p class="text-[11px] text-slate-400">Faltan liquidaciones o indicadores del período.</p>
                            @else
                                <p class="font-semibold text-slate-900">{{ $clp($leave->subsidy_amount) }}</p>
                                <p class="text-xs text-slate-500">{{ $leave->subsidy_days }} {{ $leave->subsidy_days === 1 ? 'día' : 'días' }} × {{ $clp($leave->subsidy_daily_amount) }}</p>
                                @if ($leave->waiting_days > 0)
                                    <p class="text-[11px] text-amber-600">{{ $leave->waiting_days }} {{ $leave->waiting_days === 1 ? 'día' : 'días' }} de carencia</p>
                                @endif
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Base neta {{ $clp($leave->subsidy_base_amount) }}
                                    @if (! empty($leave->subsidy_detail['months']))
                                        ({{ collect($leave->subsidy_detail['months'])->filter(fn ($m) => $m['source'])->map(fn ($m) => \Illuminate\Support\Carbon::createFromFormat('!Y-m', $m['period'])->format('m/Y').' '.($sourceLabels[$m['source']] ?? $m['source']))->implode(', ') }})
                                    @endif
                                </p>
                                @if (! empty($leave->subsidy_detail['minimum_applied']))
                                    <p class="text-[11px] text-slate-400">Se aplicó el subsidio diario mínimo.</p>
                                @endif
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $leaveStatusClasses[$leave->status] ?? '' }}">{{ $leave->status_label }}</span>
                            @if ($leave->resolution_date && $leave->status !== MedicalLeave::STATUS_CANCELLED)
                                <p class="text-[11px] text-slate-400 mt-1">{{ $leave->resolution_date->format('d/m/Y') }}</p>
                            @endif
                            @if ($leave->resolution_notes)
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $leave->resolution_notes }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @can('manageEmployees', $company)
                                @if ($leave->status !== MedicalLeave::STATUS_CANCELLED)
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <button type="button" @click="resolving = resolving === {{ $leave->id }} ? null : {{ $leave->id }}"
                                            class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">Resolución</button>
                                        <form method="POST" action="{{ route('companies.employees.medical-leaves.subsidy', [$company, $employee, $leave]) }}">
                                            @csrf
                                            <button type="submit" title="Vuelve a estimar el subsidio con las liquidaciones disponibles"
                                                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Recalcular</button>
                                        </form>
                                        <form method="POST" action="{{ route('companies.employees.medical-leaves.cancel', [$company, $employee, $leave]) }}"
                                            onsubmit="return confirm('¿Anular esta licencia? Sus días se quitarán de la asistencia.');">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">Anular</button>
                                        </form>
                                    </div>

                                    <form x-show="resolving === {{ $leave->id }}" x-cloak method="POST"
                                        x-data="{ status: '{{ in_array($leave->status, MedicalLeave::RESOLUTION_STATUSES, true) ? $leave->status : MedicalLeave::STATUS_AUTHORIZED }}' }"
                                        action="{{ route('companies.employees.medical-leaves.resolve', [$company, $employee, $leave]) }}"
                                        class="mt-3 grid grid-cols-2 gap-2 text-left">
                                        @csrf
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Resolución</label>
                                            <select name="status" x-model="status" class="w-full rounded-lg border-slate-300 text-xs py-1.5">
                                                @foreach (MedicalLeave::RESOLUTION_STATUSES as $status)
                                                    <option value="{{ $status }}">{{ MedicalLeave::STATUS_LABELS[$status] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Fecha</label>
                                            <input type="date" name="resolution_date" max="{{ now()->toDateString() }}"
                                                value="{{ $leave->resolution_date?->toDateString() ?? now()->toDateString() }}" class="w-full rounded-lg border-slate-300 text-xs py-1.5">
                                        </div>
                                        <div x-show="status === '{{ MedicalLeave::STATUS_REDUCED }}'">
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Días autorizados</label>
                                            <input type="number" name="authorized_days" min="1" max="{{ max(1, $leave->days - 1) }}"
                                                :disabled="status !== '{{ MedicalLeave::STATUS_REDUCED }}'"
                                                value="{{ $leave->authorized_days }}" class="w-full rounded-lg border-slate-300 text-xs py-1.5">
                                        </div>
                                        <div :class="status === '{{ MedicalLeave::STATUS_REDUCED }}' ? '' : 'col-span-2'">
                                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Observación</label>
                                            <input type="text" name="resolution_notes" maxlength="500" value="{{ $leave->resolution_notes }}" class="w-full rounded-lg border-slate-300 text-xs py-1.5">
                                        </div>
                                        <div class="col-span-2 flex justify-end">
                                            <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Guardar resolución</button>
                                        </div>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">Sin licencias médicas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
