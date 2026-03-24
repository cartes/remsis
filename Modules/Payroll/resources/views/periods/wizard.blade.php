<x-layouts.company :company="$company" activeTab="payroll-periods">
    @section('title', 'Cálculo de Nómina - ' . $period->getDisplayName())

    <div class="max-w-7xl mx-auto text-sm">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <x-breadcrumb :items="[
                        ['label' => 'Panel de Control', 'url' => route('companies.dashboard', $company)],
                        ['label' => 'Nómina', 'url' => route('companies.payroll-periods.index', $company)],
                        ['label' => 'Cálculo de Nómina']
                    ]" />
                    <h2 class="text-xl font-bold text-gray-800">Cálculo de Nómina - {{ $period->getDisplayName() }}</h2>
                    <p class="text-sm text-gray-500 mt-1">Estado: <span
                            class="{{ $period->getStatusBadgeClass() }} px-2 py-0.5 rounded-full text-xs border">{{ $period->getStatusLabel() }}</span>
                    </p>
                </div>

                <div class="flex space-x-3">
                    <a href="{{ route('companies.payroll-periods.index', $company) }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Volver
                    </a>

                    @if ($period->status == 'draft' || $period->status == 'open' || $period->status == 'calculated')
                        <form
                            action="{{ route('companies.payroll-periods.calculate', ['company' => $company, 'period' => $period->id]) }}"
                            method="POST">
                            @csrf
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors flex items-center shadow-sm">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                    </path>
                                </svg>
                                {{ $lines->count() > 0 ? 'Recalcular Nómina' : 'Calcular Nómina' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="p-6">
                <!-- Filters -->
                <form method="GET" class="mb-6 flex gap-4 items-end bg-gray-50 p-4 rounded-lg border border-gray-100">
                    <div class="w-1/3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Centro de Costo</label>
                        <select name="cost_center_id"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            onchange="this.form.submit()">
                            <option value="">Todos los Centros de Costo</option>
                            @foreach ($costCenters as $cc)
                                <option value="{{ $cc->id }}"
                                    {{ request('cost_center_id') == $cc->id ? 'selected' : '' }}>
                                    {{ $cc->name }} ({{ $cc->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                @if ($lines->isEmpty())
                    <div class="text-center py-16 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                        <div
                            class="bg-gray-100 rounded-full p-4 w-20 h-20 mx-auto flex items-center justify-center mb-4">
                            <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">No hay empleados calculados</h3>
                        <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">Aún no se ha procesado la nómina para
                            este
                            período. Haz clic en el botón "Calcular Nómina" para generar los registros basados en los
                            empleados activos.</p>

                        @if ($period->status == 'draft' || $period->status == 'open')
                            <div class="mt-6">
                                <form
                                    action="{{ route('companies.payroll-periods.calculate', ['company' => $company, 'period' => $period->id]) }}"
                                    method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-md font-medium">
                                        Calcular Nómina Ahora
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @else
                    <div x-data="{
                        showModal: false,
                        selectedLine: null,
                        itemDetails: { 'CO01': [], 'SC01': [], 'BI01': [], 'AG01': [] },
                        toast: { show: false, message: '', type: 'success' },
                        showToast(message, type = 'success') {
                            this.toast.message = message;
                            this.toast.type = type;
                            this.toast.show = true;
                            setTimeout(() => { this.toast.show = false; }, 3000);
                        }
                    }">
                        <!-- Toast Notification -->
                        <div x-show="toast.show" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 transform translate-y-2"
                            x-transition:enter-end="opacity-100 transform translate-y-0"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 transform translate-y-0"
                            x-transition:leave-end="opacity-0 transform translate-y-2"
                            class="fixed bottom-5 right-5 z-[100] max-w-sm w-full bg-white border-l-4 rounded-lg shadow-xl p-4"
                            :class="toast.type === 'success' ? 'border-green-500' : 'border-red-500'"
                            style="display: none;">
                            <div class="flex items-center">
                                <template x-if="toast.type === 'success'">
                                    <svg class="h-6 w-6 text-green-500 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </template>
                                <template x-if="toast.type === 'error'">
                                    <svg class="h-6 w-6 text-red-500 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </template>
                                <p class="text-sm font-medium text-gray-900" x-text="toast.message"></p>
                            </div>
                        </div>
                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            Empleado</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            Base Imp.</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            Total Haberes</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            Total Desc.</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            Líquido</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($lines as $line)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="bg-indigo-100 rounded-full p-2 mr-3">
                                                        <span
                                                            class="text-indigo-600 font-bold text-xs">{{ substr($line->employee->first_name ?? '', 0, 1) }}{{ substr($line->employee->last_name ?? '', 0, 1) }}</span>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-semibold text-gray-900">
                                                            {{ $line->employee->full_name }}</div>
                                                        <div class="text-xs text-gray-500">{{ $line->employee->rut }}
                                                        </div>
                                                        @if ($line->employee->costCenter)
                                                            <div
                                                                class="text-xs text-indigo-500 mt-0.5 bg-indigo-50 inline-block px-1.5 py-0.5 rounded">
                                                                {{ $line->employee->costCenter->name }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td id="base-salary-{{ $line->id }}"
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-600 font-medium">
                                                ${{ number_format($line->base_salary, 0, ',', '.') }}
                                            </td>
                                            <td id="gross-salary-{{ $line->id }}"
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-600 font-medium">
                                                ${{ number_format($line->gross_salary, 0, ',', '.') }}
                                            </td>
                                            <td id="total-earnings-{{ $line->id }}"
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm text-indigo-600 font-bold">
                                                ${{ number_format($line->total_earnings, 0, ',', '.') }}
                                            </td>
                                            <td id="total-deductions-{{ $line->id }}"
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-500 font-medium">
                                                -${{ number_format($line->total_deductions, 0, ',', '.') }}
                                            </td>
                                            <td id="net-salary-{{ $line->id }}"
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                                ${{ number_format($line->net_salary, 0, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <button
                                                    @click="selectedLine = {{ $line->toJson() }}; itemDetails = { 'CO01': (selectedLine.details || []).filter(d => d.concept === 'CO01').map(d => ({...d, type: d.concept})), 'SC01': (selectedLine.details || []).filter(d => d.concept === 'SC01').map(d => ({...d, type: d.concept})), 'BI01': (selectedLine.details || []).filter(d => d.concept === 'BI01').map(d => ({...d, type: d.concept})), 'AG01': (selectedLine.details || []).filter(d => d.concept === 'AG01').map(d => ({...d, type: d.concept})) }; showModal = true"
                                                    class="text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 px-3 py-1 rounded-md transition-colors">
                                                    Detalles
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            Totales ({{ $lines->count() }})</th>
                                        <th scope="col"
                                            class="px-6 py-4 text-right text-xs font-bold text-gray-900 uppercase tracking-wider">
                                            ${{ number_format($lines->sum('gross_salary'), 0, ',', '.') }}
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-4 text-right text-xs font-bold text-indigo-600 uppercase tracking-wider">
                                            ${{ number_format($lines->sum('total_earnings'), 0, ',', '.') }}
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-4 text-right text-xs font-bold text-red-600 uppercase tracking-wider">
                                            -${{ number_format($lines->sum('total_deductions'), 0, ',', '.') }}
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-4 text-right text-xs font-black text-gray-900 uppercase tracking-wider text-base">
                                            ${{ number_format($lines->sum('net_salary'), 0, ',', '.') }}
                                        </th>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Modal -->
                        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                            <div
                                class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                <div x-show="showModal" x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity"
                                    aria-hidden="true">
                                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                                </div>

                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                    aria-hidden="true">&#8203;</span>

                                <div x-show="showModal" x-transition:enter="ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                    <template x-if="selectedLine">
                                        <form @submit.prevent="updateLine">
                                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <div class="sm:flex sm:items-start">
                                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                                        <h3 class="text-lg leading-6 font-medium text-gray-900"
                                                            id="modal-title">
                                                            Editar Remuneración
                                                        </h3>
                                                        <div class="mt-4 space-y-4">
                                                            <!-- Read-only Info -->
                                                            <div class="flex justify-between border-b pb-2 mb-2">
                                                                <span class="text-sm font-medium text-gray-500">Sueldo
                                                                    Base (Contrato)</span>
                                                                <span class="text-sm font-bold text-gray-900"
                                                                    x-text="'$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.base_salary))"></span>
                                                            </div>

                                                            @if ($company->allows_overtime)
                                                                <div>
                                                                    <label
                                                                        class="block text-xs font-semibold text-gray-700 mb-1">Horas
                                                                        Extras</label>
                                                                    <div class="flex gap-2">
                                                                        <input type="number" step="0.5"
                                                                            min="0"
                                                                            x-model="selectedLine.overtime_hours"
                                                                            class="w-24 border-gray-300 rounded-md text-sm p-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                                                                        <span
                                                                            class="text-xs text-gray-500 self-center">hrs</span>
                                                                        <span
                                                                            class="text-xs font-bold text-indigo-600 self-center ml-auto"
                                                                            x-text="'$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.overtime_amount))"></span>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            @if ($enabledTaxables->contains('code', 'CO01'))
                                                                <div class="border rounded p-3 mb-3 bg-gray-50">
                                                                    <div
                                                                        class="flex justify-between items-center mb-2">
                                                                        <label
                                                                            class="block text-xs font-semibold text-gray-700">Comisiones</label>
                                                                        <button type="button"
                                                                            @click="itemDetails['CO01'].push({type: 'CO01', description: '', amount: 0})"
                                                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+
                                                                            Agregar Comisión</button>
                                                                    </div>
                                                                    <template
                                                                        x-for="(detail, index) in itemDetails['CO01']"
                                                                        :key="index">
                                                                        <div class="flex gap-2 mb-2 items-center">
                                                                            <input type="text"
                                                                                x-model="detail.description"
                                                                                placeholder="Glosa (ej: Ventas mes)"
                                                                                class="w-1/2 border-gray-300 rounded-md text-xs p-1.5 focus:ring-indigo-500 focus:border-indigo-500"
                                                                                required>
                                                                            <input type="number" min="0"
                                                                                x-model.number="detail.amount"
                                                                                placeholder="Monto"
                                                                                class="w-1/3 border-gray-300 rounded-md text-xs p-1.5 focus:ring-indigo-500 focus:border-indigo-500"
                                                                                required>
                                                                            <button type="button"
                                                                                @click="itemDetails['CO01'].splice(index, 1)"
                                                                                class="text-red-500 hover:text-red-700"><i
                                                                                    class="fas fa-trash text-xs"></i></button>
                                                                        </div>
                                                                    </template>
                                                                    <div class="text-right text-xs font-bold text-gray-600 mt-1"
                                                                        x-show="itemDetails['CO01'].length > 0">
                                                                        Total Comisiones: $<span
                                                                            x-text="new Intl.NumberFormat('es-CL').format(itemDetails['CO01'].reduce((acc, curr) => acc + (parseInt(curr.amount) || 0), 0))"></span>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            @if ($enabledTaxables->contains('code', 'SC01'))
                                                                <div class="border rounded p-3 mb-3 bg-gray-50">
                                                                    <div
                                                                        class="flex justify-between items-center mb-2">
                                                                        <label
                                                                            class="block text-xs font-semibold text-gray-700">Semana
                                                                            Corrida</label>
                                                                        <button type="button"
                                                                            @click="itemDetails['SC01'].push({type: 'SC01', description: '', amount: 0})"
                                                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+
                                                                            Agregar Sem. Corrida</button>
                                                                    </div>
                                                                    <template
                                                                        x-for="(detail, index) in itemDetails['SC01']"
                                                                        :key="index">
                                                                        <div class="flex gap-2 mb-2 items-center">
                                                                            <input type="text"
                                                                                x-model="detail.description"
                                                                                placeholder="Glosa"
                                                                                class="w-1/2 border-gray-300 rounded-md text-xs p-1.5 focus:ring-indigo-500 focus:border-indigo-500"
                                                                                required>
                                                                            <input type="number" min="0"
                                                                                x-model.number="detail.amount"
                                                                                placeholder="Monto"
                                                                                class="w-1/3 border-gray-300 rounded-md text-xs p-1.5 focus:ring-indigo-500 focus:border-indigo-500"
                                                                                required>
                                                                            <button type="button"
                                                                                @click="itemDetails['SC01'].splice(index, 1)"
                                                                                class="text-red-500 hover:text-red-700"><i
                                                                                    class="fas fa-trash text-xs"></i></button>
                                                                        </div>
                                                                    </template>
                                                                    <div class="text-right text-xs font-bold text-gray-600 mt-1"
                                                                        x-show="itemDetails['SC01'].length > 0">
                                                                        Total Sem. Corrida: $<span
                                                                            x-text="new Intl.NumberFormat('es-CL').format(itemDetails['SC01'].reduce((acc, curr) => acc + (parseInt(curr.amount) || 0), 0))"></span>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            @if ($enabledTaxables->contains('code', 'BI01'))
                                                                <div class="border rounded p-3 mb-3 bg-gray-50">
                                                                    <div
                                                                        class="flex justify-between items-center mb-2">
                                                                        <label
                                                                            class="block text-xs font-semibold text-gray-700">Bonos
                                                                            e Incentivos</label>
                                                                        <button type="button"
                                                                            @click="itemDetails['BI01'].push({type: 'BI01', description: '', amount: 0})"
                                                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+
                                                                            Agregar Bono</button>
                                                                    </div>
                                                                    <template
                                                                        x-for="(detail, index) in itemDetails['BI01']"
                                                                        :key="index">
                                                                        <div class="flex gap-2 mb-2 items-center">
                                                                            <input type="text"
                                                                                x-model="detail.description"
                                                                                placeholder="Glosa (ej: Bono Productividad)"
                                                                                class="w-1/2 border-gray-300 rounded-md text-xs p-1.5 focus:ring-indigo-500 focus:border-indigo-500"
                                                                                required>
                                                                            <input type="number" min="0"
                                                                                x-model.number="detail.amount"
                                                                                placeholder="Monto"
                                                                                class="w-1/3 border-gray-300 rounded-md text-xs p-1.5 focus:ring-indigo-500 focus:border-indigo-500"
                                                                                required>
                                                                            <button type="button"
                                                                                @click="itemDetails['BI01'].splice(index, 1)"
                                                                                class="text-red-500 hover:text-red-700"><i
                                                                                    class="fas fa-trash text-xs"></i></button>
                                                                        </div>
                                                                    </template>
                                                                    <div class="text-right text-xs font-bold text-gray-600 mt-1"
                                                                        x-show="itemDetails['BI01'].length > 0">
                                                                        Total Bonos: $<span
                                                                            x-text="new Intl.NumberFormat('es-CL').format(itemDetails['BI01'].reduce((acc, curr) => acc + (parseInt(curr.amount) || 0), 0))"></span>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            @if ($enabledTaxables->contains('code', 'AG01'))
                                                                <div class="border rounded p-3 mb-3 bg-gray-50">
                                                                    <div
                                                                        class="flex justify-between items-center mb-2">
                                                                        <label
                                                                            class="block text-xs font-semibold text-gray-700">Aguinaldos</label>
                                                                        <button type="button"
                                                                            @click="itemDetails['AG01'].push({type: 'AG01', description: '', amount: 0})"
                                                                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">+
                                                                            Agregar Aguinaldo</button>
                                                                    </div>
                                                                    <template
                                                                        x-for="(detail, index) in itemDetails['AG01']"
                                                                        :key="index">
                                                                        <div class="flex gap-2 mb-2 items-center">
                                                                            <input type="text"
                                                                                x-model="detail.description"
                                                                                placeholder="Glosa (ej: Fiestas Patrias)"
                                                                                class="w-1/2 border-gray-300 rounded-md text-xs p-1.5 focus:ring-indigo-500 focus:border-indigo-500"
                                                                                required>
                                                                            <input type="number" min="0"
                                                                                x-model.number="detail.amount"
                                                                                placeholder="Monto"
                                                                                class="w-1/3 border-gray-300 rounded-md text-xs p-1.5 focus:ring-indigo-500 focus:border-indigo-500"
                                                                                required>
                                                                            <button type="button"
                                                                                @click="itemDetails['AG01'].splice(index, 1)"
                                                                                class="text-red-500 hover:text-red-700"><i
                                                                                    class="fas fa-trash text-xs"></i></button>
                                                                        </div>
                                                                    </template>
                                                                    <div class="text-right text-xs font-bold text-gray-600 mt-1"
                                                                        x-show="itemDetails['AG01'].length > 0">
                                                                        Total Aguinaldos: $<span
                                                                            x-text="new Intl.NumberFormat('es-CL').format(itemDetails['AG01'].reduce((acc, curr) => acc + (parseInt(curr.amount) || 0), 0))"></span>
                                                                    </div>
                                                                </div>
                                                            @endif


                                                            <!-- Gratification Display -->
                                                            <div class="flex justify-between items-center mb-1 mt-6">
                                                                <label
                                                                    class="block text-sm font-bold text-slate-800">Gratificación</label>
                                                                <div
                                                                    class="text-right bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200 min-w-[120px]">
                                                                    <span class="text-sm font-black text-slate-700"
                                                                        x-text="'$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.gratification_amount || 0))"></span>
                                                                </div>
                                                            </div>
                                                            <!-- Helper text for Art 50 -->
                                                            @if ($company->gratification_system == 'art_50')
                                                                <div class="text-right mb-4">
                                                                    <span
                                                                        class="text-[10px] text-blue-600 font-bold uppercase tracking-wide bg-blue-50 px-2 py-0.5 rounded">
                                                                        Calculado automáticamente (Art. 50)
                                                                    </span>
                                                                </div>
                                                            @endif

                                                            <!-- Totals -->
                                                            <div
                                                                class="flex justify-between border-b pb-2 mb-2 bg-gray-50 -mx-4 px-4 py-2">
                                                                <span class="text-sm font-bold text-gray-700">Total
                                                                    Haberes (Imponible)</span>
                                                                <span class="text-sm font-bold text-indigo-600"
                                                                    x-text="'$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.gross_salary))"></span>
                                                            </div>

                                                            <!-- Haberes Summary -->
                                                            <div class="mt-6 space-y-2 border-t pt-4">
                                                                <div class="flex justify-between items-center text-sm">
                                                                    <span class="text-gray-600 font-medium">Total
                                                                        Imponible</span>
                                                                    <span class="font-bold text-gray-900"
                                                                        x-text="'$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.gross_salary))"></span>
                                                                </div>

                                                                <div class="p-3 bg-gray-50 rounded-lg space-y-1 mt-2">
                                                                    <div
                                                                        class="flex justify-between items-center text-[11px] uppercase tracking-wider text-gray-400 font-bold">
                                                                        <span>No Imponibles</span>
                                                                    </div>
                                                                    <div
                                                                        class="flex justify-between items-center text-xs">
                                                                        <span class="text-gray-500 italic">Asignación
                                                                            Colación</span>
                                                                        <span class="font-medium text-gray-700"
                                                                            x-text="'$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.meal_allowance))"></span>
                                                                    </div>
                                                                    <div
                                                                        class="flex justify-between items-center text-xs">
                                                                        <span class="text-gray-500 italic">Asignación
                                                                            Movilización</span>
                                                                        <span class="font-medium text-gray-700"
                                                                            x-text="'$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.mobility_allowance))"></span>
                                                                    </div>
                                                                </div>

                                                                <div
                                                                    class="flex justify-between items-center pt-2 text-sm font-black border-t border-gray-100 italic">
                                                                    <span class="text-gray-800">TOTAL HABERES</span>
                                                                    <span class="text-indigo-600"
                                                                        x-text="'$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.total_earnings))"></span>
                                                                </div>
                                                            </div>

                                                            <!-- Descuentos / Deductions -->
                                                            <div class="space-y-2 mt-6 mb-4">
                                                                <h4
                                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 border-b border-gray-100 pb-1">
                                                                    Descuentos Legales</h4>
                                                                <div class="flex justify-between text-xs">
                                                                    <span class="text-gray-500">AFP</span>
                                                                    <span class="text-red-500"
                                                                        x-text="'-$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.afp_amount))"></span>
                                                                </div>
                                                                <div class="flex justify-between text-xs">
                                                                    <span class="text-gray-500">Salud</span>
                                                                    <span class="text-red-500"
                                                                        x-text="'-$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.isapre_amount))"></span>
                                                                </div>
                                                                <div class="flex justify-between text-xs">
                                                                    <span class="text-gray-500">Seguro Cesantía</span>
                                                                    <span class="text-red-500"
                                                                        x-text="'-$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.cesantia_amount))"></span>
                                                                </div>
                                                            </div>

                                                            <!-- Final Payment Summary -->
                                                            <div
                                                                class="bg-indigo-600 text-white -mx-6 px-6 py-4 rounded-b-2xl mt-6 shadow-xl relative overflow-hidden">
                                                                <div class="absolute top-0 right-0 p-2 opacity-10">
                                                                    <i class="fas fa-money-bill-wave text-4xl"></i>
                                                                </div>
                                                                <div
                                                                    class="flex justify-between items-center relative z-10">
                                                                    <span
                                                                        class="text-xs font-bold text-indigo-100 uppercase tracking-widest">Sueldo
                                                                        Líquido Final</span>
                                                                    <span class="text-2xl font-black"
                                                                        x-text="'$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.net_salary))"></span>
                                                                </div>
                                                            </div>
                                                            <span class="text-base font-black text-indigo-700"
                                                                x-text="'$' + new Intl.NumberFormat('es-CL').format(Math.round(selectedLine.net_salary))"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit"
                                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                        Guardar y Recalcular
                                    </button>
                                    <button type="button" @click="showModal = false"
                                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                        Cancelar
                                    </button>
                                </div>
                                </form>
                                </template>
                            </div>
                        </div>
                        <script>
                            function updateLine() {
                                // Construct URL
                                let url =
                                    '{{ route('companies.payroll-periods.update-line', ['company' => $company, 'period' => $period, 'payroll' => ':lineId']) }}';
                                url = url.replace(':lineId', this.selectedLine.id);

                                let allDetails = [
                                    ...this.itemDetails['CO01'],
                                    ...this.itemDetails['SC01'],
                                    ...this.itemDetails['BI01'],
                                    ...this.itemDetails['AG01']
                                ];

                                fetch(url, {
                                        method: 'PUT',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                        },
                                        body: JSON.stringify({
                                            overtime_hours: this.selectedLine.overtime_hours,
                                            otros_descuentos: Math.round(this.selectedLine.otros_descuentos || 0),
                                            gratification_amount: Math.round(this.selectedLine.gratification_amount || 0),
                                            details: allDetails.map(d => ({
                                                type: d.type,
                                                description: d.description || 'Haber asignado',
                                                amount: Math.round(d.amount || 0)
                                            }))
                                        })
                                    })
                                    .then(response => response.text())
                                    .then(text => {
                                        try {
                                            const data = JSON.parse(text);
                                            if (data.success) {
                                                // Update Alpine state with fresh data from server
                                                this.selectedLine = data.payroll;
                                                // Re-map details to categories
                                                this.itemDetails = {
                                                    'CO01': (this.selectedLine.details || []).filter(d => d.concept === 'CO01').map(d =>
                                                        ({
                                                            ...d,
                                                            type: d.concept
                                                        })),
                                                    'SC01': (this.selectedLine.details || []).filter(d => d.concept === 'SC01').map(d =>
                                                        ({
                                                            ...d,
                                                            type: d.concept
                                                        })),
                                                    'BI01': (this.selectedLine.details || []).filter(d => d.concept === 'BI01').map(d =>
                                                        ({
                                                            ...d,
                                                            type: d.concept
                                                        })),
                                                    'AG01': (this.selectedLine.details || []).filter(d => d.concept === 'AG01').map(d =>
                                                        ({
                                                            ...d,
                                                            type: d.concept
                                                        }))
                                                };

                                                this.showToast('Línea actualizada y recalculada con éxito.', 'success');

                                                // Update main table rows if they exist
                                                const formatter = new Intl.NumberFormat('es-CL', {
                                                    style: 'currency',
                                                    currency: 'CLP',
                                                    minimumFractionDigits: 0
                                                });
                                                const formatVal = (val) => formatter.format(val).replace('CLP', '$').trim();

                                                const baseEl = document.getElementById('base-salary-' + data.payroll.id);
                                                if (baseEl) baseEl.textContent = formatVal(data.payroll.base_salary);

                                                const grossEl = document.getElementById('gross-salary-' + data.payroll.id);
                                                if (grossEl) grossEl.textContent = formatVal(data.payroll.gross_salary);

                                                const totalEarningsEl = document.getElementById('total-earnings-' + data.payroll.id);
                                                if (totalEarningsEl) totalEarningsEl.textContent = formatVal(data.payroll.total_earnings);

                                                const deductionsEl = document.getElementById('total-deductions-' + data.payroll.id);
                                                if (deductionsEl) deductionsEl.textContent = '-' + formatVal(data.payroll.total_deductions);

                                                const netEl = document.getElementById('net-salary-' + data.payroll.id);
                                                if (netEl) netEl.textContent = formatVal(data.payroll.net_salary);
                                            } else {
                                                this.showToast('Error en la actualización.', 'error');
                                            }
                                        } catch (e) {
                                            console.error("HTML Error received:", text);
                                            this.showToast("Error interno del servidor.", "error");
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        this.showToast('Error de red al actualizar.', 'error');
                                    });
                            }
                        </script>
                    </div>
            </div>

            @if ($period->status == 'calculated')
                <div class="mt-6 flex justify-end border-t border-gray-200 pt-6">
                    <button type="button" onclick="confirmClosePeriod()"
                        class="bg-green-600 text-white px-6 py-2.5 rounded-lg hover:bg-green-700 transition-colors shadow-sm font-medium flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Cerrar Período y Finalizar
                    </button>
                </div>

                <script>
                    function confirmClosePeriod() {
                        if (confirm(
                                '¿Estás seguro de que deseas cerrar este período? No se podrán hacer más cambios automáticamente.')) {
                            // AJAX or Form submit to close period
                            fetch('{{ route('companies.payroll-periods.update-status', ['company' => $company, 'period' => $period->id]) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        status: 'closed'
                                    })
                                }).then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        window.location.reload();
                                    } else {
                                        alert('Error: ' + data.message);
                                    }
                                });
                        }
                    }
                </script>
            @endif
            @endif
        </div>
    </div>
    </div>
</x-layouts.company>
