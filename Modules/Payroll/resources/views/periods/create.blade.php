<x-layouts.company :company="$company" activeTab="payroll-periods">
    @section('title', 'Crear Período de Nómina')

    <div class="max-w-4xl mx-auto text-sm" x-data="periodForm()">
        {{-- Header --}}
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-6 border-b border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-600 text-white p-3 rounded-lg shadow-md">
                        <i class="fas fa-calendar-plus text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Crear Período de Nómina</h1>
                        <p class="text-gray-600 mt-1">{{ $company->razon_social }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <form action="{{ route('companies.payroll-periods.store', ['company' => $company]) }}" method="POST"
                class="p-6 space-y-6">
                @csrf

                {{-- Period Dates --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Year --}}
                    <div>
                        <label for="year" class="block text-sm font-semibold text-gray-700 mb-2">
                            Año <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="year" name="year" x-model="year" @change="updateDates()"
                            min="2020" max="2050" value="{{ old('year', $defaultYear) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('year') border-red-500 @enderror"
                            required>
                        @error('year')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Month --}}
                    <div>
                        <label for="month" class="block text-sm font-semibold text-gray-700 mb-2">
                            Mes <span class="text-red-500">*</span>
                        </label>
                        <select id="month" name="month" x-model="month" @change="updateDates()"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('month') border-red-500 @enderror"
                            required>
                            <option value="">Seleccione un mes</option>
                            <option value="1" {{ old('month', $defaultMonth) == 1 ? 'selected' : '' }}>Enero
                            </option>
                            <option value="2" {{ old('month', $defaultMonth) == 2 ? 'selected' : '' }}>Febrero
                            </option>
                            <option value="3" {{ old('month', $defaultMonth) == 3 ? 'selected' : '' }}>Marzo
                            </option>
                            <option value="4" {{ old('month', $defaultMonth) == 4 ? 'selected' : '' }}>Abril
                            </option>
                            <option value="5" {{ old('month', $defaultMonth) == 5 ? 'selected' : '' }}>Mayo
                            </option>
                            <option value="6" {{ old('month', $defaultMonth) == 6 ? 'selected' : '' }}>Junio
                            </option>
                            <option value="7" {{ old('month', $defaultMonth) == 7 ? 'selected' : '' }}>Julio
                            </option>
                            <option value="8" {{ old('month', $defaultMonth) == 8 ? 'selected' : '' }}>Agosto
                            </option>
                            <option value="9" {{ old('month', $defaultMonth) == 9 ? 'selected' : '' }}>Septiembre
                            </option>
                            <option value="10" {{ old('month', $defaultMonth) == 10 ? 'selected' : '' }}>Octubre
                            </option>
                            <option value="11" {{ old('month', $defaultMonth) == 11 ? 'selected' : '' }}>Noviembre
                            </option>
                            <option value="12" {{ old('month', $defaultMonth) == 12 ? 'selected' : '' }}>Diciembre
                            </option>
                        </select>
                        @error('month')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Period Dates --}}
                <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                        <i class="fas fa-calendar text-blue-600"></i>
                        Fechas del Período Trabajado
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Start Date --}}
                        <div>
                            <label for="start_date" class="block text-xs font-semibold text-gray-700 mb-2">
                                Fecha de Inicio <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="start_date" name="start_date" x-model="startDate"
                                value="{{ old('start_date', $startDate) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-xs @error('start_date') border-red-500 @enderror"
                                required>
                            @error('start_date')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- End Date --}}
                        <div>
                            <label for="end_date" class="block text-xs font-semibold text-gray-700 mb-2">
                                Fecha de Término <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="end_date" name="end_date" x-model="endDate"
                                value="{{ old('end_date', $endDate) }}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-xs @error('end_date') border-red-500 @enderror"
                                required>
                            @error('end_date')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Payment Date --}}
                <div>
                    <label for="payment_date" class="block text-sm font-semibold text-gray-700 mb-2">
                        Fecha de Pago (Opcional)
                    </label>
                    <input type="date" id="payment_date" name="payment_date" x-model="paymentDate"
                        value="{{ old('payment_date', $paymentDate) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('payment_date') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">
                        <i class="fas fa-info-circle"></i>
                        Puede definirse posteriormente. Por defecto se sugiere el último día del período.
                    </p>
                    @error('payment_date')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Notes --}}
                <div>
                    <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">
                        Notas (Opcional)
                    </label>
                    <textarea id="notes" name="notes" rows="3" maxlength="1000"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('notes') border-red-500 @enderror"
                        placeholder="Información adicional sobre este período...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Error Summary --}}
                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-exclamation-circle mt-0.5"></i>
                            <div>
                                <p class="font-semibold">Por favor corrija los siguientes errores:</p>
                                <ul class="list-disc list-inside mt-2 text-xs">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('companies.payroll-periods.index', ['company' => $company]) }}"
                        class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-all font-semibold">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all shadow-md font-semibold flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        <span>Guardar como Borrador</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function periodForm() {
            return {
                year: {{ old('year', $defaultYear) }},
                month: {{ old('month', $defaultMonth) }},
                startDate: '{{ old('start_date', $startDate) }}',
                endDate: '{{ old('end_date', $endDate) }}',
                paymentDate: '{{ old('payment_date', $paymentDate) }}',

                updateDates() {
                    if (this.year && this.month) {
                        // Calculate first and last day of selected month
                        const firstDay = new Date(this.year, this.month - 1, 1);
                        const lastDay = new Date(this.year, this.month, 0);

                        this.startDate = this.formatDate(firstDay);
                        this.endDate = this.formatDate(lastDay);
                        this.paymentDate = this.formatDate(lastDay);
                    }
                },

                formatDate(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }
            }
        }
    </script>
</x-layouts.company>
