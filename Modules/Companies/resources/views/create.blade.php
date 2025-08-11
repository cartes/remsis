<x-adminpanel::layouts.master>
    @section('title', 'Crear empresa')

    @section('title', 'Crear empresa')

    @section('content')
        <div x-data="companyCreate()" class="max-w-5xl">

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
                            <input x-model="formDetails.ccaf" class="w-full border rounded p-2"
                                placeholder="Los Andes, 18, etc.">
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
                            <input type="number" min="1" max="31" x-model.number="formDetails.dia_pago_dia"
                                class="w-full border rounded p-2">
                        </div>
                    </div>
                </section>

                {{-- Banco --}}
                <section x-show="tab==='banco'" x-transition>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Banco</label>
                            <input x-model="formDetails.banco" class="w-full border rounded p-2">
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
        <script>
            function companyCreate() {
                return {
                    // UI state
                    tab: 'ident',
                    companyId: null,
                    savingEssential: false,
                    savingDetails: false,

                    // Forms
                    formEssential: {
                        razon_social: '',
                        rut: '',
                    },
                    formDetails: {
                        // identificación complementaria
                        nombre_fantasia: '',
                        giro: '',
                        email: '',
                        phone: '',
                        name: '',
                        // dirección
                        direccion: '',
                        comuna: '',
                        region: '',
                        // remuneraciones
                        tipo_contribuyente: '',
                        ccaf: '',
                        mutual: '',
                        dia_pago: '',
                        dia_pago_dia: null,
                        // banco
                        banco: '',
                        cuenta_bancaria: '',
                        // representante
                        representante_nombre: '',
                        representante_rut: '',
                        representante_cargo: '',
                        representante_email: '',
                        // meta
                        notes: '',
                    },

                    async saveEssentials() {
                        try {
                            this.savingEssential = true;

                            const resp = await axios.post("{{ route('companies.store') }}", this.formEssential, {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });

                            this.companyId = resp.data.id;
                            Alpine.store('toast').flash('Empresa creada. Completa la ficha debajo.', 'success');
                        } catch (e) {
                            const msg = e.response?.data?.message || 'Error al crear la empresa.';
                            const errors = e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(
                                '\n') : '';
                            Alpine.store('toast').flash([msg, errors].filter(Boolean).join('\n'), 'error', 6000);
                        } finally {
                            this.savingEssential = false;
                        }
                    },

                    async saveDetails() {
                        if (!this.companyId) {
                            Alpine.store('toast').flash('Primero guarda Razón social y RUT.', 'error');
                            return;
                        }
                        try {
                            this.savingDetails = true;

                            const url = "{{ route('companies.update', '__ID__') }}".replace('__ID__', this.companyId);
                            const payload = {
                                ...this.formDetails,
                                _method: 'PUT'
                            };

                            await axios.post(url, payload, {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });

                            Alpine.store('toast').flash('Ficha de empresa guardada.', 'success');
                        } catch (e) {
                            const msg = e.response?.data?.message || 'Error al guardar la ficha.';
                            const errors = e.response?.data?.errors ? Object.values(e.response.data.errors).flat().join(
                                '\n') : '';
                            Alpine.store('toast').flash([msg, errors].filter(Boolean).join('\n'), 'error', 6000);
                        } finally {
                            this.savingDetails = false;
                        }
                    }
                }
            }
        </script>
    @endpush

</x-adminpanel::layouts.master>
