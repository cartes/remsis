{{-- Modal Detalle de Nómina --}}
<div x-show="showPayrollModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" x-cloak
    class="fixed inset-0 bg-gray-500 bg-opacity-75 z-50 flex items-center justify-center p-4">

    <div @click.away="showPayrollModal = false"
        class="bg-white rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden transform transition-all flex flex-col max-h-[90vh]">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">
                    <i class="fas fa-file-invoice-dollar text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Ficha de Nómina</h3>
                    <p class="text-xs text-gray-500" x-text="selectedEmployee.user.name"></p>
                </div>
            </div>
            <button @click="showPayrollModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- Tabs Modal --}}
        <div class="flex border-b border-gray-200 bg-white overflow-x-auto">
            <button @click="activePayrollTab = 'personal'"
                :class="activePayrollTab === 'personal' ? 'border-blue-500 text-blue-600 bg-blue-50' :
                    'border-transparent text-gray-500 hover:text-gray-700'"
                class="flex-1 py-3 px-4 text-center border-b-2 font-bold text-xs uppercase tracking-wider transition-all whitespace-nowrap">
                <i class="fas fa-user mr-1"></i> Personales
            </button>
            <button @click="activePayrollTab = 'laboral'"
                :class="activePayrollTab === 'laboral' ? 'border-blue-500 text-blue-600 bg-blue-50' :
                    'border-transparent text-gray-500 hover:text-gray-700'"
                class="flex-1 py-3 px-4 text-center border-b-2 font-bold text-xs uppercase tracking-wider transition-all whitespace-nowrap">
                <i class="fas fa-briefcase mr-1"></i> Contrato
            </button>
            <button @click="activePayrollTab = 'prevision'"
                :class="activePayrollTab === 'prevision' ? 'border-blue-500 text-blue-600 bg-blue-50' :
                    'border-transparent text-gray-500 hover:text-gray-700'"
                class="flex-1 py-3 px-4 text-center border-b-2 font-bold text-xs uppercase tracking-wider transition-all whitespace-nowrap">
                <i class="fas fa-shield-alt mr-1"></i> Previsión
            </button>
            <button @click="activePayrollTab = 'remuneracion'"
                :class="activePayrollTab === 'remuneracion' ? 'border-blue-500 text-blue-600 bg-blue-50' :
                    'border-transparent text-gray-500 hover:text-gray-700'"
                class="flex-1 py-3 px-4 text-center border-b-2 font-bold text-xs uppercase tracking-wider transition-all whitespace-nowrap">
                <i class="fas fa-money-bill-wave mr-1"></i> Remuneración
            </button>
            <button @click="activePayrollTab = 'banco'"
                :class="activePayrollTab === 'banco' ? 'border-blue-500 text-blue-600 bg-blue-50' :
                    'border-transparent text-gray-500 hover:text-gray-700'"
                class="flex-1 py-3 px-4 text-center border-b-2 font-bold text-xs uppercase tracking-wider transition-all whitespace-nowrap">
                <i class="fas fa-university mr-1"></i> Bancarios
            </button>
            <button @click="activePayrollTab = 'emergencia'"
                :class="activePayrollTab === 'emergencia' ? 'border-blue-500 text-blue-600 bg-blue-50' :
                    'border-transparent text-gray-500 hover:text-gray-700'"
                class="flex-1 py-3 px-4 text-center border-b-2 font-bold text-xs uppercase tracking-wider transition-all whitespace-nowrap">
                <i class="fas fa-phone-alt mr-1"></i> Emergencia
            </button>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto p-6">
            {{-- Tab: Datos Personales --}}
            <div x-show="activePayrollTab === 'personal'" class="space-y-4">
                <div
                    class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-gray-50/80 p-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <template x-if="currentEmployeePhotoUrl()">
                            <img :src="currentEmployeePhotoUrl()" :alt="`Foto de ${selectedEmployee.user.name}`"
                                class="h-16 w-16 rounded-full border border-gray-200 object-cover shadow-sm">
                        </template>
                        <template x-if="!currentEmployeePhotoUrl()">
                            <div
                                class="flex h-16 w-16 items-center justify-center rounded-full border border-blue-100 bg-blue-100 text-lg font-bold uppercase text-blue-700 shadow-sm">
                                <span x-text="employeeInitials(selectedEmployee.user.name)"></span>
                            </div>
                        </template>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-gray-900" x-text="selectedEmployee.user.name"></p>
                            <p class="text-xs text-gray-500" x-text="selectedEmployee.user.email"></p>
                            <p class="mt-1 text-[11px] text-gray-500">JPG, PNG o WEBP hasta 2 MB.</p>
                        </div>
                    </div>

                    <div class="flex flex-col items-start gap-2 md:items-end">
                        <input x-ref="profilePhotoInput" type="file" class="hidden"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            @change="handleProfilePhotoChange($event)">
                        <button type="button" @click="$refs.profilePhotoInput.click()"
                            class="inline-flex items-center gap-2 rounded-lg border border-blue-100 bg-white px-3 py-2 text-xs font-semibold text-blue-600 transition-all hover:border-blue-200 hover:bg-blue-50">
                            <i class="fas fa-camera"></i>
                            <span
                                x-text="selectedEmployee.user.profile_photo_url ? 'Cambiar foto' : 'Subir foto'"></span>
                        </button>
                        <template x-if="selectedProfilePhoto">
                            <p class="text-[11px] text-gray-500" x-text="selectedProfilePhoto.name"></p>
                        </template>
                        <template x-if="errors.profile_photo">
                            <p class="text-[10px] font-bold text-red-500" x-text="errors.profile_photo[0]"></p>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nombres</label>
                        <input type="text" x-model="selectedEmployee.first_name"
                            :class="errors.first_name ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <template x-if="errors.first_name">
                            <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.first_name[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Apellidos</label>
                        <input type="text" x-model="selectedEmployee.last_name"
                            :class="errors.last_name ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <template x-if="errors.last_name">
                            <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.last_name[0]"></p>
                        </template>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">RUT</label>
                        <input type="text" x-model="selectedEmployee.rut"
                            :class="errors.rut ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 font-mono">
                        <template x-if="errors.rut">
                            <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.rut[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Fecha
                            Nacimiento</label>
                        <input type="date" x-model="selectedEmployee.birth_date"
                            :class="errors.birth_date ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <template x-if="errors.birth_date">
                            <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.birth_date[0]"></p>
                        </template>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Email</label>
                        <input type="email" x-model="selectedEmployee.email"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Teléfono</label>
                        <input type="text" x-model="selectedEmployee.phone"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nacionalidad</label>
                        <input type="text" x-model="selectedEmployee.nationality"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Estado Civil</label>
                        <select x-model="selectedEmployee.marital_status"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccione...</option>
                            <option value="single">Soltero/a</option>
                            <option value="married">Casado/a</option>
                            <option value="divorced">Divorciado/a</option>
                            <option value="widowed">Viudo/a</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Dirección</label>
                    <input type="text" x-model="selectedEmployee.address"
                        :class="errors.address ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                        class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    <template x-if="errors.address">
                        <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.address[0]"></p>
                    </template>
                </div>
            </div>

            {{-- Tab: Datos Laborales --}}
            <div x-show="activePayrollTab === 'laboral'" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Cargo /
                            Posición</label>
                        <input type="text" x-model="selectedEmployee.position"
                            :class="errors.position ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <template x-if="errors.position">
                            <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.position[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Fecha
                            Contratación</label>
                        <input type="date" x-model="selectedEmployee.hire_date"
                            :class="errors.hire_date ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <template x-if="errors.hire_date">
                            <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.hire_date[0]"></p>
                        </template>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tipo Contrato</label>
                        <input type="text" x-model="selectedEmployee.contract_type"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                            placeholder="Ej: Indefinido, Plazo Fijo">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Jornada Laboral</label>
                        <select x-model="selectedEmployee.work_schedule_type"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccione...</option>
                            <option value="full_time">Full Time</option>
                            <option value="part_time">Part Time</option>
                        </select>
                    </div>
                </div>

                <div x-show="selectedEmployee.work_schedule_type === 'part_time'"
                    class="space-y-4 pt-4 border-t border-gray-100" x-transition x-cloak>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Horas Semanales</label>
                        <input type="number" step="0.5" x-model="selectedEmployee.part_time_hours"
                            class="w-full md:w-1/2 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                            placeholder="Ej: 20">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Horario en la
                            semana</label>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-3 py-2 font-bold w-24">Día</th>
                                        <th class="px-3 py-2 font-bold text-center">Activo</th>
                                        <th class="px-3 py-2 font-bold">Entrada</th>
                                        <th class="px-3 py-2 font-bold">Salida</th>
                                        <th class="px-3 py-2 font-bold">Colación (min)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    <template x-for="(day, key) in selectedEmployee.part_time_schedule"
                                        :key="key">
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-3 py-2 font-medium text-gray-800 capitalize"
                                                x-text="day.label"></td>
                                            <td class="px-3 py-2 text-center">
                                                <input type="checkbox" x-model="day.active"
                                                    class="rounded text-blue-600 focus:ring-blue-500 border-gray-300">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="time" x-model="day.start" :disabled="!day.active"
                                                    class="border-gray-200 rounded-md text-xs p-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400 w-24">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="time" x-model="day.end" :disabled="!day.active"
                                                    class="border-gray-200 rounded-md text-xs p-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400 w-24">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" x-model="day.break" :disabled="!day.active"
                                                    min="0" step="1"
                                                    class="border-gray-200 rounded-md text-xs p-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400 w-16">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Centro de Costo</label>
                    <template x-if="!costCenters || costCenters.length === 0">
                        <div
                            class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 flex flex-col items-center justify-center gap-2 text-center">
                            <div class="text-yellow-600 flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span class="text-xs font-semibold">Empresa sin centros de costo creados</span>
                            </div>
                            <a href="{{ route('companies.edit', ['company' => $company->id, 'section' => 'remunerations', 'tab' => 'cost-centers']) }}"
                                class="inline-flex items-center gap-1 bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs font-bold hover:bg-blue-700 transition-colors shadow-sm">
                                <i class="fas fa-plus-circle"></i> Crear Centros de Costo
                            </a>
                        </div>
                    </template>

                    <template x-if="costCenters && costCenters.length > 0">
                        <select x-model="selectedEmployee.cost_center_id"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Sin asignar</option>
                            <template x-for="cc in costCenters" :key="cc.id">
                                <option :value="cc.id" x-text="`${cc.code} - ${cc.name}`"></option>
                            </template>
                        </select>
                    </template>
                </div>
            </div>

            {{-- Tab: Previsión Social --}}
            <div x-show="activePayrollTab === 'prevision'" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">AFP</label>
                    <select x-model="selectedEmployee.afp_id"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione AFP</option>
                        <template x-for="afp in afps" :key="afp.id">
                            <option :value="afp.id" x-text="afp.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Salud
                        (Isapre/Fonasa)</label>
                    <select x-model="selectedEmployee.isapre_id"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione Salud</option>
                        <template x-for="isa in isapres" :key="isa.id">
                            <option :value="isa.id" x-text="isa.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Monto Adicional
                        Salud</label>
                    <input type="number" step="0.01" x-model="selectedEmployee.health_contribution"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                        placeholder="0.00">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Caja de Compensación
                        (CCAF)</label>
                    <select x-model="selectedEmployee.ccaf_id"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione CCAF</option>
                        <template x-for="ccaf in ccafs" :key="ccaf.id">
                            <option :value="ccaf.id" x-text="ccaf.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">APV (Ahorro Previsional
                        Voluntario)</label>
                    <input type="number" step="0.01" x-model="selectedEmployee.apv_amount"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                        placeholder="0.00">
                </div>
            </div>

            {{-- Tab: Remuneración --}}
            <div x-show="activePayrollTab === 'remuneracion'" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Sueldo Base</label>
                        <input type="number" x-model="selectedEmployee.salary"
                            :class="errors.salary ? 'border-red-500 ring-red-100' : 'border-gray-200'"
                            class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <template x-if="errors.salary">
                            <p class="text-[10px] text-red-500 font-bold mt-1" x-text="errors.salary[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tipo Sueldo</label>
                        <select x-model="selectedEmployee.salary_type"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccione...</option>
                            <option value="mensual">Mensual</option>
                            <option value="quincenal">Quincenal</option>
                            <option value="semanal">Semanal</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Cargas Familiares</label>
                    <input type="number" min="0" x-model="selectedEmployee.num_dependents"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            {{-- Tab: Datos Bancarios --}}
            <div x-show="activePayrollTab === 'banco'" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Banco</label>
                    <select x-model="selectedEmployee.bank_id"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione Banco</option>
                        <template x-for="banco in bancos" :key="banco.id">
                            <option :value="banco.id" x-text="banco.nombre"></option>
                        </template>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nº Cuenta</label>
                        <input type="text" x-model="selectedEmployee.bank_account_number"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Tipo Cuenta</label>
                        <select x-model="selectedEmployee.bank_account_type"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccione...</option>
                            <option value="corriente">Corriente</option>
                            <option value="vista">Vista / RUT</option>
                            <option value="ahorro">Ahorro</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Tab: Contacto Emergencia --}}
            <div x-show="activePayrollTab === 'emergencia'" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nombre Contacto</label>
                    <input type="text" x-model="selectedEmployee.emergency_contact_name"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Teléfono Contacto</label>
                    <input type="text" x-model="selectedEmployee.emergency_contact_phone"
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
            <button @click="showPayrollModal = false"
                class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-100 transition-all">
                Cerrar
            </button>
            <button @click="updatePayroll" :disabled="payrollLoading"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 shadow-md transition-all flex items-center justify-center gap-2 disabled:opacity-50">
                <i class="fas fa-spinner fa-spin" x-show="payrollLoading"></i>
                <i class="fas fa-save" x-show="!payrollLoading"></i>
                <span x-text="payrollLoading ? 'Guardando...' : 'Guardar Cambios'"></span>
            </button>
        </div>
    </div>
</div>
