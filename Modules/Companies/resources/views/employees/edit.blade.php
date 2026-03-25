<x-layouts.company :company="$company" activeTab="employees">

<div x-data="{
    tab: 'personal',
    saving: false,
    notification: null,
    items: {{ Illuminate\Support\Js::from($employee->employeeItems->map(fn($ei) => [
        'id'                  => $ei->id,
        'item_id'             => $ei->item_id,
        'item_name'           => $ei->item->name ?? '—',
        'item_type'           => $ei->item->type ?? '',
        'amount'              => $ei->amount,
        'unit'                => $ei->unit,
        'periodicity'         => $ei->periodicity,
        'total_installments'  => $ei->total_installments,
        'current_installment' => $ei->current_installment,
        'is_active'           => $ei->is_active,
    ])) }},
    catalogItems: {{ Illuminate\Support\Js::from($catalogItems->map(fn($i) => [
        'id'   => $i->id,
        'name' => $i->name,
        'type' => $i->type,
    ])) }},
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
}">

{{-- ── Header del colaborador ───────────────────────────────────── --}}
<div class="mb-6 flex items-center gap-4">
    <div class="w-16 h-16 rounded-2xl bg-slate-200 flex items-center justify-center text-2xl font-bold text-slate-500 overflow-hidden flex-shrink-0">
        @if($employee->user?->profile_photo)
            <img src="{{ asset('storage/' . $employee->user->profile_photo) }}" class="w-full h-full object-cover">
        @else
            {{ strtoupper(substr($employee->first_name ?? '?', 0, 1)) }}
        @endif
    </div>
    <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ $employee->full_name }}</h1>
        <p class="text-sm text-slate-500">{{ $employee->position ?? 'Sin cargo' }} · {{ $employee->rut ?? 'Sin RUT' }}</p>
    </div>
    <div class="ml-auto flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold
            {{ $employee->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $employee->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
            {{ $employee->status === 'active' ? 'Activo' : 'Inactivo' }}
        </span>
    </div>
</div>

{{-- ── Tabs horizontales ────────────────────────────────────────── --}}
<div class="border-b border-slate-200 mb-6">
    <nav class="flex gap-1 -mb-px overflow-x-auto">
        @foreach([
            ['personal',       'Personal'],
            ['laboral',        'Laboral'],
            ['previsional',    'Previsional'],
            ['remuneraciones', 'Remuneraciones'],
            ['items',          'Ítems'],
        ] as [$key, $label])
        <button type="button" @click="tab = '{{ $key }}'"
            class="whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 transition-colors"
            :class="tab === '{{ $key }}'
                ? 'border-slate-900 text-slate-900'
                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'">
            {{ $label }}
        </button>
        @endforeach
    </nav>
</div>

{{-- ── Notificación flash ───────────────────────────────────────── --}}
<template x-if="notification">
    <div class="mb-4 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium"
        :class="notification.type === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
        <span x-text="notification.msg"></span>
    </div>
</template>

@if(session('success'))
<div class="mb-4 flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-700">
    {{ session('success') }}
</div>
@endif

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- TAB: PERSONAL                                                   --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'personal'" x-cloak>
    <form method="POST" action="{{ route('companies.employees.update', [$company, $employee]) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="section" value="personal">

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-5">Información Personal</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nombre</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}"
                        class="w-full rounded-xl border @error('first_name') border-red-400 bg-red-50 @else border-slate-200 @enderror px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    @error('first_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Apellido</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}"
                        class="w-full rounded-xl border @error('last_name') border-red-400 bg-red-50 @else border-slate-200 @enderror px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    @error('last_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">RUT</label>
                    <input type="text" name="rut" value="{{ old('rut', $employee->rut) }}" placeholder="12.345.678-9"
                        class="w-full rounded-xl border @error('rut') border-red-400 bg-red-50 @else border-slate-200 @enderror px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    @error('rut')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email', $employee->email) }}"
                        class="w-full rounded-xl border @error('email') border-red-400 bg-red-50 @else border-slate-200 @enderror px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fecha de Nacimiento</label>
                    <input type="date" name="birth_date" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Género</label>
                    <select name="gender" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value="">Seleccionar</option>
                        <option value="male"   {{ old('gender', $employee->gender) === 'male'   ? 'selected' : '' }}>Masculino</option>
                        <option value="female" {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>Femenino</option>
                        <option value="other"  {{ old('gender', $employee->gender) === 'other'  ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nacionalidad</label>
                    <input type="text" name="nationality" value="{{ old('nationality', $employee->nationality) }}" placeholder="Chileno/a"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Teléfono</label>
                    <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" placeholder="+56 9 1234 5678"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Dirección</label>
                    <input type="text" name="address" value="{{ old('address', $employee->address) }}"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- TAB: LABORAL                                                    --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'laboral'" x-cloak>
    <form method="POST" action="{{ route('companies.employees.update', [$company, $employee]) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="section" value="laboral">

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-5">Información Laboral</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cargo</label>
                    <input type="text" name="position" value="{{ old('position', $employee->position) }}"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fecha de Ingreso</label>
                    <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo de Contrato</label>
                    <select name="contract_type" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value="">Seleccionar</option>
                        @foreach(['indefinido' => 'Indefinido', 'plazo_fijo' => 'Plazo Fijo', 'por_obra' => 'Por Obra', 'honorarios' => 'Honorarios'] as $val => $label)
                        <option value="{{ $val }}" {{ old('contract_type', $employee->contract_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Jornada</label>
                    <select name="work_schedule_type" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value="full_time"  {{ old('work_schedule_type', $employee->work_schedule_type) === 'full_time'  ? 'selected' : '' }}>Jornada Completa</option>
                        <option value="part_time"  {{ old('work_schedule_type', $employee->work_schedule_type) === 'part_time'  ? 'selected' : '' }}>Jornada Parcial</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Centro de Costo</label>
                    <select name="cost_center_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value="">Sin asignar</option>
                        @foreach($costCenters as $cc)
                        <option value="{{ $cc->id }}" {{ old('cost_center_id', $employee->cost_center_id) == $cc->id ? 'selected' : '' }}>{{ $cc->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-3 pt-6">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_in_payroll" value="0">
                        <input type="checkbox" name="is_in_payroll" value="1" class="sr-only peer"
                            {{ old('is_in_payroll', $employee->is_in_payroll) ? 'checked' : '' }}>
                        <div class="w-10 h-6 bg-slate-200 peer-focus:ring-2 peer-focus:ring-slate-900/20 rounded-full peer peer-checked:bg-slate-900 transition-all"></div>
                        <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow transition-all peer-checked:translate-x-4"></div>
                    </label>
                    <span class="text-sm font-medium text-slate-700">Genera liquidación de sueldo</span>
                </div>

            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- TAB: PREVISIONAL                                                --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'previsional'" x-cloak>
    <form method="POST" action="{{ route('companies.employees.update', [$company, $employee]) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="section" value="previsional">

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-5">Información Previsional</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">AFP</label>
                    <select name="afp_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value="">Sin AFP</option>
                        @foreach($afps as $afp)
                        <option value="{{ $afp->id }}" {{ old('afp_id', $employee->afp_id) == $afp->id ? 'selected' : '' }}>{{ $afp->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-data="{ health: '{{ old('health_system', $employee->health_system ?? 'fonasa') }}' }">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Sistema de Salud</label>
                    <div class="flex gap-2 mb-3">
                        <button type="button" @click="health = 'fonasa'"
                            class="flex-1 rounded-xl border-2 py-2 text-sm font-semibold transition-all"
                            :class="health === 'fonasa' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-600'">Fonasa</button>
                        <button type="button" @click="health = 'isapre'"
                            class="flex-1 rounded-xl border-2 py-2 text-sm font-semibold transition-all"
                            :class="health === 'isapre' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-600'">Isapre</button>
                    </div>
                    <input type="hidden" name="health_system" :value="health">
                    <div x-show="health === 'isapre'" class="space-y-3">
                        <select name="isapre_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            <option value="">Seleccionar Isapre</option>
                            @foreach($isapres as $isapre)
                            <option value="{{ $isapre->id }}" {{ old('isapre_id', $employee->isapre_id) == $isapre->id ? 'selected' : '' }}>{{ $isapre->nombre }}</option>
                            @endforeach
                        </select>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">$</span>
                            <input type="number" name="health_contribution" value="{{ old('health_contribution', $employee->health_contribution) }}" placeholder="Monto plan (CLP)"
                                class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">CCAF</label>
                    <select name="ccaf_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value="">Sin CCAF</option>
                        @foreach($ccafs as $ccaf)
                        <option value="{{ $ccaf->id }}" {{ old('ccaf_id', $employee->ccaf_id) == $ccaf->id ? 'selected' : '' }}>{{ $ccaf->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">APV Mensual ($)</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">$</span>
                        <input type="number" name="apv_amount" value="{{ old('apv_amount', $employee->apv_amount) }}" placeholder="0" min="0"
                            class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- TAB: REMUNERACIONES                                             --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'remuneraciones'" x-cloak>
    <form method="POST" action="{{ route('companies.employees.update', [$company, $employee]) }}"
        x-data="{ paymentMethod: '{{ old('payment_method', $employee->payment_method ?? 'efectivo') }}' }">
        @csrf @method('PATCH')
        <input type="hidden" name="section" value="remuneraciones">

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-5">Remuneraciones</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Sueldo Base ($)</label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs font-bold">$</span>
                        <input type="number" name="salary" value="{{ old('salary', $employee->salary) }}" placeholder="0" min="0"
                            class="w-full pl-7 pr-3.5 py-2.5 rounded-xl border @error('salary') border-red-400 bg-red-50 @else border-slate-200 @enderror text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                    @error('salary')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo de Salario</label>
                    <select name="salary_type" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value="mensual"    {{ old('salary_type', $employee->salary_type) === 'mensual'    ? 'selected' : '' }}>Mensual</option>
                        <option value="quincenal"  {{ old('salary_type', $employee->salary_type) === 'quincenal'  ? 'selected' : '' }}>Quincenal</option>
                        <option value="semanal"    {{ old('salary_type', $employee->salary_type) === 'semanal'    ? 'selected' : '' }}>Semanal</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Método de Pago</label>
                    <div class="flex gap-3">
                        @foreach(['efectivo' => 'Efectivo', 'cheque' => 'Cheque', 'transferencia' => 'Transferencia'] as $val => $label)
                        <button type="button" @click="paymentMethod = '{{ $val }}'"
                            class="flex-1 rounded-xl border-2 py-2.5 text-sm font-semibold transition-all"
                            :class="paymentMethod === '{{ $val }}' ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-600 hover:border-slate-300'">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="payment_method" :value="paymentMethod">
                </div>

                <div x-show="paymentMethod === 'transferencia'" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Banco</label>
                        <select name="bank_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            <option value="">Seleccionar banco</option>
                            @foreach($bancos as $banco)
                            <option value="{{ $banco->id }}" {{ old('bank_id', $employee->bank_id) == $banco->id ? 'selected' : '' }}>{{ $banco->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo de Cuenta</label>
                        <select name="bank_account_type" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            <option value="corriente" {{ old('bank_account_type', $employee->bank_account_type) === 'corriente' ? 'selected' : '' }}>Corriente</option>
                            <option value="vista"     {{ old('bank_account_type', $employee->bank_account_type) === 'vista'     ? 'selected' : '' }}>Vista / RUT</option>
                            <option value="ahorro"    {{ old('bank_account_type', $employee->bank_account_type) === 'ahorro'    ? 'selected' : '' }}>Ahorro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Número de Cuenta</label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                Guardar cambios
            </button>
        </div>
    </form>
</div>

{{-- ════════════════════════════════════════════════════════════════ --}}
{{-- TAB: ÍTEMS                                                      --}}
{{-- ════════════════════════════════════════════════════════════════ --}}
<div x-show="tab === 'items'" x-cloak x-data="{ itemTab: 'haberes' }">

    {{-- Sub-tabs --}}
    <div class="flex gap-1 mb-5">
        @foreach(['haberes' => 'Haberes', 'descuentos' => 'Descuentos', 'creditos' => 'Créditos'] as $key => $label)
        <button type="button" @click="itemTab = '{{ $key }}'"
            class="rounded-lg px-4 py-2 text-sm font-semibold transition-all"
            :class="itemTab === '{{ $key }}' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- Botón agregar ítem --}}
    <div class="flex justify-end mb-4">
        <button type="button" @click="showAddItem = !showAddItem"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
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
                <select x-model="newItem.item_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    <option value="">Seleccionar concepto</option>
                    <template x-for="cat in filteredCatalog(itemTab)" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name"></option>
                    </template>
                </select>
                <p x-show="filteredCatalog(itemTab).length === 0" class="text-xs text-slate-400 mt-1">
                    No hay conceptos en el catálogo de esta empresa. Agrégalos desde Configuración → Catálogo de Ítems.
                </p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Monto</label>
                <input type="number" x-model="newItem.amount" placeholder="0" min="0"
                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Unidad</label>
                <select x-model="newItem.unit" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    <option value="CLP">CLP ($)</option>
                    <option value="UF">UF</option>
                    <option value="UTM">UTM</option>
                    <option value="PERCENTAGE">Porcentaje (%)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Periodicidad</label>
                <select x-model="newItem.periodicity" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    <option value="fixed">Fijo (todos los meses)</option>
                    <option value="variable">Variable (este mes)</option>
                </select>
            </div>
            <div x-show="itemTab === 'creditos'">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Total de Cuotas</label>
                <input type="number" x-model="newItem.total_installments" placeholder="ej: 12" min="1"
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
    @foreach(['haberes' => 'Haberes', 'descuentos' => 'Descuentos', 'creditos' => 'Créditos'] as $tabKey => $tabLabel)
    <div x-show="itemTab === '{{ $tabKey }}'">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <template x-if="filteredItems('{{ $tabKey }}').length === 0">
                <div class="text-center py-12 text-slate-400">
                    <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                    <p class="text-sm font-medium">Sin {{ strtolower($tabLabel) }} asignados</p>
                    <p class="text-xs mt-1">Usa el botón "Agregar ítem" para asignar conceptos.</p>
                </div>
            </template>
            <template x-if="filteredItems('{{ $tabKey }}').length > 0">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Concepto</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">Monto</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Unidad</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Periodicidad</th>
                            <th x-show="'{{ $tabKey }}' === 'creditos'" class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Cuotas</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="ei in filteredItems('{{ $tabKey }}')" :key="ei.id">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-5 py-3.5 font-medium text-slate-800" x-text="ei.item_name"></td>
                                <td class="px-5 py-3.5 text-right font-semibold text-slate-900" x-text="ei.unit === 'CLP' ? formatMoney(ei.amount) : ei.amount + ' ' + ei.unit"></td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600" x-text="ei.unit"></span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="ei.periodicity === 'fixed' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700'"
                                        x-text="ei.periodicity === 'fixed' ? 'Fijo' : 'Variable'"></span>
                                </td>
                                <td x-show="'{{ $tabKey }}' === 'creditos'" class="px-5 py-3.5 text-center text-xs text-slate-600">
                                    <span x-text="ei.current_installment ?? 0"></span>/<span x-text="ei.total_installments ?? '∞'"></span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-block w-2 h-2 rounded-full"
                                        :class="ei.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    <button type="button" @click="deleteItem(ei.id)"
                                        class="text-slate-400 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
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
