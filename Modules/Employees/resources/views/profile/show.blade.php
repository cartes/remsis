<x-adminpanel::layouts.master>
@section('title', 'Ficha del Colaborador')

@section('content')
@php
    $emp  = $employee;
    $name = $emp?->full_name ?? $user->name;
    $completion = $emp?->completion_percentage ?? 0;

    $contractLabels = [
        'indefinido'   => 'Indefinido',
        'plazo_fijo'   => 'Plazo Fijo',
        'obra_faena'   => 'Por Obra o Faena',
        'honorarios'   => 'Honorarios',
        'part_time'    => 'Part-time',
    ];
    $scheduleLabels = [
        'full_time' => 'Jornada Completa',
        'part_time' => 'Jornada Parcial',
    ];
    $healthLabels = ['fonasa' => 'FONASA', 'isapre' => 'ISAPRE'];
    $genderLabels = ['masculino' => 'Masculino', 'femenino' => 'Femenino', 'otro' => 'Otro'];
    $paymentLabels = ['transferencia' => 'Transferencia Bancaria', 'cheque' => 'Cheque', 'efectivo' => 'Efectivo'];

    $diasEmpresa  = $emp?->hire_date ? $emp->hire_date->diffInDays(now())  : null;
    $aniosEmpresa = $emp?->hire_date ? $emp->hire_date->diffInYears(now()) : null;
@endphp

<div
    x-data="{ activeTab: 'resumen', dropdownItemsOpen: false }"
    @click.away="dropdownItemsOpen = false"
    class="min-h-screen bg-slate-50"
