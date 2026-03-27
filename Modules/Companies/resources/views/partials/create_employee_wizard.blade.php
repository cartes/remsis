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

            @include("companies::partials.wizard.step1")
            @include("companies::partials.wizard.step2")
            @include("companies::partials.wizard.step3")
            @include("companies::partials.wizard.step4")
            @include("companies::partials.wizard.step5")


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
