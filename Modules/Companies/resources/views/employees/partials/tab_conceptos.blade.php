        <div x-show="tab === 'conceptos'" x-cloak x-data="{ itemTab: 'haberes' }">

            {{-- Sub-tabs --}}
            <div class="flex gap-1 mb-5">
                @foreach (['haberes' => 'Haberes', 'descuentos' => 'Descuentos', 'creditos' => 'Créditos'] as $key => $label)
                    <button type="button" @click="itemTab = '{{ $key }}'"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition-all"
                        :class="itemTab === '{{ $key }}' ? 'bg-slate-900 text-white' :
                            'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Botón agregar ítem --}}
            <div class="flex justify-end mb-4">
                <button type="button" @click="showAddItem = !showAddItem"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Agregar ítem
                </button>
            </div>

            {{-- Panel agregar ítem --}}
            <div x-show="showAddItem" x-cloak class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-5">
                <h3 class="text-sm font-bold text-slate-700 mb-4">Nuevo Ítem</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Concepto del catálogo</label>
                        <select x-model="newItem.item_id"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            <option value="">Seleccionar concepto</option>
                            <template x-for="cat in filteredCatalog(itemTab)" :key="cat.id">
                                <option :value="cat.id" x-text="cat.name"></option>
                            </template>
                        </select>
                        <p x-show="filteredCatalog(itemTab).length === 0" class="text-xs text-slate-400 mt-1">
                            No hay conceptos en el catálogo de esta empresa. Agrégalos desde Configuración → Catálogo de
                            Ítems.
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Monto</label>
                        <input type="number" x-model="newItem.amount" placeholder="0" min="0"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Unidad</label>
                        <select x-model="newItem.unit"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            <option value="CLP">CLP ($)</option>
                            <option value="UF">UF</option>
                            <option value="UTM">UTM</option>
                            <option value="PERCENTAGE">Porcentaje (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Periodicidad</label>
                        <select x-model="newItem.periodicity"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            <option value="fixed">Fijo (todos los meses)</option>
                            <option value="variable">Variable (este mes)</option>
                        </select>
                    </div>
                    <div x-show="itemTab === 'creditos'">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Total de Cuotas</label>
                        <input type="number" x-model="newItem.total_installments" placeholder="ej: 12"
                            min="1"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3 flex justify-end gap-3">
                        <button type="button" @click="showAddItem = false"
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="button" @click="saveItem()" :disabled="saving"
                            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-colors disabled:opacity-50">
                            <span x-show="saving">Guardando...</span>
                            <span x-show="!saving">Guardar ítem</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tablas por tipo --}}
            @foreach (['haberes' => 'Haberes', 'descuentos' => 'Descuentos', 'creditos' => 'Créditos'] as $tabKey => $tabLabel)
                <div x-show="itemTab === '{{ $tabKey }}'">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <template x-if="filteredItems('{{ $tabKey }}').length === 0">
                            <div class="text-center py-12 text-slate-400">
                                <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                </svg>
                                <p class="text-sm font-medium">Sin {{ strtolower($tabLabel) }} asignados</p>
                                <p class="text-xs mt-1">Usa el botón "Agregar ítem" para asignar conceptos.</p>
                            </div>
                        </template>
                        <template x-if="filteredItems('{{ $tabKey }}').length > 0">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th
                                            class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                            Concepto</th>
                                        <th
                                            class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                            Monto</th>
                                        <th
                                            class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                            Unidad</th>
                                        <th
                                            class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                            Periodicidad</th>
                                        <th x-show="'{{ $tabKey }}' === 'creditos'"
                                            class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                            Cuotas</th>
                                        <th
                                            class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">
                                            Estado</th>
                                        <th class="px-5 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="ei in filteredItems('{{ $tabKey }}')"
                                        :key="ei.id">
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-5 py-3.5 font-medium text-slate-800" x-text="ei.item_name">
                                            </td>
                                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900"
                                                x-text="ei.unit === 'CLP' ? formatMoney(ei.amount) : ei.amount + ' ' + ei.unit">
                                            </td>
                                            <td class="px-5 py-3.5 text-center">
                                                <span
                                                    class="inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600"
                                                    x-text="ei.unit"></span>
                                            </td>
                                            <td class="px-5 py-3.5 text-center">
                                                <span
                                                    class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                                                    :class="ei.periodicity === 'fixed' ? 'bg-blue-50 text-blue-700' :
                                                        'bg-amber-50 text-amber-700'"
                                                    x-text="ei.periodicity === 'fixed' ? 'Fijo' : 'Variable'"></span>
                                            </td>
                                            <td x-show="'{{ $tabKey }}' === 'creditos'"
                                                class="px-5 py-3.5 text-center text-xs text-slate-600">
                                                <span x-text="ei.current_installment ?? 0"></span>/<span
                                                    x-text="ei.total_installments ?? '∞'"></span>
                                            </td>
                                            <td class="px-5 py-3.5 text-center">
                                                <span class="inline-block w-2 h-2 rounded-full"
                                                    :class="ei.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                            </td>
                                            <td class="px-5 py-3.5 text-right">
                                                <button type="button" @click="deleteItem(ei.id)"
                                                    class="text-slate-400 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </template>
                    </div>
                </div>
            @endforeach

        </div>
