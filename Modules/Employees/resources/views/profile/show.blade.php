<x-adminpanel::layouts.master>
@section('title', 'Mi Ficha Personal')

@section('content')
@php
    $emp   = $employee;
    $name  = $emp?->full_name ?? $user->name;
    $completion = $emp?->completion_percentage ?? 0;

    // Contrato labels
    $contractLabels = [
        'indefinido'      => 'Indefinido',
        'plazo_fijo'      => 'Plazo Fijo',
        'obra_faena'      => 'Por Obra o Faena',
        'honorarios'      => 'Honorarios',
        'part_time'       => 'Part-time',
    ];
    $scheduleLabels = [
        'full_time'  => 'Jornada Completa',
        'part_time'  => 'Jornada Parcial',
    ];
    $healthLabels = [
        'fonasa'  => 'FONASA',
        'isapre'  => 'ISAPRE',
    ];
    $genderLabels = [
        'masculino' => 'Masculino',
        'femenino'  => 'Femenino',
        'otro'      => 'Otro',
    ];
    $paymentLabels = [
        'transferencia' => 'Transferencia Bancaria',
        'cheque'        => 'Cheque',
        'efectivo'      => 'Efectivo',
    ];

    // Días en la empresa
    $diasEmpresa = $emp?->hire_date ? $emp->hire_date->diffInDays(now()) : null;
    $aniosEmpresa = $emp?->hire_date ? $emp->hire_date->diffInYears(now()) : null;
@endphp

