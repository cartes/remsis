            <div x-show="currentStep === 3" x-cloak>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Seguridad Social</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-5">

                    {{-- AFP --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">AFP</label>
                        <select x-model="form.afp_id"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="">No cotiza (Exento / Jubilado)</option>
                            <template x-for="afp in afps" :key="afp.id">
                                <option :value="afp.id" x-text="afp.nombre"></option>
                            </template>
                        </select>
                    </div>

                    {{-- CCAF --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Caja de Compensación (CCAF)</label>
                        <select x-model="form.ccaf_id"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="">Sin CCAF</option>
                            <template x-for="ccaf in ccafs" :key="ccaf.id">
                                <option :value="ccaf.id" x-text="ccaf.nombre"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Sistema de Salud --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-2">Sistema de Salud</label>
                        <div class="flex gap-3">
                            <button type="button"
                                @click="form.health_system = 'fonasa'; form.isapre_id = ''; form.health_contribution = ''"
                                class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-all"
                                :class="form.health_system === 'fonasa'
                                    ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                                    : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                                FONASA
                            </button>
                            <button type="button"
                                @click="form.health_system = 'isapre'"
                                class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-all"
                                :class="form.health_system === 'isapre'
                                    ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                                    : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                                ISAPRE
                            </button>
                        </div>
                    </div>

                    {{-- Campos Isapre (condicional) --}}
                    <div x-show="form.health_system === 'isapre'" class="col-span-2 grid grid-cols-2 gap-x-6 gap-y-4 pt-1"
                        x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Isapre</label>
                            <select x-model="form.isapre_id"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                                <option value="">Seleccionar Isapre…</option>
                                <template x-for="isa in isapres" :key="isa.id">
                                    <option :value="isa.id" x-text="isa.nombre"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Monto plan (UF)</label>
                            <input type="number" x-model="form.health_contribution" placeholder="Ej: 3.25" step="0.01" min="0"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                        </div>
                    </div>

                    {{-- APV --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">APV mensual ($)</label>
                        <input type="number" x-model="form.apv_amount" placeholder="0" min="0"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                        <p class="mt-1 text-[10px] text-slate-400">Ahorro Previsional Voluntario</p>
                    </div>

                </div>
            </div>
