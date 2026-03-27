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
                @foreach ([['resumen', 'Resumen'], ['liquidaciones', 'Liquidaciones'], ['documentos', 'Documentos'], ['historial', 'Historial'], ['conceptos', 'Conceptos de Pago']] as [$key, $label])
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
        @include('companies::employees.partials.tab_resumen')
        @include('companies::employees.partials.tab_liquidaciones')
        @include('companies::employees.partials.tab_documentos')
        @include('companies::employees.partials.tab_conceptos')
        @include('companies::employees.partials.tab_historial')

    </div>{{-- /x-data principal --}}

</x-layouts.company>
