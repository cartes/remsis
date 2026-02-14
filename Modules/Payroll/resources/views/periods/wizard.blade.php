<x-layouts.company :company="$company" activeTab="payroll-periods">
    @section('title', 'Cálculo de Nómina - ' . $period->getDisplayName())

    <div class="max-w-7xl mx-auto text-sm">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <div>
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
                    <div x-data="{ showModal: false, selectedLine: null }">
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
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-600 font-medium">
                                                ${{ number_format($line->base_salary, 0, ',', '.') }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-600 font-medium">
                                                ${{ number_format($line->gross_salary, 0, ',', '.') }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-500 font-medium">
                                                -${{ number_format($line->total_deductions, 0, ',', '.') }}
                                            </td>
                                            <td
                                                class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                                ${{ number_format($line->net_salary, 0, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <button @click="selectedLine = {{ $line->toJson() }}; showModal = true"
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
                                            ${{ number_format($lines->sum('base_salary'), 0, ',', '.') }}
                                        </th>
                                        <th scope="col"
                                            class="px-6 py-4 text-right text-xs font-bold text-gray-900 uppercase tracking-wider">
                                            ${{ number_format($lines->sum('gross_salary'), 0, ',', '.') }}
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
                                                                    x-text="'$' + new Intl.NumberFormat('es-CL').format(selectedLine.base_salary)"></span>
                                                            </div>

                                                            <!-- Editable Fields -->
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
                                                                            x-text="'$' + new Intl.NumberFormat('es-CL').format(selectedLine.overtime_amount)"></span>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            <!-- Gratification Display -->
                                                            <div class="flex justify-between items-center mb-2">
                                                                <label
                                                                    class="block text-xs font-semibold text-gray-700 mb-1">Gratificación</label>
                                                                <div class="flex gap-2">
                                                                    <input type="number" min="0"
                                                                        x-model="selectedLine.gratification_amount"
                                                                        @if ($company->gratification_system == 'art_50') readonly @endif
                                                                        class="w-32 border-gray-300 rounded-md text-sm p-1.5 focus:ring-indigo-500 focus:border-indigo-500 text-right {{ $company->gratification_system == 'art_50' ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' }}">
                                                                </div>
                                                            </div>
                                                            <!-- Helper text for Art 50 -->
                                                            @if ($company->gratification_system == 'art_50')
                                                                <p
                                                                    class="text-xs text-blue-600 mb-2 text-right font-medium">
                                                                    Calculado automáticamente (Art. 50)</p>
                                                            @endif

                                                            <!-- Totals -->
                                                            <div
                                                                class="flex justify-between border-b pb-2 mb-2 bg-gray-50 -mx-4 px-4 py-2">
                                                                <span class="text-sm font-bold text-gray-700">Total
                                                                    Haberes (Imponible)</span>
                                                                <span class="text-sm font-bold text-indigo-600"
                                                                    x-text="'$' + new Intl.NumberFormat('es-CL').format(selectedLine.gross_salary)"></span>
                                                            </div>

                                                            <div class="space-y-2 mb-4">
                                                                <div class="flex justify-between text-xs">
                                                                    <span class="text-gray-500">AFP</span>
                                                                    <span class="text-red-500"
                                                                        x-text="'-$' + new Intl.NumberFormat('es-CL').format(selectedLine.afp_amount)"></span>
                                                                </div>
                                                                <div class="flex justify-between text-xs">
                                                                    <span class="text-gray-500">Salud</span>
                                                                    <span class="text-red-500"
                                                                        x-text="'-$' + new Intl.NumberFormat('es-CL').format(selectedLine.isapre_amount)"></span>
                                                                </div>
                                                                <div class="flex justify-between text-xs">
                                                                    <span class="text-gray-500">Seguro Cesantía</span>
                                                                    <span class="text-red-500"
                                                                        x-text="'-$' + new Intl.NumberFormat('es-CL').format(selectedLine.cesantia_amount)"></span>
                                                                </div>
                                                            </div>

                                                            <!-- Other Discounts Editable -->
                                                            <div>
                                                                <label
                                                                    class="block text-xs font-semibold text-gray-700 mb-1">Otros
                                                                    Descuentos</label>
                                                                <input type="number" min="0"
                                                                    x-model="selectedLine.otros_descuentos"
                                                                    class="w-full border-gray-300 rounded-md text-sm p-1.5 focus:ring-indigo-500 focus:border-indigo-500">
                                                            </div>

                                                            <div class="flex justify-between border-t pt-2 mb-2">
                                                                <span class="text-sm font-medium text-gray-500">Total
                                                                    Descuentos</span>
                                                                <span class="text-sm font-bold text-red-600"
                                                                    x-text="'-$' + new Intl.NumberFormat('es-CL').format(selectedLine.total_deductions)"></span>
                                                            </div>

                                                            <div
                                                                class="flex justify-between border-t border-gray-200 pt-3 mt-2 bg-indigo-50 -mx-4 px-4 py-3 rounded-b-lg">
                                                                <span class="text-base font-bold text-gray-900">Líquido
                                                                    a Pagar</span>
                                                                <span class="text-base font-black text-indigo-700"
                                                                    x-text="'$' + new Intl.NumberFormat('es-CL').format(selectedLine.net_salary)"></span>
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
                                        '{{ route('companies.payroll-periods.update-line', ['company' => $company->id, 'period' => $period->id, 'line' => ':lineId']) }}';
                                    url = url.replace(':lineId', this.selectedLine.id);

                                    fetch(url, {
                                            method: 'PUT',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                            },
                                            body: JSON.stringify({
                                                overtime_hours: this.selectedLine.overtime_hours,
                                                otros_descuentos: this.selectedLine.otros_descuentos,
                                                gratification_amount: this.selectedLine.gratification_amount
                                            })
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                window.location.reload();
                                            } else {
                                                alert('Error: ' + data.message);
                                            }
                                        })
                                        .catch(error => {
                                            console.error('Error:', error);
                                            alert('Error al actualizar.');
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
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
