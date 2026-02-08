<x-adminpanel::layouts.master>
    @section('title', 'Crear empresa')

    @section('content')
        <div x-data="companyCreate()" x-init="init()" class="max-w-5xl">

            {{-- Sección 1: Datos esenciales (alineado a la izquierda) --}}
            <div class="bg-white rounded shadow p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">Datos esenciales</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium mb-1">Razón social <span class="text-red-500">*</span></label>
                        <input x-model="formEssential.razon_social"
                            class="w-full border rounded p-2 focus:outline-none focus:ring focus:border-blue-300"
                            :disabled="savingEssential || companyId">
                    </div>
                    <div>
                        <label class="block font-medium mb-1">RUT <span class="text-red-500">*</span></label>
                        <input x-model="formEssential.rut"
                            class="w-full border rounded p-2 focus:outline-none focus:ring focus:border-blue-300"
                            :disabled="savingEssential || companyId">
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <a href="{{ route('companies.index') }}"
                        class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">
                        Cancelar
                    </a>

                    <button x-show="!companyId" @click="saveEssentials" :disabled="savingEssential"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!savingEssential">Guardar y continuar</span>
                        <span x-show="savingEssential">Guardando…</span>
                    </button>

                    <button x-show="companyId" type="button" class="bg-emerald-600 text-white px-4 py-2 rounded" disabled>
                        Esenciales guardados ✔
                    </button>
                </div>
            </div>

            {{-- Separador visual --}}
            <div class="border-t border-gray-200 mb-6"></div>

            {{-- Sección 2: Tabs (solo cuando ya existe companyId) --}}
            <div x-show="companyId" x-transition class="bg-white rounded shadow p-6">
                <div class="flex flex-wrap gap-2 border-b mb-6">
                    <button @click="tab='ident'" class="px-4 py-2"
                        :class="tab === 'ident' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">
                        Identificación
                    </button>
                    <button @click="tab='dir'" class="px-4 py-2"
                        :class="tab === 'dir' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">
                        Dirección
                    </button>
                    <button @click="tab='remu'" class="px-4 py-2"
                        :class="tab === 'remu' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">
                        Remuneraciones
                    </button>
                    <button @click="tab='banco'" class="px-4 py-2"
                        :class="tab === 'banco' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">
                        Banco
                    </button>
                    <button @click="tab='rep'" class="px-4 py-2"
                        :class="tab === 'rep' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">
                        Representante
                    </button>
                    <button @click="tab='meta'" class="px-4 py-2"
                        :class="tab === 'meta' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">
                        Metadatos
                    </button>
                </div>

                {{-- Identificación (complementaria) --}}
                <section x-show="tab==='ident'" x-transition>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Nombre de fantasía</label>
                            <input x-model="formDetails.nombre_fantasia" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Giro</label>
                            <input x-model="formDetails.giro" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Email</label>
                            <input type="email" x-model="formDetails.email" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Teléfono</label>
                            <input x-model="formDetails.phone" class="w-full border rounded p-2">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-medium mb-1">Nombre interno (opcional)</label>
                            <input x-model="formDetails.name" class="w-full border rounded p-2">
                        </div>
                    </div>
                </section>

                {{-- Dirección --}}
                <section x-show="tab==='dir'" x-transition>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="block font-medium mb-1">Dirección</label>
                            <input x-model="formDetails.direccion" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Comuna</label>
                            <input x-model="formDetails.comuna" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Región</label>
                            <input x-model="formDetails.region" class="w-full border rounded p-2">
                        </div>
                    </div>
                </section>

                {{-- Remuneraciones --}}
                <section x-show="tab==='remu'" x-transition>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Tipo contribuyente</label>
                            <select x-model="formDetails.tipo_contribuyente" class="w-full border rounded p-2">
                                <option value="">—</option>
                                <option value="natural">Natural</option>
                                <option value="juridica">Jurídica</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium mb-1">CCAF</label>
                            <select x-model="formDetails.ccaf_id" class="w-full border rounded p-2">
                                <option value="">Selecciona una CCAF</option>
                                <template x-for="item in options.ccafs" :key="item.id">
                                    <option :value="item.id" x-text="item.nombre"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Mutual</label>
                            <input x-model="formDetails.mutual" class="w-full border rounded p-2"
                                placeholder="ACHS, IST, Mutual...">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Día de pago</label>
                            <select x-model="formDetails.dia_pago" class="w-full border rounded p-2">
                                <option value="">—</option>
                                <option value="ultimo_dia_habil">Último día hábil</option>
                                <option value="dia_fijo">Día fijo</option>
                                <option value="quincenal">Quincenal</option>
                            </select>
                        </div>
                        <div x-show="formDetails.dia_pago==='dia_fijo'">
                            <label class="block font-medium mb-1">Día (1–31)</label>
                            <input type="number" min="1" max="31"
                                x-model.number="formDetails.dia_pago_dia" class="w-full border rounded p-2">
                        </div>
                    </div>
                </section>

                {{-- Banco --}}
                <section x-show="tab==='banco'" x-transition>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Banco</label>
                            <select x-model="formDetails.bank_id" class="w-full border rounded p-2">
                                <option value="">Selecciona un Banco</option>
                                <template x-for="item in options.bancos" :key="item.id">
                                    <option :value="item.id" x-text="item.nombre"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Cuenta bancaria</label>
                            <input x-model="formDetails.cuenta_bancaria" class="w-full border rounded p-2">
                        </div>
                    </div>
                </section>

                {{-- Representante --}}
                <section x-show="tab==='rep'" x-transition>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Nombre</label>
                            <input x-model="formDetails.representante_nombre" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">RUT</label>
                            <input x-model="formDetails.representante_rut" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Cargo</label>
                            <input x-model="formDetails.representante_cargo" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Email</label>
                            <input type="email" x-model="formDetails.representante_email"
                                class="w-full border rounded p-2">
                        </div>
                    </div>
                </section>

                {{-- Metadatos --}}
                <section x-show="tab==='meta'" x-transition>
                    <div>
                        <label class="block font-medium mb-1">Notas / Observaciones</label>
                        <textarea x-model="formDetails.notes" class="w-full border rounded p-2 min-h-[120px]"></textarea>
                    </div>
                </section>

                <div class="flex gap-2 pt-4">
                    <button @click="saveDetails" :disabled="savingDetails"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!savingDetails">Guardar ficha</span>
                        <span x-show="savingDetails">Guardando…</span>
                    </button>
                    <a href="{{ route('companies.index') }}"
                        class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">
                        Volver
                    </a>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

        <script>
            // Axios base (CSRF + AJAX headers)
            (function setupAxios() {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (token) axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
                axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            })();

            // Componente Alpine para "Crear Empresa"
            function companyCreate() {
                return {
                    // UI
                    tab: 'ident',
                    companyId: null,
                    savingEssential: false,
                    savingDetails: false,

                    // Catálogos
                    options: {
                        ccafs: [], // [{id,name,code?}, ...]
                        bancos: [],
                    },

                    // Paso 1: esenciales
                    formEssential: {
                        razon_social: '',
                        rut: '',
                    },

                    // Paso 2: ficha (tabs)
                    formDetails: {
                        // Identificación complementaria
                        nombre_fantasia: '',
                        giro: '',
                        email: '',
                        phone: '',
                        name: '',

                        // Dirección
                        direccion: '',
                        comuna: '',
                        region: '',

                        // Remuneraciones (empresa)
                        tipo_contribuyente: '',
                        dia_pago: '',
                        dia_pago_dia: null,
                        ccaf_id: null, // FK

                        // Banco (FK)
                        bank_id: null,

                        // Representante
                        representante_nombre: '',
                        representante_rut: '',
                        representante_cargo: '',
                        representante_email: '',

                        // Metadatos
                        notes: '',
                    },

                    // Carga inicial
                    async init() {
                        try {
                            const resp = await axios.get("{{ route('settings.ccafs.json') }}", {
                                headers: {
                                    Accept: 'application/json'
                                }
                            });

                            const rows = Array.isArray(resp.data) ? resp.data : [];
                            // Normaliza a { id, name, code? } sin importar cómo se llame la columna
                            this.options.ccafs = rows.map(r => ({
                                id: r.id,
                                nombre: r.name ?? r.nombre ?? r.descripcion ?? '',
                                code: r.code ?? r.codigo ?? null,
                            }));

                            // DEBUG opcional (quitar luego):
                            // console.log('CCAFs normalizadas:', this.options.ccafs);
                        } catch (e) {
                            toast('No se pudo cargar CCAF.', 'error');
                        }

                        try {
                            const resp = await axios.get("{{ route('settings.bancos.json') }}", {
                                headers: {
                                    Accept: 'application/json'
                                }
                            });
                            this.options.bancos = Array.isArray(resp.data) ? resp.data : [];
                        } catch (e) {
                            toast('No se pudo cargar Bancos.', 'error');
                        }
                    },


                    // Guarda Razón Social + RUT => crea registro y habilita tabs
                    async saveEssentials() {
                        if (!this.formEssential.razon_social?.trim() || !this.formEssential.rut?.trim()) {
                            toast('Ingresa Razón social y RUT.', 'error');
                            return;
                        }

                        this.savingEssential = true;
                        try {
                            const resp = await axios.post("{{ route('companies.store') }}", this.formEssential, {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });

                            if (resp.status === 201 && resp.data?.id) {
                                const url = "{{ route('companies.edit', '__ID__') }}".replace('__ID__', resp.data.id);
                                window.location.href = url;
                                return;
                            }


                            const msg = (resp.data?.errors && Object.values(resp.data.errors).flat().join('\n')) ||
                                resp.data?.message ||
                                'Error al crear la empresa.';
                            toast(msg, 'error', 7000);
                        } finally {
                            this.savingEssential = false;
                        }
                    },

                    // Guarda la ficha (tabs) vía PUT
                    async saveDetails() {
                        if (!this.companyId) {
                            toast('Primero guarda Razón social y RUT.', 'error');
                            return;
                        }
                        if (this.formDetails.dia_pago === 'dia_fijo' && !this.formDetails.dia_pago_dia) {
                            toast('Debes indicar el día de pago (1–31).', 'error');
                            return;
                        }

                        this.savingDetails = true;
                        try {
                            const url = "{{ route('companies.update', '__ID__') }}".replace('__ID__', this.companyId);
                            const resp = await axios.post(url, {
                                ...this.formDetails,
                                _method: 'PUT'
                            }, {
                                headers: {
                                    Accept: 'application/json'
                                },
                                validateStatus: () => true, // << clave
                            });

                            if (resp.status >= 200 && resp.status < 300) {
                                toast(resp.data?.message ?? 'Ficha guardada.', 'success');
                                return;
                            }

                            const msg = (resp.data?.errors && Object.values(resp.data.errors).flat().join('\n')) ||
                                resp.data?.message ||
                                'Error al guardar la ficha.';
                            toast(msg, 'error', 7000);
                        } finally {
                            this.savingDetails = false;
                        }
                    }

                }
            }

            // (Opcional) disparar flashes si algún día vuelves con redirect a esta vista
            window.addEventListener('DOMContentLoaded', () => {
                @if (session('success'))
                    Alpine.store('toast').flash(@json(session('success')), 'success');
                @endif
                @if ($errors->any())
                    Alpine.store('toast').flash(@json(implode("\n", $errors->all())), 'error', 7000);
                @endif
            });
        </script>
    @endpush

</x-adminpanel::layouts.master>
