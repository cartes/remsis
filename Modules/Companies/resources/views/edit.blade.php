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
                    init() {
                        this.$watch('activeTab', value => {
                            const url = new URL(window.location);
                            url.searchParams.set('tab', value);
                            window.history.replaceState(null, '', url);
                        });
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
