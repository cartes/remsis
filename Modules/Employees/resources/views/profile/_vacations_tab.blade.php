{{-- Vacaciones del colaborador: saldo, solicitud e historial. Requiere $vacationBalance y $vacations. --}}
@php
    $vacationErrors = $errors->getBag('vacation');
    $fmtDays = fn ($days) => \Modules\Employees\Services\VacationService::formatDays((float) $days);
    $vacationStatus = [
        'pending' => 'bg-amber-100 text-amber-700',
        'approved' => 'bg-emerald-100 text-emerald-700',
        'rejected' => 'bg-rose-100 text-rose-700',
        'cancelled' => 'bg-slate-100 text-slate-600',
    ];
@endphp
<div x-show="activeTab === 'vacaciones'" x-cloak>
    @if (session('portal_tab') === 'vacaciones' && session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if ($vacationErrors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($vacationErrors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $vacationBalance || ! $vacationBalance['has_hire_date'])
        <div class="bg-white rounded-lg shadow-sm border border-slate-100 py-12 text-center">
            <p class="text-sm font-semibold text-slate-500">Saldo no disponible</p>
            <p class="text-xs text-slate-400 mt-1">Tu ficha aún no tiene fecha de ingreso. Pide a tu empresa que la complete.</p>
        </div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @foreach ([
                ['Devengados', $vacationBalance['initial'] + $vacationBalance['accrued'] + $vacationBalance['progressive'], 'text-slate-800'],
                ['Utilizados', $vacationBalance['used'], 'text-violet-600'],
                ['En solicitud', $vacationBalance['pending'], 'text-amber-600'],
                ['Disponibles', $vacationBalance['available'], $vacationBalance['available'] < 0 ? 'text-red-600' : 'text-emerald-600'],
            ] as [$label, $value, $class])
                <div class="bg-white rounded-lg shadow-sm p-5 border border-slate-100">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">{{ $label }}</p>
                    <p class="text-2xl font-black {{ $class }}">{{ $fmtDays($value) }}</p>
                    <p class="text-[11px] text-slate-400">días hábiles</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="bg-white rounded-lg shadow-sm border border-slate-100 p-5">
                <h3 class="text-sm font-bold text-slate-700 mb-1">Solicitar vacaciones</h3>
                <p class="text-xs text-slate-400 mb-4">
                    Sábados, domingos y festivos no se descuentan de tu saldo. La solicitud queda pendiente hasta que tu empresa la apruebe.
                </p>
                <form method="POST" action="{{ route('employee.vacations.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Desde</label>
                        <input type="date" name="start_date" required min="{{ now()->toDateString() }}" value="{{ old('start_date') }}"
                            class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Hasta</label>
                        <input type="date" name="end_date" required min="{{ now()->toDateString() }}" value="{{ old('end_date') }}"
                            class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Comentario (opcional)</label>
                        <input type="text" name="notes" maxlength="500" value="{{ old('notes') }}" class="w-full rounded-lg border-slate-300 text-sm">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">
                        Enviar solicitud
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-100 lg:col-span-2">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-700">Mis vacaciones</h3>
                </div>
                @forelse ($vacations as $vacation)
                    <div class="flex flex-wrap items-center gap-4 px-5 py-4 border-b border-slate-50 last:border-b-0">
                        <div class="flex-1 min-w-[10rem]">
                            <p class="text-sm font-semibold text-slate-700">
                                {{ $vacation->start_date->format('d/m/Y') }} – {{ $vacation->end_date->format('d/m/Y') }}
                            </p>
                            <p class="text-xs text-slate-400">
                                {{ $fmtDays($vacation->working_days) }} días hábiles
                                @if ($vacation->rejection_reason)
                                    · Motivo del rechazo: {{ $vacation->rejection_reason }}
                                @endif
                            </p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $vacationStatus[$vacation->status] ?? '' }}">{{ $vacation->status_label }}</span>
                        @if ($vacation->status === 'pending')
                            <form method="POST" action="{{ route('employee.vacations.cancel', $vacation->id) }}">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-slate-600 border border-slate-200 px-3 py-1.5 rounded-lg hover:border-slate-400 transition-colors">Anular</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="py-12 text-center">
                        <p class="text-sm font-semibold text-slate-500">Sin vacaciones registradas</p>
                        <p class="text-xs text-slate-400 mt-1">Tus solicitudes y vacaciones aprobadas aparecerán aquí.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
