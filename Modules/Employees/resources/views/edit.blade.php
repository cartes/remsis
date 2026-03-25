<x-layouts.company :company="$employee->company" activeTab="employees">

<div
    x-data="{
        tab: '{{ session('active_tab', 'personales') }}',
        healthSystem: '{{ old('health_system', $employee->health_system ?? '') }}',
        nationality: '{{ old('nationality', $employee->nationality ?? 'Chile') }}',
        searchNationality: '',
        nationalityDropdownOpen: false,
        countries: [
            'Chile',
            'Afganistán','Alemania','Angola','Argentina','Australia','Austria',
            'Bangladés','Bélgica','Bolivia','Brasil','Bulgaria',
            'Canadá','Colombia','Corea del Sur','Costa Rica','Cuba',
            'Dinamarca','República Dominicana',
            'Ecuador','Egipto','Emiratos Árabes','Eslovaquia','España','Estados Unidos',
            'Finlandia','Francia',
            'Grecia','Guatemala',
            'Haití','Países Bajos','Honduras','Hungría',
            'India','Indonesia','Irán','Irak','Irlanda','Israel','Italia',
            'Jamaica','Japón','Jordania',
            'Kenia','Kuwait',
            'Líbano','Libia',
            'Marruecos','México',
            'Nueva Zelanda','Nicaragua','Nigeria','Noruega',
            'Pakistán','Panamá','Paraguay','Perú','Polonia','Portugal',
            'Rumania','Rusia',
            'El Salvador','Siria','Sudáfrica','Suecia','Suiza',
            'Tailandia','Taiwán','Túnez','Turquía',
            'Ucrania','Uruguay',
            'Venezuela','Vietnam',
            'Otro',
        ],
        get filteredNationalities() {
            if (!this.searchNationality) return this.countries;
            const search = this.searchNationality.toLowerCase();
            return this.countries.filter(c => c.toLowerCase().includes(search));
        }
    }"
    x-init="
        const hash = window.location.hash.replace('#', '');
        if (['personales','previsional','laboral'].includes(hash)) tab = hash;
    "
>

