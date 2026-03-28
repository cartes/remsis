<x-layouts.company :company="$company" activeTab="items">
@section('title', 'Catálogo de Conceptos - ' . ($company->razon_social ?? $company->name))

<div
    x-data="{
        // ── Estado del modal ───────────────────────────────────
        isOpen: false,
        isDeleting: false,
        saving: false,
        notification: null,

        // ── Datos del formulario ───────────────────────────────
        editingId: null,
        form: {
            name: '',
            code: '',
            type: '',
            calculation_type: 'fijo',
            assignment_type: 'distinto_por_persona',
            currency: 'CLP',
            default_amount: '',
            is_taxable: false,
            is_gratification_base: false,
            is_overtime_base: false,
        },
        errors: {},

        // ── Catálogo local (reactivo) ──────────────────────────
        items: {{ Illuminate\Support\Js::from($items->map(fn($i) => [
            'id'                   => $i->id,
            'name'                 => $i->name,
            'code'                 => $i->code,
            'type'                 => $i->type,
            'type_label'           => $i->type_label,
            'calculation_type'     => $i->calculation_type,
            'calc_label'           => $i->calc_label,
            'assignment_type'      => $i->assignment_type,
            'assign_label'         => $i->assign_label,
            'currency'             => $i->currency,
            'default_amount'       => $i->default_amount,
            'is_taxable'           => $i->is_taxable,
            'is_gratification_base'=> $i->is_gratification_base,
            'is_overtime_base'     => $i->is_overtime_base,
        ])) }},

        // ── Filtro de búsqueda ─────────────────────────────────
        search: '',
        filterType: '',
        get filteredItems() {
            return this.items.filter(i => {
                const q = this.search.toLowerCase();
                const matchSearch = !q || i.name.toLowerCase().includes(q) || (i.code && i.code.toLowerCase().includes(q));
                const matchType   = !this.filterType || i.type === this.filterType;
                return matchSearch && matchType;
            });
        },

        // ── Abrir modal nuevo ──────────────────────────────────
        openCreate() {
            this.editingId = null;
            this.errors = {};
            this.form = {
                name: '', code: '', type: '',
                calculation_type: 'fijo',
                assignment_type: 'distinto_por_persona',
                currency: 'CLP', default_amount: '',
                is_taxable: false, is_gratification_base: false, is_overtime_base: false,
            };
            this.isOpen = true;
        },

        // ── Abrir modal edición ────────────────────────────────
        openEdit(item) {
            this.editingId = item.id;
            this.errors = {};
            this.form = {
                name:                  item.name,
                code:                  item.code ?? '',
                type:                  item.type,
                calculation_type:      item.calculation_type ?? 'fijo',
                assignment_type:       item.assignment_type ?? 'distinto_por_persona',
                currency:              item.currency ?? 'CLP',
                default_amount:        item.default_amount ?? '',
                is_taxable:            item.is_taxable,
                is_gratification_base: item.is_gratification_base,
                is_overtime_base:      item.is_overtime_base,
            };
            this.isOpen = true;
        },

        // ── Guardar (crear o editar) ───────────────────────────
        async save() {
            this.saving = true;
            this.errors = {};

            const url    = this.editingId
                ? '{{ route('companies.items.update', ['company' => $company, 'item' => '__ID__']) }}'.replace('__ID__', this.editingId)
                : '{{ route('companies.items.store', $company) }}';
            const method = this.editingId ? 'PATCH' : 'POST';

            try {
                const res = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.form,
                        default_amount: this.form.assignment_type === 'igual_para_todos'
                            ? parseFloat(this.form.default_amount) || null
                            : null,
                    }),
                });

                const data = await res.json();

                if (!res.ok) {
                    this.errors = data.errors ?? {};
                    return;
                }

                if (this.editingId) {
                    const idx = this.items.findIndex(i => i.id === this.editingId);
                    if (idx !== -1) this.items[idx] = this.buildRow(data.item);
                } else {
                    this.items.unshift(this.buildRow(data.item));
                }

                this.isOpen = false;
                this.showNotification(this.editingId ? 'Concepto actualizado.' : 'Concepto creado exitosamente.');
            } catch (e) {
                this.showNotification('Ocurrió un error inesperado.', 'error');
            } finally {
                this.saving = false;
            }
        },

        // ── Eliminar ───────────────────────────────────────────
        async deleteItem(id) {
            if (!confirm('¿Eliminar este concepto? Esta acción no se puede deshacer.')) return;
            this.isDeleting = true;

            const url = '{{ route('companies.items.destroy', ['company' => $company, 'item' => '__ID__']) }}'.replace('__ID__', id);

            try {
                const res  = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });
                const data = await res.json();

                if (!res.ok) {
                    this.showNotification(data.message ?? 'No se pudo eliminar.', 'error');
                    return;
                }

                this.items = this.items.filter(i => i.id !== id);
                this.showNotification('Concepto eliminado.');
            } catch (e) {
                this.showNotification('Ocurrió un error inesperado.', 'error');
            } finally {
                this.isDeleting = false;
            }
        },

        // ── Helpers ────────────────────────────────────────────
        buildRow(i) {
            const typeLabels   = { haber_imponible: 'Haber Imponible', haber_no_imponible: 'Haber No Imponible', descuento_legal: 'Descuento Legal', descuento_varios: 'Descuento Varios', credito: 'Crédito' };
            const calcLabels   = { fijo: 'Fijo', proporcional_ausencia: 'Proporcional a días', liquido: 'Líquido' };
            const assignLabels = { igual_para_todos: 'Igual para todos', distinto_por_persona: 'Por persona' };
            return { ...i, type_label: typeLabels[i.type] ?? i.type, calc_label: calcLabels[i.calculation_type] ?? i.calculation_type, assign_label: assignLabels[i.assignment_type] ?? i.assignment_type };
        },

        showNotification(msg, type = 'success') {
            this.notification = { msg, type };
            setTimeout(() => this.notification = null, 4000);
        },

        typeBadgeClass(type) {
            const map = {
                haber_imponible:    'bg-blue-100 text-blue-700 border-blue-200',
                haber_no_imponible: 'bg-cyan-100 text-cyan-700 border-cyan-200',
                descuento_legal:    'bg-red-100 text-red-700 border-red-200',
                descuento_varios:   'bg-orange-100 text-orange-700 border-orange-200',
                credito:            'bg-violet-100 text-violet-700 border-violet-200',
            };
            return map[type] ?? 'bg-slate-100 text-slate-600 border-slate-200';
        },
    }"
