            <div x-show="currentStep === 1" x-cloak>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-5">Datos Personales</p>
                <div class="grid grid-cols-2 gap-x-6 gap-y-5">

                    {{-- Nombre --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Nombres <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.first_name" placeholder="Ej: María Fernanda"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
                            :class="errors.first_name ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                        <p x-show="errors.first_name" class="mt-1 text-xs text-red-500" x-text="errors.first_name"></p>
                    </div>

                    {{-- Apellidos --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Apellidos <span class="text-red-400">*</span></label>
                        <input type="text" x-model="form.last_name" placeholder="Ej: González Soto"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
                            :class="errors.last_name ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                        <p x-show="errors.last_name" class="mt-1 text-xs text-red-500" x-text="errors.last_name"></p>
                    </div>

                    {{-- RUT --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">RUT</label>
                        <input type="text" :value="form.rut" @input="onRutInput($event.target.value)" placeholder="Ej: 12.345.678-9"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400 font-mono tracking-wide"
                            :class="errors.rut ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                        <p x-show="errors.rut" class="mt-1 text-xs text-red-500" x-text="errors.rut"></p>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Correo electrónico <span class="text-red-400">*</span></label>
                        <input type="email" x-model="form.email" placeholder="correo@empresa.cl"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
                            :class="errors.email ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                        <p x-show="errors.email" class="mt-1 text-xs text-red-500" x-text="errors.email"></p>
                    </div>

                    {{-- Contraseña --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Contraseña de acceso <span class="text-red-400">*</span></label>
                        <input type="password" x-model="form.password" placeholder="Mínimo 6 caracteres"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400"
                            :class="errors.password ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-white'">
                        <p x-show="errors.password" class="mt-1 text-xs text-red-500" x-text="errors.password"></p>
                    </div>

                    {{-- Fecha de nacimiento --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Fecha de nacimiento</label>
                        <input type="date" x-model="form.birth_date"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                    {{-- Nacionalidad (Buscador) --}}
                    <div class="relative" @click.away="nationalityDropdownOpen = false">
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Nacionalidad</label>

                        <div @click="nationalityDropdownOpen = !nationalityDropdownOpen"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus-within:ring-2 focus-within:ring-slate-900/10 focus-within:border-slate-400 cursor-pointer flex items-center justify-between">
                            <span x-text="form.nationality || 'Seleccionar nacionalidad'" :class="!form.nationality ? 'text-slate-300' : ''"></span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform" :class="nationalityDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>

                        {{-- Dropdown --}}
                        <div x-show="nationalityDropdownOpen" x-cloak
                            class="absolute z-[80] mt-1 w-full bg-white rounded-xl shadow-2xl border border-slate-100 overflow-hidden"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100">

                            {{-- Search input inside dropdown --}}
                            <div class="p-2 border-b border-slate-50">
                                <input type="text" x-model="searchNationality" placeholder="Buscar país..."
                                    @click.stop
                                    class="w-full px-3 py-2 text-xs border border-slate-100 rounded-lg outline-none focus:bg-slate-50 transition-colors">
                            </div>

                            <div class="max-h-48 overflow-y-auto pt-1 pb-1 scrollbar-thin">
                                <template x-for="country in filteredNationalities" :key="country">
                                    <div @click="form.nationality = country; nationalityDropdownOpen = false; searchNationality = ''"
                                        class="px-4 py-2 text-xs text-slate-600 hover:bg-slate-50 hover:text-slate-900 cursor-pointer transition-colors flex items-center justify-between"
                                        :class="form.nationality === country ? 'bg-slate-50 text-slate-900 font-semibold' : ''">
                                        <span x-text="country"></span>
                                        <template x-if="form.nationality === country">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </template>
                                    </div>
                                </template>
                                <div x-show="filteredNationalities.length === 0" class="px-4 py-3 text-xs text-slate-400 italic text-center">
                                    No se encontraron resultados
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Género --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Género</label>
                        <select x-model="form.gender"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                            <option value="">Sin especificar</option>
                            <option value="male">Masculino</option>
                            <option value="female">Femenino</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>

                    {{-- Teléfono --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Teléfono</label>
                        <input type="tel" x-model="form.phone" placeholder="+56 9 1234 5678"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                    {{-- Dirección --}}
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Dirección</label>
                        <input type="text" x-model="form.address" placeholder="Calle, número, comuna"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-300 outline-none transition-all focus:ring-2 focus:ring-slate-900/10 focus:border-slate-400">
                    </div>

                </div>
            </div>
