{{-- ═══════════════════════════════════════════════════════════════════
     Wizard de Creación de Colaborador — Alpine.js
     Escucha el evento: open-create-wizard.window
     Al completar: recarga la página con la nueva lista
 ═══════════════════════════════════════════════════════════════════ --}}

@push('scripts')
<script>
window.createEmployeeWizard = function () {
    return {
        isOpen: false,
        currentStep: 1,
        totalSteps: 5,
        loading: false,
        errors: {},

        afps:        @json($afps),
        isapres:     @json($isapres),
        ccafs:       @json($ccafs),
        bancos:      @json($bancos),
        costCenters: @json($costCenters),

        steps: [
            { id: 1, label: 'Personal' },
            { id: 2, label: 'Laboral' },
            { id: 3, label: 'Previsión' },
            { id: 4, label: 'Remuneración' },
            { id: 5, label: 'Pago' },
        ],

        form: {},

        defaultForm() {
            return {
                // Paso 1
                first_name: '', last_name: '', rut: '', email: '',
                password: '', birth_date: '', gender: '', phone: '', address: '',
                // Paso 2
                position: '', hire_date: '', contract_type: '',
                work_schedule_type: 'full_time', cost_center_id: '',
                // Paso 3
                afp_id: '', health_system: 'fonasa', isapre_id: '',
                health_contribution: '', ccaf_id: '', apv_amount: '',
                // Paso 4
                salary: '', salary_type: 'mensual',
                meal_allowance: '', mobility_allowance: '', num_dependents: 0,
                // Paso 5
                bank_id: '', bank_account_type: '', bank_account_number: '',
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

        // ── RUT chileno ──────────────────────────────────────────────
        onRutInput(value) {
            const raw = value.replace(/[^0-9kK]/g, '').toUpperCase();
            if (raw.length <= 1) { this.form.rut = raw; return; }
            const body = raw.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            this.form.rut = `${body}-${raw.slice(-1)}`;
        },

        isValidRut(rut) {
            if (!rut || !rut.trim()) return true;
            const raw = rut.replace(/[^0-9kK]/g, '').toUpperCase();
            if (raw.length < 2) return false;
            const body = raw.slice(0, -1);
            const dv   = raw.slice(-1);
            let sum = 0, mult = 2;
            for (let i = body.length - 1; i >= 0; i--) {
                sum  += parseInt(body[i]) * mult;
                mult  = mult === 7 ? 2 : mult + 1;
            }
            const rem = 11 - (sum % 11);
            return dv === (rem === 11 ? '0' : rem === 10 ? 'K' : String(rem));
        },

        // ── Validación por paso (solo paso 1 tiene campos requeridos) ──
        validateStep() {
            const e = {};
            if (this.currentStep === 1) {
                if (!this.form.first_name?.trim()) e.first_name = 'El nombre es requerido';
                if (!this.form.last_name?.trim())  e.last_name  = 'El apellido es requerido';
                if (!this.form.email?.trim()) {
                    e.email = 'El correo es requerido';
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                    e.email = 'Formato de correo inválido';
                }
                if (!this.form.password) {
                    e.password = 'La contraseña es requerida';
                } else if (this.form.password.length < 6) {
                    e.password = 'Mínimo 6 caracteres';
                }
                if (this.form.rut && !this.isValidRut(this.form.rut)) {
                    e.rut = 'RUT no válido (verifica el dígito verificador)';
                }
            }
            this.errors = e;
            return Object.keys(e).length === 0;
        },

        // ── Navegación ────────────────────────────────────────────────
        next() { if (this.validateStep()) this.currentStep++; },
        prev() { if (this.currentStep > 1) { this.currentStep--; this.errors = {}; } },
        isStepDone(step)  { return step < this.currentStep; },

        stepFieldMap: {
            1: ['first_name','last_name','rut','email','password','birth_date','gender','phone','address'],
            2: ['position','hire_date','contract_type','work_schedule_type','cost_center_id'],
            3: ['afp_id','health_system','isapre_id','health_contribution','ccaf_id','apv_amount'],
            4: ['salary','salary_type','meal_allowance','mobility_allowance','num_dependents'],
            5: ['bank_id','bank_account_type','bank_account_number'],
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
                    '{{ route("companies.employees.store", $company) }}',
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
                        toast(err.response?.data?.message || 'Error al crear el colaborador', 'error');
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

{{-- ─── Modal Wizard ──────────────────────────────────────────────────── --}}
<div
    x-data="createEmployeeWizard()"
    @open-create-wizard.window="open()"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-[70] flex items-center justify-center p-4"
    style="background:rgba(15,23,42,0.4);backdrop-filter:blur(4px);"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div
        @click.away="close()"
        class="bg-white rounded-2xl shadow-[0_25px_60px_rgba(0,0,0,0.15)] w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-3"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-3"
    >

        {{-- ── Header ─────────────────────────────────────────────── --}}
        <div class="flex items-center justify-between px-7 py-5 border-b border-slate-100 flex-shrink-0">
            <div>
                <h2 class="text-base font-bold text-slate-900 tracking-tight">Nuevo Colaborador</h2>
                <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Completa cada paso · los campos opcionales pueden editarse luego</p>
            </div>
            <button @click="close()" :disabled="loading"
                class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all disabled:opacity-40">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        {{-- ── Stepper ─────────────────────────────────────────────── --}}
        <div class="px-7 pt-5 pb-4 border-b border-slate-100 flex-shrink-0">
            <div class="relative flex items-start justify-between">
                {{-- Línea de fondo --}}
                <div class="absolute top-[15px] left-4 right-4 h-px bg-slate-100"></div>
                {{-- Línea de progreso --}}
                <div class="absolute top-[15px] left-4 h-px bg-slate-900 transition-all duration-500 ease-out"
                     :style="`width: calc((100% - 2rem) * ${(currentStep - 1) / (totalSteps - 1)})`"></div>

                <template x-for="step in steps" :key="step.id">
                    <div class="relative z-10 flex flex-col items-center gap-2 flex-1">
                        {{-- Círculo --}}
                        <div class="w-[30px] h-[30px] rounded-full flex items-center justify-center text-[11px] font-bold border-2 bg-white transition-all duration-300"
                            :class="{
                                'border-slate-900 bg-slate-900 text-white ring-4 ring-slate-900/10': currentStep === step.id && !isStepDone(step.id),
                                'border-slate-900 bg-slate-900 text-white': isStepDone(step.id),
                                'border-red-400 bg-red-50 text-red-500': hasStepError(step.id),
                                'border-slate-200 text-slate-400': !isStepDone(step.id) && currentStep !== step.id && !hasStepError(step.id),
                            }">
                            <template x-if="isStepDone(step.id) && !hasStepError(step.id)">
                                <i class="fas fa-check text-[9px]"></i>
                            </template>
                            <template x-if="hasStepError(step.id)">
                                <i class="fas fa-exclamation text-[9px]"></i>
                            </template>
                            <template x-if="!isStepDone(step.id) && !hasStepError(step.id)">
                                <span x-text="step.id"></span>
                            </template>
                        </div>
                        {{-- Etiqueta --}}
                        <span class="text-[10px] font-bold transition-colors whitespace-nowrap"
                            :class="{
                                'text-slate-900': currentStep === step.id,
                                'text-slate-500': isStepDone(step.id),
                                'text-red-400': hasStepError(step.id) && !isStepDone(step.id),
                                'text-slate-300': !isStepDone(step.id) && currentStep !== step.id && !hasStepError(step.id),
                            }"
                            x-text="step.label">
                        </span>
                    </div>
                </template>
            </div>
        </div>

        {{-- ── Contenido (scrollable) ──────────────────────────────── --}}
        <div class="flex-1 overflow-y-auto px-7 py-6 min-h-0">

            {{-- ─────── PASO 1: Información Personal ─────────────── --}}
            <div x-show="currentStep === 1">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-5">Información Personal</p>
                <div class="grid grid-cols-2 gap-x-5 gap-y-4">

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                            Nombres <span class="text-red-400">*</span>
                        </label>
                        <input type="text" x-model="form.first_name" placeholder="Ej: Juan Andrés"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border outline-none transition-all"
                            :class="errors.first_name ? 'border-red-300 bg-red-50 focus:ring-2 focus:ring-red-100' : 'border-slate-200 focus:border-slate-400 focus:ring-2 focus:ring-slate-100'">
                        <p x-show="errors.first_name" x-text="errors.first_name" class="mt-1 text-[10px] text-red-500 font-medium"></p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                            Apellidos <span class="text-red-400">*</span>
                        </label>
                        <input type="text" x-model="form.last_name" placeholder="Ej: Pérez González"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border outline-none transition-all"
                            :class="errors.last_name ? 'border-red-300 bg-red-50 focus:ring-2 focus:ring-red-100' : 'border-slate-200 focus:border-slate-400 focus:ring-2 focus:ring-slate-100'">
                        <p x-show="errors.last_name" x-text="errors.last_name" class="mt-1 text-[10px] text-red-500 font-medium"></p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">RUT</label>
                        <input type="text" x-model="form.rut"
                            @input="onRutInput($event.target.value)"
                            placeholder="12.345.678-9" maxlength="12"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border outline-none transition-all font-mono tracking-wide"
                            :class="errors.rut ? 'border-red-300 bg-red-50 focus:ring-2 focus:ring-red-100' : 'border-slate-200 focus:border-slate-400 focus:ring-2 focus:ring-slate-100'">
                        <p x-show="errors.rut" x-text="errors.rut" class="mt-1 text-[10px] text-red-500 font-medium"></p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Fecha de Nacimiento</label>
                        <input type="date" x-model="form.birth_date"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Género</label>
                        <select x-model="form.gender"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                            <option value="">Seleccionar…</option>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                            <option value="otro">Otro</option>
                            <option value="prefiero_no_decir">Prefiero no decir</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Teléfono</label>
                        <input type="text" x-model="form.phone" placeholder="+56 9 XXXX XXXX"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                            Correo Electrónico <span class="text-red-400">*</span>
                        </label>
                        <input type="email" x-model="form.email" placeholder="colaborador@empresa.cl"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border outline-none transition-all"
                            :class="errors.email ? 'border-red-300 bg-red-50 focus:ring-2 focus:ring-red-100' : 'border-slate-200 focus:border-slate-400 focus:ring-2 focus:ring-slate-100'">
                        <p x-show="errors.email" x-text="errors.email" class="mt-1 text-[10px] text-red-500 font-medium"></p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                            Contraseña de Acceso <span class="text-red-400">*</span>
                        </label>
                        <input type="password" x-model="form.password" placeholder="Mínimo 6 caracteres"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border outline-none transition-all"
                            :class="errors.password ? 'border-red-300 bg-red-50 focus:ring-2 focus:ring-red-100' : 'border-slate-200 focus:border-slate-400 focus:ring-2 focus:ring-slate-100'">
                        <p x-show="errors.password" x-text="errors.password" class="mt-1 text-[10px] text-red-500 font-medium"></p>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Dirección</label>
                        <input type="text" x-model="form.address" placeholder="Calle, número, comuna, ciudad"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                    </div>

                </div>
            </div>

            {{-- ─────── PASO 2: Información Laboral ──────────────── --}}
            <div x-show="currentStep === 2" x-cloak>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-5">Información Laboral</p>
                <div class="grid grid-cols-2 gap-x-5 gap-y-4">

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Cargo / Puesto</label>
                        <input type="text" x-model="form.position" placeholder="Ej: Analista de TI"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Centro de Costo</label>
                        <select x-model="form.cost_center_id"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                            <option value="">Sin asignar</option>
                            <template x-for="cc in costCenters" :key="cc.id">
                                <option :value="cc.id" x-text="(cc.code ? cc.code + ' – ' : '') + cc.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Fecha de Ingreso</label>
                        <input type="date" x-model="form.hire_date"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Tipo de Contrato</label>
                        <select x-model="form.contract_type"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                            <option value="">Seleccionar…</option>
                            <option value="indefinido">Indefinido</option>
                            <option value="plazo_fijo">Plazo Fijo</option>
                            <option value="obra_faena">Por Obra o Faena</option>
                        </select>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Jornada Laboral</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="form.work_schedule_type = 'full_time'"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 text-sm font-semibold transition-all text-left"
                                :class="form.work_schedule_type === 'full_time'
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-sm'
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300 bg-white'">
                                <i class="fas fa-briefcase w-4 text-center text-xs flex-shrink-0"></i>
                                <div>
                                    <div>Jornada Completa</div>
                                    <div class="text-[10px] font-normal opacity-70">45 hrs semanales</div>
                                </div>
                            </button>
                            <button type="button" @click="form.work_schedule_type = 'part_time'"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 text-sm font-semibold transition-all text-left"
                                :class="form.work_schedule_type === 'part_time'
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-sm'
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300 bg-white'">
                                <i class="fas fa-hourglass-half w-4 text-center text-xs flex-shrink-0"></i>
                                <div>
                                    <div>Media Jornada</div>
                                    <div class="text-[10px] font-normal opacity-70">Part-time</div>
                                </div>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ─────── PASO 3: Previsión y Salud ─────────────────── --}}
            <div x-show="currentStep === 3" x-cloak>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-5">Previsión y Salud</p>
                <div class="grid grid-cols-2 gap-x-5 gap-y-4">

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">AFP</label>
                        <select x-model="form.afp_id"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                            <option value="">Seleccionar AFP…</option>
                            <template x-for="afp in afps" :key="afp.id">
                                <option :value="afp.id" x-text="afp.nombre"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">CCAF (Caja)</label>
                        <select x-model="form.ccaf_id"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                            <option value="">Sin CCAF</option>
                            <template x-for="ccaf in ccafs" :key="ccaf.id">
                                <option :value="ccaf.id" x-text="ccaf.nombre"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Sistema de Salud --}}
                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Sistema de Salud</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="form.health_system = 'fonasa'; form.isapre_id = ''; form.health_contribution = ''"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 text-sm font-semibold transition-all text-left"
                                :class="form.health_system === 'fonasa'
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-sm'
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300 bg-white'">
                                <i class="fas fa-hospital w-4 text-center text-xs flex-shrink-0"></i>
                                <div>
                                    <div>FONASA</div>
                                    <div class="text-[10px] font-normal opacity-70">Fondo Nacional de Salud</div>
                                </div>
                            </button>
                            <button type="button" @click="form.health_system = 'isapre'"
                                class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 text-sm font-semibold transition-all text-left"
                                :class="form.health_system === 'isapre'
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-sm'
                                    : 'border-slate-200 text-slate-500 hover:border-slate-300 bg-white'">
                                <i class="fas fa-shield-alt w-4 text-center text-xs flex-shrink-0"></i>
                                <div>
                                    <div>ISAPRE</div>
                                    <div class="text-[10px] font-normal opacity-70">Instituto de Salud Previsional</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Isapre condicional --}}
                    <div x-show="form.health_system === 'isapre'" class="col-span-2 grid grid-cols-2 gap-x-5 gap-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Institución Isapre</label>
                            <select x-model="form.isapre_id"
                                class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                                <option value="">Seleccionar…</option>
                                <template x-for="isa in isapres" :key="isa.id">
                                    <option :value="isa.id" x-text="isa.nombre"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Cotización Pactada ($)</label>
                            <input type="number" x-model="form.health_contribution" placeholder="0" min="0"
                                class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                        </div>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">APV — Ahorro Previsional Voluntario ($)</label>
                        <input type="number" x-model="form.apv_amount" placeholder="0 (opcional)" min="0"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                        <p class="mt-1 text-[10px] text-slate-400">Monto mensual destinado a Ahorro Previsional Voluntario.</p>
                    </div>

                </div>
            </div>

            {{-- ─────── PASO 4: Remuneraciones ─────────────────────── --}}
            <div x-show="currentStep === 4" x-cloak>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-5">Remuneraciones</p>
                <div class="grid grid-cols-2 gap-x-5 gap-y-4">

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sueldo Base ($)</label>
                        <input type="number" x-model="form.salary" placeholder="0" min="0"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Periodicidad de Pago</label>
                        <select x-model="form.salary_type"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                            <option value="mensual">Mensual</option>
                            <option value="quincenal">Quincenal</option>
                            <option value="semanal">Semanal</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Asig. de Colación ($)</label>
                        <input type="number" x-model="form.meal_allowance" placeholder="0" min="0"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                        <p class="mt-1 text-[10px] text-slate-400">No imponible ni tributable.</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Asig. de Movilización ($)</label>
                        <input type="number" x-model="form.mobility_allowance" placeholder="0" min="0"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                        <p class="mt-1 text-[10px] text-slate-400">No imponible ni tributable.</p>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Número de Cargas Familiares</label>
                        <input type="number" x-model="form.num_dependents" min="0" max="20"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                        <p class="mt-1 text-[10px] text-slate-400">Usado para el cálculo del Impuesto Único a la Renta.</p>
                    </div>

                    <div class="col-span-2">
                        <div class="flex items-start gap-3 bg-blue-50/60 rounded-xl px-4 py-3 border border-blue-100/80">
                            <i class="fas fa-info-circle text-blue-400 mt-0.5 text-sm flex-shrink-0"></i>
                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                La <strong class="font-semibold text-slate-700">gratificación</strong> se configura a nivel de empresa (Art. 47 o 50 del Código del Trabajo) y se aplica automáticamente al calcular la nómina.
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ─────── PASO 5: Método de Pago ─────────────────────── --}}
            <div x-show="currentStep === 5" x-cloak>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-5">Método de Pago</p>
                <div class="grid grid-cols-2 gap-x-5 gap-y-4">

                    <div class="col-span-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Banco</label>
                        <select x-model="form.bank_id"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all">
                            <option value="">Seleccionar banco…</option>
                            <template x-for="banco in bancos" :key="banco.id">
                                <option :value="banco.id" x-text="banco.nombre ?? banco.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Tipo de Cuenta</label>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="tipo in [{v:'corriente',l:'Corriente'},{v:'vista',l:'Vista / RUT'},{v:'ahorro',l:'Ahorro'}]" :key="tipo.v">
                                <button type="button" @click="form.bank_account_type = tipo.v"
                                    class="px-2 py-2.5 rounded-lg border-2 text-xs font-semibold transition-all"
                                    :class="form.bank_account_type === tipo.v
                                        ? 'border-slate-900 bg-slate-900 text-white'
                                        : 'border-slate-200 text-slate-500 hover:border-slate-300 bg-white'"
                                    x-text="tipo.l">
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Número de Cuenta</label>
                        <input type="text" x-model="form.bank_account_number" placeholder="Ej: 00012345678"
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 transition-all font-mono tracking-wide">
                    </div>

                </div>

                {{-- Resumen previo a crear --}}
                <div class="mt-6 bg-slate-50 rounded-xl border border-slate-100 p-5">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Resumen</p>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2.5 text-[11px]">
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-400 flex-shrink-0">Nombre</span>
                            <span class="font-semibold text-slate-700 text-right" x-text="(form.first_name + ' ' + form.last_name).trim() || '—'"></span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-400 flex-shrink-0">RUT</span>
                            <span class="font-semibold text-slate-700 font-mono" x-text="form.rut || '—'"></span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-400 flex-shrink-0">Correo</span>
                            <span class="font-semibold text-slate-700 truncate ml-2" x-text="form.email || '—'"></span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-400 flex-shrink-0">Cargo</span>
                            <span class="font-semibold text-slate-700" x-text="form.position || '—'"></span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-400 flex-shrink-0">Sueldo Base</span>
                            <span class="font-semibold text-slate-700" x-text="form.salary ? '$ ' + Number(form.salary).toLocaleString('es-CL') : '—'"></span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-400 flex-shrink-0">Salud</span>
                            <span class="font-semibold text-slate-700 uppercase" x-text="form.health_system || '—'"></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /content --}}

        {{-- ── Footer de Navegación ────────────────────────────────── --}}
        <div class="flex items-center justify-between px-7 py-4 border-t border-slate-100 bg-slate-50/60 flex-shrink-0 rounded-b-2xl">

            <button type="button" @click="prev()"
                x-show="currentStep > 1"
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-slate-600 rounded-xl border border-slate-200 hover:bg-white hover:shadow-sm transition-all">
                <i class="fas fa-arrow-left text-xs"></i>
                Anterior
            </button>
            <div x-show="currentStep === 1"></div>

            <div class="flex items-center gap-4">
                <span class="text-[10px] font-semibold text-slate-300 tabular-nums" x-text="`${currentStep} / ${totalSteps}`"></span>

                <button type="button" @click="next()"
                    x-show="currentStep < totalSteps"
                    class="flex items-center gap-2 px-5 py-2.5 text-sm font-bold bg-slate-900 text-white rounded-xl hover:bg-slate-800 shadow-sm transition-all">
                    Continuar
                    <i class="fas fa-arrow-right text-xs"></i>
                </button>

                <button type="button" @click="submit()"
                    x-show="currentStep === totalSteps"
                    :disabled="loading"
                    class="flex items-center gap-2 px-5 py-2.5 text-sm font-bold bg-slate-900 text-white rounded-xl hover:bg-slate-800 shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="!loading">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-user-plus text-xs"></i>
                            Crear Colaborador
                        </span>
                    </template>
                    <template x-if="loading">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin text-xs"></i>
                            Creando…
                        </span>
                    </template>
                </button>
            </div>

        </div>

    </div>
</div>
