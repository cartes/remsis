{{-- ═══════════════════════════════════════════════════════════════════
     Wizard de Creación de Colaborador — Alpine.js 5 pasos
     Escucha el evento: open-create-wizard.window
 ═══════════════════════════════════════════════════════════════════ --}}

@push('scripts')
<script>
window.createEmployeeWizard = function () {
    return {
        isOpen:      false,
        currentStep: 1,
        totalSteps:  5,
        loading:     false,
        errors:      {},
        searchNationality: '',
        nationalityDropdownOpen: false,

        afps:        @json($afps),
        isapres:     @json($isapres),
        ccafs:       @json($ccafs),
        bancos:      @json($bancos),
        costCenters: @json($costCenters),

        steps: [
            { id: 1, label: 'Identificación' },
            { id: 2, label: 'Contrato'       },
            { id: 3, label: 'Previsión'      },
            { id: 4, label: 'Sueldo'         },
            { id: 5, label: 'Pago'           },
        ],

        // ── Países — Chile primero, resto alfabético ──────────────────
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
        },

        form: {},

        defaultForm() {
            return {
                // Paso 1 — Identificación
                first_name: '', last_name: '', rut: '', email: '',
                password: '', birth_date: '', gender: '', phone: '',
                address: '', nationality: 'Chile',
                // Paso 2 — Contrato
                position: '', hire_date: '', contract_type: 'indefinido',
                work_schedule_type: 'full_time', cost_center_id: '',
                // Paso 3 — Previsión
                afp_id: '', health_system: 'fonasa', isapre_id: '',
                health_contribution: '', ccaf_id: '', apv_amount: '',
                // Paso 4 — Sueldo
                salary: '', salary_type: 'mensual',
                num_dependents: 0,
                // Paso 5 — Pago
                payment_method: 'efectivo',
                bank_id: '', bank_account_type: 'corriente', bank_account_number: '',
            };
        },

        open() {
            this.form = this.defaultForm();
            this.errors = {};
            this.currentStep = 1;
            this.isOpen = true;
        },

        close() {
            if (this.loading) return;
            this.isOpen = false;
        },

        // ── RUT ───────────────────────────────────────────────────────
        onRutInput(val) {
            const raw = val.replace(/[^0-9kK]/g, '').toUpperCase();
            if (raw.length <= 1) { this.form.rut = raw; return; }
            const body = raw.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            this.form.rut = `${body}-${raw.slice(-1)}`;
        },

        isValidRut(rut) {
            if (!rut?.trim()) return true;
            const raw  = rut.replace(/[^0-9kK]/g, '').toUpperCase();
            if (raw.length < 2) return false;
            const body = raw.slice(0, -1);
            const dv   = raw.slice(-1);
            let sum = 0, mult = 2;
            for (let i = body.length - 1; i >= 0; i--) {
                sum += parseInt(body[i]) * mult;
                mult = mult === 7 ? 2 : mult + 1;
            }
            const rem = 11 - (sum % 11);
            return dv === (rem === 11 ? '0' : rem === 10 ? 'K' : String(rem));
        },

        // ── Validación por paso ───────────────────────────────────────
        validateStep() {
            const e = {};
            if (this.currentStep === 1) {
                if (!this.form.first_name?.trim()) e.first_name = 'El nombre es requerido';
                if (!this.form.last_name?.trim())  e.last_name  = 'El apellido es requerido';
                if (!this.form.email?.trim()) {
                    e.email = 'El correo es requerido';
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                    e.email = 'Correo inválido';
                }
                if (!this.form.password) {
                    e.password = 'La contraseña es requerida';
                } else if (this.form.password.length < 6) {
                    e.password = 'Mínimo 6 caracteres';
                }
                if (this.form.rut && !this.isValidRut(this.form.rut)) {
                    e.rut = 'RUT inválido';
                }
            }
            if (this.currentStep === 5 && this.form.payment_method === 'transferencia') {
                if (!this.form.bank_id)             e.bank_id             = 'Selecciona un banco';
                if (!this.form.bank_account_type)   e.bank_account_type   = 'Selecciona tipo de cuenta';
                if (!this.form.bank_account_number?.trim()) e.bank_account_number = 'Ingresa el número de cuenta';
            }
            this.errors = e;
            return Object.keys(e).length === 0;
        },

        // ── Navegación ────────────────────────────────────────────────
        next() { if (this.validateStep()) this.currentStep++; },
        prev() { if (this.currentStep > 1) { this.currentStep--; this.errors = {}; } },

        isStepDone(step)  { return step < this.currentStep; },
        isStepActive(step){ return step === this.currentStep; },

        stepFieldMap: {
            1: ['first_name','last_name','rut','email','password'],
            2: ['position','hire_date','contract_type'],
            3: ['afp_id','health_system','isapre_id','health_contribution'],
            4: ['salary','salary_type'],
            5: ['bank_id','bank_account_type','bank_account_number','payment_method'],
        },

        hasStepError(step) {
            return (this.stepFieldMap[step] || []).some(f => this.errors[f]);
        },

        goToErrorStep() {
            for (let s = 1; s <= this.totalSteps; s++) {
                if (this.hasStepError(s)) { this.currentStep = s; return; }
            }
        },

        // ── Envío final ───────────────────────────────────────────────
        async submit() {
            if (!this.validateStep()) return;
            this.loading = true;
            this.errors  = {};
            try {
                const response = await axios.post(
                    '{{ route("companies.employees.store", $company, false) }}',
                    this.form
                );
                if (typeof toast === 'function') {
                    toast(response.data.message || 'Colaborador creado exitosamente', 'success');
                }
                this.isOpen = false;
                setTimeout(() => window.location.reload(), 700);
            } catch (err) {
                if (err.response?.status === 422) {
                    this.errors = err.response.data.errors || {};
                    this.goToErrorStep();
                } else {
                    if (typeof toast === 'function') {
                        toast(err.response?.data?.message || 'Error al crear el colaborador. Intenta nuevamente.', 'error');
                    }
                }
            } finally {
                this.loading = false;
            }
        },
    };
};
</script>
@endpush

