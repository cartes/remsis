            <div x-show="currentStep === 2" x-cloak>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Información Laboral</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-5">

                    {{-- Cargo --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Cargo / Puesto</label>
                        <input type="text" x-model="form.position" placeholder="Ej: Analista de RRHH"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                    {{-- Centro de costo --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Centro de Costo</label>
                        <select x-model="form.cost_center_id"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="">Sin asignar</option>
                            <template x-for="cc in costCenters" :key="cc.id">
                                <option :value="cc.id" x-text="`${cc.code} — ${cc.name}`"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Fecha de ingreso --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Fecha de Ingreso</label>
                        <input type="date" x-model="form.hire_date"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                    {{-- Tipo de contrato --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Tipo de Contrato</label>
                        <select x-model="form.contract_type"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="indefinido">Indefinido</option>
                            <option value="plazo_fijo">Plazo Fijo</option>
                            <option value="obra_faena">Por Obra o Faena</option>
                            <option value="honorarios">Honorarios</option>
                            <option value="part_time">Part-time</option>
                        </select>
                    </div>

                    {{-- Jornada --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-2">Jornada de Trabajo</label>
                        <div class="flex gap-3">
                            <button type="button" @click="form.work_schedule_type = 'full_time'"
                                class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-all"
                                :class="form.work_schedule_type === 'full_time'
                                    ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                                    : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                                Jornada Completa
                            </button>
                            <button type="button" @click="form.work_schedule_type = 'part_time'"
                                class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-all"
                                :class="form.work_schedule_type === 'part_time'
                                    ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                                    : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                                Jornada Parcial
                            </button>
                        </div>
                    </div>

                </div>
            </div>
