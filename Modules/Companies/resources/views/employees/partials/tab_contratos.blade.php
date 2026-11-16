{{--
    PESTAÑA: CONTRATOS
    Historial de versiones del contrato (contrato original y anexos) con su vigencia.
    Requiere: $employee->contracts, $employee->activeContract, $costCenters
--}}
@php
    $current = $employee->activeContract ?? $employee->contracts->first();
    $amendmentErrors = $errors->getBag('amendment');
    $scheduleLabels = ['full_time' => 'Jornada completa', 'part_time' => 'Jornada parcial', 'art_22' => 'Art. 22 (sin límite de jornada)'];
    $today = now()->startOfDay();
@endphp
<div x-show="tab === 'contratos'" x-cloak x-data="{ showAmendment: {{ $amendmentErrors->any() ? 'true' : 'false' }} }">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Historial contractual</h2>
            <p class="text-xs text-slate-400 mt-0.5">Cada liquidación usa el sueldo y las condiciones vigentes en su mes.</p>
        </div>
        @can('manageEmployees', $company)
            <button type="button" @click="showAmendment = !showAmendment"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700 transition-colors">
                Registrar anexo
            </button>
        @endcan
    </div>

    @can('manageEmployees', $company)
        <div x-show="showAmendment" x-cloak class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
            <h3 class="text-sm font-bold text-slate-800">Nuevo anexo de contrato</h3>
            <p class="text-xs text-slate-500 mt-1 mb-5">
                Las condiciones nuevas rigen desde la fecha de vigencia; las anteriores se conservan para los meses previos.
                Usa el primer día del mes: una liquidación aplica a todo el mes las condiciones vigentes a su cierre.
            </p>

            @if ($amendmentErrors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($amendmentErrors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('companies.employees.contracts.amend', [$company, $employee]) }}"
                class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Vigencia desde *</label>
                    <input type="date" name="effective_date" required
                        value="{{ old('effective_date', now()->addMonthNoOverflow()->startOfMonth()->toDateString()) }}"
                        class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Sueldo base ($) *</label>
                    <input type="number" name="base_salary" min="0" step="1" required
                        value="{{ old('base_salary', (int) ($current?->base_salary ?? $employee->salary)) }}"
                        class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Cargo</label>
                    <input type="text" name="position" maxlength="150"
                        value="{{ old('position', $current?->position ?? $employee->position) }}"
                        class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tipo de contrato *</label>
                    <select name="contract_type" required class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach (\Modules\Employees\Models\EmploymentContract::TYPE_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected(old('contract_type', $current?->contract_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Término del plazo</label>
                    <input type="date" name="end_date"
                        value="{{ old('end_date', $current?->end_date?->toDateString()) }}"
                        class="w-full rounded-lg border-slate-300 text-sm">
                    <p class="text-[11px] text-slate-400 mt-1">Sólo para plazo fijo.</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jornada *</label>
                    <select name="work_schedule_type" required class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($scheduleLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('work_schedule_type', $current?->work_schedule_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Horas semanales *</label>
                    <input type="number" name="weekly_hours" min="1" max="45" required
                        value="{{ old('weekly_hours', $current?->weekly_hours ?? ($company->weekly_hours ?? 42)) }}"
                        class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Centro de costo</label>
                    <select name="cost_center_id" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Sin centro de costo</option>
                        @foreach ($costCenters as $costCenter)
                            <option value="{{ $costCenter->id }}" @selected((string) old('cost_center_id', $current?->cost_center_id) === (string) $costCenter->id)>
                                {{ $costCenter->code }} · {{ $costCenter->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Motivo del anexo *</label>
                    <input type="text" name="change_reason" maxlength="500" required value="{{ old('change_reason') }}"
                        placeholder="Ej.: reajuste anual, cambio de cargo, paso a contrato indefinido"
                        class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div class="md:col-span-3 flex justify-end gap-2">
                    <button type="button" @click="showAmendment = false"
                        class="rounded-lg border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
                    <button type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">Guardar anexo</button>
                </div>
            </form>
        </div>
    @endcan

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <th class="px-5 py-3.5 text-left">Vigencia</th>
                    <th class="px-5 py-3.5 text-left">Documento</th>
                    <th class="px-5 py-3.5 text-left">Tipo y cargo</th>
                    <th class="px-5 py-3.5 text-left">Jornada</th>
                    <th class="px-5 py-3.5 text-right">Sueldo base</th>
                    <th class="px-5 py-3.5 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($employee->contracts as $version)
                    @php
                        $from = $version->valid_from ?? $version->start_date;
                        $state = match (true) {
                            $version->status !== 'active' => ['Terminado', 'bg-slate-100 text-slate-600 border-slate-200'],
                            $from && $from->gt($today) => ['Futuro', 'bg-amber-50 text-amber-700 border-amber-200'],
                            $version->valid_until && $version->valid_until->lt($today) => ['Histórico', 'bg-slate-100 text-slate-600 border-slate-200'],
                            default => ['Vigente', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
                        };
                    @endphp
                    <tr>
                        <td class="px-5 py-4 whitespace-nowrap">
                            <p class="font-semibold text-slate-800">{{ $from?->format('d/m/Y') ?? '—' }}</p>
                            <p class="text-xs text-slate-400">hasta {{ $version->valid_until?->format('d/m/Y') ?? 'la fecha' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-700">{{ $version->version_type === 'anexo' ? 'Anexo' : 'Contrato' }}</p>
                            <p class="text-xs text-slate-400">{{ $version->contract_number }}</p>
                            @if ($version->change_reason)
                                <p class="text-xs text-slate-500 mt-1">{{ $version->change_reason }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-slate-700">{{ $version->type_label }}</p>
                            <p class="text-xs text-slate-400">{{ $version->position ?: 'Sin cargo' }}</p>
                            @if ($version->end_date)
                                <p class="text-xs text-slate-400">Plazo hasta {{ $version->end_date->format('d/m/Y') }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-slate-700 whitespace-nowrap">
                            {{ $scheduleLabels[$version->work_schedule_type] ?? $version->work_schedule_type }}
                            <span class="text-xs text-slate-400">· {{ $version->weekly_hours }} h</span>
                        </td>
                        <td class="px-5 py-4 text-right font-semibold text-slate-900 whitespace-nowrap">
                            $&nbsp;{{ number_format((float) $version->base_salary, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $state[1] }}">{{ $state[0] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">Este colaborador aún no tiene contrato registrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-slate-400 mt-4">
        Corregir un dato en la pestaña Resumen modifica la versión vigente desde su inicio. Para un cambio que rige
        desde una fecha (reajuste, ascenso, cambio de jornada) registra un anexo.
    </p>
</div>