{{-- ─────────────────── CABECERA DE LA FICHA ─────────────────── --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div class="flex items-center gap-4">
        {{-- Avatar --}}
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-blue-500/25 flex-shrink-0">
            {{ strtoupper(substr($employee->first_name ?? '?', 0, 1)) }}
        </div>
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-none">
                {{ $employee->full_name ?: 'Colaborador sin nombre' }}
            </h1>
            <p class="text-sm text-slate-500 mt-1 font-medium">
                {{ $employee->position ?? 'Sin cargo asignado' }}
                @if($employee->company)
                    &nbsp;·&nbsp; {{ $employee->company->razon_social ?? $employee->company->name }}
                @endif
            </p>
        </div>
    </div>

    {{-- Barra de completitud --}}
    <div class="flex-shrink-0 w-full sm:w-48">
        <div class="flex justify-between items-center mb-1.5">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Ficha completada</span>
            <span class="text-[11px] font-black text-slate-700">{{ $employee->completion_percentage }}%</span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
            <div class="h-2 rounded-full transition-all duration-500
                {{ $employee->completion_percentage >= 80 ? 'bg-emerald-500' : ($employee->completion_percentage >= 50 ? 'bg-amber-400' : 'bg-rose-400') }}"
                style="width: {{ $employee->completion_percentage }}%">
            </div>
        </div>
    </div>
</div>

{{-- ─────────────────── MENSAJES FLASH ─────────────────── --}}
@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
    x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 mb-6 text-sm font-semibold shadow-sm">
    <i class="fas fa-check-circle text-emerald-500"></i>
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 mb-6 text-sm shadow-sm">
    <i class="fas fa-circle-exclamation text-red-500 mt-0.5"></i>
    <ul class="font-semibold space-y-0.5">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- ─────────────────── NAVEGACIÓN DE PESTAÑAS ─────────────────── --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

    {{-- Tab bar --}}
    <div class="flex border-b border-slate-100 bg-slate-50/50">
        @foreach([
            ['key' => 'personales',  'label' => 'Datos Personales',   'icon' => 'fa-user'],
            ['key' => 'previsional', 'label' => 'Info. Previsional',   'icon' => 'fa-shield-halved'],
            ['key' => 'laboral',     'label' => 'Datos Laborales',     'icon' => 'fa-briefcase'],
        ] as $t)
        <button type="button"
            @click="tab = '{{ $t['key'] }}'; window.location.hash = '{{ $t['key'] }}'"
            class="flex-1 flex items-center justify-center gap-2 px-4 py-4 text-sm font-bold transition-all border-b-2"
            :class="tab === '{{ $t['key'] }}'
                ? 'border-blue-600 text-blue-700 bg-white'
                : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-white'">
            <i class="fas {{ $t['icon'] }} text-[13px]"
                :class="tab === '{{ $t['key'] }}' ? 'text-blue-600' : 'text-slate-400'"></i>
            <span class="hidden sm:inline">{{ $t['label'] }}</span>
        </button>
        @endforeach
    </div>

    {{-- ════════════════════════════════════════
         PESTAÑA 1 — DATOS PERSONALES
    ═════════════════════════════════════════ --}}
    <div x-show="tab === 'personales'" x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        <form method="POST" action="{{ route('employees.update', $employee) }}" class="p-6 sm:p-8">
            @csrf
            @method('PUT')
            <input type="hidden" name="section" value="personales">

            <h2 class="text-base font-black text-slate-800 mb-6 flex items-center gap-2">
                <i class="fas fa-user text-blue-500 text-sm"></i> Datos Personales
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                {{-- RUT --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        RUT <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="rut"
                        value="{{ old('rut', $employee->rut) }}"
                        placeholder="12345678-9"
                        @input="$el.value = window.formatRut ? window.formatRut($el.value) : $el.value"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-mono font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('rut') border-red-400 bg-red-50 @enderror">
                    @error('rut')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Correo Electrónico
                    </label>
                    <input type="email" name="email"
                        value="{{ old('email', $employee->email) }}"
                        placeholder="colaborador@empresa.cl"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('email') border-red-400 bg-red-50 @enderror">
                    @error('email')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Nombres --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Nombres <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="first_name"
                        value="{{ old('first_name', $employee->first_name) }}"
                        placeholder="Juan"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('first_name') border-red-400 bg-red-50 @enderror">
                    @error('first_name')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Apellidos --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Apellidos <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="last_name"
                        value="{{ old('last_name', $employee->last_name) }}"
                        placeholder="Pérez González"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('last_name') border-red-400 bg-red-50 @enderror">
                    @error('last_name')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Fecha de Nacimiento --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Fecha de Nacimiento
                    </label>
                    <input type="date" name="birth_date"
                        value="{{ old('birth_date', optional($employee->birth_date)->format('Y-m-d')) }}"
                        max="{{ now()->subYear()->format('Y-m-d') }}"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('birth_date') border-red-400 bg-red-50 @enderror">
                    @error('birth_date')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Género --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Género
                    </label>
                    <select name="gender"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('gender') border-red-400 bg-red-50 @enderror">
                        <option value="">— Seleccionar —</option>
                        <option value="male"   {{ old('gender', $employee->gender) === 'male'   ? 'selected' : '' }}>Masculino</option>
                        <option value="female" {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>Femenino</option>
                        <option value="other"  {{ old('gender', $employee->gender) === 'other'  ? 'selected' : '' }}>Otro / Prefiero no indicar</option>
                    </select>
                    @error('gender')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Nacionalidad (Buscador) --}}
                <div class="sm:col-span-2 relative" @click.away="nationalityDropdownOpen = false">
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Nacionalidad
                    </label>
                    <input type="hidden" name="nationality" :value="nationality">
                    
                    <div @click="nationalityDropdownOpen = !nationalityDropdownOpen"
                        class="w-full sm:w-1/2 px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all cursor-pointer flex items-center justify-between"
                        :class="nationalityDropdownOpen ? 'ring-2 ring-blue-500/20 border-blue-400' : ''">
                        <span x-text="nationality || 'Seleccionar nacionalidad'" :class="!nationality ? 'text-slate-300' : ''"></span>
                        <svg class="w-4 h-4 text-slate-400 transition-transform" :class="nationalityDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    {{-- Dropdown --}}
                    <div x-show="nationalityDropdownOpen" x-cloak
                        class="absolute z-50 mt-1 w-full sm:w-1/2 bg-white rounded-xl shadow-2xl border border-slate-100 overflow-hidden"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100">
                        
                        {{-- Search input inside dropdown --}}
                        <div class="p-2 border-b border-slate-50">
                            <input type="text" x-model="searchNationality" placeholder="Buscar país..."
                                @click.stop
                                class="w-full px-3 py-2 text-xs border border-slate-100 rounded-lg outline-none focus:bg-slate-50 transition-colors">
                        </div>

                        <div class="max-h-48 overflow-y-auto pt-1 pb-1 scrollbar-thin">
                            <template x-for="country in filteredNationalities" :key="country">
                                <div @click="nationality = country; nationalityDropdownOpen = false; searchNationality = ''"
                                    class="px-4 py-2 text-xs text-slate-600 hover:bg-slate-50 hover:text-slate-900 cursor-pointer transition-colors flex items-center justify-between"
                                    :class="nationality === country ? 'bg-blue-50 text-blue-700 font-semibold' : ''">
                                    <span x-text="country"></span>
                                    <template x-if="nationality === country">
                                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </template>
                                </div>
                            </template>
                            <div x-show="filteredNationalities.length === 0" class="px-4 py-3 text-xs text-slate-400 italic text-center">
                                No se encontraron resultados
                            </div>
                        </div>
                    </div>
                    @error('nationality')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

            </div>{{-- /grid --}}

            <div class="mt-8 flex justify-end">
                <button type="submit"
                    class="flex items-center gap-2 px-6 py-2.5 bg-slate-900 text-white text-sm font-black rounded-xl hover:bg-slate-800 active:scale-95 transition-all shadow-sm">
                    <i class="fas fa-floppy-disk text-xs"></i>
                    Guardar datos personales
                </button>
            </div>
        </form>
    </div>

    {{-- ════════════════════════════════════════
         PESTAÑA 2 — INFORMACIÓN PREVISIONAL
    ═════════════════════════════════════════ --}}
    <div x-show="tab === 'previsional'" x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        <form method="POST" action="{{ route('employees.update', $employee) }}" class="p-6 sm:p-8">
            @csrf
            @method('PUT')
            <input type="hidden" name="section" value="previsional">

            <h2 class="text-base font-black text-slate-800 mb-6 flex items-center gap-2">
                <i class="fas fa-shield-halved text-blue-500 text-sm"></i> Información Previsional
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                {{-- Sistema de Salud --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-2">
                        Institución de Salud
                    </label>
                    <div class="flex gap-3">
                        @foreach(['fonasa' => 'FONASA', 'isapre' => 'ISAPRE'] as $val => $lbl)
                        <label class="flex-1 flex items-center gap-3 px-4 py-3 rounded-xl border-2 cursor-pointer transition-all"
                            :class="healthSystem === '{{ $val }}'
                                ? 'border-blue-500 bg-blue-50 text-blue-700'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'">
                            <input type="radio" name="health_system" value="{{ $val }}"
                                x-model="healthSystem"
                                {{ old('health_system', $employee->health_system) === $val ? 'checked' : '' }}
                                class="sr-only">
                            <span class="w-4 h-4 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition-all"
                                :class="healthSystem === '{{ $val }}' ? 'border-blue-500 bg-blue-500' : 'border-slate-300'">
                                <span class="w-1.5 h-1.5 rounded-full bg-white"
                                    x-show="healthSystem === '{{ $val }}'"></span>
                            </span>
                            <span class="font-black text-sm">{{ $lbl }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('health_system')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Isapre + Valor plan (solo si isapre) --}}
                <div x-show="healthSystem === 'isapre'" x-transition>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Isapre
                    </label>
                    <select name="isapre_id"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('isapre_id') border-red-400 bg-red-50 @enderror">
                        <option value="">— Seleccionar —</option>
                        @foreach($isapres as $isapre)
                            <option value="{{ $isapre->id }}"
                                {{ old('isapre_id', $employee->isapre_id) == $isapre->id ? 'selected' : '' }}>
                                {{ $isapre->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('isapre_id')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                <div x-show="healthSystem === 'isapre'" x-transition>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Valor Plan de Salud <span class="text-slate-400 font-medium normal-case">(CLP)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">$</span>
                        <input type="number" name="health_contribution"
                            value="{{ old('health_contribution', $employee->health_contribution) }}"
                            placeholder="0"
                            min="0"
                            class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('health_contribution') border-red-400 bg-red-50 @enderror">
                    </div>
                    @error('health_contribution')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- AFP --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        AFP
                    </label>
                    <select name="afp_id"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('afp_id') border-red-400 bg-red-50 @enderror">
                        <option value="">— Seleccionar —</option>
                        @foreach($afps as $afp)
                            <option value="{{ $afp->id }}"
                                {{ old('afp_id', $employee->afp_id) == $afp->id ? 'selected' : '' }}>
                                {{ $afp->nombre }}
                                @if($afp->rate) ({{ $afp->rate }}%) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('afp_id')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Tipo de Contrato / Seguro Cesantía --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Seguro de Cesantía
                        <span class="text-slate-400 font-medium normal-case">(tipo de contrato)</span>
                    </label>
                    <select name="contract_type"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('contract_type') border-red-400 bg-red-50 @enderror">
                        <option value="">— Seleccionar —</option>
                        <option value="indefinido"  {{ old('contract_type', $employee->contract_type) === 'indefinido'  ? 'selected' : '' }}>Indefinido</option>
                        <option value="plazo_fijo"  {{ old('contract_type', $employee->contract_type) === 'plazo_fijo'  ? 'selected' : '' }}>Plazo Fijo</option>
                        <option value="obra_faena"  {{ old('contract_type', $employee->contract_type) === 'obra_faena'  ? 'selected' : '' }}>Obra o Faena</option>
                        <option value="honorarios"  {{ old('contract_type', $employee->contract_type) === 'honorarios'  ? 'selected' : '' }}>Honorarios</option>
                    </select>
                    @error('contract_type')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- APV --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        APV <span class="text-slate-400 font-medium normal-case">(Ahorro Previsional Voluntario)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">$</span>
                        <input type="number" name="apv_amount"
                            value="{{ old('apv_amount', $employee->apv_amount) }}"
                            placeholder="0"
                            min="0"
                            class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('apv_amount') border-red-400 bg-red-50 @enderror">
                    </div>
                    @error('apv_amount')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

            </div>{{-- /grid --}}

            {{-- Info note --}}
            <div class="mt-6 flex items-start gap-2.5 bg-blue-50 border border-blue-100 rounded-xl px-4 py-3">
                <i class="fas fa-circle-info text-blue-400 text-sm mt-0.5 flex-shrink-0"></i>
                <p class="text-xs text-blue-700 font-semibold leading-relaxed">
                    Las tasas de AFP y los topes de salud se aplican automáticamente al calcular la liquidación, según los parámetros legales vigentes.
                </p>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="flex items-center gap-2 px-6 py-2.5 bg-slate-900 text-white text-sm font-black rounded-xl hover:bg-slate-800 active:scale-95 transition-all shadow-sm">
                    <i class="fas fa-floppy-disk text-xs"></i>
                    Guardar datos previsionales
                </button>
            </div>
        </form>
    </div>

    {{-- ════════════════════════════════════════
         PESTAÑA 3 — DATOS LABORALES
    ═════════════════════════════════════════ --}}
    <div x-show="tab === 'laboral'" x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

        <form method="POST" action="{{ route('employees.update', $employee) }}" class="p-6 sm:p-8">
            @csrf
            @method('PUT')
            <input type="hidden" name="section" value="laboral">

            <h2 class="text-base font-black text-slate-800 mb-6 flex items-center gap-2">
                <i class="fas fa-briefcase text-blue-500 text-sm"></i> Datos Laborales
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                {{-- Cargo --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Cargo / Puesto
                    </label>
                    <input type="text" name="position"
                        value="{{ old('position', $employee->position) }}"
                        placeholder="Analista de RR.HH."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('position') border-red-400 bg-red-50 @enderror">
                    @error('position')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Centro de Costo / Departamento --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Departamento / Área
                    </label>
                    <select name="cost_center_id"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('cost_center_id') border-red-400 bg-red-50 @enderror">
                        <option value="">— Sin asignar —</option>
                        @foreach($costCenters as $cc)
                            <option value="{{ $cc->id }}"
                                {{ old('cost_center_id', $employee->cost_center_id) == $cc->id ? 'selected' : '' }}>
                                @if($cc->code) [{{ $cc->code }}] @endif {{ $cc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('cost_center_id')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Fecha de Ingreso --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Fecha de Ingreso
                    </label>
                    <input type="date" name="hire_date"
                        value="{{ old('hire_date', optional($employee->hire_date)->format('Y-m-d')) }}"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('hire_date') border-red-400 bg-red-50 @enderror">
                    @error('hire_date')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Tipo de Remuneración --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Tipo de Remuneración
                    </label>
                    <select name="salary_type"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('salary_type') border-red-400 bg-red-50 @enderror">
                        <option value="">— Seleccionar —</option>
                        <option value="mensual"   {{ old('salary_type', $employee->salary_type) === 'mensual'   ? 'selected' : '' }}>Mensual</option>
                        <option value="quincenal" {{ old('salary_type', $employee->salary_type) === 'quincenal' ? 'selected' : '' }}>Quincenal</option>
                        <option value="semanal"   {{ old('salary_type', $employee->salary_type) === 'semanal'   ? 'selected' : '' }}>Semanal</option>
                    </select>
                    @error('salary_type')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Sueldo Base --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-wider mb-1.5">
                        Sueldo Base <span class="text-slate-400 font-medium normal-case">(bruto CLP)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold">$</span>
                        <input type="number" name="salary"
                            value="{{ old('salary', $employee->salary) }}"
                            placeholder="800000"
                            min="0"
                            class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all @error('salary') border-red-400 bg-red-50 @enderror">
                    </div>
                    @error('salary')<p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                {{-- Colación y Movilización — ahora gestionadas en Ítems --}}
                <div class="col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-slate-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-xs font-semibold text-slate-700 mb-1">Colación y Movilización</p>
                            <p class="text-xs text-slate-500">Estos haberes se gestionan desde la ficha del colaborador → pestaña <strong>Ítems</strong>.</p>
                        </div>
                    </div>
                </div>

            </div>{{-- /grid --}}

            {{-- Toggle: Genera Liquidación --}}
            <div class="mt-6 flex items-center justify-between bg-slate-50 border border-slate-200 rounded-2xl px-5 py-4">
                <div>
                    <p class="text-sm font-black text-slate-800">Genera Liquidación de Sueldo</p>
                    <p class="text-xs text-slate-500 mt-0.5 font-medium">
                        Al desactivar, el colaborador queda excluido del cálculo de nómina mensual.
                    </p>
                </div>
                <label x-data="{ on: {{ $employee->is_in_payroll ? 'true' : 'false' }} }"
                    class="relative inline-flex items-center cursor-pointer flex-shrink-0 ml-4">
                    <input type="hidden" name="is_in_payroll" value="0">
                    <input type="checkbox" name="is_in_payroll" value="1"
                        x-model="on"
                        class="sr-only peer">
                    <div @click="on = !on"
                        class="w-12 h-6 rounded-full border-2 transition-all cursor-pointer flex-shrink-0"
                        :class="on ? 'bg-blue-600 border-blue-600' : 'bg-slate-200 border-slate-300'">
                        <div class="w-4 h-4 bg-white rounded-full shadow-sm transition-transform duration-200 mt-0.5"
                            :class="on ? 'translate-x-6 ml-0.5' : 'translate-x-0.5'">
                        </div>
                    </div>
                    <span class="ml-3 text-sm font-black"
                        :class="on ? 'text-blue-700' : 'text-slate-400'"
                        x-text="on ? 'Activo' : 'Inactivo'">
                    </span>
                </label>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="flex items-center gap-2 px-6 py-2.5 bg-slate-900 text-white text-sm font-black rounded-xl hover:bg-slate-800 active:scale-95 transition-all shadow-sm">
                    <i class="fas fa-floppy-disk text-xs"></i>
                    Guardar datos laborales
                </button>
            </div>
        </form>
    </div>

</div>{{-- /panel --}}
</div>{{-- /x-data --}}

</x-layouts.company>
