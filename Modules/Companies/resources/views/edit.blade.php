<x-adminpanel::layouts.master>
    @section('title', 'Editar empresa')

    @section('content')
    <div class="max-w-5xl">

        {{-- Cabecera: Esenciales SOLO LECTURA --}}
        <div class="bg-white rounded shadow p-6 mb-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm text-gray-500">Razón social</div>
                    <div class="text-xl font-semibold">{{ $company->razon_social }}</div>

                    <div class="mt-3 text-sm text-gray-500">RUT</div>
                    <div class="text-lg">{{ $company->rut }}</div>
                </div>

                <a href="{{ route('companies.essentials.edit', $company) }}"
                   class="inline-flex items-center gap-2 bg-amber-500 text-white px-4 py-2 rounded hover:bg-amber-600">
                    <i class="fas fa-pen-to-square"></i> Editar esenciales
                </a>
            </div>
        </div>

        {{-- Tabs + Formulario de detalles --}}
        <div class="bg-white rounded shadow p-6" x-data="{ 
            tab: 'ident', 
            diaPago: '{{ old('dia_pago', $company->dia_pago) }}' 
        }">
            {{-- Tabs --}}
            <div class="flex flex-wrap gap-2 border-b mb-6">
                <button type="button" @click="tab='ident'" class="px-4 py-2"
                    :class="tab==='ident' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">Identificación</button>
                <button type="button" @click="tab='dir'" class="px-4 py-2"
                    :class="tab==='dir' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">Dirección</button>
                <button type="button" @click="tab='remu'" class="px-4 py-2"
                    :class="tab==='remu' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">Remuneraciones</button>
                <button type="button" @click="tab='banco'" class="px-4 py-2"
                    :class="tab==='banco' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">Banco</button>
                <button type="button" @click="tab='rep'" class="px-4 py-2"
                    :class="tab==='rep' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">Representante</button>
                <button type="button" @click="tab='meta'" class="px-4 py-2"
                    :class="tab==='meta' ? 'border-b-2 border-blue-600 font-semibold' : 'text-gray-500'">Metadatos</button>
            </div>

            <form method="POST" action="{{ route('companies.update', $company) }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Identificación --}}
                <section x-show="tab==='ident'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Nombre de fantasía</label>
                            <input name="nombre_fantasia" value="{{ old('nombre_fantasia',$company->nombre_fantasia) }}"
                                   class="w-full border rounded p-2">
                            @error('nombre_fantasia') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Giro</label>
                            <input name="giro" value="{{ old('giro',$company->giro) }}" class="w-full border rounded p-2">
                            @error('giro') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email',$company->email) }}"
                                   class="w-full border rounded p-2">
                            @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Teléfono</label>
                            <input name="phone" value="{{ old('phone',$company->phone) }}" class="w-full border rounded p-2">
                            @error('phone') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block font-medium mb-1">Nombre interno (opcional)</label>
                            <input name="name" value="{{ old('name',$company->name) }}" class="w-full border rounded p-2">
                            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- Dirección --}}
                <section x-show="tab==='dir'">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="block font-medium mb-1">Dirección</label>
                            <input name="direccion" value="{{ old('direccion',$company->direccion) }}"
                                   class="w-full border rounded p-2">
                            @error('direccion') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Comuna</label>
                            <input name="comuna" value="{{ old('comuna',$company->comuna) }}"
                                   class="w-full border rounded p-2">
                            @error('comuna') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Región</label>
                            <input name="region" value="{{ old('region',$company->region) }}"
                                   class="w-full border rounded p-2">
                            @error('region') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- Remuneraciones --}}
                <section x-show="tab==='remu'">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Tipo contribuyente</label>
                            <select name="tipo_contribuyente" class="w-full border rounded p-2">
                                <option value="">—</option>
                                <option value="natural"  @selected(old('tipo_contribuyente',$company->tipo_contribuyente)==='natural')>Natural</option>
                                <option value="juridica" @selected(old('tipo_contribuyente',$company->tipo_contribuyente)==='juridica')>Jurídica</option>
                            </select>
                            @error('tipo_contribuyente') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block font-medium mb-1">CCAF</label>
                            <select name="ccaf_id" class="w-full border rounded p-2">
                                <option value="">Selecciona una CCAF</option>
                                @foreach($ccafs as $c)
                                    <option value="{{ $c->id }}" @selected(old('ccaf_id',$company->ccaf_id)===$c->id)>{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                            @error('ccaf_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block font-medium mb-1">Día de pago</label>
                            <select name="dia_pago" class="w-full border rounded p-2" x-model="diaPago">
                                <option value="">—</option>
                                <option value="ultimo_dia_habil">Último día hábil</option>
                                <option value="dia_fijo">Día fijo</option>
                                <option value="quincenal">Quincenal</option>
                            </select>
                            @error('dia_pago') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="diaPago==='dia_fijo'">
                            <label class="block font-medium mb-1">Día (1–31)</label>
                            <input type="number" min="1" max="31" name="dia_pago_dia"
                                   value="{{ old('dia_pago_dia',$company->dia_pago_dia) }}"
                                   class="w-full border rounded p-2">
                            @error('dia_pago_dia') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- Banco --}}
                <section x-show="tab==='banco'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Banco</label>
                            <input name="banco" value="{{ old('banco',$company->banco) }}" class="w-full border rounded p-2">
                            @error('banco') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Cuenta bancaria</label>
                            <input name="cuenta_bancaria" value="{{ old('cuenta_bancaria',$company->cuenta_bancaria) }}"
                                   class="w-full border rounded p-2">
                            @error('cuenta_bancaria') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- Representante --}}
                <section x-show="tab==='rep'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium mb-1">Nombre</label>
                            <input name="representante_nombre" value="{{ old('representante_nombre',$company->representante_nombre) }}"
                                   class="w-full border rounded p-2">
                            @error('representante_nombre') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-medium mb-1">RUT</label>
                            <input name="representante_rut" value="{{ old('representante_rut',$company->representante_rut) }}"
                                   class="w-full border rounded p-2">
                            @error('representante_rut') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Cargo</label>
                            <input name="representante_cargo" value="{{ old('representante_cargo',$company->representante_cargo) }}"
                                   class="w-full border rounded p-2">
                            @error('representante_cargo') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-medium mb-1">Email</label>
                            <input type="email" name="representante_email" value="{{ old('representante_email',$company->representante_email) }}"
                                   class="w-full border rounded p-2">
                            @error('representante_email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                {{-- Metadatos --}}
                <section x-show="tab==='meta'">
                    <label class="block font-medium mb-1">Notas / Observaciones</label>
                    <textarea name="notes" class="w-full border rounded p-2 min-h-[120px]">{{ old('notes',$company->notes) }}</textarea>
                    @error('notes') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </section>

                <div class="flex gap-2 pt-2">
                    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar ficha</button>
                    <a href="{{ route('companies.index') }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Volver</a>
                </div>
            </form>
        </div>

    </div>
    @endsection
</x-adminpanel::layouts.master>