{{-- ─── Modal Overlay ────────────────────────────────────────────────── --}}
<div
    x-data="createEmployeeWizard()"
    @open-create-wizard.window="open()"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-[70] flex items-center justify-center p-4"
    style="background:rgba(15,23,42,0.45);backdrop-filter:blur(6px);"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    {{-- ─── Panel ──────────────────────────────────────────────────── --}}
    <div
        @click.away="close()"
        class="bg-white rounded-2xl shadow-[0_32px_80px_rgba(0,0,0,0.18)] w-full max-w-4xl max-h-[94vh] flex flex-col overflow-hidden"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
    >

        {{-- ── Header ──────────────────────────────────────────────── --}}
        <div class="px-8 pt-7 pb-0 flex-shrink-0">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Nuevo Colaborador</h2>
                    <p class="text-xs text-slate-400 mt-1">Completa cada paso · los campos opcionales pueden editarse después</p>
                </div>
                <button @click="close()" :disabled="loading"
                    class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all disabled:opacity-40 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- ── Stepper ──────────────────────────────────────────── --}}
            <div class="flex items-center w-full mb-0">
                <template x-for="(step, idx) in steps" :key="step.id">
                    <div class="flex items-center" :class="idx < steps.length - 1 ? 'flex-1' : ''">
                        {{-- Circle + Label --}}
                        <div class="flex flex-col items-center gap-1.5">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-300 border-2"
                                :class="{
                                    'bg-slate-900 border-slate-900 text-white shadow-md':          isStepActive(step.id),
                                    'bg-emerald-500 border-emerald-500 text-white':                isStepDone(step.id),
                                    'bg-white border-slate-200 text-slate-400':                    !isStepActive(step.id) && !isStepDone(step.id),
                                    'border-red-400 bg-red-50 text-red-500':                        hasStepError(step.id),
                                }"
                            >
                                <template x-if="isStepDone(step.id) && !hasStepError(step.id)">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                                <template x-if="!isStepDone(step.id) || hasStepError(step.id)">
                                    <span x-text="step.id"></span>
                                </template>
                            </div>
                            <span class="text-[10px] font-medium whitespace-nowrap transition-colors duration-300"
                                :class="{
                                    'text-slate-900': isStepActive(step.id),
                                    'text-emerald-600': isStepDone(step.id) && !hasStepError(step.id),
                                    'text-slate-400': !isStepActive(step.id) && !isStepDone(step.id),
                                    'text-red-500': hasStepError(step.id),
                                }"
                                x-text="step.label"
                            ></span>
                        </div>
                        {{-- Connector line --}}
                        <div x-show="idx < steps.length - 1"
                            class="flex-1 h-0.5 mx-2 mb-4 transition-all duration-500 rounded-full"
                            :class="isStepDone(step.id) ? 'bg-emerald-400' : 'bg-slate-200'"
                        ></div>
                    </div>
                </template>
            </div>
            <div class="border-t border-slate-100 mt-4"></div>
        </div>

        {{-- ── Form Body (scrollable) ───────────────────────────────── --}}
        <div class="flex-1 overflow-y-auto px-8 py-6">

            {{-- ════ PASO 1: Identificación ═══════════════════════════ --}}
            <div x-show="currentStep === 1" x-cloak>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Datos Personales</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-5">

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Nombres <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.first_name" placeholder="Ej: María Fernanda"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
                            :class="errors.first_name ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                        <p x-show="errors.first_name" class="mt-1 text-xs text-red-500" x-text="errors.first_name"></p>
                    </div>

                    {{-- Apellidos --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Apellidos <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.last_name" placeholder="Ej: González Soto"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
                            :class="errors.last_name ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                        <p x-show="errors.last_name" class="mt-1 text-xs text-red-500" x-text="errors.last_name"></p>
                    </div>

                    {{-- RUT --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">RUT</label>
                        <input type="text" :value="form.rut" @input="onRutInput($event.target.value)" placeholder="Ej: 12.345.678-9"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 font-mono tracking-wide"
                            :class="errors.rut ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                        <p x-show="errors.rut" class="mt-1 text-xs text-red-500" x-text="errors.rut"></p>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Correo electrónico <span class="text-red-400">*</span></label>
                        <input type="email" x-model="form.email" placeholder="correo@empresa.cl"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
                            :class="errors.email ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                        <p x-show="errors.email" class="mt-1 text-xs text-red-500" x-text="errors.email"></p>
                    </div>

                    {{-- Contraseña --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Contraseña de acceso <span class="text-red-400">*</span></label>
                        <input type="password" x-model="form.password" placeholder="Mínimo 6 caracteres"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
                            :class="errors.password ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                        <p x-show="errors.password" class="mt-1 text-xs text-red-500" x-text="errors.password"></p>
                    </div>

                    {{-- Fecha de nacimiento --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Fecha de nacimiento</label>
                        <input type="date" x-model="form.birth_date"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                    {{-- Nacionalidad (Buscador) --}}
                    <div class="relative" @click.away="nationalityDropdownOpen = false">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Nacionalidad</label>
                        
                        <div @click="nationalityDropdownOpen = !nationalityDropdownOpen"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus-within:ring-2 focus-within:ring-slate-900/10 focus-within:border-slate-400 cursor-pointer flex items-center justify-between">
                            <span x-text="form.nationality || 'Seleccionar nacionalidad'" :class="!form.nationality ? 'text-slate-300' : ''"></span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="nationalityDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>

                        {{-- Dropdown --}}
                        <div x-show="nationalityDropdownOpen" x-cloak
                            class="absolute z-[80] mt-1 w-full bg-white rounded-xl shadow-2xl border border-slate-100 overflow-hidden"
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
                                    <div @click="form.nationality = country; nationalityDropdownOpen = false; searchNationality = ''"
                                        class="px-4 py-2 text-xs text-slate-600 hover:bg-slate-50 hover:text-slate-900 cursor-pointer transition-colors flex items-center justify-between"
                                        :class="form.nationality === country ? 'bg-slate-50 text-slate-900 font-semibold' : ''">
                                        <span x-text="country"></span>
                                        <template x-if="form.nationality === country">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    </div>

                    {{-- Género --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Género</label>
                        <select x-model="form.gender"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="">Sin especificar</option>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    {{-- Teléfono --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Teléfono</label>
                        <input type="tel" x-model="form.phone" placeholder="+56 9 1234 5678"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                    {{-- Dirección --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Dirección</label>
                        <input type="text" x-model="form.address" placeholder="Calle, número, comuna"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                </div>
            </div>

            {{-- ════ PASO 2: Contrato ══════════════════════════════════ --}}
            <div x-show="currentStep === 2" x-cloak>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Información Laboral</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-5">

                    {{-- Cargo --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Cargo / Puesto</label>
                        <input type="text" x-model="form.position" placeholder="Ej: Analista de RRHH"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                    {{-- Centro de costo --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Centro de Costo</label>
                        <select x-model="form.cost_center_id"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="">Sin asignar</option>
                            <template x-for="cc in costCenters" :key="cc.id">
                                <option :value="cc.id" x-text="`${cc.code} — ${cc.name}`"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Fecha de ingreso --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Fecha de Ingreso</label>
                        <input type="date" x-model="form.hire_date"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                    {{-- Tipo de contrato --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Tipo de Contrato</label>
                        <select x-model="form.contract_type"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="indefinido">Indefinido</option>
                            <option value="plazo_fijo">Plazo Fijo</option>
                            <option value="obra_faena">Por Obra o Faena</option>
                            <option value="honorarios">Honorarios</option>
                            <option value="part_time">Part-time</option>
                        </select>
                    </div>

                    {{-- Jornada --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-2">Jornada de Trabajo</label>
                        <div class="flex gap-3">
                            <button type="button" @click="form.work_schedule_type = 'full_time'"
                                class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-all"
                                :class="form.work_schedule_type === 'full_time'
                                    ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                                    : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                                Jornada Completa
                            </button>
                            <button type="button" @click="form.work_schedule_type = 'part_time'"
                                class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-all"
                                :class="form.work_schedule_type === 'part_time'
                                    ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                                    : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                                Jornada Parcial
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ════ PASO 3: Previsión Social ══════════════════════════ --}}
            <div x-show="currentStep === 3" x-cloak>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Seguridad Social</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-5">

                    {{-- AFP --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">AFP</label>
                        <select x-model="form.afp_id"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="">Sin AFP (independiente)</option>
                            <template x-for="afp in afps" :key="afp.id">
                                <option :value="afp.id" x-text="afp.nombre"></option>
                            </template>
                        </select>
                    </div>

                    {{-- CCAF --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Caja de Compensación (CCAF)</label>
                        <select x-model="form.ccaf_id"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="">Sin CCAF</option>
                            <template x-for="ccaf in ccafs" :key="ccaf.id">
                                <option :value="ccaf.id" x-text="ccaf.nombre"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Sistema de Salud --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-2">Sistema de Salud</label>
                        <div class="flex gap-3">
                            <button type="button"
                                @click="form.health_system = 'fonasa'; form.isapre_id = ''; form.health_contribution = ''"
                                class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-all"
                                :class="form.health_system === 'fonasa'
                                    ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                                    : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                                FONASA
                            </button>
                            <button type="button"
                                @click="form.health_system = 'isapre'"
                                class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-all"
                                :class="form.health_system === 'isapre'
                                    ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                                    : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                                ISAPRE
                            </button>
                        </div>
                    </div>

                    {{-- Campos Isapre (condicional) --}}
                    <div x-show="form.health_system === 'isapre'" class="col-span-2 grid grid-cols-2 gap-x-6 gap-y-4 pt-1"
                        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Isapre</label>
                            <select x-model="form.isapre_id"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                                <option value="">Seleccionar Isapre…</option>
                                <template x-for="isa in isapres" :key="isa.id">
                                    <option :value="isa.id" x-text="isa.nombre"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Monto plan (UF)</label>
                            <input type="number" x-model="form.health_contribution" placeholder="Ej: 3.25" step="0.01" min="0"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                        </div>
                    </div>

                    {{-- APV --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">APV mensual ($)</label>
                        <input type="number" x-model="form.apv_amount" placeholder="0" min="0"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                        <p class="mt-1 text-[10px] text-slate-400">Ahorro Previsional Voluntario</p>
                    </div>

                </div>
            </div>

            {{-- ════ PASO 4: Sueldo ════════════════════════════════════ --}}
            <div x-show="currentStep === 4" x-cloak>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Remuneración</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-5">

                    {{-- Sueldo base --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Sueldo Base ($)</label>
                        <input type="number" x-model="form.salary" placeholder="Ej: 650000" min="0"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                    {{-- Periodicidad --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Periodicidad de pago</label>
                        <select x-model="form.salary_type"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="mensual">Mensual</option>
                            <option value="quincenal">Quincenal</option>
                            <option value="semanal">Semanal</option>
                        </select>
                    </div>

                    {{-- Gratificación --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-2">Gratificación Legal</label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([['art_47','Art. 47 (anual)'],['art_50','Art. 50 (mensual)'],['sin_gratificacion','Sin gratificación']] as [$val,$label])
                            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 cursor-pointer hover:bg-slate-100 transition-colors"
                                @click="form.gratificacion = '{{ $val }}'">
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                    :class="form.gratificacion === '{{ $val }}' ? 'border-slate-900' : 'border-slate-300'">
                                    <div class="w-2 h-2 rounded-full bg-slate-900 transition-all"
                                        :class="form.gratificacion === '{{ $val }}' ? 'opacity-100' : 'opacity-0'"></div>
                                </div>
                                <span class="text-xs font-medium text-slate-700">{{ $label }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Asignación de movilización y colación — ahora gestionadas desde la ficha del colaborador vía Ítems --}}
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-slate-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-xs font-semibold text-slate-700 mb-1">Colación y Movilización</p>
                                <p class="text-xs text-slate-500">Estos haberes se configuran desde la ficha del colaborador → pestaña <strong>Ítems</strong>, una vez creado el registro.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Cargas familiares --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">N° de Cargas Familiares</label>
                        <input type="number" x-model="form.num_dependents" placeholder="0" min="0"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                </div>
            </div>

            {{-- ════ PASO 5: Pago ══════════════════════════════════════ --}}
            <div x-show="currentStep === 5" x-cloak>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Información de Pago</p>
                <div class="space-y-6">

                    {{-- Método de pago --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-2">Medio de Pago</label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([['transferencia','Transferencia','M13 2H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1v5h5M9 13h6M9 17h6M9 9h2'],['cheque','Cheque','M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],['efectivo','Efectivo','M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z']] as [$val,$label,$icon])
                            <button type="button" @click="form.payment_method = '{{ $val }}'"
                                class="flex flex-col items-center gap-2 rounded-xl border-2 px-4 py-4 transition-all"
                                :class="form.payment_method === '{{ $val }}'
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-md'
                                    : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50'">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/>
                                </svg>
                                <span class="text-sm font-semibold">{{ $label }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Datos bancarios (solo si Transferencia) --}}
                    <div x-show="form.payment_method === 'transferencia'"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="grid grid-cols-2 gap-x-6 gap-y-5 pt-1"
                    >
                        <p class="col-span-2 text-xs font-semibold text-slate-500 uppercase tracking-widest">Datos Bancarios</p>

                        {{-- Banco --}}
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Banco <span class="text-red-400">*</span></label>
                            <select x-model="form.bank_id"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
                                :class="errors.bank_id ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                                <option value="">Seleccionar banco…</option>
                                <template x-for="banco in bancos" :key="banco.id">
                                    <option :value="banco.id" x-text="banco.nombre ?? banco.name"></option>
                                </template>
                            </select>
                            <p x-show="errors.bank_id" class="mt-1 text-xs text-red-500" x-text="errors.bank_id"></p>
                        </div>

                        {{-- Número de cuenta --}}
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Número de Cuenta <span class="text-red-400">*</span></label>
                            <input type="text" x-model="form.bank_account_number" placeholder="Ej: 00012345678"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 font-mono"
                                :class="errors.bank_account_number ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                            <p x-show="errors.bank_account_number" class="mt-1 text-xs text-red-500" x-text="errors.bank_account_number"></p>
                        </div>

                        {{-- Tipo de cuenta --}}
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-2">Tipo de Cuenta <span class="text-red-400">*</span></label>
                            <div class="flex gap-3">
                                @foreach([['corriente','Cuenta Corriente'],['vista','Cuenta Vista / RUT'],['ahorro','Cuenta de Ahorro']] as [$v,$l])
                                <button type="button" @click="form.bank_account_type = '{{ $v }}'"
                                    class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-all"
                                    :class="form.bank_account_type === '{{ $v }}'
                                        ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                                        : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                                    {{ $l }}
                                </button>
                                @endforeach
                            </div>
                            <p x-show="errors.bank_account_type" class="mt-1 text-xs text-red-500" x-text="errors.bank_account_type"></p>
                        </div>

                    </div>

                    {{-- Mensaje info para efectivo/cheque --}}
                    <div x-show="form.payment_method !== 'transferencia'"
                        x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="flex items-center gap-3 rounded-xl bg-slate-50 border border-slate-200 px-5 py-4">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-slate-500">
                            El pago por <strong class="text-slate-700" x-text="form.payment_method"></strong> no requiere datos bancarios.
                            Podrás agregar información bancaria más tarde desde el perfil del colaborador.
                        </p>
                    </div>

                </div>
            </div>

        </div>{{-- /form-body --}}

        {{-- ── Footer ──────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between px-8 py-5 border-t border-slate-100 bg-slate-50/60 flex-shrink-0">

            {{-- Izquierda: Atrás / Cancelar --}}
            <div>
                <button x-show="currentStep > 1" @click="prev()" type="button" :disabled="loading"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg text-sm font-medium text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 transition-all disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Atrás
                </button>
                <button x-show="currentStep === 1" @click="close()" type="button" :disabled="loading"
                    class="inline-flex items-center px-4 py-2.5 rounded-lg text-sm font-medium text-slate-500 bg-white border border-slate-200 hover:bg-slate-100 transition-all disabled:opacity-50">
                    Cancelar
                </button>
            </div>

            {{-- Derecha: Siguiente / Guardar --}}
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 mr-1" x-text="`Paso ${currentStep} de ${totalSteps}`"></span>

                <button x-show="currentStep < totalSteps" @click="next()" type="button" :disabled="loading"
                    class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-lg text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800 active:bg-slate-950 shadow-sm transition-all disabled:opacity-60">
                    Siguiente
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                <button x-show="currentStep === totalSteps" @click="submit()" type="button"
                    :disabled="loading"
                    class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm transition-all disabled:opacity-60">
                    <template x-if="!loading">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    <template x-if="loading">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </template>
                    <span x-text="loading ? 'Guardando...' : 'Guardar Colaborador'"></span>
                </button>
            </div>

        </div>{{-- /footer --}}

    </div>{{-- /panel --}}
</div>{{-- /overlay --}}
