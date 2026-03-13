{{-- Modal Ficha Honorario --}}
<div x-show="showDetailsModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak
    class="fixed inset-0 bg-gray-900 bg-opacity-75 z-[60] flex items-center justify-center p-4 sm:p-6 backdrop-blur-sm">

    <div @click.away="showDetailsModal = false"
        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden transform transition-all">

        {{-- Encabezado Modal --}}
        <div class="px-6 py-5 border-b border-gray-100 bg-white flex justify-between items-center z-10 shadow-sm relative">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 font-bold text-lg">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black text-gray-800 tracking-tight" x-text="isEditing ? 'Ficha Colaborador Honorarios' : 'Nuevo Colaborador a Honorarios'"></h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-gray-500 text-xs font-medium" x-show="isEditing" x-text="form.first_name + ' ' + form.last_name"></span>
                        <span x-show="isEditing" class="w-1 h-1 bg-gray-300 rounded-full"></span>
                        <span class="text-indigo-600 text-xs font-bold" x-show="isEditing" x-text="form.rut"></span>
                    </div>
                </div>
            </div>
            
            <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 w-8 h-8 rounded-lg flex items-center justify-center transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Loader (cuando carga datos) --}}
        <div x-show="modalLoading" class="absolute inset-0 z-50 bg-white bg-opacity-80 flex items-center justify-center backdrop-blur-sm">
            <div class="flex flex-col items-center">
                <i class="fas fa-circle-notch fa-spin text-4xl text-indigo-500 mb-3"></i>
                <p class="text-indigo-600 font-bold text-sm tracking-widest uppercase">Cargando Datos...</p>
            </div>
        </div>

        {{-- Tabs de Navegación --}}
        <div class="flex border-b border-gray-100 bg-gray-50/50 px-6 gap-6 shrink-0 pt-2" x-show="isEditing">
            <button @click="activeTab = 'personal'"
                class="pb-3 text-sm font-bold border-b-2 transition-colors relative"
                :class="activeTab === 'personal' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-400 hover:text-gray-600'">
                <i class="fas fa-user-tie mb-1"></i> Ficha Personal
            </button>
            <button @click="activeTab = 'boletas'"
                class="pb-3 text-sm font-bold border-b-2 transition-colors relative"
                :class="activeTab === 'boletas' ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-gray-400 hover:text-gray-600'">
                <i class="fas fa-file-invoice-dollar mb-1"></i> Historial Boletas
                <span class="ml-2 bg-indigo-100 text-indigo-700 py-0.5 px-2 rounded-full text-[10px]" x-text="form.receipts?.length || 0"></span>
            </button>
        </div>

        {{-- Cuerpo Principal (Scrollable) --}}
        <div class="flex-1 overflow-y-auto px-6 py-6 bg-slate-50/50 relative">
            
            {{-- TAB: FICHA PERSONAL --}}
            <div x-show="activeTab === 'personal'" x-transition:enter="transition ease-out duration-200 delay-100"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                class="space-y-8">
                
                <form id="freelancerForm" @submit.prevent="saveFreelancer">
                    {{-- 1. Información Básica --}}
                    <div>
                        <h4 class="text-sm font-black text-gray-800 uppercase tracking-wider mb-4 border-b border-gray-200 pb-2">Información Básica</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombres <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.first_name" required
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-white"
                                    :class="errors.first_name ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-gray-300'">
                                <p x-show="errors.first_name" class="mt-1 text-xs text-red-500 font-medium" x-text="errors.first_name?.[0]"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Apellidos <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.last_name" required
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                                    :class="errors.last_name ? 'border-red-400' : 'border-gray-300'">
                                <p x-show="errors.last_name" class="mt-1 text-xs text-red-500 font-medium" x-text="errors.last_name?.[0]"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">RUT <span class="text-red-500">*</span></label>
                                <input type="text" x-model="form.rut" required placeholder="12345678-9"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                                    :class="errors.rut ? 'border-red-400' : 'border-gray-300'">
                                <p x-show="errors.rut" class="mt-1 text-xs text-red-500 font-medium" x-text="errors.rut?.[0]"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Profesión / Servicio a Prestar</label>
                                <input type="text" x-model="form.profession"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Correo Electrónico</label>
                                <input type="email" x-model="form.email"
                                    class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                                    :class="errors.email ? 'border-red-400' : 'border-gray-300'">
                                <p x-show="errors.email" class="mt-1 text-xs text-red-500 font-medium" x-text="errors.email?.[0]"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Teléfono</label>
                                <input type="text" x-model="form.phone"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Dirección</label>
                                <input type="text" x-model="form.address"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            </div>
                        </div>
                    </div>

                    {{-- 2. Condiciones Honorarios --}}
                    <div class="mt-8">
                        <h4 class="text-sm font-black text-gray-800 uppercase tracking-wider mb-4 border-b border-gray-200 pb-2">Condiciones de Honorarios</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Honorario Bruto Base Referencial</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" x-model="form.default_gross_fee" min="0" step="1"
                                        class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                </div>
                                <p class="mt-1 text-[10px] text-gray-400">Monto utilizado como cálculo base autollenado en boletas.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tasa de Retención por Defecto (%) <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="number" x-model="form.default_retention_rate" min="0" max="100" step="0.25" required
                                        class="w-full pr-8 pl-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">%</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-[10px] text-gray-400">Tasa legal actual (ej. 13.75%).</p>
                            </div>
                            
                            <div class="flex items-center mt-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-700">Estado Activo</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Datos Bancarios --}}
                    <div class="mt-8">
                        <h4 class="text-sm font-black text-gray-800 uppercase tracking-wider mb-4 border-b border-gray-200 pb-2">Información de Pago (Banco)</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Banco</label>
                                <select x-model="form.bank_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                    <option value="">Seleccione un banco</option>
                                    <template x-for="bank in banks" :key="bank.id">
                                        <option :value="bank.id" x-text="bank.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tipo de Cuenta</label>
                                <select x-model="form.bank_account_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                    <option value="">Seleccione tipo</option>
                                    <option value="vista">Cuenta Vista</option>
                                    <option value="corriente">Cuenta Corriente</option>
                                    <option value="ahorro">Cuenta de Ahorro</option>
                                    <option value="rut">Cuenta RUT</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nº Cuenta</label>
                                <input type="text" x-model="form.bank_account_number" placeholder="Ej: 123456789"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TAB: BOLETAS --}}
            <div x-show="activeTab === 'boletas'" style="display: none;">
                
                {{-- Formulario Registro de Boleta --}}
                <div x-show="showReceiptForm" class="bg-indigo-50 border border-indigo-100 rounded-xl p-5 mb-6" x-transition>
                    <h4 class="text-sm font-black text-indigo-900 uppercase tracking-wider mb-4 border-b border-indigo-200 pb-2" x-text="receiptForm.id ? 'Editar Boleta' : 'Ingresar Nueva Boleta'"></h4>
                    
                    <form @submit.prevent="saveReceipt">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">Número de Boleta</label>
                                <input type="text" x-model="receiptForm.receipt_number"
                                    class="w-full px-3 py-2 border border-indigo-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <p x-show="receiptErrors.receipt_number" class="mt-1 text-xs text-red-500 font-medium" x-text="receiptErrors.receipt_number?.[0]"></p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">Fecha Emisión <span class="text-red-500">*</span></label>
                                <input type="date" x-model="receiptForm.issue_date" required
                                    class="w-full px-3 py-2 border border-indigo-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <p x-show="receiptErrors.issue_date" class="mt-1 text-xs text-red-500 font-medium" x-text="receiptErrors.issue_date?.[0]"></p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">Emisor (Quién emite)</label>
                                <select x-model="receiptForm.issuer" class="w-full px-3 py-2 border border-indigo-300 rounded-lg text-sm focus:ring-indigo-500">
                                    <option value="freelancer">Colaborador (Freelancer)</option>
                                    <option value="company">Empresa (Terceros)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">Estado de Pago</label>
                                <select x-model="receiptForm.status" class="w-full px-3 py-2 border border-indigo-300 rounded-lg text-sm focus:ring-indigo-500">
                                    <option value="pending">Pendiente</option>
                                    <option value="paid">Pagada / Procesada</option>
                                    <option value="annulled">Anulada</option>
                                </select>
                            </div>
                            
                            <!-- Calculadora de montos -->
                            <div class="md:col-span-4 grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t border-indigo-200 mt-2">
                                <div>
                                    <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">Monto Bruto <span class="text-[9px] text-gray-500 font-normal lowercase">(Ingresar altera el resto)</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500">$</span></div>
                                        <input type="number" x-model="receiptForm.gross_amount" @input="calculateAmounts('gross')" min="0" required
                                            class="w-full pl-6 pr-3 py-2 border border-indigo-300 rounded-lg text-sm font-mono font-bold">
                                    </div>
                                    <p x-show="receiptErrors.gross_amount" class="mt-1 text-xs text-red-500 font-medium" x-text="receiptErrors.gross_amount?.[0]"></p>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">Retención Impuesto <span class="text-[9px] text-gray-500 font-normal" x-text="`(${form.default_retention_rate}%)`"></span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-gray-500">$</span></div>
                                        <input type="number" x-model="receiptForm.retention_amount" readonly
                                            class="w-full pl-6 pr-3 py-2 border border-indigo-200 bg-indigo-50/50 rounded-lg text-sm font-mono text-indigo-900 pointer-events-none">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-indigo-700 uppercase mb-1">Monto Líquido a Pagar <span class="text-[9px] text-gray-500 font-normal lowercase">(Autocalcula bruto)</span></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><span class="text-green-600 font-bold">$</span></div>
                                        <input type="number" x-model="receiptForm.net_amount" @input="calculateAmounts('net')" min="0" required
                                            class="w-full pl-6 pr-3 py-2 border border-green-400 bg-green-50 rounded-lg text-sm font-mono font-black text-green-700 focus:ring-green-500 focus:border-green-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-3 mt-5">
                            <button type="button" @click="cancelReceipt" class="px-4 py-2 border border-indigo-200 text-indigo-700 rounded-lg text-sm font-semibold hover:bg-indigo-100 transition-colors">Cancelar</button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-sm flex items-center gap-2 disabled:opacity-50" :disabled="receiptFormLoading">
                                <i class="fas fa-spinner fa-spin" x-show="receiptFormLoading"></i>
                                <span x-text="receiptFormLoading ? 'Guardando...' : 'Guardar Boleta'"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-bold text-gray-800 tracking-tight">Registro de Boletas</h4>
                    <button type="button" @click="openAddReceipt" x-show="!showReceiptForm"
                        class="bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-lg hover:bg-indigo-200 transition-colors text-xs font-bold flex items-center gap-2 border border-indigo-200 shadow-sm">
                        <i class="fas fa-plus"></i> Ingresar Boleta
                    </button>
                </div>

                {{-- Tabla de Boletas --}}
                <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 font-bold tracking-wider text-gray-500 uppercase text-[10px]">
                            <tr>
                                <th class="px-4 py-3 text-left">Nº Boleta / Fecha</th>
                                <th class="px-4 py-3 text-right">Bruto</th>
                                <th class="px-4 py-3 text-right">Retención</th>
                                <th class="px-4 py-3 text-right text-indigo-600">Líquido</th>
                                <th class="px-4 py-3 text-center">Estado</th>
                                <th class="px-4 py-3 pr-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <template x-if="form.receipts?.length === 0">
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                        <i class="fas fa-file-invoice-dollar text-3xl mb-2 opacity-50"></i>
                                        <p class="text-sm">No se han registrado boletas para este prestador.</p>
                                    </td>
                                </tr>
                            </template>
                            
                            <template x-for="(receipt, idx) in form.receipts" :key="receipt.id">
                                <tr class="hover:bg-gray-50" :class="{'opacity-50': receipt.status === 'annulled'}">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-800 text-sm">
                                            Nº <span x-text="receipt.receipt_number || 'S/N'"></span>
                                        </div>
                                        <div class="text-[11px] text-gray-500 font-mono" x-text="formatDate(receipt.issue_date)"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-sm text-gray-600" x-text="formatMoney(receipt.gross_amount)"></td>
                                    <td class="px-4 py-3 text-right font-mono text-sm text-red-500">
                                        <span x-text="formatMoney(receipt.retention_amount)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono text-sm font-black text-green-600 bg-green-50/30" x-text="formatMoney(receipt.net_amount)"></td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        <span x-show="receipt.status === 'pending'" class="px-2 py-1 bg-yellow-100 text-yellow-800 border border-yellow-200 rounded-md font-bold text-[10px] uppercase">Pendiente</span>
                                        <span x-show="receipt.status === 'paid'" class="px-2 py-1 bg-green-100 text-green-800 border border-green-200 rounded-md font-bold text-[10px] uppercase">Pagada</span>
                                        <span x-show="receipt.status === 'annulled'" class="px-2 py-1 bg-red-100 text-red-800 border border-red-200 rounded-md font-bold text-[10px] uppercase">Anulada</span>
                                    </td>
                                    <td class="px-4 py-3 pr-6 text-right border-l border-transparent flex justify-end gap-2">
                                        <button @click="editReceipt(idx)" class="text-indigo-500 hover:text-indigo-800 hover:bg-indigo-50 w-7 h-7 flex items-center justify-center rounded transition-colors" title="Editar boleta">
                                            <i class="fas fa-edit text-xs"></i>
                                        </button>
                                        <button @click="deleteReceipt(receipt.id)" class="text-red-400 hover:text-red-700 hover:bg-red-50 w-7 h-7 flex items-center justify-center rounded transition-colors" title="Eliminar boleta">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        {{-- Footer Acciones --}}
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-2xl shrink-0" x-show="activeTab === 'personal'">
            <button type="button" @click="showDetailsModal = false"
                class="px-5 py-2.5 border border-gray-200 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-100 transition-all font-medium">
                Cancelar
            </button>
            <button type="submit" form="freelancerForm" :disabled="loading"
                class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 shadow-md shadow-indigo-500/30 transition-all flex items-center justify-center gap-2 outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-spinner fa-spin" x-show="loading" style="display: none;"></i>
                <i class="fas fa-save" x-show="!loading"></i>
                <span x-text="loading ? 'Guardando...' : 'Guardar Cambios'"></span>
            </button>
        </div>
    </div>
</div>
