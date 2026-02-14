<x-layouts.company :company="$company" activeTab="accounting">
    @section('title', 'Editar Empresa')
    <script>
        (function() {
            const initCompanyForm = () => {
                Alpine.data('companyForm', () => ({
                    activeTab: @json(request('tab', 'ident')),
                    diaPago: @json(old('dia_pago', $company->dia_pago)),
                    showEssentialsModal: false,
                    loadingEssentials: false,
                    essentials: {
                        razon_social: @json($company->razon_social),
                        rut: @json($company->rut)
                    },
                    weeklyHours: @json($company->weekly_hours ?? 44),
                    editingWeeklyHours: false,
                    schedule: @json($company->work_schedule) || {
                        mon: {
                            label: 'Lunes',
                            active: true,
                            start: '09:00',
                            end: '18:30',
                            break: 60
                        },
                        tue: {
                            label: 'Martes',
                            active: true,
                            start: '09:00',
                            end: '18:30',
                            break: 60
                        },
                        wed: {
                            label: 'Miércoles',
                            active: true,
                            start: '09:00',
                            end: '18:30',
                            break: 60
                        },
                        thu: {
                            label: 'Jueves',
                            active: true,
                            start: '09:00',
                            end: '18:30',
                            break: 60
                        },
                        fri: {
                            label: 'Viernes',
                            active: true,
                            start: '09:00',
                            end: '16:30',
                            break: 60
                        },
                        sat: {
                            label: 'Sábado',
                            active: false,
                            start: '10:00',
                            end: '14:00',
                            break: 0
                        },
                        sun: {
                            label: 'Domingo',
                            active: false,
                            start: '10:00',
                            end: '14:00',
                            break: 0
                        },
                    },
                    init() {
                        this.$watch('activeTab', value => {
                            const url = new URL(window.location);
                            url.searchParams.set('tab', value);
                            window.history.replaceState(null, '', url);
                        });
                    },
                    calculateScheduleHours() {
                        let totalMinutes = 0;
                        Object.values(this.schedule).forEach(day => {
                            if (day.active && day.start && day.end) {
                                const start = new Date(`1970-01-01T${day.start}:00`);
                                const end = new Date(`1970-01-01T${day.end}:00`);
                                let diff = (end - start) / 1000 / 60; // minutos
                                if (diff < 0) diff += 24 * 60; // cruce de medianoche
                                diff -= (parseInt(day.break) || 0);
                                totalMinutes += Math.max(0, diff);
                            }
                        });
                        return Number((totalMinutes / 60).toFixed(2));
                    },
                    get hoursDiscrepancy() {
                        const calculated = this.calculateScheduleHours();
                        const defined = parseFloat(this.weeklyHours) || 0;
                        return Math.abs(calculated - defined) > 0.1;
                    },
                    async updateEssentials() {
                        this.loadingEssentials = true;
                        try {
                            const response = await axios.put(
                                "{{ route('companies.essentials.update', $company) }}", this
                                .essentials);
                            window.location.reload();
                        } catch (error) {
                            toast(error.response?.data?.message || "Error al actualizar datos",
                                "error");
                        } finally {
                            this.loadingEssentials = false;
                        }
                    }
                }));
            };

            if (typeof Alpine !== 'undefined') {
                initCompanyForm();
            } else {
                document.addEventListener('alpine:init', initCompanyForm);
            }
        })();
    </script>

    <div class="max-w-7xl mx-auto" x-data="companyForm">

        {{-- Toast Notifications for Session Flashes --}}
        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    toast("{{ session('success') }}", 'success');
                });
            </script>
        @endif

        @if (session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    toast("{{ session('error') }}", 'error');
                });
            </script>
        @endif

        {{-- Subheader with specific actions for accounting --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">Datos de Contabilidad</h2>
                <p class="text-sm text-gray-500 mt-1">Configuración técnica y legal de la empresa.</p>
            </div>
            <button @click="showEssentialsModal = true"
                class="inline-flex items-center gap-2 bg-amber-500 text-white px-4 py-2.5 rounded-lg hover:bg-amber-600 transition-colors shadow-sm font-semibold text-sm">
                <i class="fas fa-pen-to-square"></i> Editar Esenciales
            </button>
        </div>

        {{-- Tabbed Form --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">

            {{-- Tab Navigation (Folder Style) --}}
            <div class="flex overflow-x-auto border-b border-gray-200 pl-2 bg-gray-100/70">
                <button @click="activeTab = 'ident'"
                    :class="{
                        'bg-white border-gray-200 border-t border-l border-r text-blue-700 rounded-t-lg font-bold -mb-px z-10 shadow-sm': activeTab === 'ident',
                        'text-gray-500 hover:text-gray-700 hover:bg-gray-50/50 border-transparent': activeTab !== 'ident'
                    }"
                    class="px-6 py-3 text-sm transition-all duration-200 flex items-center gap-2 whitespace-nowrap focus:outline-none border-b-0 mr-1 rounded-t-lg">
                    <i class="fas fa-id-card"></i>
                    Identificación
                </button>
                <button @click="activeTab = 'dir'"
                    :class="{
                        'bg-white border-gray-200 border-t border-l border-r text-blue-700 rounded-t-lg font-bold -mb-px z-10 shadow-sm': activeTab === 'dir',
                        'text-gray-500 hover:text-gray-700 hover:bg-gray-50/50 border-transparent': activeTab !== 'dir'
                    }"
                    class="px-6 py-3 text-sm transition-all duration-200 flex items-center gap-2 whitespace-nowrap focus:outline-none border-b-0 mr-1 rounded-t-lg">
                    <i class="fas fa-map-marker-alt"></i>
                    Dirección
                </button>
                <button @click="activeTab = 'remu'"
                    :class="{
                        'bg-white border-gray-200 border-t border-l border-r text-blue-700 rounded-t-lg font-bold -mb-px z-10 shadow-sm': activeTab === 'remu',
                        'text-gray-500 hover:text-gray-700 hover:bg-gray-50/50 border-transparent': activeTab !== 'remu'
                    }"
                    class="px-6 py-3 text-sm transition-all duration-200 flex items-center gap-2 whitespace-nowrap focus:outline-none border-b-0 mr-1 rounded-t-lg">
                    <i class="fas fa-money-bill-wave"></i>
                    Remuneraciones
                </button>
                {{-- Cost Centers Tab --}}
                <button @click="activeTab = 'cost-centers'"
                    :class="{
                        'bg-white border-gray-200 border-t border-l border-r text-blue-700 rounded-t-lg font-bold -mb-px z-10 shadow-sm': activeTab === 'cost-centers',
                        'text-gray-500 hover:text-gray-700 hover:bg-gray-50/50 border-transparent': activeTab !== 'cost-centers'
                    }"
                    class="px-6 py-3 text-sm transition-all duration-200 flex items-center gap-2 whitespace-nowrap focus:outline-none border-b-0 mr-1 rounded-t-lg">
                    <i class="fas fa-sitemap"></i>
                    Centros de Costos
                </button>
                <button @click="activeTab = 'banco'"
                    :class="{
                        'bg-white border-gray-200 border-t border-l border-r text-blue-700 rounded-t-lg font-bold -mb-px z-10 shadow-sm': activeTab === 'banco',
                        'text-gray-500 hover:text-gray-700 hover:bg-gray-50/50 border-transparent': activeTab !== 'banco'
                    }"
                    class="px-6 py-3 text-sm transition-all duration-200 flex items-center gap-2 whitespace-nowrap focus:outline-none border-b-0 mr-1 rounded-t-lg">
                    <i class="fas fa-university"></i>
                    Banco
                </button>
                <button @click="activeTab = 'rep'"
                    :class="{
                        'bg-white border-gray-200 border-t border-l border-r text-blue-700 rounded-t-lg font-bold -mb-px z-10 shadow-sm': activeTab === 'rep',
                        'text-gray-500 hover:text-gray-700 hover:bg-gray-50/50 border-transparent': activeTab !== 'rep'
                    }"
                    class="px-6 py-3 text-sm transition-all duration-200 flex items-center gap-2 whitespace-nowrap focus:outline-none border-b-0 mr-1 rounded-t-lg">
                    <i class="fas fa-user-tie"></i>
                    Representante
                </button>
                <button @click="activeTab = 'meta'"
                    :class="{
                        'bg-white border-gray-200 border-t border-l border-r text-blue-700 rounded-t-lg font-bold -mb-px z-10 shadow-sm': activeTab === 'meta',
                        'text-gray-500 hover:text-gray-700 hover:bg-gray-50/50 border-transparent': activeTab !== 'meta'
                    }"
                    class="px-6 py-3 text-sm transition-all duration-200 flex items-center gap-2 whitespace-nowrap focus:outline-none border-b-0 mr-1 rounded-t-lg">
                    <i class="fas fa-sticky-note"></i>
                    Notas
                </button>
            </div>


            {{-- Cost Centers Tab --}}
            <div x-show="activeTab === 'cost-centers'"
                class="bg-white border border-gray-200 border-t-0 rounded-b-lg shadow-sm p-6"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0">
                @include('companies::partials.cost_centers_tab')
            </div>

            <div x-show="activeTab !== 'cost-centers'"
                class="bg-white border border-gray-200 border-t-0 rounded-b-lg shadow-sm">
                <form method="POST" action="{{ route('companies.update', $company) }}" class="p-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tab" x-model="activeTab">

                    {{-- Identificación Tab --}}
                    <div x-show="activeTab === 'ident'" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-1">Información de Identificación</h3>
                            <p class="text-sm text-gray-500">Datos básicos de la empresa</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre de Fantasía</label>
                                <input name="nombre_fantasia"
                                    value="{{ old('nombre_fantasia', $company->nombre_fantasia) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                @error('nombre_fantasia')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Giro</label>
                                <input name="giro" value="{{ old('giro', $company->giro) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                @error('giro')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input type="email" name="email" value="{{ old('email', $company->email) }}"
                                        class="w-full border-gray-300 rounded-lg p-2.5 pl-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                </div>
                                @error('email')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Teléfono</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-phone text-gray-400"></i>
                                    </div>
                                    <input name="phone" value="{{ old('phone', $company->phone) }}"
                                        class="w-full border-gray-300 rounded-lg p-2.5 pl-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                </div>
                                @error('phone')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre Interno <span
                                        class="text-gray-400 font-normal">(opcional)</span></label>
                                <input name="name" value="{{ old('name', $company->name) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                @error('name')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Dirección Tab --}}
                    <div x-show="activeTab === 'dir'" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-1">Dirección y Ubicación</h3>
                            <p class="text-sm text-gray-500">Datos de ubicación física de la empresa</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Dirección</label>
                                <input name="direccion" value="{{ old('direccion', $company->direccion) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                @error('direccion')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Comuna</label>
                                <input name="comuna" value="{{ old('comuna', $company->comuna) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                @error('comuna')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Región</label>
                                <input name="region" value="{{ old('region', $company->region) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                @error('region')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Remuneraciones Tab --}}
                    <div x-show="activeTab === 'remu'" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-1">Configuración de Remuneraciones</h3>
                            <p class="text-sm text-gray-500">Parámetros de pago y beneficios</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo
                                    Contribuyente</label>
                                <select name="tipo_contribuyente"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Seleccionar...</option>
                                    <option value="natural" @selected(old('tipo_contribuyente', $company->tipo_contribuyente) === 'natural')>Natural</option>
                                    <option value="juridica" @selected(old('tipo_contribuyente', $company->tipo_contribuyente) === 'juridica')>Jurídica</option>
                                </select>
                                @error('tipo_contribuyente')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">CCAF</label>
                                <select name="ccaf_id"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Seleccionar CCAF...</option>
                                    @foreach ($ccafs as $c)
                                        <option value="{{ $c->id }}" @selected(old('ccaf_id', $company->ccaf_id) === $c->id)>
                                            {{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('ccaf_id')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Mutual de
                                    Seguridad</label>
                                <select name="mutual_id"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Seleccionar Mutual...</option>
                                    @foreach ($mutuales as $m)
                                        <option value="{{ $m->id }}" @selected(old('mutual_id', $company->mutual_id) === $m->id)>
                                            {{ $m->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('mutual_id')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Día de Pago</label>
                                <select name="dia_pago"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                    x-model="diaPago">
                                    <option value="">Seleccionar...</option>
                                    <option value="ultimo_dia_habil">Último día hábil</option>
                                    <option value="dia_fijo">Día fijo</option>
                                    <option value="quincenal">Quincenal</option>
                                </select>
                                @error('dia_pago')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            <div x-show="diaPago==='dia_fijo'" x-transition>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Día del Mes
                                    (1–31)</label>
                                <input type="number" min="1" max="31" name="dia_pago_dia"
                                    value="{{ old('dia_pago_dia', $company->dia_pago_dia) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                @error('dia_pago_dia')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Sistema de Gratificación --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Sistema de
                                    Gratificación</label>
                                <select name="gratification_system"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="sin_gratificacion" @selected(old('gratification_system', $company->gratification_system) === 'sin_gratificacion')>Sin gratificación
                                    </option>
                                    <option value="art_47" @selected(old('gratification_system', $company->gratification_system) === 'art_47')>Gratificación legal Art. 47
                                        (30% utilidades)</option>
                                    <option value="art_50" @selected(old('gratification_system', $company->gratification_system) === 'art_50')>Gratificación legal Art. 50
                                        (25% remuneraciones con tope 4,75 IMM)</option>
                                    <option value="convencional" @selected(old('gratification_system', $company->gratification_system) === 'convencional')>Gratificación
                                        convencional (contrato/colectivo)</option>
                                </select>
                                @error('gratification_system')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Meses de Gratificación --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Meses de pago al
                                    año</label>
                                <select name="gratification_months"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    @foreach ([1, 2, 4, 12] as $months)
                                        <option value="{{ $months }}" @selected(old('gratification_months', $company->gratification_months) == $months)>
                                            {{ $months }} {{ $months == 1 ? 'mes (Anual)' : 'meses' }}
                                            @if ($months == 2)
                                                (Semestral)
                                            @endif
                                            @if ($months == 4)
                                                (Trimestral)
                                            @endif
                                            @if ($months == 12)
                                                (Mensual)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('gratification_months')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Permitir Horas Extras --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Permitir Horas
                                    Extras</label>
                                <div class="flex items-center mt-2">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="allows_overtime" value="1"
                                            class="sr-only peer" @checked(old('allows_overtime', $company->allows_overtime ?? true))>
                                        <div
                                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                                        </div>
                                        <span class="ml-3 text-sm font-medium text-gray-700">Habilitar cálculo de horas
                                            extras</span>
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Si se deshabilita, no se podrán ingresar horas
                                    extras en la nómina.</p>
                            </div>

                            {{-- Horas Semanales --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Horas de trabajo
                                    semanal</label>
                                <div class="flex items-center gap-3">
                                    <input type="number" name="weekly_hours" x-model="weeklyHours"
                                        :readonly="!editingWeeklyHours"
                                        class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-gray-50"
                                        :class="{
                                            'bg-white': editingWeeklyHours,
                                            'bg-gray-50 text-gray-500': !
                                                editingWeeklyHours
                                        }">
                                    <button type="button"
                                        @click="editingWeeklyHours = !editingWeeklyHours; if(editingWeeklyHours) $nextTick(() => $el.previousElementSibling.focus())"
                                        class="px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors shadow-sm"
                                        x-text="editingWeeklyHours ? 'Cancelar' : 'Modificar'">
                                    </button>
                                </div>
                                @error('weekly_hours')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div x-show="hoursDiscrepancy" x-transition
                                class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-3">
                                <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
                                <div class="text-sm text-amber-800">
                                    <p class="font-semibold">Discrepancia detectada</p>
                                    <p>La suma del horario (<span x-text="calculateScheduleHours()"></span> hrs) no
                                        coincide
                                        con las horas semanales definidas.</p>
                                </div>
                            </div>
                        </div>

                        {{-- Horario de Oficina Section --}}
                        <div class="mt-8 border-t border-gray-100 pt-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">Horario de Oficina</h3>
                            <p class="text-sm text-gray-500 mb-6">Define los turnos de trabajo y horarios de colación
                                para la semana.</p>

                            {{-- Hidden input to store json --}}
                            <input type="hidden" name="work_schedule" :value="JSON.stringify(schedule)">

                            <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-3 font-bold w-32">Día</th>
                                            <th class="px-4 py-3 font-bold">Estado</th>
                                            <th class="px-4 py-3 font-bold">Entrada</th>
                                            <th class="px-4 py-3 font-bold">Salida</th>
                                            <th class="px-4 py-3 font-bold">Colación (min)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(day, key) in schedule" :key="key">
                                            <tr class="bg-white hover:bg-gray-50/50 transition-colors">
                                                <td class="px-4 py-3 font-medium text-gray-800 capitalize"
                                                    x-text="day.label"></td>
                                                <td class="px-4 py-3">
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" x-model="day.active"
                                                            class="sr-only peer">
                                                        <div
                                                            class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                                                        </div>
                                                    </label>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="time" x-model="day.start" :disabled="!day.active"
                                                        class="border-gray-300 rounded-md text-sm p-1.5 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400 w-32">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="time" x-model="day.end" :disabled="!day.active"
                                                        class="border-gray-300 rounded-md text-sm p-1.5 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400 w-32">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="relative w-24">
                                                        <input type="number" x-model="day.break"
                                                            :disabled="!day.active" min="0" step="15"
                                                            class="w-full border-gray-300 rounded-md text-sm p-1.5 pr-8 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400">
                                                        <span
                                                            class="absolute right-2 top-1.5 text-xs text-gray-400 font-medium">min</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Banco Tab --}}
                    <div x-show="activeTab === 'banco'" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-1">Información Bancaria</h3>
                            <p class="text-sm text-gray-500">Datos de cuenta bancaria</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Banco</label>
                                <select name="bank_id"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Seleccionar Banco...</option>
                                    @foreach ($bancos as $banco)
                                        <option value="{{ $banco->id }}" @selected(old('bank_id', $company->bank_id) === $banco->id)>
                                            {{ $banco->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('bank_id')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Cuenta Bancaria</label>
                                <input name="cuenta_bancaria"
                                    value="{{ old('cuenta_bancaria', $company->cuenta_bancaria) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors font-mono">
                                @error('cuenta_bancaria')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Representante Tab --}}
                    <div x-show="activeTab === 'rep'" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-1">Representante Legal</h3>
                            <p class="text-sm text-gray-500">Información del representante de la empresa</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nombre Completo</label>
                                <input name="representante_nombre"
                                    value="{{ old('representante_nombre', $company->representante_nombre) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                @error('representante_nombre')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">RUT</label>
                                <input name="representante_rut"
                                    value="{{ old('representante_rut', $company->representante_rut) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors font-mono">
                                @error('representante_rut')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Cargo</label>
                                <input name="representante_cargo"
                                    value="{{ old('representante_cargo', $company->representante_cargo) }}"
                                    class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                @error('representante_cargo')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input type="email" name="representante_email"
                                        value="{{ old('representante_email', $company->representante_email) }}"
                                        class="w-full border-gray-300 rounded-lg p-2.5 pl-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                </div>
                                @error('representante_email')
                                    <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                            class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Notas Tab --}}
                    <div x-show="activeTab === 'meta'" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-1">Notas y Observaciones</h3>
                            <p class="text-sm text-gray-500">Información adicional sobre la empresa</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Notas Internas</label>
                            <textarea name="notes" rows="6"
                                class="w-full border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                                placeholder="Escribe cualquier observación o nota importante sobre esta empresa...">{{ old('notes', $company->notes) }}</textarea>
                            @error('notes')
                                <p class="text-red-600 text-xs mt-1 flex items-center gap-1"><i
                                        class="fas fa-exclamation-circle"></i>{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Action Buttons (Only for form tabs) --}}
                    <div x-show="activeTab !== 'payroll' && activeTab !== 'cost-centers'"
                        class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
                        <a href="{{ route('companies.index') }}"
                            class="inline-flex items-center gap-2 bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            <i class="fas fa-arrow-left"></i>
                            Volver
                        </a>
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-blue-600 text-white px-8 py-2.5 rounded-lg hover:bg-blue-700 transition-all shadow-md hover:shadow-lg font-bold transform hover:-translate-y-0.5">
                            <i class="fas fa-save"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>

            </div>
        </div>
        {{-- Essentials Modal --}}
        <div x-show="showEssentialsModal" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" x-cloak
            class="fixed inset-0 bg-gray-500 bg-opacity-75 z-[60] flex items-center justify-center p-4">

            <div @click.away="showEssentialsModal = false"
                class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800">Editar Datos Esenciales</h3>
                    <button @click="showEssentialsModal = false"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form @submit.prevent="updateEssentials" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Razón
                            Social</label>
                        <input type="text" x-model="essentials.razon_social" required
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">RUT</label>
                        <input type="text" x-model="essentials.rut" required
                            class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" @click="showEssentialsModal = false"
                            class="flex-1 px-4 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="loadingEssentials"
                            class="flex-1 px-4 py-2.5 bg-amber-500 text-white rounded-lg text-sm font-semibold hover:bg-amber-600 shadow-sm transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                            <i class="fas fa-spinner fa-spin" x-show="loadingEssentials"></i>
                            <i class="fas fa-save" x-show="!loadingEssentials"></i>
                            <span x-text="loadingEssentials ? 'Guardando...' : 'Guardar Cambios'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</x-layouts.company>
