<x-layouts.company :company="$company" activeTab="employees">
    @section('title', 'Editar Colaborador - ' . $employee->full_name)

    <div class="mb-6">
        {{-- Breadcrumbs Navigation --}}
        <x-breadcrumb :items="[
            ['label' => 'Panel de Control', 'url' => route('companies.dashboard', $company)],
            ['label' => 'Colaboradores', 'url' => route('companies.employees', $company)],
            ['label' => $employee->full_name],
        ]" />
    </div>

    <div x-data="{
        tab: '{{ session('active_tab', 'resumen') }}',
        saving: false,
        notification: null,
        isEditModalOpen: false,
        modalTab: 'personal',
        healthSystem: '{{ old('health_system', $employee->health_system ?? 'fonasa') }}',
        paymentMethod: '{{ old('payment_method', $employee->payment_method ?? 'efectivo') }}',
        items: {{ Illuminate\Support\Js::from(
            $employee->employeeItems->map(
                fn($ei) => [
                    'id' => $ei->id,
                    'item_id' => $ei->item_id,
                    'item_name' => $ei->item->name ?? '—',
                    'item_type' => $ei->item->type ?? '',
                    'amount' => $ei->amount,
                    'unit' => $ei->unit,
                    'periodicity' => $ei->periodicity,
                    'total_installments' => $ei->total_installments,
                    'current_installment' => $ei->current_installment,
                    'is_active' => $ei->is_active,
                ],
            ),
        ) }},
        catalogItems: {{ Illuminate\Support\Js::from(
            $catalogItems->map(
                fn($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'type' => $i->type,
                ],
            ),
        ) }},
        newItem: { item_id: '', amount: '', unit: 'CLP', periodicity: 'fixed', total_installments: '', notes: '' },
        showAddItem: false,
        addItemTab: 'haberes',
        showNotification(msg, type = 'success') {
            this.notification = { msg, type };
            setTimeout(() => this.notification = null, 3500);
        },
        formatMoney(val) {
            return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }).format(val);
        },
        filteredCatalog(type) {
            const map = {
                haberes: ['haber_imponible', 'haber_no_imponible'],
                descuentos: ['descuento_legal', 'descuento_varios'],
                creditos: ['credito'],
            };
            return this.catalogItems.filter(i => (map[type] || []).includes(i.type));
        },
        filteredItems(type) {
            const map = {
                haberes: ['haber_imponible', 'haber_no_imponible'],
                descuentos: ['descuento_legal', 'descuento_varios'],
                creditos: ['credito'],
            };
            return this.items.filter(i => (map[type] || []).includes(i.item_type));
        },
        async saveItem() {
            if (!this.newItem.item_id || !this.newItem.amount) return;
            this.saving = true;
            try {
                const res = await fetch('{{ route('companies.employees.items.store', [$company, $employee], false) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                    body: JSON.stringify(this.newItem),
                });
                const data = await res.json();
                if (data.success) {
                    const cat = this.catalogItems.find(i => i.id == this.newItem.item_id);
                    this.items.push({ ...data.item, item_name: cat?.name, item_type: cat?.type });
                    this.newItem = { item_id: '', amount: '', unit: 'CLP', periodicity: 'fixed', total_installments: '', notes: '' };
                    this.showAddItem = false;
                    this.showNotification('Ítem agregado correctamente.');
                }
            } catch (e) { this.showNotification('Error al guardar el ítem.', 'error'); }
            this.saving = false;
        },
        async deleteItem(id) {
            if (!confirm('¿Eliminar este ítem?')) return;
            const res = await fetch('{{ route('companies.employees.items.destroy', [$company, $employee, '__ID__'], false) }}'.replace('__ID__', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                this.items = this.items.filter(i => i.id !== id);
                this.showNotification('Ítem eliminado.');
            }
        },
        isPayslipModalOpen: false,
        selectedPayslip: null,
        formatCLP(val) {
            return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', minimumFractionDigits: 0 }).format(val || 0);
        },
        getMonthName(month) {
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            return meses[month - 1] || '—';
        }
    }">

        {{-- ── Header del colaborador ───────────────────────────────────── --}}
        <div class="mb-6 flex items-center gap-4">
            <div
                class="w-16 h-16 rounded-2xl bg-slate-200 flex items-center justify-center text-2xl font-bold text-slate-500 overflow-hidden flex-shrink-0">
                @if ($employee->user?->profile_photo)
                    <img src="{{ asset('storage/' . $employee->user->profile_photo) }}"
                        class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr($employee->first_name ?? '?', 0, 1)) }}
                @endif
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $employee->full_name }}</h1>
                <p class="text-sm text-slate-500">{{ $employee->position ?? 'Sin cargo' }} ·
                    {{ $employee->rut ?? 'Sin RUT' }}</p>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold
            {{ $employee->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                    <span
                        class="w-1.5 h-1.5 rounded-full {{ $employee->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    {{ $employee->status === 'active' ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </div>

        {{-- ── Tabs horizontales ────────────────────────────────────────── --}}
        <div class="border-b border-slate-200 mb-6">
            <nav class="flex gap-1 -mb-px overflow-x-auto">
                @foreach ([['resumen', 'Resumen'], ['liquidaciones', 'Liquidaciones'], ['documentos', 'Documentos'], ['conceptos', 'Conceptos de Pago']] as [$key, $label])
                    <button type="button" @click="tab = '{{ $key }}'"
                        class="whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === '{{ $key }}'
                            ?
                            'border-slate-900 text-slate-900' :
                            'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- ── Notificación flash ───────────────────────────────────────── --}}
        <template x-if="notification">
            <div class="mb-4 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium"
                :class="notification.type === 'error' ? 'bg-red-50 text-red-700 border border-red-200' :
                    'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                <span x-text="notification.msg"></span>
            </div>
        </template>

        @if (session('success'))
            <div
                class="mb-4 flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- TAB: RESUMEN                                                    --}}
        {{-- ════════════════════════════════════════════════════════════════ --}}
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
                                                x-text="formatCLP(selectedPayslip?.gross_salary || selectedPayslip?.total_earnings)"></span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-600">Gratificación Legal</span>
                                            <span class="font-mono text-slate-400 italic">Incluida</span>
                                        </div>
                                    </div>
                                    {{-- No Imponibles --}}
                                    <div class="space-y-2 pt-2">
                                        <p class="text-[9px] font-bold text-slate-300 uppercase italic">Haberes No
                                            Imponibles</p>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-600">Asignación Movilización</span>
                                            <span class="font-mono text-slate-400 italic">$ 0</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-600">Asignación Colación</span>
                                            <span class="font-mono text-slate-400 italic">$ 0</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-8 pt-4 border-t-2 border-slate-200 flex justify-between items-baseline">
                                    <span class="text-xs font-black uppercase text-slate-900 group">Total
                                        Haberes</span>
                                    <span
                                        class="text-lg font-black text-slate-900 underline decoration-slate-200 underline-offset-4"
                                        x-text="formatCLP(selectedPayslip?.gross_salary || selectedPayslip?.total_earnings)"></span>
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

        <div x-show="tab === 'liquidaciones'" x-cloak>

            {{-- Cabecera --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Historial de Liquidaciones
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Registro de sueldos procesados del colaborador</p>
                </div>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ $employee->payrolls->count() }}
                    {{ $employee->payrolls->count() === 1 ? 'liquidación' : 'liquidaciones' }}
                </span>
            </div>

            @if ($employee->payrolls->isEmpty())

                {{-- Empty state --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-16 text-center">
                    <div class="mx-auto mb-5 w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-600 mb-1">Sin liquidaciones históricas</h3>
                    <p class="text-sm text-slate-400 max-w-xs mx-auto leading-relaxed">
                        Este colaborador aún no tiene liquidaciones históricas procesadas.
                    </p>
                </div>
            @else
                {{-- Tabla de liquidaciones --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th
                                    class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Período</th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Total Haberes</th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Total Descuentos</th>
                                <th
                                    class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sueldo Líquido</th>
                                <th
                                    class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Estado</th>
                                <th
                                    class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @php
                                $meses = [
                                    1 => 'Enero',
                                    2 => 'Febrero',
                                    3 => 'Marzo',
                                    4 => 'Abril',
                                    5 => 'Mayo',
                                    6 => 'Junio',
                                    7 => 'Julio',
                                    8 => 'Agosto',
                                    9 => 'Septiembre',
                                    10 => 'Octubre',
                                    11 => 'Noviembre',
                                    12 => 'Diciembre',
                                ];
                                $statusMap = [
                                    'pending' => [
                                        'label' => 'Pendiente',
                                        'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    ],
                                    'processed' => [
                                        'label' => 'Procesado',
                                        'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    ],
                                    'paid' => [
                                        'label' => 'Pagado',
                                        'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    ],
                                    'cancelled' => [
                                        'label' => 'Anulado',
                                        'class' => 'bg-red-50 text-red-600 border-red-200',
                                    ],
                                ];
                            @endphp

                            @foreach ($employee->payrolls as $payroll)
                                @php
                                    $badge = $statusMap[$payroll->status] ?? $statusMap['pending'];
                                    $haberes =
                                        $payroll->total_earnings > 0
                                            ? $payroll->total_earnings
                                            : $payroll->gross_salary;
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition-colors group">

                                    {{-- Período --}}
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-100 transition-colors">
                                                <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-600"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 0 -2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
                                                </svg>
                                            </div>
                                            <button type="button"
                                                @click="selectedPayslip = {{ json_encode($payroll) }}; isPayslipModalOpen = true"
                                                class="text-left group/btn">
                                                <p
                                                    class="font-semibold text-blue-600 group-hover/btn:text-blue-800 transition-colors">
                                                    {{ $meses[$payroll->period_month] ?? '—' }}
                                                    {{ $payroll->period_year }}
                                                </p>
                                                @if ($payroll->payment_date)
                                                    <p class="text-xs text-slate-400 mt-0.5">
                                                        Pagado el
                                                        {{ \Carbon\Carbon::parse($payroll->payment_date)->format('d/m/Y') }}
                                                    </p>
                                                @endif
                                            </button>
                                        </div>
                                    </td>

                                    {{-- Total Haberes --}}
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-semibold text-slate-800">
                                            $&nbsp;{{ number_format($haberes, 0, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- Total Descuentos --}}
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-semibold text-red-600">
                                            $&nbsp;{{ number_format($payroll->total_deductions, 0, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- Sueldo Líquido --}}
                                    <td class="px-5 py-4 text-right">
                                        <span class="font-bold text-slate-900 text-base">
                                            $&nbsp;{{ number_format($payroll->net_salary, 0, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- Estado badge --}}
                                    <td class="px-5 py-4 text-center">
                                        <span
                                            class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badge['class'] }}">
                                            {{ $badge['label'] }}
                                        </span>
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="px-5 py-4 text-center">
                                        <a href="{{ route('companies.employees.payrolls.pdf', [$company, $employee, $payroll]) }}"
                                            title="Descargar liquidación PDF"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm hover:border-slate-900 hover:text-slate-900 transition-all group-hover:shadow-md">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            PDF
                                        </a>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>

                        {{-- Resumen totales al pie --}}
                        @php
                            $totalHaberes = $employee->payrolls->sum(
                                fn($p) => $p->total_earnings > 0 ? $p->total_earnings : $p->gross_salary,
                            );
                            $totalDescuentos = $employee->payrolls->sum('total_deductions');
                            $totalLiquido = $employee->payrolls->sum('net_salary');
                        @endphp
                        <tfoot class="bg-slate-50 border-t border-slate-200">
                            <tr>
                                <td class="px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Totales acumulados
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-bold text-slate-700">
                                    $&nbsp;{{ number_format($totalHaberes, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-bold text-red-600">
                                    $&nbsp;{{ number_format($totalDescuentos, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-3 text-right text-xs font-bold text-slate-900">
                                    $&nbsp;{{ number_format($totalLiquido, 0, ',', '.') }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>

                </div>

            @endif

        </div>

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- TAB: DOCUMENTOS                                                  --}}
        {{-- ════════════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'documentos'" x-cloak
             x-data="{ isUploadModalOpen: false }">

            {{-- Cabecera --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Carpeta Digital</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Contratos, anexos y comprobantes del colaborador</p>
                </div>
                <button type="button" @click="isUploadModalOpen = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 transition-colors">
                    {{-- Heroicon: arrow-up-tray --}}
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                    Subir Documento
                </button>
            </div>

            @forelse ($employee->documents as $document)

                @php
                    $docTypeLabelMap = [
                        'contrato'    => 'Contrato',
                        'anexo'       => 'Anexo',
                        'comprobante' => 'Comprobante',
                        'legal'       => 'Documento Legal',
                        'otro'        => 'Otro',
                    ];
                    $sigBadgeMap = [
                        'sin_firma' => [
                            'label' => 'Sin firma',
                            'class' => 'bg-slate-100 text-slate-500 border-slate-200',
                            'dot'   => 'bg-slate-400',
                        ],
                        'pendiente_colaborador' => [
                            'label' => 'Pendiente Colaborador',
                            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                            'dot'   => 'bg-amber-500',
                        ],
                        'pendiente_empresa' => [
                            'label' => 'Pendiente Empresa',
                            'class' => 'bg-orange-50 text-orange-700 border-orange-200',
                            'dot'   => 'bg-orange-500',
                        ],
                        'firmado_completamente' => [
                            'label' => 'Firmado',
                            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'dot'   => 'bg-emerald-500',
                        ],
                    ];
                    $sigBadge = $sigBadgeMap[$document->signature_status] ?? $sigBadgeMap['sin_firma'];
                @endphp

                @if ($loop->first)
                {{-- Tabla: abrir sólo en la primera iteración --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Documento</th>
                                <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Fecha de Carga</th>
                                <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Estado de Firma</th>
                                <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                @endif

                            <tr class="hover:bg-slate-50/70 transition-colors group">

                                {{-- Documento --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0 group-hover:bg-red-100 transition-colors">
                                            {{-- Heroicon: document --}}
                                            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-800 leading-tight">{{ $document->title }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">
                                                {{ $docTypeLabelMap[$document->document_type] ?? $document->document_type }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Fecha de carga --}}
                                <td class="px-5 py-4 text-slate-500">
                                    {{ $document->created_at->format('d/m/Y') }}
                                </td>

                                {{-- Badge estado firma --}}
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $sigBadge['class'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $sigBadge['dot'] }}"></span>
                                        {{ $sigBadge['label'] }}
                                    </span>
                                </td>

                                {{-- Acciones --}}
                                <td class="px-5 py-4 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        {{-- Descargar --}}
                                        <a href="{{ route('companies.employees.documents.download', [$company, $employee, $document]) }}"
                                            title="Descargar"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm hover:border-slate-900 hover:text-slate-900 transition-all">
                                            {{-- Heroicon: arrow-down-tray --}}
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            Descargar
                                        </a>

                                        {{-- Solicitar Firma --}}
                                        @if (in_array($document->signature_status, ['sin_firma', 'pendiente_colaborador', 'pendiente_empresa']))
                                            <button type="button" title="Solicitar firma"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-100 transition-all">
                                                {{-- Heroicon: pencil-square --}}
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                                Solicitar Firma
                                            </button>
                                        @endif

                                        {{-- Eliminar --}}
                                        <form method="POST"
                                            action="{{ route('companies.employees.documents.destroy', [$company, $employee, $document]) }}"
                                            onsubmit="return confirm('¿Eliminar este documento?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Eliminar"
                                                class="inline-flex items-center rounded-lg border border-red-100 bg-red-50 p-1.5 text-red-400 hover:bg-red-100 hover:text-red-600 transition-all">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                @if ($loop->last)
                        </tbody>
                    </table>
                </div>
                @endif

            @empty

                {{-- Empty state --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-16 text-center">
                    <div class="mx-auto mb-5 w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center">
                        {{-- Heroicon: folder-open --}}
                        <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-600 mb-1">Carpeta digital vacía</h3>
                    <p class="text-sm text-slate-400 max-w-xs mx-auto leading-relaxed">
                        Sube el primer contrato o anexo del colaborador usando el botón <strong>"Subir Documento"</strong>.
                    </p>
                </div>

            @endforelse

            {{-- ── Modal: Subir Documento ──────────────────────────────────── --}}
            <div x-show="isUploadModalOpen"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 @keydown.escape.window="isUploadModalOpen = false">

                {{-- Backdrop --}}
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                     @click="isUploadModalOpen = false"></div>

                {{-- Panel --}}
                <div class="relative z-10 w-full max-w-lg bg-white rounded-2xl shadow-2xl"
                     @click.stop>

                    {{-- Header modal --}}
                    <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-900">Subir Documento</h3>
                        </div>
                        <button type="button" @click="isUploadModalOpen = false"
                            class="rounded-lg p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Formulario --}}
                    <form method="POST"
                          action="{{ route('companies.employees.documents.store', [$company, $employee]) }}"
                          enctype="multipart/form-data"
                          class="px-6 py-5 space-y-4">
                        @csrf

                        {{-- Título --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                Título del documento <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" required
                                placeholder="Ej: Contrato de Trabajo Indefinido"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
                        </div>

                        {{-- Tipo de documento --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                Tipo de documento <span class="text-red-500">*</span>
                            </label>
                            <select name="document_type" required
                                class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
                                <option value="">— Selecciona un tipo —</option>
                                <option value="contrato">Contrato</option>
                                <option value="anexo">Anexo</option>
                                <option value="comprobante">Comprobante</option>
                                <option value="legal">Documento Legal</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        {{-- Archivo PDF --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                Archivo PDF <span class="text-red-500">*</span>
                            </label>
                            <label class="flex flex-col items-center justify-center w-full rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-4 py-8 cursor-pointer hover:border-blue-400 hover:bg-blue-50/40 transition-colors"
                                   x-data="{ fileName: '' }">
                                <svg class="w-10 h-10 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <p class="text-sm text-slate-400 text-center" x-text="fileName || 'Haz clic para seleccionar o arrastra el PDF aquí'"></p>
                                <p class="text-xs text-slate-300 mt-1">Máximo 10 MB · Solo PDF</p>
                                <input type="file" name="file" accept=".pdf" required class="sr-only"
                                    @change="fileName = $event.target.files[0]?.name || ''">
                            </label>
                            @error('file')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Botones --}}
                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="button" @click="isUploadModalOpen = false"
                                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                                </svg>
                                Subir Documento
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        </div>

        {{-- ════════════════════════════════════════════════════════════════ --}}
        {{-- TAB: ÍTEMS                                                      --}}
        {{-- ════════════════════════════════════════════════════════════════ --}}
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

    </div>{{-- /x-data principal --}}

</x-layouts.company>
