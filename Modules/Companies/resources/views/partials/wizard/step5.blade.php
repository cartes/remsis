            <div x-show="currentStep === 5" x-cloak>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Información de Pago</p>
                <div class="space-y-6">

                    {{-- Método de pago --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-2">Medio de Pago</label>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([['transferencia','Transferencia','M13 2H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1v5h5M9 13h6M9 17h6M9 9h2'],['cheque','Cheque','M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],['efectivo','Efectivo','M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z']] as [$val,$label,$icon])
                            <button type="button" @click="form.payment_method = '{{ $val }}'"
                                class="flex flex-col items-center gap-2 rounded-xl border-2 px-4 py-4 transition-all"
                                :class="form.payment_method === '{{ $val }}'
                                    ? 'border-slate-900 bg-slate-900 text-white shadow-md'
                                    : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300 hover:bg-slate-50'">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/>
                                </svg>
                                <span class="text-sm font-semibold">{{ $label }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Datos bancarios (solo si Transferencia) --}}
                    <div x-show="form.payment_method === 'transferencia'"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="grid grid-cols-2 gap-x-6 gap-y-5 pt-1"
                    >
                        <p class="col-span-2 text-xs font-semibold text-slate-500 uppercase tracking-widest">Datos Bancarios</p>

                        {{-- Banco --}}
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Banco <span class="text-red-400">*</span></label>
                            <select x-model="form.bank_id"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
                                :class="errors.bank_id ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                                <option value="">Seleccionar banco…</option>
                                <template x-for="banco in bancos" :key="banco.id">
                                    <option :value="banco.id" x-text="banco.nombre ?? banco.name"></option>
                                </template>
                            </select>
                            <p x-show="errors.bank_id" class="mt-1 text-xs text-red-500" x-text="errors.bank_id"></p>
                        </div>

                        {{-- Número de cuenta --}}
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1.5">Número de Cuenta <span class="text-red-400">*</span></label>
                            <input type="text" x-model="form.bank_account_number" placeholder="Ej: 00012345678"
                                class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 font-mono"
                                :class="errors.bank_account_number ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                            <p x-show="errors.bank_account_number" class="mt-1 text-xs text-red-500" x-text="errors.bank_account_number"></p>
                        </div>

                        {{-- Tipo de cuenta --}}
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-2">Tipo de Cuenta <span class="text-red-400">*</span></label>
                            <div class="flex gap-3">
                                @foreach([['corriente','Cuenta Corriente'],['vista','Cuenta Vista / RUT'],['ahorro','Cuenta de Ahorro']] as [$v,$l])
                                <button type="button" @click="form.bank_account_type = '{{ $v }}'"
                                    class="flex-1 rounded-lg border py-2.5 text-sm font-medium transition-all"
                                    :class="form.bank_account_type === '{{ $v }}'
                                        ? 'bg-slate-900 border-slate-900 text-white shadow-sm'
                                        : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300'">
                                    {{ $l }}
                                </button>
                                @endforeach
                            </div>
                            <p x-show="errors.bank_account_type" class="mt-1 text-xs text-red-500" x-text="errors.bank_account_type"></p>
                        </div>

                    </div>

                    {{-- Mensaje info para efectivo/cheque --}}
                    <div x-show="form.payment_method !== 'transferencia'"
                        x-transition:enter="ease-out duration-200"