<div x-data="{ activeTab: 'resumen' }" class="min-h-screen bg-slate-50">

    {{-- ══════════════ HEADER DEL COLABORADOR ══════════════ --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex items-center gap-5">

                {{-- Avatar --}}
                <div class="relative flex-shrink-0">
                    @if($user->profile_photo)
                        <img src="{{ Storage::url($user->profile_photo) }}" alt="{{ $name }}"
                            class="w-20 h-20 rounded-2xl object-cover ring-2 ring-slate-100">
                    @else
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-slate-700 to-slate-900 flex items-center justify-center ring-2 ring-slate-100">
                            <span class="text-2xl font-bold text-white">{{ strtoupper(substr($emp?->first_name ?? $user->name, 0, 1)) }}{{ strtoupper(substr($emp?->last_name ?? '', 0, 1)) }}</span>
                        </div>
                    @endif
                    {{-- Status dot --}}
                    <span class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white {{ ($emp?->status ?? 'active') === 'active' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                </div>

                {{-- Info principal --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ $name }}</h1>
                        @if($emp?->rut)
                            <span class="text-xs font-mono bg-slate-100 text-slate-500 px-2 py-0.5 rounded-md">{{ $emp->rut }}</span>
                        @endif
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ ($emp?->status ?? 'active') === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ ($emp?->status ?? 'active') === 'active' ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 mt-1.5 flex-wrap">
                        <span class="text-sm text-slate-500">{{ $emp?->position ?? 'Sin cargo asignado' }}</span>
                        @if($emp?->company)
                            <span class="text-slate-300">·</span>
                            <span class="text-sm text-slate-500">{{ $emp->company->name }}</span>
                        @endif
                        @if($emp?->hire_date)
                            <span class="text-slate-300">·</span>
                            <span class="text-sm text-slate-400">Ingresó {{ $emp->hire_date->format('d M Y') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Completitud del perfil --}}
                <div class="hidden sm:block text-right flex-shrink-0">
                    <div class="text-xs text-slate-400 mb-1 font-medium">Completitud del perfil</div>
                    <div class="flex items-center gap-2">
                        <div class="w-32 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500
                                {{ $completion >= 80 ? 'bg-emerald-500' : ($completion >= 50 ? 'bg-amber-400' : 'bg-red-400') }}"
                                style="width: {{ $completion }}%"></div>
                        </div>
                        <span class="text-sm font-bold {{ $completion >= 80 ? 'text-emerald-600' : ($completion >= 50 ? 'text-amber-600' : 'text-red-500') }}">{{ $completion }}%</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ══════════════ LAYOUT PRINCIPAL ══════════════ --}}
    <div class="max-w-7xl mx-auto px-6 py-6 flex gap-6 items-start">

        {{-- ─── SIDEBAR NAV ─────────────────────────────────────────── --}}
        <nav class="w-52 flex-shrink-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden sticky top-6">
            @php
            $navItems = [
                ['id' => 'resumen',    'label' => 'Resumen',            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',   'locked' => false],
                ['id' => 'personal',   'label' => 'Personal',           'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',   'locked' => false],
                ['id' => 'trabajo',    'label' => 'Trabajo',            'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'locked' => false],
                ['id' => 'haberes',    'label' => 'Haberes',            'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'locked' => false],
                ['id' => 'liquidaciones', 'label' => 'Liquidaciones',   'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'locked' => false],
                ['id' => 'documentos', 'label' => 'Documentos',         'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',  'locked' => false],
                ['id' => 'asistencia', 'label' => 'Asistencia',         'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'locked' => false],
                ['id' => 'vacaciones', 'label' => 'Vacaciones',         'icon' => 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z', 'locked' => false],
                ['id' => 'beneficios', 'label' => 'Beneficios',         'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', 'locked' => true],
                ['id' => 'talento',    'label' => 'Talento',            'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'locked' => true],
                ['id' => 'actividad',  'label' => 'Actividad',          'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'locked' => false],
            ];
            @endphp

            <div class="py-2">
                @foreach($navItems as $item)
                <button
                    @if(!$item['locked'])
                        @click="activeTab = '{{ $item['id'] }}'"
                    @endif
                    class="w-full flex items-center gap-3 px-4 py-2.5 text-sm font-medium transition-all group relative
                        {{ $item['locked'] ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:bg-slate-50' }}"
                    :class="{{ $item['locked'] ? '{}' : "activeTab === '{$item['id']}' ? 'text-blue-600 bg-blue-50' : 'text-slate-600'" }}"
                >
                    {{-- Active indicator bar --}}
                    @if(!$item['locked'])
                    <span class="absolute left-0 top-1 bottom-1 w-0.5 rounded-r-full bg-blue-500 transition-all"
                        :class="activeTab === '{{ $item['id'] }}' ? 'opacity-100' : 'opacity-0'"></span>
                    @endif

                    <svg class="w-4 h-4 flex-shrink-0 transition-colors
                        {{ $item['locked'] ? 'text-slate-400' : '' }}"
                        :class="{{ $item['locked'] ? "'text-slate-400'" : "activeTab === '{$item['id']}' ? 'text-blue-500' : 'text-slate-400 group-hover:text-slate-600'" }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon'] }}"/>
                    </svg>

                    <span class="flex-1 text-left">{{ $item['label'] }}</span>

                    @if($item['locked'])
                    <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    @endif
                </button>
                @endforeach
            </div>
        </nav>

        {{-- ─── CONTENIDO PRINCIPAL ─────────────────────────────────── --}}
        <div class="flex-1 min-w-0">

            {{-- ════ TAB: RESUMEN ════════════════════════════════════════ --}}
            <div x-show="activeTab === 'resumen'" x-cloak>
                {{-- Cards de métricas --}}
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    {{-- Cargo --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Cargo</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5 leading-tight">{{ $emp?->position ?? '—' }}</p>
                    </div>

                    {{-- Último sueldo líquido (mockup) --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Sueldo Base</p>
                        @if($emp?->salary)
                            <p class="text-sm font-bold text-emerald-600 mt-0.5">$ {{ number_format($emp->salary, 0, ',', '.') }}</p>
                        @else
                            <p class="text-sm font-medium text-slate-300 mt-0.5">Sin asignar</p>
                        @endif
                    </div>

                    {{-- Días en empresa --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="w-9 h-9 rounded-lg bg-violet-50 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Antigüedad</p>
                        @if($diasEmpresa !== null)
                            <p class="text-sm font-bold text-violet-600 mt-0.5">{{ $aniosEmpresa > 0 ? $aniosEmpresa.' año'.($aniosEmpresa>1?'s':'') : $diasEmpresa.' días' }}</p>
                        @else
                            <p class="text-sm font-medium text-slate-300 mt-0.5">—</p>
                        @endif
                    </div>

                    {{-- Vacaciones disponibles (mockup) --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center mb-3">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">Vacaciones</p>
                        <p class="text-sm font-medium text-slate-300 mt-0.5 italic">Próximamente</p>
                    </div>
                </div>

                {{-- Resumen de datos --}}
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    {{-- Datos previsionales --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 lg:col-span-2">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-4">Resumen Previsional</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center p-3 rounded-lg bg-slate-50 border border-slate-100">
                                <p class="text-[10px] text-slate-400 uppercase font-semibold mb-1">AFP</p>
                                <p class="text-sm font-bold text-slate-700">{{ optional($emp?->afp)->nombre ?? '—' }}</p>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-slate-50 border border-slate-100">
                                <p class="text-[10px] text-slate-400 uppercase font-semibold mb-1">Salud</p>
                                <p class="text-sm font-bold text-slate-700">{{ $healthLabels[$emp?->health_system ?? ''] ?? '—' }}</p>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-slate-50 border border-slate-100">
                                <p class="text-[10px] text-slate-400 uppercase font-semibold mb-1">CCAF</p>
                                <p class="text-sm font-bold text-slate-700">{{ optional($emp?->ccaf)->nombre ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Datos contrato --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-4">Contrato</h3>
                        <dl class="space-y-2.5">
                            <div>
                                <dt class="text-[10px] text-slate-400 uppercase font-semibold">Tipo</dt>
                                <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ $contractLabels[$emp?->contract_type ?? ''] ?? ($emp?->contract_type ?? '—') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] text-slate-400 uppercase font-semibold">Jornada</dt>
                                <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ $scheduleLabels[$emp?->work_schedule_type ?? ''] ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] text-slate-400 uppercase font-semibold">Ingreso</dt>
                                <dd class="text-sm font-medium text-slate-700 mt-0.5">{{ $emp?->hire_date?->format('d M Y') ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                </div>
            </div>

            {{-- ════ TAB: PERSONAL ══════════════════════════════════════ --}}
            <div x-show="activeTab === 'personal'" x-cloak>
                <div class="space-y-5">

                    {{-- Datos Maestros --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Datos Personales</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-5">
                            @php
                            $personalFields = [
                                ['label' => 'Nombre completo',    'value' => $emp?->full_name],
                                ['label' => 'RUT',                'value' => $emp?->rut,          'mono' => true],
                                ['label' => 'Correo electrónico', 'value' => $emp?->email ?? $user->email],
                                ['label' => 'Teléfono',           'value' => $emp?->phone],
                                ['label' => 'Fecha de nacimiento','value' => $emp?->birth_date?->format('d \d\e F \d\e Y')],
                                ['label' => 'Género',             'value' => $genderLabels[$emp?->gender ?? ''] ?? null],
                                ['label' => 'Nacionalidad',       'value' => $emp?->nationality],
                                ['label' => 'Estado civil',       'value' => match($emp?->marital_status) { 'single'=>'Soltero/a','married'=>'Casado/a','divorced'=>'Divorciado/a','widowed'=>'Viudo/a', default=>null }],
                                ['label' => 'Dirección',          'value' => $emp?->address, 'wide' => true],
                            ];
                            @endphp
                            @foreach($personalFields as $field)
                            <div class="{{ ($field['wide'] ?? false) ? 'col-span-2 md:col-span-3' : '' }}">
                                <dt class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">{{ $field['label'] }}</dt>
                                <dd class="mt-0.5 text-sm font-medium text-slate-800 {{ ($field['mono'] ?? false) ? 'font-mono' : '' }}">
                                    {{ $field['value'] ?? '—' }}
                                </dd>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Datos Previsionales --}}
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Previsión y Salud</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-5">

                            {{-- AFP --}}
                            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-6 h-6 rounded-md bg-blue-500 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold text-blue-700 uppercase">AFP</span>
                                </div>
                                <p class="text-base font-bold text-blue-900">{{ optional($emp?->afp)->nombre ?? '—' }}</p>
                                <p class="text-xs text-blue-500 mt-0.5">Fondo de Pensiones</p>
                            </div>

                            {{-- Salud --}}
                            <div class="p-4 rounded-xl {{ $emp?->health_system === 'isapre' ? 'bg-purple-50 border border-purple-100' : 'bg-teal-50 border border-teal-100' }}">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-6 h-6 rounded-md {{ $emp?->health_system === 'isapre' ? 'bg-purple-500' : 'bg-teal-500' }} flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold {{ $emp?->health_system === 'isapre' ? 'text-purple-700' : 'text-teal-700' }} uppercase">Salud</span>
                                </div>
                                <p class="text-base font-bold {{ $emp?->health_system === 'isapre' ? 'text-purple-900' : 'text-teal-900' }}">
                                    @if($emp?->health_system === 'isapre')
                                        {{ optional($emp->isapre)->nombre ?? 'ISAPRE' }}
                                    @else
                                        {{ $healthLabels[$emp?->health_system ?? ''] ?? '—' }}
                                    @endif
                                </p>
                                @if($emp?->health_system === 'isapre' && $emp?->health_contribution)
                                    <p class="text-xs {{ $emp->health_system === 'isapre' ? 'text-purple-500' : 'text-teal-500' }} mt-0.5">Plan: {{ $emp->health_contribution }} UF</p>
                                @else
                                    <p class="text-xs {{ $emp?->health_system === 'isapre' ? 'text-purple-400' : 'text-teal-400' }} mt-0.5">Sistema de salud</p>
                                @endif
                            </div>

                            {{-- CCAF --}}
                            <div class="p-4 rounded-xl bg-amber-50 border border-amber-100">
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="w-6 h-6 rounded-md bg-amber-500 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <span class="text-xs font-bold text-amber-700 uppercase">CCAF</span>
                                </div>
                                <p class="text-base font-bold text-amber-900">{{ optional($emp?->ccaf)->nombre ?? '—' }}</p>
                                <p class="text-xs text-amber-500 mt-0.5">Caja de Compensación</p>
                            </div>

                            {{-- APV --}}
                            @if($emp?->apv_amount)
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">APV Mensual</p>
                                <p class="text-lg font-bold text-slate-800">$ {{ number_format($emp->apv_amount, 0, ',', '.') }}</p>
                                <p class="text-xs text-slate-400">Ahorro Previsional Voluntario</p>
                            </div>
                            @endif

                        </div>
                    </div>

                    {{-- Datos bancarios --}}
                    @if($emp?->bank_id || $emp?->payment_method)
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-4">Método de Pago</h3>
                        <div class="flex items-center gap-5 flex-wrap">
                            <div>
                                <dt class="text-[10px] font-semibold text-slate-400 uppercase">Medio</dt>
                                <dd class="text-sm font-semibold text-slate-800 mt-0.5">{{ $paymentLabels[$emp?->payment_method ?? ''] ?? '—' }}</dd>
                            </div>
                            @if($emp?->payment_method === 'transferencia')
                            <div>
                                <dt class="text-[10px] font-semibold text-slate-400 uppercase">Banco</dt>
                                <dd class="text-sm font-semibold text-slate-800 mt-0.5">{{ optional($emp->bank)->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-semibold text-slate-400 uppercase">Tipo cuenta</dt>
                                <dd class="text-sm font-semibold text-slate-800 mt-0.5 capitalize">{{ $emp?->bank_account_type ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-semibold text-slate-400 uppercase">N° cuenta</dt>
                                <dd class="text-sm font-mono font-semibold text-slate-800 mt-0.5">{{ $emp?->bank_account_number ?? '—' }}</dd>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                </div>
            </div>

            {{-- ════ TAB: TRABAJO ═══════════════════════════════════════ --}}
            <div x-show="activeTab === 'trabajo'" x-cloak>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Información Contractual</h3>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-6">
                        @php
                        $workFields = [
                            ['label' => 'Cargo / Puesto',       'value' => $emp?->position],
                            ['label' => 'Tipo de contrato',     'value' => $contractLabels[$emp?->contract_type ?? ''] ?? ($emp?->contract_type)],
                            ['label' => 'Jornada',              'value' => $scheduleLabels[$emp?->work_schedule_type ?? ''] ?? null],
                            ['label' => 'Fecha de ingreso',     'value' => $emp?->hire_date?->format('d \d\e F \d\e Y')],
                            ['label' => 'Centro de costo',      'value' => optional($emp?->costCenter)->code ? optional($emp->costCenter)->code.' — '.optional($emp->costCenter)->name : null],
                            ['label' => 'Empresa',              'value' => optional($emp?->company)->name],
                        ];
                        @endphp
                        @foreach($workFields as $field)
                        <div>
                            <dt class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">{{ $field['label'] }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-slate-800">{{ $field['value'] ?? '—' }}</dd>
                        </div>
                        @endforeach
                    </div>

                    {{-- Remuneraciones --}}
                    <div class="border-t border-slate-100 pt-6">
                        <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-4">Remuneración Pactada</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                                <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-wide">Sueldo Base</p>
                                <p class="text-xl font-bold text-emerald-800 mt-1">
                                    @if($emp?->salary) $&nbsp;{{ number_format($emp->salary, 0, ',', '.') }} @else <span class="text-slate-300 text-base">—</span> @endif
                                </p>
                                <p class="text-xs text-emerald-400 mt-0.5 capitalize">{{ $emp?->salary_type ?? 'mensual' }}</p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Movilización</p>
                                <p class="text-xl font-bold text-slate-700 mt-1">
                                    @if($emp?->mobility_allowance) $&nbsp;{{ number_format($emp->mobility_allowance, 0, ',', '.') }} @else <span class="text-slate-300 text-base">—</span> @endif
                                </p>
                            </div>
                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Colación</p>
                                <p class="text-xl font-bold text-slate-700 mt-1">
                                    @if($emp?->meal_allowance) $&nbsp;{{ number_format($emp->meal_allowance, 0, ',', '.') }} @else <span class="text-slate-300 text-base">—</span> @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════ TAB: HABERES Y DESCUENTOS ══════════════════════════ --}}
            <div x-show="activeTab === 'haberes'" x-cloak>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Haberes y Descuentos Fijos</h3>
                        <span class="text-xs bg-blue-50 text-blue-500 border border-blue-100 px-2.5 py-1 rounded-full font-medium">Próximamente</span>
                    </div>
                    <div class="p-12 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-500">Sin haberes ni descuentos configurados</p>
                        <p class="text-xs text-slate-400 mt-1 max-w-xs">Aquí aparecerán los bonos fijos, asignaciones especiales y descuentos pactados asociados a este colaborador.</p>
                    </div>
                </div>
            </div>

            {{-- ════ TAB: LIQUIDACIONES ═════════════════════════════════ --}}
            <div x-show="activeTab === 'liquidaciones'" x-cloak>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
                    <div class="p-6 border-b border-slate-100">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Historial de Liquidaciones</h3>
                    </div>
                    {{-- Mockup table --}}
                    <div class="divide-y divide-slate-100">
                        @foreach([['Marzo 2025','$ 987.340','Pagada'],['Febrero 2025','$ 987.340','Pagada'],['Enero 2025','$ 982.100','Pagada']] as [$mes,$monto,$estado])
                        <div class="flex items-center gap-4 px-6 py-4 opacity-30">
                            <div class="w-9 h-9 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4.5 h-4.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-700">Liquidación {{ $mes }}</p>
                                <p class="text-xs text-slate-400">Líquido a pago: {{ $monto }}</p>
                            </div>
                            <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ $estado }}</span>
                            <button class="text-slate-300 hover:text-slate-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </button>
                        </div>
                        @endforeach
                    </div>
                    <div class="p-8 flex flex-col items-center">
                        <p class="text-xs text-slate-400 font-medium">Las liquidaciones reales estarán disponibles cuando se procese la primera nómina.</p>
                    </div>
                </div>
            </div>

            {{-- ════ TAB: DOCUMENTOS ════════════════════════════════════ --}}
            <div x-show="activeTab === 'documentos'" x-cloak>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Carpeta Digital</h3>
                        <span class="text-xs bg-blue-50 text-blue-500 border border-blue-100 px-2.5 py-1 rounded-full font-medium">Próximamente</span>
                    </div>
                    <div class="grid grid-cols-3 md:grid-cols-5 gap-4">
                        @foreach(['Contrato.pdf','Anexo 1.pdf','CI Frente.jpg','CI Reverso.jpg','AFP Cert.pdf'] as $doc)
                        <div class="flex flex-col items-center gap-2 p-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 opacity-30 cursor-not-allowed">
                            <svg class="w-10 h-10 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs text-slate-500 font-medium text-center leading-tight">{{ $doc }}</span>
                        </div>
                        @endforeach
                        <div class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-slate-200 hover:border-blue-300 hover:bg-blue-50 transition-colors cursor-pointer">
                            <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <span class="text-xs text-slate-400 font-medium">Subir doc.</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ════ TAB: ASISTENCIA ════════════════════════════════════ --}}
            <div x-show="activeTab === 'asistencia'" x-cloak>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Registro de Asistencia</h3>
                        <span class="text-xs bg-blue-50 text-blue-500 border border-blue-100 px-2.5 py-1 rounded-full font-medium">Próximamente</span>
                    </div>
                    <div class="grid grid-cols-4 gap-3 mb-6">
                        @foreach([['Días trabajados','22','text-emerald-600','bg-emerald-50 border-emerald-100'],['Atrasos','0','text-amber-600','bg-amber-50 border-amber-100'],['Ausencias','0','text-red-500','bg-red-50 border-red-100'],['Horas extra','0','text-blue-600','bg-blue-50 border-blue-100']] as [$l,$v,$tc,$bg])
                        <div class="p-4 rounded-xl border {{ $bg }} opacity-40 text-center">
                            <p class="text-2xl font-bold {{ $tc }}">{{ $v }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $l }}</p>
                        </div>
                        @endforeach
                    </div>
                    <p class="text-center text-xs text-slate-400">El módulo de asistencia estará disponible próximamente.</p>
                </div>
            </div>

            {{-- ════ TAB: VACACIONES ════════════════════════════════════ --}}
            <div x-show="activeTab === 'vacaciones'" x-cloak>
                <div class="space-y-5">
                    <div class="grid grid-cols-3 gap-4">
                        @foreach([['Días ganados','15','bg-emerald-50 border-emerald-100','text-emerald-700'],['Días usados','0','bg-slate-50 border-slate-200','text-slate-600'],['Saldo disponible','15','bg-blue-50 border-blue-100','text-blue-700']] as [$l,$v,$bg,$tc])
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 opacity-40">
                            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">{{ $l }}</p>
                            <p class="text-4xl font-black {{ $tc }} mt-2">{{ $v }}</p>
                            <p class="text-xs text-slate-400 mt-1">días hábiles</p>
                        </div>
                        @endforeach
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 flex flex-col items-center text-center">
                        <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center mb-4">
                            <svg class="w-7 h-7 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-600">Módulo de Vacaciones</p>
                        <p class="text-xs text-slate-400 mt-1 max-w-xs">La gestión de solicitudes de vacaciones estará disponible próximamente. Los datos mostrados son referenciales.</p>
                    </div>
                </div>
            </div>

            {{-- ════ TAB: BENEFICIOS (locked) ═══════════════════════════ --}}
            <div x-show="activeTab === 'beneficios'" x-cloak>
                @include('employees::profile._locked_tab', ['icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', 'title' => 'Beneficios', 'desc' => 'Gestión de beneficios corporativos, seguros complementarios y convenios.'])
            </div>

            {{-- ════ TAB: TALENTO (locked) ══════════════════════════════ --}}
            <div x-show="activeTab === 'talento'" x-cloak>
                @include('employees::profile._locked_tab', ['icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'title' => 'Talento', 'desc' => 'Evaluaciones de desempeño, objetivos y planes de desarrollo profesional.'])
            </div>

            {{-- ════ TAB: ACTIVIDAD ════════════════════════════════════ --}}
            <div x-show="activeTab === 'actividad'" x-cloak>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-6">Línea de Tiempo</h3>
                    <div class="relative">
                        {{-- Vertical line --}}
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-slate-100"></div>
                        <div class="space-y-0">
                            @php
                            $events = [
                                ['date' => 'Hoy', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'color' => 'bg-blue-500', 'title' => 'Perfil actualizado', 'desc' => 'Se actualizó la información del colaborador.'],
                                ['date' => $emp?->hire_date?->format('d M Y') ?? '—', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01', 'color' => 'bg-emerald-500', 'title' => 'Ingreso a la empresa', 'desc' => optional($emp?->company)->name ?? 'Empresa'],
                                ['date' => 'Sistema', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'color' => 'bg-violet-500', 'title' => 'Ficha creada en Remsis', 'desc' => 'Registro inicial del colaborador.'],
                            ];
                            @endphp
                            @foreach($events as $event)
                            <div class="relative flex gap-4 pb-6">
                                <div class="w-8 h-8 rounded-full {{ $event['color'] }} flex items-center justify-center flex-shrink-0 z-10 ring-2 ring-white">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $event['icon'] }}"/>
                                    </svg>
                                </div>
                                <div class="flex-1 pt-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-800">{{ $event['title'] }}</p>
                                        <span class="text-xs text-slate-400">{{ $event['date'] }}</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $event['desc'] }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /contenido --}}
    </div>{{-- /layout --}}
</div>{{-- /x-data --}}
@endsection
</x-adminpanel::layouts.master>
