<x-layouts.company :company="$company" activeTab="payroll-periods">
    @section('title', 'Períodos de Nómina')

    <div class="max-w-7xl mx-auto text-sm" x-data="periodManager()">
        {{-- Header --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-6 border-b border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="bg-blue-600 text-white p-3 rounded-lg shadow-md">
                            <i class="fas fa-calendar-alt text-2xl"></i>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-800">Períodos de Nómina</h1>
                            <p class="text-gray-600 mt-1">Gestión de períodos mensuales de remuneraciones</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        {{-- Year Filter --}}
                        @if (isset($availableYears) && $availableYears->isNotEmpty())
                            <select x-model="selectedYear" @change="filterByYear()"
                                class="bg-white text-gray-700 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-all font-semibold">
                                @foreach ($availableYears as $year)
                                    <option value="{{ $year }}" {{ $year == $selectedYear ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        <a href="{{ route('companies.payroll-periods.create', ['company' => $company]) }}"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all shadow-md font-semibold flex items-center gap-2">
                            <i class="fas fa-plus"></i>
                            <span>Crear Período</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div
                class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Table --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-4 text-left">Período</th>
                        <th class="px-6 py-4 text-left">Fechas del Período</th>
                        <th class="px-6 py-4 text-left">Fecha de Pago</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($periods as $period)
                        <tr class="hover:bg-gray-50 transition-colors group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-gray-800">{{ $period->getDisplayName() }}</div>
                                @if ($period->notes)
                                    <div class="text-[11px] text-gray-500">{{ Str::limit($period->notes, 40) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                <div class="flex items-center gap-1 text-xs">
                                    <i class="fas fa-calendar text-gray-400"></i>
                                    {{ $period->start_date->format('d/m/Y') }} -
                                    {{ $period->end_date->format('d/m/Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                @if ($period->payment_date)
                                    <div class="flex items-center gap-1 text-xs">
                                        <i class="fas fa-money-bill-wave text-green-500"></i>
                                        {{ $period->payment_date->format('d/m/Y') }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">No definida</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="px-2 py-1 rounded-full text-[10px] uppercase font-bold border {{ $period->getStatusBadgeClass() }}">
                                    {{ $period->getStatusLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div
                                    class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    {{-- Status Change Buttons --}}
                                    @if ($period->status === 'draft')
                                        <button @click="updateStatus({{ $period->id }}, 'open')"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                            title="Abrir Período">
                                            <i class="fas fa-folder-open"></i>
                                        </button>
                                    @elseif($period->status === 'open')
                                        <button @click="updateStatus({{ $period->id }}, 'closed')"
                                            class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition-all"
                                            title="Cerrar Período">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @elseif($period->status === 'closed')
                                        <button @click="updateStatus({{ $period->id }}, 'paid')"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-all"
                                            title="Marcar como Pagado">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    @endif

                                    <button class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition-all"
                                        title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="opacity-30">
                                    <i class="fas fa-calendar-times text-5xl mb-4"></i>
                                    <p class="text-gray-600">No hay períodos registrados para este año</p>
                                    <a href="{{ route('companies.payroll-periods.create', ['company' => $company]) }}"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-semibold mt-2 inline-block">
                                        Crear el primer período
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pagination --}}
            @if ($periods->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $periods->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        function periodManager() {
            return {
                selectedYear: {{ $selectedYear ?? 'null' }},

                filterByYear() {
                    const url = new URL(window.location.href);
                    url.searchParams.set('year', this.selectedYear);
                    window.location.href = url.toString();
                },

                async updateStatus(periodId, newStatus) {
                    if (!confirm('¿Está seguro de cambiar el estado de este período?')) {
                        return;
                    }

                    try {
                        const response = await fetch(
                            `/companies/{{ $company->id }}/payroll-periods/${periodId}/status`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    status: newStatus
                                })
                            });

                        const data = await response.json();

                        if (data.success) {
                            // Show success message and reload
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert(data.message || 'Error al actualizar el estado');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error al actualizar el estado del período');
                    }
                }
            }
        }
    </script>
</x-layouts.company>
