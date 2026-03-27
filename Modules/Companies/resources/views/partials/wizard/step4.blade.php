            <div x-show="currentStep === 4" x-cloak>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Remuneración</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-5">

                    {{-- Sueldo base --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Sueldo Base ($)</label>
                        <input type="number" x-model="form.salary" placeholder="Ej: 650000" min="0"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                    {{-- Periodicidad --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Periodicidad de pago</label>
                        <select x-model="form.salary_type"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="mensual">Mensual</option>
                            <option value="quincenal">Quincenal</option>
                            <option value="semanal">Semanal</option>
                        </select>
                    </div>

                    {{-- Gratificación --}}
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-2">Gratificación Legal</label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([['art_47','Art. 47 (anual)'],['art_50','Art. 50 (mensual)'],['sin_gratificacion','Sin gratificación']] as [$val,$label])
                            <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-2.5 cursor-pointer hover:bg-slate-100 transition-colors"
                                @click="form.gratificacion = '{{ $val }}'">
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all"
                                    :class="form.gratificacion === '{{ $val }}' ? 'border-slate-900' : 'border-slate-300'">
                                    <div class="w-2 h-2 rounded-full bg-slate-900 transition-all"
                                        :class="form.gratificacion === '{{ $val }}' ? 'opacity-100' : 'opacity-0'"></div>
                                </div>
                                <span class="text-xs font-medium text-slate-700">{{ $label }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Asignación de movilización y colación — ahora gestionadas desde la ficha del colaborador vía Ítems --}}
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-slate-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-xs font-semibold text-slate-700 mb-1">Colación y Movilización</p>
                                <p class="text-xs text-slate-500">Estos haberes se configuran desde la ficha del colaborador → pestaña <strong>Ítems</strong>, una vez creado el registro.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Cargas familiares --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">N° de Cargas Familiares</label>
                        <input type="number" x-model="form.num_dependents" placeholder="0" min="0"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                </div>
            </div>