>

    {{-- ══════════════ HEADER ══════════════ --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 py-6 flex items-center gap-5">

            {{-- Avatar --}}
            <div class="relative flex-shrink-0">
                @if($user->profile_photo ?? null)
                    <img src="{{ Storage::url($user->profile_photo) }}" alt="{{ $name }}"
                        class="w-[72px] h-[72px] rounded-2xl object-cover ring-2 ring-slate-100">
                @else
                    <div class="w-[72px] h-[72px] rounded-2xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center ring-2 ring-slate-100 select-none">
                        <span class="text-xl font-black text-white tracking-tight">
                            {{ strtoupper(substr($emp?->first_name ?? $user->name, 0, 1)) }}{{ strtoupper(substr($emp?->last_name ?? '', 0, 1)) }}
                        </span>
                    </div>
                @endif
                <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white {{ ($emp?->status ?? 'active') === 'active' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-lg font-bold text-slate-900 tracking-tight">{{ $name }}</h1>
                    @if($emp?->rut)
                        <span class="text-xs font-mono bg-slate-100 text-slate-500 px-2 py-0.5 rounded-md">{{ $emp->rut }}</span>
                    @endif
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ ($emp?->status ?? 'active') === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                        {{ ($emp?->status ?? 'active') === 'active' ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                <div class="flex items-center gap-3 mt-1 flex-wrap text-sm text-slate-500">
                    <span>{{ $emp?->position ?? 'Sin cargo asignado' }}</span>
                    @if($emp?->company)
                        <span class="text-slate-300">·</span>
                        <span>{{ $emp->company->name }}</span>
                    @endif
                    @if($emp?->hire_date)
                        <span class="text-slate-300">·</span>
                        <span class="text-slate-400">Ingresó {{ $emp->hire_date->format('d M Y') }}</span>
                    @endif
                </div>
            </div>

            {{-- Completitud --}}
            <div class="hidden sm:flex flex-col items-end gap-1.5 flex-shrink-0">
                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Completitud</span>
                <div class="flex items-center gap-2">
                    <div class="w-28 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500
                            {{ $completion >= 80 ? 'bg-emerald-500' : ($completion >= 50 ? 'bg-amber-400' : 'bg-red-400') }}"
                            style="width: {{ $completion }}%"></div>
                    </div>
                    <span class="text-sm font-bold {{ $completion >= 80 ? 'text-emerald-600' : ($completion >= 50 ? 'text-amber-600' : 'text-red-500') }}">{{ $completion }}%</span>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════ TABS HORIZONTALES ══════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-6">
            <nav class="flex items-stretch gap-0 -mb-px overflow-x-auto scrollbar-hide">

                {{-- Resumen --}}
                <button @click="activeTab = 'resumen'; dropdownItemsOpen = false"
                    class="flex items-center gap-1.5 px-4 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 focus:outline-none"
                    :class="activeTab === 'resumen'
                        ? 'text-blue-600 border-blue-600'
                        : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Resumen
                </button>

                {{-- Perfil --}}
                <button @click="activeTab = 'perfil'; dropdownItemsOpen = false"
                    class="flex items-center gap-1.5 px-4 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 focus:outline-none"
                    :class="activeTab === 'perfil'
                        ? 'text-blue-600 border-blue-600'
                        : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Perfil
                </button>

                {{-- Ítems (Dropdown) --}}
                <div class="relative flex items-stretch">
                    <button @click="dropdownItemsOpen = !dropdownItemsOpen"
                        class="flex items-center gap-1.5 px-4 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 focus:outline-none"
                        :class="['haberes','descuentos','creditos'].includes(activeTab)
                            ? 'text-blue-600 border-blue-600'
                            : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span :class="['haberes','descuentos','creditos'].includes(activeTab) ? 'text-blue-600' : ''">
                            Ítems
                        </span>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="dropdownItemsOpen ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    {{-- Dropdown panel --}}
                    <div x-show="dropdownItemsOpen"
                        x-transition:enter="ease-out duration-150"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        x-cloak
                        class="absolute top-full left-0 mt-1 w-44 bg-white rounded-xl shadow-lg border border-slate-200 py-1.5 z-50"
                    >
                        @foreach([['haberes','Haberes','M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2'],['descuentos','Descuentos','M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'],['creditos','Créditos','M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z']] as [$tab, $label, $icon])
                        <button @click="activeTab = '{{ $tab }}'; dropdownItemsOpen = false"
                            class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm transition-colors"
                            :class="activeTab === '{{ $tab }}'
                                ? 'text-blue-600 bg-blue-50 font-semibold'
                                : 'text-gray-600 hover:bg-slate-50 hover:text-gray-800 font-medium'">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/>
                            </svg>
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Liquidaciones --}}
                <button @click="activeTab = 'liquidaciones'; dropdownItemsOpen = false"
                    class="flex items-center gap-1.5 px-4 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 focus:outline-none"
                    :class="activeTab === 'liquidaciones'
                        ? 'text-blue-600 border-blue-600'
                        : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Liquidaciones
                </button>

                {{-- Asistencia --}}
                <button @click="activeTab = 'asistencia'; dropdownItemsOpen = false"
                    class="flex items-center gap-1.5 px-4 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 focus:outline-none"
                    :class="activeTab === 'asistencia'
                        ? 'text-blue-600 border-blue-600'
                        : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Asistencia
                </button>

                {{-- Documentos --}}
                <button @click="activeTab = 'documentos'; dropdownItemsOpen = false"
                    class="flex items-center gap-1.5 px-4 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 focus:outline-none"
                    :class="activeTab === 'documentos'
                        ? 'text-blue-600 border-blue-600'
                        : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    Documentos
                </button>

                {{-- Historial --}}
                <button @click="activeTab = 'historial'; dropdownItemsOpen = false"
                    class="flex items-center gap-1.5 px-4 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors duration-150 focus:outline-none"
                    :class="activeTab === 'historial'
                        ? 'text-blue-600 border-blue-600'
                        : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Historial
                </button>

            </nav>
        </div>
    </div>

    {{-- ══════════════ CONTENIDO POR TAB ══════════════ --}}
    <div class="max-w-7xl mx-auto px-6 py-6">

        {{-- ════ RESUMEN ════ --}}
        <div x-show="activeTab === 'resumen'" x-cloak>

            {{-- Métricas rápidas --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm p-5 border border-slate-100">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Cargo</p>
                    <p class="text-sm font-bold text-slate-800 leading-snug">{{ $emp?->position ?? '—' }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 border border-slate-100">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Sueldo Base</p>
                    @if($emp?->salary)
                        <p class="text-base font-black text-emerald-600">$ {{ number_format($emp->salary, 0, ',', '.') }}</p>
                    @else
                        <p class="text-sm font-medium text-slate-300">Sin asignar</p>
                    @endif
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 border border-slate-100">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Antigüedad</p>
                    @if($diasEmpresa !== null)
                        <p class="text-base font-black text-violet-600">{{ $aniosEmpresa > 0 ? $aniosEmpresa.' año'.($aniosEmpresa>1?'s':'') : $diasEmpresa.' días' }}</p>
                    @else
                        <p class="text-sm font-medium text-slate-300">—</p>
                    @endif
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5 border border-slate-100">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">AFP</p>
                    <p class="text-sm font-bold text-slate-800">{{ optional($emp?->afp)->nombre ?? '—' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- Datos contractuales --}}
                <div class="bg-white rounded-lg shadow-sm border border-slate-100 p-5 lg:col-span-2">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Contrato y Remuneración</h3>
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <dt class="text-[10px] font-semibold text-slate-400 uppercase">Tipo contrato</dt>
                            <dd class="text-sm font-semibold text-slate-700 mt-0.5">{{ $contractLabels[$emp?->contract_type ?? ''] ?? ($emp?->contract_type ?? '—') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold text-slate-400 uppercase">Jornada</dt>
                            <dd class="text-sm font-semibold text-slate-700 mt-0.5">{{ $scheduleLabels[$emp?->work_schedule_type ?? ''] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold text-slate-400 uppercase">Ingreso</dt>
                            <dd class="text-sm font-semibold text-slate-700 mt-0.5">{{ $emp?->hire_date?->format('d M Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold text-slate-400 uppercase">Movilización</dt>
                            <dd class="text-sm font-semibold text-slate-700 mt-0.5">{{ $emp?->mobility_allowance ? '$ '.number_format($emp->mobility_allowance,0,',','.') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold text-slate-400 uppercase">Colación</dt>
                            <dd class="text-sm font-semibold text-slate-700 mt-0.5">{{ $emp?->meal_allowance ? '$ '.number_format($emp->meal_allowance,0,',','.') : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold text-slate-400 uppercase">Centro costo</dt>
                            <dd class="text-sm font-semibold text-slate-700 mt-0.5">{{ optional($emp?->costCenter)->code ?? '—' }}</dd>
                        </div>
                    </div>
                </div>

                {{-- Previsión --}}
                <div class="bg-white rounded-lg shadow-sm border border-slate-100 p-5">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Previsión Social</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between py-2 border-b border-slate-50">
                            <span class="text-xs font-medium text-slate-500">AFP</span>
                            <span class="text-xs font-bold text-slate-800">{{ optional($emp?->afp)->nombre ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-slate-50">
                            <span class="text-xs font-medium text-slate-500">Salud</span>
                            <span class="text-xs font-bold text-slate-800">
                                @if($emp?->health_system === 'isapre')
                                    {{ optional($emp->isapre)->nombre ?? 'ISAPRE' }}
                                @else
                                    {{ $healthLabels[$emp?->health_system ?? ''] ?? '—' }}
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2">
                            <span class="text-xs font-medium text-slate-500">CCAF</span>
                            <span class="text-xs font-bold text-slate-800">{{ optional($emp?->ccaf)->nombre ?? '—' }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ════ PERFIL ════ --}}
        <div x-show="activeTab === 'perfil'" x-cloak>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                {{-- Datos personales --}}
                <div class="bg-white rounded-lg shadow-sm border border-slate-100 p-5 lg:col-span-2">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-5">Datos Personales</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-5">
                        @php
                        $fields = [
                            ['RUT',               $emp?->rut,                                        true],
                            ['Email',             $emp?->email ?? $user->email,                      false],
                            ['Teléfono',          $emp?->phone,                                      false],
                            ['Fecha de nac.',     $emp?->birth_date?->format('d/m/Y'),               false],
                            ['Género',            $genderLabels[$emp?->gender ?? ''] ?? null,        false],
                            ['Nacionalidad',      $emp?->nationality,                                false],
                            ['Estado civil',      match($emp?->marital_status) { 'single'=>'Soltero/a','married'=>'Casado/a','divorced'=>'Divorciado/a','widowed'=>'Viudo/a', default=>null }, false],
                            ['Dirección',         $emp?->address,                                    false],
                        ];
                        @endphp
                        @foreach($fields as [$label, $value, $mono])
                        <div>
                            <dt class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">{{ $label }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-slate-800 {{ $mono ? 'font-mono' : '' }}">{{ $value ?? '—' }}</dd>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Previsión detalle --}}
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-100 p-5">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Previsión y Salud</h3>
                        <div class="space-y-3">
                            <div class="p-3 rounded-lg bg-blue-50 border border-blue-100">
                                <p class="text-[10px] font-bold text-blue-500 uppercase">AFP</p>
                                <p class="text-sm font-bold text-blue-900 mt-0.5">{{ optional($emp?->afp)->nombre ?? '—' }}</p>
                            </div>
                            <div class="p-3 rounded-lg {{ $emp?->health_system === 'isapre' ? 'bg-purple-50 border border-purple-100' : 'bg-teal-50 border border-teal-100' }}">
                                <p class="text-[10px] font-bold {{ $emp?->health_system === 'isapre' ? 'text-purple-500' : 'text-teal-500' }} uppercase">Salud</p>
                                <p class="text-sm font-bold {{ $emp?->health_system === 'isapre' ? 'text-purple-900' : 'text-teal-900' }} mt-0.5">
                                    @if($emp?->health_system === 'isapre')
                                        {{ optional($emp->isapre)->nombre ?? 'ISAPRE' }}
                                        @if($emp->health_contribution) <span class="text-xs font-normal">({{ $emp->health_contribution }} UF)</span>@endif
                                    @else
                                        {{ $healthLabels[$emp?->health_system ?? ''] ?? '—' }}
                                    @endif
                                </p>
                            </div>
                            <div class="p-3 rounded-lg bg-amber-50 border border-amber-100">
                                <p class="text-[10px] font-bold text-amber-500 uppercase">CCAF</p>
                                <p class="text-sm font-bold text-amber-900 mt-0.5">{{ optional($emp?->ccaf)->nombre ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Pago --}}
                    <div class="bg-white rounded-lg shadow-sm border border-slate-100 p-5">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Medio de Pago</h3>
                        <p class="text-sm font-bold text-slate-800">{{ $paymentLabels[$emp?->payment_method ?? ''] ?? '—' }}</p>
                        @if($emp?->payment_method === 'transferencia' && $emp?->bank_id)
                        <div class="mt-3 space-y-1 text-xs text-slate-500">
                            <p><span class="font-medium">Banco:</span> {{ optional($emp->bank)->name ?? '—' }}</p>
                            <p><span class="font-medium">Cuenta:</span> {{ $emp->bank_account_type ? ucfirst($emp->bank_account_type) : '—' }}</p>
                            <p class="font-mono">{{ $emp->bank_account_number ?? '—' }}</p>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{-- ════ HABERES ════ --}}
        <div x-show="activeTab === 'haberes'" x-cloak>
            <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-700">Haberes Fijos</h3>
                    <span class="text-xs bg-blue-50 text-blue-600 border border-blue-100 px-2.5 py-1 rounded-full font-medium">Próximamente</span>
                </div>
                <div class="py-12 flex flex-col items-center text-center">
                    <div class="w-14 h-14 rounded-xl bg-emerald-50 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-500">Sin haberes configurados</p>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs">Aquí aparecerán los bonos fijos, asignaciones especiales y otros haberes pactados.</p>
                </div>
            </div>
        </div>

        {{-- ════ DESCUENTOS ════ --}}
        <div x-show="activeTab === 'descuentos'" x-cloak>
            <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-700">Descuentos Fijos</h3>
                    <span class="text-xs bg-blue-50 text-blue-600 border border-blue-100 px-2.5 py-1 rounded-full font-medium">Próximamente</span>
                </div>
                <div class="py-12 flex flex-col items-center text-center">
                    <div class="w-14 h-14 rounded-xl bg-red-50 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-500">Sin descuentos configurados</p>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs">Aquí aparecerán los descuentos fijos y retenciones pactadas con el colaborador.</p>
                </div>
            </div>
        </div>

        {{-- ════ CRÉDITOS ════ --}}
        <div x-show="activeTab === 'creditos'" x-cloak>
            <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-100">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-700">Créditos</h3>
                    <span class="text-xs bg-blue-50 text-blue-600 border border-blue-100 px-2.5 py-1 rounded-full font-medium">Próximamente</span>
                </div>
                <div class="py-12 flex flex-col items-center text-center">
                    <div class="w-14 h-14 rounded-xl bg-violet-50 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-500">Sin créditos registrados</p>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs">Aquí aparecerán los créditos sociales, préstamos de empresa y cuotas por descontar.</p>
                </div>
            </div>
        </div>

        {{-- ════ LIQUIDACIONES ════ --}}
        <div x-show="activeTab === 'liquidaciones'" x-cloak>
            <div class="bg-white rounded-lg shadow-sm border border-slate-100">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-700">Historial de Liquidaciones</h3>
                </div>
                {{-- Rows mockup --}}
                <div class="divide-y divide-slate-50">
                    @foreach([
                        ['Marzo 2025',    '$ 987.340', 'Pagada',   'bg-emerald-100 text-emerald-700'],
                        ['Febrero 2025',  '$ 987.340', 'Pagada',   'bg-emerald-100 text-emerald-700'],
                        ['Enero 2025',    '$ 982.100', 'Pagada',   'bg-emerald-100 text-emerald-700'],
                    ] as [$mes, $monto, $estado, $badge])
                    <div class="flex items-center gap-4 px-5 py-4 opacity-40">
                        <div class="w-8 h-8 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-slate-700">Liquidación {{ $mes }}</p>
                            <p class="text-xs text-slate-400">Líquido: {{ $monto }}</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $badge }}">{{ $estado }}</span>
                        <button class="text-slate-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                        </button>
                    </div>
                    @endforeach
                </div>
                <div class="px-5 py-5 text-center">
                    <p class="text-xs text-slate-400">Las liquidaciones reales estarán disponibles al procesar la primera nómina.</p>
                </div>
            </div>
        </div>

        {{-- ════ ASISTENCIA ════ --}}
        <div x-show="activeTab === 'asistencia'" x-cloak>
            <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-100">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-slate-700">Registro de Asistencia</h3>
                    <span class="text-xs bg-blue-50 text-blue-600 border border-blue-100 px-2.5 py-1 rounded-full font-medium">Próximamente</span>
                </div>
                <div class="grid grid-cols-4 gap-3 mb-6 opacity-40">
                    @foreach([['Días trabajados','22','text-emerald-600'],['Atrasos','0','text-amber-600'],['Ausencias','0','text-red-500'],['Horas extra','0','text-blue-600']] as [$l,$v,$c])
                    <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 text-center">
                        <p class="text-2xl font-black {{ $c }}">{{ $v }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $l }}</p>
                    </div>
                    @endforeach
                </div>
                <p class="text-center text-xs text-slate-400">El módulo de asistencia estará disponible próximamente.</p>
            </div>
        </div>

        {{-- ════ DOCUMENTOS ════ --}}
        <div x-show="activeTab === 'documentos'" x-cloak>
            <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-100">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-bold text-slate-700">Carpeta Digital</h3>
                    <button class="text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-100 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Subir documento
                    </button>
                </div>
                <div class="grid grid-cols-4 md:grid-cols-6 gap-4 opacity-40">
                    @foreach(['Contrato.pdf','Anexo 1.pdf','CI Frente.jpg','CI Reverso.jpg','AFP Cert.pdf'] as $doc)
                    <div class="flex flex-col items-center gap-2 p-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 cursor-not-allowed">
                        <svg class="w-9 h-9 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-[10px] text-slate-500 font-medium text-center leading-tight">{{ $doc }}</span>
                    </div>
                    @endforeach
                </div>
                <p class="text-center text-xs text-slate-400 mt-6">La gestión de documentos estará disponible próximamente.</p>
            </div>
        </div>

        {{-- ════ HISTORIAL ════ --}}
        <div x-show="activeTab === 'historial'" x-cloak>
            <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-100">
                <h3 class="text-sm font-bold text-slate-700 mb-5">Línea de Tiempo</h3>
                <div class="relative pl-10">
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-slate-100 rounded-full"></div>
                    <div class="space-y-0">
                        @php
                        $events = [
                            ['Hoy',                                                   'Perfil actualizado',       'Se actualizó la información del colaborador.',  'bg-blue-500',    'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                            [$emp?->hire_date?->format('d M Y') ?? '—',               'Ingreso a la empresa',     optional($emp?->company)->name ?? 'Empresa',     'bg-emerald-500', 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745'],
                            [$emp?->created_at?->format('d M Y') ?? '—',              'Ficha creada en Remsis',   'Registro inicial del colaborador.',             'bg-violet-500',  'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944'],
                        ];
                        @endphp
                        @foreach($events as [$date, $title, $desc, $color, $icon])
                        <div class="relative flex gap-4 pb-7">
                            <div class="absolute -left-6 top-0.5 w-8 h-8 rounded-full {{ $color }} flex items-center justify-center flex-shrink-0 z-10 ring-2 ring-white">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
                                    <span class="text-xs text-slate-400">{{ $date }}</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $desc }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /contenido --}}
</div>{{-- /x-data --}}
@endsection
</x-adminpanel::layouts.master>