>

    {{-- ── Breadcrumb ──────────────────────────────────────────── --}}
    <div class="mb-6">
        <x-breadcrumb :items="[
            ['label' => 'Panel de Control', 'url' => route('companies.dashboard', $company)],
            ['label' => 'Catálogo de Conceptos'],
        ]" />
    </div>

    {{-- ── Notificación flash ───────────────────────────────────── --}}
    <template x-if="notification">
        <div class="mb-5 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold border"
            :class="notification.type === 'error'
                ? 'bg-red-50 text-red-700 border-red-200'
                : 'bg-emerald-50 text-emerald-700 border-emerald-200'">
            <i class="fas text-base" :class="notification.type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'"></i>
            <span x-text="notification.msg"></span>
        </div>
    </template>

    {{-- ── Encabezado ───────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-tags text-blue-500 text-xl"></i>
                Catálogo de Conceptos
            </h1>
            <p class="text-sm text-slate-500 mt-1 font-medium">
                Define los haberes, descuentos y créditos que se podrán asignar a los colaboradores.
            </p>
        </div>
        <button @click="openCreate()" type="button"
            class="flex items-center gap-2 px-5 py-2.5 bg-slate-900 text-white text-sm font-black rounded-xl hover:bg-slate-800 active:scale-95 transition-all shadow-sm flex-shrink-0">
            <i class="fas fa-plus text-xs"></i>
            Nuevo Concepto
        </button>
    </div>

    {{-- ── Filtros ──────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-5">
        <div class="relative flex-1">
            <i class="fas fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
            <input type="text" x-model="search" placeholder="Buscar por nombre o código…"
                class="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
        </div>
        <select x-model="filterType"
            class="sm:w-56 px-3.5 py-2.5 text-sm border border-slate-200 rounded-xl bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
            <option value="">Todos los tipos</option>
            <option value="haber_imponible">Haber Imponible</option>
            <option value="haber_no_imponible">Haber No Imponible</option>
            <option value="descuento_legal">Descuento Legal</option>
            <option value="descuento_varios">Descuento Varios</option>
            <option value="credito">Crédito</option>
        </select>
    </div>

    {{-- ── Tabla ────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- Tabla con datos --}}
        <template x-if="filteredItems.length > 0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="text-left px-5 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-widest">Nombre / Código</th>
                            <th class="text-left px-4 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-widest">Tipo</th>
                            <th class="text-left px-4 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-widest hidden md:table-cell">Cálculo</th>
                            <th class="text-left px-4 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-widest hidden lg:table-cell">Asignación</th>
                            <th class="text-left px-4 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-widest hidden lg:table-cell">Moneda</th>
                            <th class="text-center px-4 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-widest hidden xl:table-cell">Reglas</th>
                            <th class="text-right px-5 py-3.5 text-[11px] font-black text-slate-500 uppercase tracking-widest">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in filteredItems" :key="item.id">
                            <tr class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors group">

                                {{-- Nombre / Código --}}
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900" x-text="item.name"></p>
                                    <template x-if="item.code">
                                        <span class="font-mono text-[11px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded mt-0.5 inline-block" x-text="item.code"></span>
                                    </template>
                                </td>

                                {{-- Tipo badge --}}
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border"
                                        :class="typeBadgeClass(item.type)"
                                        x-text="item.type_label">
                                    </span>
                                </td>

                                {{-- Cálculo --}}
                                <td class="px-4 py-4 hidden md:table-cell">
                                    <span class="text-xs font-semibold text-slate-600" x-text="item.calc_label"></span>
                                </td>

                                {{-- Asignación --}}
                                <td class="px-4 py-4 hidden lg:table-cell">
                                    <span class="text-xs font-semibold text-slate-600" x-text="item.assign_label"></span>
                                    <template x-if="item.assignment_type === 'igual_para_todos' && item.default_amount">
                                        <span class="block text-[11px] text-slate-400 font-mono" x-text="'$' + Number(item.default_amount).toLocaleString('es-CL')"></span>
                                    </template>
                                </td>

                                {{-- Moneda --}}
                                <td class="px-4 py-4 hidden lg:table-cell">
                                    <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md" x-text="item.currency"></span>
                                </td>

                                {{-- Reglas tributarias --}}
                                <td class="px-4 py-4 hidden xl:table-cell">
                                    <div class="flex items-center justify-center gap-2">
                                        <template x-if="item.is_taxable">
                                            <span title="Tributable" class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                                                <i class="fas fa-dollar-sign text-[9px]"></i>
                                            </span>
                                        </template>
                                        <template x-if="item.is_gratification_base">
                                            <span title="Base Gratificación" class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                                <i class="fas fa-hand-holding-dollar text-[9px]"></i>
                                            </span>
                                        </template>
                                        <template x-if="item.is_overtime_base">
                                            <span title="Base Horas Extras" class="w-5 h-5 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                                <i class="fas fa-clock text-[9px]"></i>
                                            </span>
                                        </template>
                                        <template x-if="!item.is_taxable && !item.is_gratification_base && !item.is_overtime_base">
                                            <span class="text-slate-300 text-xs">—</span>
                                        </template>
                                    </div>
                                </td>

                                {{-- Acciones --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEdit(item)" type="button" title="Editar"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-300 hover:bg-blue-50 transition-all">
                                            <i class="fas fa-pen text-xs"></i>
                                        </button>
                                        <button @click="deleteItem(item.id)" type="button" title="Eliminar"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:text-red-600 hover:border-red-300 hover:bg-red-50 transition-all">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>

        {{-- Empty state --}}
        <template x-if="filteredItems.length === 0">
            <div class="flex flex-col items-center justify-center py-20 text-center px-6">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4 shadow-inner">
                    <i class="fas fa-tags text-2xl text-slate-300"></i>
                </div>
                <h3 class="text-sm font-black text-slate-700 mb-1">
                    <template x-if="search || filterType">
                        <span>Sin resultados para ese filtro</span>
                    </template>
                    <template x-if="!search && !filterType">
                        <span>Aún no hay conceptos en el catálogo</span>
                    </template>
                </h3>
                <p class="text-xs text-slate-400 max-w-xs leading-relaxed mb-5">
                    <template x-if="!search && !filterType">
                        <span>Empieza creando los haberes, descuentos y créditos que usarás en las liquidaciones de tus colaboradores.</span>
                    </template>
                    <template x-if="search || filterType">
                        <span>Prueba cambiando los filtros de búsqueda.</span>
                    </template>
                </p>
                <template x-if="!search && !filterType">
                    <button @click="openCreate()" type="button"
                        class="flex items-center gap-2 px-5 py-2.5 bg-slate-900 text-white text-sm font-black rounded-xl hover:bg-slate-800 transition-all">
                        <i class="fas fa-plus text-xs"></i>
                        Crear primer concepto
                    </button>
                </template>
            </div>
        </template>

    </div>

    {{-- Contador --}}
    <p class="mt-3 text-xs text-slate-400 font-semibold text-right" x-show="filteredItems.length > 0">
        <span x-text="filteredItems.length"></span> concepto(s) en el catálogo
    </p>


    {{-- ══════════════════════════════════════════════════════════
         MODAL CREAR / EDITAR
    ══════════════════════════════════════════════════════════ --}}
    <div x-show="isOpen" x-cloak style="display:none"
        class="fixed inset-0 z-50 flex items-start justify-center pt-10 pb-8 px-4 overflow-y-auto"
        @keydown.escape.window="isOpen = false">

        {{-- Overlay --}}
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="isOpen = false"></div>

        {{-- Panel --}}
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl ring-1 ring-slate-200 z-10"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-black text-slate-900" x-text="editingId ? 'Editar Concepto' : 'Nuevo Concepto'"></h2>
                <button @click="isOpen = false" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-5">

                {{-- Nombre y Código --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="form.name" placeholder="Ej: Sueldo Base, Bono de Asistencia…"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 transition-all"
                            :class="errors.name ? 'border-red-400 focus:ring-red-200' : 'border-slate-200 focus:ring-slate-900/10'">
                        <p x-show="errors.name" class="text-xs text-red-500 mt-1 font-semibold" x-text="errors.name?.[0]"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Código</label>
                        <input type="text" x-model="form.code" placeholder="Ej: SB-001"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-mono text-slate-800 focus:outline-none focus:ring-2 transition-all uppercase"
                            :class="errors.code ? 'border-red-400 focus:ring-red-200' : 'border-slate-200 focus:ring-slate-900/10'">
                        <p x-show="errors.code" class="text-xs text-red-500 mt-1 font-semibold" x-text="errors.code?.[0]"></p>
                    </div>
                </div>

                {{-- Tipo --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">
                        Tipo de concepto <span class="text-red-500">*</span>
                    </label>
                    <select x-model="form.type"
                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 transition-all"
                        :class="errors.type ? 'border-red-400 focus:ring-red-200' : 'border-slate-200 focus:ring-slate-900/10'">
                        <option value="">Seleccionar tipo…</option>
                        <optgroup label="Haberes">
                            <option value="haber_imponible">Haber Imponible</option>
                            <option value="haber_no_imponible">Haber No Imponible</option>
                        </optgroup>
                        <optgroup label="Descuentos">
                            <option value="descuento_legal">Descuento Legal</option>
                            <option value="descuento_varios">Descuento Varios</option>
                        </optgroup>
                        <option value="credito">Crédito</option>
                    </select>
                    <p x-show="errors.type" class="text-xs text-red-500 mt-1 font-semibold" x-text="errors.type?.[0]"></p>
                </div>

                {{-- Reglas de cálculo --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Forma de cálculo</label>
                        <select x-model="form.calculation_type"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            <option value="fijo">Fijo</option>
                            <option value="proporcional_ausencia">Proporcional a días trabajados</option>
                            <option value="liquido">Líquido</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Moneda</label>
                        <select x-model="form.currency"
                            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                            <option value="CLP">CLP ($)</option>
                            <option value="UF">UF</option>
                            <option value="UTM">UTM</option>
                        </select>
                    </div>
                </div>

                {{-- Tipo de asignación --}}
                <div>
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">Monto a asignar</label>
                    <select x-model="form.assignment_type"
                        class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        <option value="distinto_por_persona">Distinto por persona (se ingresa en la ficha)</option>
                        <option value="igual_para_todos">Igual para todos (monto fijo global)</option>
                    </select>
                </div>

                {{-- Monto por defecto — solo si igual_para_todos --}}
                <div x-show="form.assignment_type === 'igual_para_todos'"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0">
                    <label class="block text-xs font-black text-slate-600 uppercase tracking-widest mb-1.5">
                        Monto por defecto <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-bold"
                            x-text="form.currency === 'CLP' ? '$' : form.currency"></span>
                        <input type="number" x-model="form.default_amount" min="0" step="0.01" placeholder="0"
                            class="w-full rounded-xl border pl-8 pr-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 transition-all"
                            :class="errors.default_amount ? 'border-red-400 focus:ring-red-200' : 'border-slate-200 focus:ring-slate-900/10'">
                    </div>
                    <p x-show="errors.default_amount" class="text-xs text-red-500 mt-1 font-semibold" x-text="errors.default_amount?.[0]"></p>
                </div>

                {{-- Reglas tributarias — solo si haber_imponible --}}
                <div x-show="form.type === 'haber_imponible'"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                    <p class="text-[11px] font-black text-blue-600 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                        <i class="fas fa-sliders"></i> Reglas tributarias del haber
                    </p>
                    <div class="space-y-2.5">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" x-model="form.is_taxable"
                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">Es tributable</span>
                                <span class="block text-xs text-slate-400">Afecta al Impuesto Único a la Renta</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" x-model="form.is_gratification_base"
                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">Base para gratificación</span>
                                <span class="block text-xs text-slate-400">Se incluye en el cálculo de la gratificación (Art. 50)</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" x-model="form.is_overtime_base"
                                class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">Base para horas extras</span>
                                <span class="block text-xs text-slate-400">Se incluye en el cálculo de horas extras</span>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/60 rounded-b-2xl">
                <button @click="isOpen = false" type="button"
                    class="px-4 py-2 text-sm font-bold text-slate-600 hover:text-slate-900 transition-colors">
                    Cancelar
                </button>
                <button @click="save()" type="button" :disabled="saving"
                    class="flex items-center gap-2 px-5 py-2.5 bg-slate-900 text-white text-sm font-black rounded-xl hover:bg-slate-800 active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas text-xs" :class="saving ? 'fa-circle-notch fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="saving ? 'Guardando…' : (editingId ? 'Guardar cambios' : 'Crear concepto')"></span>
                </button>
            </div>

        </div>{{-- /panel --}}
    </div>{{-- /modal --}}

</div>{{-- /x-data --}}
</x-layouts.company>
