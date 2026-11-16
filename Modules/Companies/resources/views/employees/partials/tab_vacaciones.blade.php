{{--
    PESTAÑA: VACACIONES (feriado anual, Arts. 67 a 70 CT)
    Requiere: $vacationBalance, $employee->vacationRequests
--}}
@php
    $vacationErrors = $errors->getBag('vacation');
    $fmtDays = fn ($days) => \Modules\Employees\Services\VacationService::formatDays((float) $days);
    $calendar = app(\Modules\Employees\Services\WorkingDayCalendar::class);
    $missingCalendar = collect([now()->year, now()->year + 1])->reject(fn ($year) => $calendar->hasCalendarFor($year));
    $statusClasses = [
        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
        'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
@endphp
<div x-show="tab === 'vacaciones'" x-cloak x-data="{ showSettings: false }">

    <div class="mb-6">
        <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Vacaciones</h2>
        <p class="text-xs text-slate-400 mt-0.5">
            Feriado anual de {{ $fmtDays($vacationBalance['per_year']) }} días hábiles por año de servicio
            @if ($vacationBalance['progressive_per_year'] > 0)
                más {{ $vacationBalance['progressive_per_year'] }} de feriado progresivo
            @endif
            · sábados, domingos y festivos no se descuentan.
        </p>
    </div>

    @if ($vacationErrors->any())
        <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($vacationErrors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $vacationBalance['has_hire_date'])
        <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            El colaborador no tiene fecha de ingreso, por lo que no se puede calcular su saldo de vacaciones.
        </div>
    @else
        @if (! $vacationBalance['entitled'])
            <div class="mb-4 rounded-xl bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
                Aún no cumple un año de servicio: el saldo mostrado es proporcional y otorgarlo antes es un anticipo voluntario de la empresa.
            </div>
        @endif
        @if ($vacationBalance['exceeds_accumulation'])
            <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                El saldo supera dos períodos de feriado. La ley sólo permite acumular hasta dos (Art. 70 CT): conviene programar vacaciones.
            </div>
        @endif
    @endif
    @if ($missingCalendar->isNotEmpty())
        <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            No hay calendario de festivos cargado para {{ $missingCalendar->implode(' ni ') }}: en esos años sólo se excluyen sábados y domingos.
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @foreach ([
            ['Devengados', $vacationBalance['initial'] + $vacationBalance['accrued'] + $vacationBalance['progressive'], 'text-slate-900'],
            ['Utilizados', $vacationBalance['used'], 'text-violet-700'],
            ['En solicitud', $vacationBalance['pending'], 'text-amber-700'],
            ['Disponibles', $vacationBalance['available'], $vacationBalance['available'] < 0 ? 'text-red-600' : 'text-emerald-700'],
        ] as [$label, $value, $class])
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ $label }}</p>
                <p class="text-2xl font-black mt-1 {{ $class }}">{{ $fmtDays($value) }}</p>
                <p class="text-[11px] text-slate-400">días hábiles</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        @can('manageRequests', $company)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="text-sm font-bold text-slate-800 mb-1">Registrar vacaciones</h3>
                <p class="text-xs text-slate-500 mb-4">Quedan aprobadas de inmediato y se marcan en la asistencia.</p>
                <form method="POST" action="{{ route('companies.employees.vacations.store', [$company, $employee]) }}" class="grid grid-cols-2 gap-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Desde *</label>
                        <input type="date" name="start_date" required value="{{ old('start_date') }}" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Hasta *</label>
                        <input type="date" name="end_date" required value="{{ old('end_date') }}" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Observación</label>
                        <input type="text" name="notes" maxlength="500" value="{{ old('notes') }}" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div class="col-span-2 flex justify-end">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">Registrar</button>
                    </div>
                </form>
            </div>
        @endcan

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-800">Cómo se calcula el saldo</h3>
                @can('manageEmployees', $company)
                    <button type="button" @click="showSettings = !showSettings" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Ajustar</button>
                @endcan
            </div>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Se devenga desde</dt><dd class="font-semibold text-slate-800">{{ $vacationBalance['accrual_from']?->format('d/m/Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Saldo de apertura</dt><dd class="font-semibold text-slate-800">{{ $fmtDays($vacationBalance['initial']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Devengado proporcional</dt><dd class="font-semibold text-slate-800">{{ $fmtDays($vacationBalance['accrued']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Feriado progresivo</dt><dd class="font-semibold text-slate-800">{{ $fmtDays($vacationBalance['progressive']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Años de servicio</dt><dd class="font-semibold text-slate-800">{{ $vacationBalance['years_of_service'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Meses con otros empleadores</dt><dd class="font-semibold text-slate-800">{{ (int) $employee->previous_employment_months }}</dd></div>
            </dl>

            @can('manageEmployees', $company)
                <form x-show="showSettings" x-cloak method="POST"
                    action="{{ route('companies.employees.vacations.settings', [$company, $employee]) }}"
                    class="grid grid-cols-2 gap-4 mt-5 pt-5 border-t border-slate-100">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Saldo de apertura (días)</label>
                        <input type="number" step="0.01" name="vacation_initial_balance"
                            value="{{ old('vacation_initial_balance', $employee->vacation_initial_balance) }}" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha de ese saldo</label>
                        <input type="date" name="vacation_initial_balance_date"
                            value="{{ old('vacation_initial_balance_date', $employee->vacation_initial_balance_date?->toDateString()) }}" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Meses trabajados con otros empleadores (acreditados)</label>
                        <input type="number" min="0" max="600" name="previous_employment_months"
                            value="{{ old('previous_employment_months', (int) $employee->previous_employment_months) }}" class="w-full rounded-lg border-slate-300 text-sm">
                        <p class="text-[11px] text-slate-400 mt-1">Para el feriado progresivo se reconocen hasta 120 meses (Art. 68 CT).</p>
                    </div>
                    <p class="col-span-2 text-[11px] text-slate-400">
                        Con saldo de apertura, el devengo y los días utilizados se cuentan desde esa fecha. Déjalo vacío para calcular desde el ingreso.
                    </p>
                    <div class="col-span-2 flex justify-end">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">Guardar</button>
                    </div>
                </form>
            @endcan
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <th class="px-5 py-3.5 text-left">Período</th>
                    <th class="px-5 py-3.5 text-right">Días hábiles</th>
                    <th class="px-5 py-3.5 text-left">Origen y observación</th>
                    <th class="px-5 py-3.5 text-center">Estado</th>
                    <th class="px-5 py-3.5 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($employee->vacationRequests as $vacation)
                    <tr>
                        <td class="px-5 py-4 whitespace-nowrap font-semibold text-slate-800">
                            {{ $vacation->start_date->format('d/m/Y') }} – {{ $vacation->end_date->format('d/m/Y') }}
                        </td>
                        <td class="px-5 py-4 text-right font-semibold text-slate-900">{{ $fmtDays($vacation->working_days) }}</td>
                        <td class="px-5 py-4 text-xs text-slate-500">
                            {{ $vacation->origin === 'company' ? 'Registrada por la empresa' : 'Solicitada por el colaborador' }}
                            @if ($vacation->notes)
                                <p class="text-slate-600 mt-0.5">{{ $vacation->notes }}</p>
                            @endif
                            @if ($vacation->rejection_reason)
                                <p class="text-rose-600 mt-0.5">Motivo: {{ $vacation->rejection_reason }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$vacation->status] ?? '' }}">{{ $vacation->status_label }}</span>
                            @if ($vacation->resolver)
                                <p class="text-[11px] text-slate-400 mt-1">{{ $vacation->resolver->name }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @can('manageRequests', $company)
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    @if ($vacation->status === 'pending')
                                        <form method="POST" action="{{ route('companies.employees.vacations.approve', [$company, $employee, $vacation]) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Aprobar</button>
                                        </form>
                                        <form method="POST" action="{{ route('companies.employees.vacations.reject', [$company, $employee, $vacation]) }}" class="flex items-center gap-1">
                                            @csrf
                                            <input type="text" name="rejection_reason" required maxlength="500" placeholder="Motivo del rechazo"
                                                class="w-40 rounded-lg border-slate-300 text-xs py-1.5">
                                            <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100">Rechazar</button>
                                        </form>
                                    @elseif ($vacation->status === 'approved')
                                        <form method="POST" action="{{ route('companies.employees.vacations.cancel', [$company, $employee, $vacation]) }}"
                                            onsubmit="return confirm('¿Anular estas vacaciones? Se quitarán de la asistencia y los días volverán al saldo.');">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Anular</button>
                                        </form>
                                    @endif
                                </div>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">Sin vacaciones registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
