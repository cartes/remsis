        <div x-show="tab === 'resumen'" x-cloak>

            {{-- Cabecera de sección con botón Editar --}}
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5.121 17.804A9 9 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Resumen del Colaborador
                </h2>
                <button type="button" @click="isEditModalOpen = true"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:border-slate-900 hover:text-slate-900 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Editar Información
                </button>
            </div>

            {{-- Cards de métricas rápidas --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

                {{-- Cargo Actual --}}
                <div
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-tight">Cargo
                            Actual</p>
                    </div>
                    <p class="text-sm font-bold text-slate-900 leading-snug">
                        {{ $employee->position ?: 'Sin cargo asignado' }}</p>
                </div>

                {{-- Jefe Directo --}}
                <div
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-tight">Jefe
                            Directo</p>
                    </div>
                    <p class="text-sm font-bold text-slate-400 italic">No asignado</p>
                </div>

                {{-- Vacaciones disponibles --}}
                <div
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-tight">
                            Vacaciones Disp.</p>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-black text-slate-900">15</span>
                        <span class="text-xs font-semibold text-slate-400">días</span>
                    </div>
                </div>

                {{-- Último sueldo líquido --}}
                <div
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-tight">Últ.
                            Sueldo Líquido</p>
                    </div>
                    <p class="text-sm font-black text-slate-400 italic">Sin liquidación</p>
                </div>

            </div>

            {{-- Información detallada en dos columnas --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                {{-- Datos Personales --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                        </svg>
                        Datos Personales
                    </h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                            <dt class="text-xs font-semibold text-slate-400">RUT</dt>
                            <dd class="text-sm font-mono font-semibold text-slate-800">{{ $employee->rut ?: '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                            <dt class="text-xs font-semibold text-slate-400">Correo</dt>
                            <dd class="text-sm text-slate-800">{{ $employee->email ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                            <dt class="text-xs font-semibold text-slate-400">Teléfono</dt>
                            <dd class="text-sm text-slate-800">{{ $employee->phone ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                            <dt class="text-xs font-semibold text-slate-400">Nacimiento</dt>
                            <dd class="text-sm text-slate-800">
                                {{ $employee->birth_date ? $employee->birth_date->format('d/m/Y') : '—' }}</dd>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-slate-50">
                            <dt class="text-xs font-semibold text-slate-400">Género</dt>
                            <dd class="text-sm text-slate-800">
                                @php $generos = ['male'=>'Masculino','female'=>'Femenino','other'=>'Otro']; @endphp
                                {{ isset($generos[$employee->gender]) ? $generos[$employee->gender] : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <dt class="text-xs font-semibold text-slate-400">Nacionalidad</dt>
                            <dd class="text-sm text-slate-800">{{ $employee->nationality ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Datos Laborales + Previsionales --}}
                <div class="space-y-4">

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <h3
                            class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Datos Laborales
                        </h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                <dt class="text-xs font-semibold text-slate-400">F. Ingreso</dt>
                                <dd class="text-sm text-slate-800">
                                    {{ $employee->hire_date ? $employee->hire_date->format('d/m/Y') : '—' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                <dt class="text-xs font-semibold text-slate-400">Contrato</dt>
                                <dd class="text-sm text-slate-800">
                                    @php $contratos = ['indefinido'=>'Indefinido','plazo_fijo'=>'Plazo Fijo','por_obra'=>'Por Obra','honorarios'=>'Honorarios']; @endphp
                                    {{ $contratos[$employee->contract_type] ?? '—' }}
                                </dd>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                <dt class="text-xs font-semibold text-slate-400">Área / CC</dt>
                                <dd class="text-sm text-slate-800">{{ $employee->costCenter?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <dt class="text-xs font-semibold text-slate-400">Sueldo Base</dt>
                                <dd class="text-sm font-semibold text-slate-900">
                                    {{ $employee->salary ? '$' . number_format($employee->salary, 0, ',', '.') : '—' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <h3
                            class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Previsional
                        </h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                <dt class="text-xs font-semibold text-slate-400">AFP</dt>
                                <dd class="text-sm text-slate-800">{{ $employee->afp?->nombre ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-slate-50">
                                <dt class="text-xs font-semibold text-slate-400">Salud</dt>
                                <dd class="text-sm text-slate-800">
                                    @if ($employee->health_system === 'isapre')
                                        Isapre{{ $employee->isapre ? ' – ' . $employee->isapre->nombre : '' }}
                                    @elseif($employee->health_system === 'fonasa')
                                        Fonasa
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <dt class="text-xs font-semibold text-slate-400">CCAF</dt>
                                <dd class="text-sm text-slate-800">{{ $employee->ccaf?->nombre ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                </div>
            </div>

        </div>

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- MODAL: EDICIÓN COMPLETA DEL COLABORADOR                         --}}
        {{-- ════════════════════════════════════════════════════════════════ --}}
        <div x-show="isEditModalOpen" x-cloak style="display:none" class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-edit-title" role="dialog" aria-modal="true">

            {{-- Backdrop --}}
            <div x-show="isEditModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="isEditModalOpen = false">
            </div>

            {{-- Panel --}}
            <div class="flex min-h-full items-end justify-center p-4 sm:items-center sm:p-0">
                <div x-show="isEditModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative w-full sm:max-w-3xl bg-white rounded-2xl shadow-xl overflow-hidden" @click.stop>

                    {{-- Header del modal --}}
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-white sticky top-0 z-10">
                        <h3 id="modal-edit-title" class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            Editar Información
                            <span class="text-slate-400 font-medium">· {{ $employee->full_name }}</span>
                        </h3>
                        <button type="button" @click="isEditModalOpen = false"
                            class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Tabs internos del modal --}}
                    <div class="flex border-b border-slate-100 bg-slate-50/60 overflow-x-auto">
                        @foreach ([['personal', 'Personal'], ['laboral', 'Laboral'], ['previsional', 'Previsional'], ['remuneraciones', 'Remuneraciones']] as [$mKey, $mLabel])
                            <button type="button" @click="modalTab = '{{ $mKey }}'"
                                class="whitespace-nowrap px-5 py-3 text-sm font-semibold border-b-2 transition-colors"
                                :class="modalTab === '{{ $mKey }}'
                                    ?
                                    'border-slate-900 text-slate-900 bg-white' :
                                    'border-transparent text-slate-500 hover:text-slate-700'">
                                {{ $mLabel }}
                            </button>
                        @endforeach
                    </div>

                    {{-- ── SECCIÓN: PERSONAL ── --}}
                    <div x-show="modalTab === 'personal'" class="p-6">
                        <form method="POST"
                            action="{{ route('companies.employees.update', [$company, $employee]) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="section" value="personal">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nombre <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="first_name"
                                        value="{{ old('first_name', $employee->first_name) }}"
                                        class="w-full rounded-xl border @error('first_name') border-red-400 bg-red-50 @else border-slate-200 @enderror px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                    @error('first_name')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Apellido <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" name="last_name"
                                        value="{{ old('last_name', $employee->last_name) }}"
                                        class="w-full rounded-xl border @error('last_name') border-red-400 bg-red-50 @else border-slate-200 @enderror px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                    @error('last_name')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">RUT</label>
                                    <input type="text" name="rut" value="{{ old('rut', $employee->rut) }}"
                                        placeholder="12.345.678-9"
                                        class="w-full rounded-xl border @error('rut') border-red-400 bg-red-50 @else border-slate-200 @enderror px-3.5 py-2.5 text-sm font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                    @error('rut')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Correo
                                        Electrónico</label>
                                    <input type="email" name="email"
                                        value="{{ old('email', $employee->email) }}"
                                        class="w-full rounded-xl border @error('email') border-red-400 bg-red-50 @else border-slate-200 @enderror px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                    @error('email')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fecha de
                                        Nacimiento</label>
                                    <input type="date" name="birth_date"
                                        value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}"
                                        max="{{ now()->subYear()->format('Y-m-d') }}"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Género</label>
                                    <select name="gender"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                        <option value="">Seleccionar</option>
                                        <option value="male"
                                            {{ old('gender', $employee->gender) === 'male' ? 'selected' : '' }}>
                                            Masculino</option>
                                        <option value="female"
                                            {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>
                                            Femenino</option>
                                        <option value="other"
                                            {{ old('gender', $employee->gender) === 'other' ? 'selected' : '' }}>Otro
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-slate-600 mb-1.5">Nacionalidad</label>
                                    <input type="text" name="nationality"
                                        value="{{ old('nationality', $employee->nationality) }}"
                                        placeholder="Chileno/a"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Teléfono</label>
                                    <input type="text" name="phone"
                                        value="{{ old('phone', $employee->phone) }}" placeholder="+56 9 1234 5678"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Dirección</label>
                                    <input type="text" name="address"
                                        value="{{ old('address', $employee->address) }}"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                                <button type="button" @click="isEditModalOpen = false"
                                    class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- ── SECCIÓN: LABORAL ── --}}
                    <div x-show="modalTab === 'laboral'" class="p-6">
                        <form method="POST"
                            action="{{ route('companies.employees.update', [$company, $employee]) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="section" value="laboral">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cargo /
                                        Puesto</label>
                                    <input type="text" name="position"
                                        value="{{ old('position', $employee->position) }}"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fecha de
                                        Ingreso</label>
                                    <input type="date" name="hire_date"
                                        value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo de
                                        Contrato</label>
                                    <select name="contract_type"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                        <option value="">Seleccionar</option>
                                        @foreach (['indefinido' => 'Indefinido', 'plazo_fijo' => 'Plazo Fijo', 'por_obra' => 'Por Obra', 'honorarios' => 'Honorarios'] as $val => $label)
                                            <option value="{{ $val }}"
                                                {{ old('contract_type', $employee->contract_type) === $val ? 'selected' : '' }}>
                                                {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Jornada</label>
                                    <select name="work_schedule_type"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                        <option value="full_time"
                                            {{ old('work_schedule_type', $employee->work_schedule_type) === 'full_time' ? 'selected' : '' }}>
                                            Jornada Completa</option>
                                        <option value="part_time"
                                            {{ old('work_schedule_type', $employee->work_schedule_type) === 'part_time' ? 'selected' : '' }}>
                                            Jornada Parcial</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Centro de
                                        Costo</label>
                                    <select name="cost_center_id"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                        <option value="">Sin asignar</option>
                                        @foreach ($costCenters as $cc)
                                            <option value="{{ $cc->id }}"
                                                {{ old('cost_center_id', $employee->cost_center_id) == $cc->id ? 'selected' : '' }}>
                                                {{ $cc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex items-center gap-3 pt-5">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="is_in_payroll" value="0">
                                        <input type="checkbox" name="is_in_payroll" value="1"
                                            class="sr-only peer"
                                            {{ old('is_in_payroll', $employee->is_in_payroll) ? 'checked' : '' }}>
                                        <div
                                            class="w-10 h-6 bg-slate-200 rounded-full peer peer-checked:bg-slate-900 transition-all peer-focus:ring-2 peer-focus:ring-slate-900/20">
                                        </div>
                                        <div
                                            class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow transition-all peer-checked:translate-x-4">
                                        </div>
                                    </label>
                                    <span class="text-sm font-medium text-slate-700">Genera liquidación de
                                        sueldo</span>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                                <button type="button" @click="isEditModalOpen = false"
                                    class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- ── SECCIÓN: PREVISIONAL ── --}}
                    <div x-show="modalTab === 'previsional'" class="p-6">
                        <form method="POST"
                            action="{{ route('companies.employees.update', [$company, $employee]) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="section" value="previsional">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">AFP</label>
                                    <select name="afp_id"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                        <option value="">No cotiza (Exento / Jubilado)</option>
                                        @foreach ($afps as $afp)
                                            <option value="{{ $afp->id }}"
                                                {{ old('afp_id', $employee->afp_id) == $afp->id ? 'selected' : '' }}>
                                                {{ $afp->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">CCAF</label>
                                    <select name="ccaf_id"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                        <option value="">No cotiza</option>
                                        @foreach ($ccafs as $ccaf)
                                            <option value="{{ $ccaf->id }}"
                                                {{ old('ccaf_id', $employee->ccaf_id) == $ccaf->id ? 'selected' : '' }}>
                                                {{ $ccaf->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-600 mb-2">Sistema de
                                        Salud</label>
                                    <div class="flex gap-2 mb-3">
                                        <button type="button" @click="healthSystem = 'fonasa'"
                                            class="flex-1 rounded-xl border-2 py-2.5 text-sm font-semibold transition-all"
                                            :class="healthSystem === 'fonasa' ? 'border-slate-900 bg-slate-900 text-white' :
                                                'border-slate-200 text-slate-600 hover:border-slate-300'">
                                            Fonasa
                                        </button>
                                        <button type="button" @click="healthSystem = 'isapre'"
                                            class="flex-1 rounded-xl border-2 py-2.5 text-sm font-semibold transition-all"
                                            :class="healthSystem === 'isapre' ? 'border-slate-900 bg-slate-900 text-white' :
                                                'border-slate-200 text-slate-600 hover:border-slate-300'">
                                            Isapre
                                        </button>
                                    </div>
                                    <input type="hidden" name="health_system" :value="healthSystem">
                                </div>
                                <div x-show="healthSystem === 'isapre'" x-transition>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Isapre</label>
                                    <select name="isapre_id"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                        <option value="">Seleccionar Isapre</option>
                                        @foreach ($isapres as $isapre)
                                            <option value="{{ $isapre->id }}"
                                                {{ old('isapre_id', $employee->isapre_id) == $isapre->id ? 'selected' : '' }}>
                                                {{ $isapre->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div x-show="healthSystem === 'isapre'" x-transition>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Monto Plan de
                                        Salud ($)</label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">$</span>
                                        <input type="number" name="health_contribution"
                                            value="{{ old('health_contribution', $employee->health_contribution) }}"
                                            placeholder="0" min="0"
                                            class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">APV Mensual
                                        ($)</label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">$</span>
                                        <input type="number" name="apv_amount"
                                            value="{{ old('apv_amount', $employee->apv_amount) }}" placeholder="0"
                                            min="0"
                                            class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                                <button type="button" @click="isEditModalOpen = false"
                                    class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- ── SECCIÓN: REMUNERACIONES ── --}}
                    <div x-show="modalTab === 'remuneraciones'" class="p-6">
                        <form method="POST"
                            action="{{ route('companies.employees.update', [$company, $employee]) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="section" value="remuneraciones">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Sueldo Base
                                        ($)</label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">$</span>
                                        <input type="number" name="salary"
                                            value="{{ old('salary', $employee->salary) }}" placeholder="0"
                                            min="0"
                                            class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border @error('salary') border-red-400 bg-red-50 @else border-slate-200 @enderror text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                    </div>
                                    @error('salary')
                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo de
                                        Salario</label>
                                    <select name="salary_type"
                                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                        <option value="mensual"
                                            {{ old('salary_type', $employee->salary_type) === 'mensual' ? 'selected' : '' }}>
                                            Mensual</option>
                                        <option value="quincenal"
                                            {{ old('salary_type', $employee->salary_type) === 'quincenal' ? 'selected' : '' }}>
                                            Quincenal</option>
                                        <option value="semanal"
                                            {{ old('salary_type', $employee->salary_type) === 'semanal' ? 'selected' : '' }}>
                                            Semanal</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-600 mb-2">Método de
                                        Pago</label>
                                    <div class="flex gap-2">
                                        @foreach (['efectivo' => 'Efectivo', 'cheque' => 'Cheque', 'transferencia' => 'Transferencia'] as $val => $label)
                                            <button type="button" @click="paymentMethod = '{{ $val }}'"
                                                class="flex-1 rounded-xl border-2 py-2.5 text-sm font-semibold transition-all"
                                                :class="paymentMethod === '{{ $val }}' ?
                                                    'border-slate-900 bg-slate-900 text-white' :
                                                    'border-slate-200 text-slate-600 hover:border-slate-300'">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <input type="hidden" name="payment_method" :value="paymentMethod">
                                </div>
                                <div x-show="paymentMethod === 'transferencia'"
                                    class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-3" x-transition>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Banco</label>
                                        <select name="bank_id"
                                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                            <option value="">Seleccionar banco</option>
                                            @foreach ($bancos as $banco)
                                                <option value="{{ $banco->id }}"
                                                    {{ old('bank_id', $employee->bank_id) == $banco->id ? 'selected' : '' }}>
                                                    {{ $banco->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo de
                                            Cuenta</label>
                                        <select name="bank_account_type"
                                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                            <option value="corriente"
                                                {{ old('bank_account_type', $employee->bank_account_type) === 'corriente' ? 'selected' : '' }}>
                                                Corriente</option>
                                            <option value="vista"
                                                {{ old('bank_account_type', $employee->bank_account_type) === 'vista' ? 'selected' : '' }}>
                                                Vista / RUT</option>
                                            <option value="ahorro"
                                                {{ old('bank_account_type', $employee->bank_account_type) === 'ahorro' ? 'selected' : '' }}>
                                                Ahorro</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">N° de
                                            Cuenta</label>
                                        <input type="text" name="bank_account_number"
                                            value="{{ old('bank_account_number', $employee->bank_account_number) }}"
                                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                                <button type="button" @click="isEditModalOpen = false"
                                    class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>

                </div>{{-- /panel --}}
            </div>
        </div>{{-- /modal --}}

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- TAB: LIQUIDACIONES                                              --}}
        {{-- ════════════════════════════════════════════════════════════════ --}}

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- MODAL: PREVISUALIZACIÓN DE LIQUIDACIÓN                          --}}
        {{-- ════════════════════════════════════════════════════════════════ --}}
        <div x-show="isPayslipModalOpen" x-cloak style="display:none" class="fixed inset-0 z-[60] overflow-y-auto"
            role="dialog" aria-modal="true">

            {{-- Backdrop --}}
            <div x-show="isPayslipModalOpen" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="isPayslipModalOpen = false">
            </div>

            {{-- Contenido del Modal --}}
            <div class="flex min-h-full items-center justify-center p-4">
                <div x-show="isPayslipModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden print:shadow-none"
                    @click.stop>

                    {{-- Header del Modal --}}
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50/50">
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Vista Previa de Liquidación
                        </h3>
                        <button type="button" @click="isPayslipModalOpen = false"
                            class="rounded-xl p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 transition-all">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Área del "Documento" --}}
                    <div class="p-8 sm:p-12 text-slate-800 bg-white" id="payslip-content">

                        {{-- Cabecera Documento --}}
                        <div
                            class="flex flex-col sm:flex-row justify-between gap-8 mb-10 border-b-2 border-slate-900 pb-8">
                            <div class="space-y-1">
                                <h4 class="text-xl font-black text-slate-900 uppercase">{{ $company->name }}</h4>
                                <p class="text-sm font-bold text-slate-500">RUT: {{ $company->rut ?? '76.123.456-7' }}
                                </p>
                                <p class="text-xs text-slate-400">
                                    {{ $company->address ?? 'Calle Falsa 123, Santiago, Chile' }}</p>
                                <div
                                    class="mt-4 inline-block bg-slate-900 text-white px-3 py-1 rounded text-xs font-bold uppercase tracking-widest">
                                    Liquidación <span x-text="getMonthName(selectedPayslip?.period_month)"></span>
                                    <span x-text="selectedPayslip?.period_year"></span>
                                </div>
                            </div>
                            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100 min-w-[300px]">
                                <table class="w-full text-xs space-y-2">
                                    <tr>
                                        <td class="py-1 text-slate-400 font-bold uppercase tracking-tighter">
                                            Trabajador:</td>
                                        <td class="py-1 pl-4 font-black text-slate-900 uppercase">
                                            {{ $employee->full_name }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1 text-slate-400 font-bold uppercase tracking-tighter">RUT:</td>
                                        <td class="py-1 pl-4 font-mono font-bold">{{ $employee->rut }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1 text-slate-400 font-bold uppercase tracking-tighter">Cargo:
                                        </td>
                                        <td class="py-1 pl-4 font-bold">{{ $employee->position ?: 'No especificado' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-1 text-slate-400 font-bold uppercase tracking-tighter">Días
                                            Trab.:</td>
                                        <td class="py-1 pl-4 font-black">30</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- Cuerpo Documento --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">

                            {{-- Haberes --}}
                            <div>
                                <h5
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 border-b border-slate-100 pb-2">
                                    I. Haberes</h5>
                                <div class="space-y-4">
                                    {{-- Imponibles --}}
                                    <div class="space-y-2">
                                        <p class="text-[9px] font-bold text-slate-300 uppercase italic">Haberes
                                            Imponibles</p>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-600">Sueldo Base</span>
                                            <span class="font-mono font-bold"
                                                x-text="formatCLP(selectedPayslip?.base_salary)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm"
                                            x-show="selectedPayslip?.gratification_amount > 0">
                                            <span class="text-slate-600">Gratificación Legal</span>
                                            <span class="font-mono font-bold"
                                                x-text="formatCLP(selectedPayslip?.gratification_amount)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm"
                                            x-show="selectedPayslip?.overtime_amount > 0">
                                            <span class="text-slate-600">Horas Extras</span>
                                            <span class="font-mono font-bold"
                                                x-text="formatCLP(selectedPayslip?.overtime_amount)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm"
                                            x-show="selectedPayslip?.comisiones_amount > 0">
                                            <span class="text-slate-600">Comisiones</span>
                                            <span class="font-mono font-bold"
                                                x-text="formatCLP(selectedPayslip?.comisiones_amount)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm"
                                            x-show="selectedPayslip?.bonos_imponibles_amount > 0">
                                            <span class="text-slate-600">Bonos Imponibles</span>
                                            <span class="font-mono font-bold"
                                                x-text="formatCLP(selectedPayslip?.bonos_imponibles_amount)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm"
                                            x-show="selectedPayslip?.semana_corrida_amount > 0">
                                            <span class="text-slate-600">Semana Corrida</span>
                                            <span class="font-mono font-bold"
                                                x-text="formatCLP(selectedPayslip?.semana_corrida_amount)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm"
                                            x-show="selectedPayslip?.aguinaldos_amount > 0">
                                            <span class="text-slate-600">Aguinaldos</span>
                                            <span class="font-mono font-bold"
                                                x-text="formatCLP(selectedPayslip?.aguinaldos_amount)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm pt-2 border-t border-slate-50">
                                            <span class="text-slate-900 font-bold">Total Haberes Imponibles</span>
                                            <span class="font-mono font-bold text-slate-900"
                                                x-text="formatCLP(selectedPayslip?.gross_salary)"></span>
                                        </div>
                                    </div>
                                    {{-- No Imponibles --}}
                                    <div class="space-y-2 pt-2">
                                        <p class="text-[9px] font-bold text-slate-300 uppercase italic">Haberes No
                                            Imponibles</p>
                                        <div class="flex justify-between text-sm"
                                            x-show="selectedPayslip?.mobility_allowance > 0">
                                            <span class="text-slate-600">Asignación Movilización</span>
                                            <span class="font-mono font-bold"
                                                x-text="formatCLP(selectedPayslip?.mobility_allowance)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm"
                                            x-show="selectedPayslip?.meal_allowance > 0">
                                            <span class="text-slate-600">Asignación Colación</span>
                                            <span class="font-mono font-bold"
                                                x-text="formatCLP(selectedPayslip?.meal_allowance)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm"
                                            x-show="(selectedPayslip?.non_taxable_earnings - (selectedPayslip?.mobility_allowance || 0) - (selectedPayslip?.meal_allowance || 0)) > 0">
                                            <span class="text-slate-600">Otros No Imponibles</span>
                                            <span class="font-mono font-bold"
                                                x-text="formatCLP(selectedPayslip?.non_taxable_earnings - (selectedPayslip?.mobility_allowance || 0) - (selectedPayslip?.meal_allowance || 0))"></span>
                                        </div>
                                        <div class="flex justify-between text-sm pt-2 border-t border-slate-50">
                                            <span class="text-slate-900 font-bold">Total Haberes No Imponibles</span>
                                            <span class="font-mono font-bold text-slate-900"
                                                x-text="formatCLP(selectedPayslip?.non_taxable_earnings)"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-8 pt-4 border-t-2 border-slate-200 flex justify-between items-baseline">
                                    <span class="text-xs font-black uppercase text-slate-900 group">Total
                                        Haberes</span>
                                    <span
                                        class="text-lg font-black text-slate-900 underline decoration-slate-200 underline-offset-4"
                                        x-text="formatCLP(selectedPayslip?.total_earnings)"></span>
                                </div>
                            </div>

                            {{-- Descuentos --}}
                            <div>
                                <h5
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 border-b border-slate-100 pb-2">
                                    II. Descuentos</h5>
                                <div class="space-y-4">
                                    {{-- Legales --}}
                                    <div class="space-y-2">
                                        <p class="text-[9px] font-bold text-slate-300 uppercase italic">Descuentos
                                            Previsionales</p>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-600">Cotización AFP
                                                ({{ $employee->afp?->nombre ?? 'AFP' }})</span>
                                            <span class="font-mono font-bold text-red-600"
                                                x-text="formatCLP(selectedPayslip?.total_deductions * 0.7)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-600">Cotización Salud
                                                ({{ $employee->health_system === 'fonasa' ? 'FONASA' : 'ISAPRE' }})</span>
                                            <span class="font-mono font-bold text-red-600"
                                                x-text="formatCLP(selectedPayslip?.total_deductions * 0.3)"></span>
                                        </div>
                                    </div>
                                    {{-- Otros --}}
                                    <div class="space-y-2 pt-2">
                                        <p class="text-[9px] font-bold text-slate-300 uppercase italic">Otros
                                            Descuentos</p>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-600">Préstamos / Otros</span>
                                            <span class="font-mono text-slate-400 italic">$ 0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-8 pt-4 border-t-2 border-slate-200 flex justify-between items-baseline">
                                    <span class="text-xs font-black uppercase text-slate-900">Total Descuentos</span>
                                    <span
                                        class="text-lg font-black text-red-600 underline decoration-red-100 underline-offset-4"
                                        x-text="formatCLP(selectedPayslip?.total_deductions)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Footer Pago --}}
                        <div class="mt-16 flex justify-end">
                            <div
                                class="bg-slate-900 text-white p-8 rounded-2xl shadow-xl min-w-[320px] transform hover:scale-[1.02] transition-transform">
                                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 mb-2">Sueldo
                                    Líquido a Pagar</p>
                                <div class="flex items-baseline justify-between">
                                    <span class="text-sm font-bold opacity-50">CLP</span>
                                    <span class="text-4xl font-black"
                                        x-text="formatCLP(selectedPayslip?.net_salary)"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Firmas (Visual) --}}
                        <div class="mt-20 grid grid-cols-2 gap-20 opacity-30">
                            <div class="border-t border-slate-400 pt-4 text-center">
                                <p class="text-[10px] uppercase font-bold text-slate-500">Firma Empleador</p>
                            </div>
                            <div class="border-t border-slate-400 pt-4 text-center">
                                <p class="text-[10px] uppercase font-bold text-slate-500">Firma Trabajador</p>
                            </div>
                        </div>

                    </div>

                    {{-- Footer Modal --}}
                    <div class="bg-slate-50 px-8 py-4 flex justify-between items-center border-t border-slate-100">
                        <p class="text-[10px] font-bold text-slate-400 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M2.166 4.9L10 9.15l7.834-4.25a2.291 2.291 0 00-2.117-1.4H4.283a2.291 2.291 0 00-2.117 1.4z"
                                    clip-rule="evenodd" />
                                <path
                                    d="M1.834 6.1L10 10.55l8.166-4.45A2.164 2.164 0 0018 6.5V17a2.291 2.291 0 01-2.283 2.3H4.283A2.291 2.291 0 012 17V6.5c0-.14.013-.277.042-.41z" />
                            </svg>
                            Generado por Remsis
                        </p>
                        <div class="flex gap-2">
                            <button type="button" @click="window.print()"
                                class="rounded-xl bg-white border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 transition-all flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Imprimir
                            </button>
                            <button type="button" @click="isPayslipModalOpen = false"
                                class="rounded-xl bg-slate-900 px-6 py-2 text-xs font-bold text-white hover:bg-slate-800 transition-all">
                                Cerrar
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
