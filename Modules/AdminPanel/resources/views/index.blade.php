<x-adminpanel::layouts.master>
    @section('title', 'Dashboard General')

    @section('content')
        <div class="space-y-6 animate-in fade-in duration-700">
            <!-- Header Section -->
            <div class="flex items-center justify-end">
                <div class="flex items-center space-x-3 bg-white p-1 rounded-xl border border-gray-100 shadow-sm">
                    <button
                        class="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 rounded-lg transition-colors flex items-center">
                        <i class="fa-solid fa-calendar-days mr-2"></i>
                        {{ now()->translatedFormat('M d') }} - {{ now()->addMonths(1)->translatedFormat('M d') }}
                    </button>
                    <div class="h-4 w-px bg-gray-200"></div>
                    <button
                        class="px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 rounded-lg transition-colors flex items-center">
                        Mensual
                        <i class="fa-solid fa-chevron-down ml-2 text-[10px]"></i>
                    </button>
                </div>
            </div>

            <!-- KPI Segments (Payroll Centric) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach (['active_companies' => ['Empresas Activas', 'fa-building', 'indigo'], 'active_employees' => ['Empleados Activos', 'fa-user-group', 'blue'], 'social_security' => ['Previred Pendiente', 'fa-shield-heart', 'rose']] as $key => $meta)
                    <div
                        class="bg-white p-6 rounded-[24px] border border-gray-100 shadow-sm hover:shadow-md transition-all group">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-{{ $meta[2] }}-50 flex items-center justify-center text-{{ $meta[2] }}-500 border border-{{ $meta[2] }}-100 group-hover:scale-110 transition-transform">
                                    <i class="fa-solid {{ $meta[1] }}"></i>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-400">{{ $meta[0] }}</h3>
                            </div>
                        </div>
                        <div class="mt-4 flex items-end justify-between">
                            <div>
                                <p class="text-3xl font-black text-gray-800 tracking-tight">{{ $stats[$key]['value'] }}</p>
                            </div>
                            <div @class([
                                'flex items-center px-2 py-0.5 rounded-full text-[10px] font-black tracking-wider',
                                'bg-emerald-50 text-emerald-600' => $stats[$key]['trend'] === 'up',
                                'bg-rose-50 text-rose-600' => $stats[$key]['trend'] === 'down',
                            ])>
                                {{ $stats[$key]['change'] }}
                                <i @class([
                                    'fa-solid ml-1',
                                    'fa-arrow-trend-up' => $stats[$key]['trend'] === 'up',
                                    'fa-arrow-trend-down' => $stats[$key]['trend'] === 'down',
                                ])></i>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Middle Section: Chart & Messages -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Payroll Overview Chart -->
                <div
                    class="lg:col-span-8 bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <div class="flex items-center space-x-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                                <h2 class="text-sm font-bold text-gray-400 uppercase tracking-widest">Historial de Pagos
                                </h2>
                            </div>
                            <p class="text-2xl font-black text-gray-800 mt-1">Crecimiento de Nómina</p>
                        </div>
                        <div class="flex space-x-2">
                            <button
                                class="px-3 py-1.5 text-[10px] font-bold border border-gray-100 rounded-lg text-gray-500 hover:bg-gray-50">Reporte
                                Detallado</button>
                        </div>
                    </div>

                    <!-- Mock Payroll Chart -->
                    <div
                        class="h-64 flex items-end justify-around space-x-4 px-4 bg-slate-50/30 rounded-3xl border border-slate-50/50 pt-8">
                        @foreach ($payrollHistory as $data)
                            <div class="flex-1 flex flex-col items-center group cursor-pointer h-full justify-end">
                                <div class="relative w-full max-w-[100px] flex flex-col-reverse space-y-reverse space-y-1">
                                    <div class="w-full bg-indigo-600 rounded-lg shadow-lg group-hover:bg-indigo-700 transition-all"
                                        style="height: {{ $data['amount'] * 4 }}px"></div>
                                    <div class="w-full bg-indigo-400 rounded-lg opacity-30"
                                        style="height: {{ $data['amount'] * 1.5 }}px"></div>
                                    <div
                                        class="absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[10px] font-black py-1 px-3 rounded-lg opacity-0 group-hover:opacity-100 transition-all whitespace-nowrap z-10 scale-90 group-hover:scale-100 shadow-xl">
                                        ${{ $data['amount'] }}M
                                    </div>
                                </div>
                                <span
                                    class="mt-4 text-xs font-black text-gray-400 uppercase tracking-widest">{{ $data['month'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex justify-center space-x-6">
                        <div class="flex items-center space-x-1.5">
                            <div class="w-2 h-2 rounded-full bg-indigo-600"></div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Sueldos
                                Líquidos</span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <div class="w-2 h-2 rounded-full bg-indigo-200"></div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">Leyes
                                Sociales</span>
                        </div>
                    </div>
                </div>

                <!-- Received Messages (Requested Section) -->
                <div class="lg:col-span-4 bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm flex flex-col">
                    <div class="flex items-center justify-between mb-6 px-2">
                        <h2 class="text-sm font-bold text-gray-800 flex items-center tracking-tight">
                            <i class="fa-solid fa-envelope-open-text text-indigo-500 mr-2 opacity-50"></i>
                            Mensajes Recibidos
                        </h2>
                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 text-[10px] font-black rounded-md">2
                            NUEVOS</span>
                    </div>

                    <div class="flex-1 divide-y divide-gray-50 overflow-y-auto no-scrollbar">
                        @foreach ($messages as $msg)
                            <div
                                class="py-4 hover:bg-slate-50 transition-colors cursor-pointer group relative rounded-2xl px-2">
                                @if (!$msg['is_read'])
                                    <div
                                        class="absolute left-[-4px] top-1/2 -translate-y-1/2 w-1 h-8 bg-indigo-600 rounded-r-full shadow-[0_0_8px_rgba(79,70,229,0.4)]">
                                    </div>
                                @endif
                                <div class="flex space-x-3">
                                    <div
                                        class="w-10 h-10 bg-[#F8F9FB] rounded-xl flex-shrink-0 flex items-center justify-center border border-gray-50 group-hover:bg-white group-hover:border-gray-100 transition-all shadow-sm">
                                        <i
                                            class="fa-solid {{ $msg['icon'] }} text-gray-400 group-hover:text-indigo-500"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <p @class([
                                                'text-xs truncate transition-colors',
                                                'font-black text-slate-800' => !$msg['is_read'],
                                                'text-gray-500' => $msg['is_read'],
                                            ])>
                                                {{ $msg['sender'] }}
                                            </p>
                                            <span
                                                class="text-[9px] font-bold text-gray-300 whitespace-nowrap ml-2 uppercase">{{ $msg['time'] }}</span>
                                        </div>
                                        <p
                                            class="text-[11px] text-gray-400 truncate mt-0.5 group-hover:text-gray-600 transition-colors">
                                            {{ $msg['subject'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <a href="#"
                        class="mt-4 block py-3 text-center text-[10px] font-black text-indigo-600 uppercase tracking-[0.2em] hover:bg-indigo-50 rounded-2xl transition-all border border-transparent hover:border-indigo-100">
                        Ir a Mensajería
                    </a>
                </div>
            </div>

            <!-- Bottom Section: New Users & Tax Calendar -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- New Users Table (Requested Section) -->
                <div class="lg:col-span-8 bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm overflow-hidden">
                    <div class="flex items-center justify-between mb-8 px-2">
                        <h2
                            class="text-sm font-bold text-gray-800 flex items-center tracking-tight uppercase tracking-widest opacity-60">
                            Nuevos Usuarios
                        </h2>
                        <a href="#"
                            class="text-[10px] font-black text-indigo-600 uppercase tracking-widest hover:underline">Gestionar</a>
                    </div>

                    <table class="w-full">
                        <thead>
                            <tr
                                class="text-[10px] font-black text-gray-300 uppercase tracking-[0.15em] border-b border-gray-50">
                                <th class="text-left pb-4 font-black">Usuario</th>
                                <th class="text-left pb-4 font-black">Rol</th>
                                <th class="text-left pb-4 font-black">Estado</th>
                                <th class="text-right pb-4 font-black">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach ($newUsers as $user)
                                <tr class="group hover:bg-slate-50/50 transition-colors">
                                    <td class="py-4">
                                        <div class="flex items-center space-x-3">
                                            <div
                                                class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-xs shadow-sm group-hover:scale-110 transition-transform">
                                                {{ $user['avatar'] }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-slate-800 tracking-tight truncate">
                                                    {{ $user['name'] }}</p>
                                                <p class="text-[10px] text-gray-400 truncate">{{ $user['email'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-xs font-bold text-gray-500 uppercase tracking-tighter">
                                        {{ $user['role'] }}</td>
                                    <td class="py-4">
                                        <span @class([
                                            'px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider',
                                            'bg-emerald-50 text-emerald-600 border border-emerald-100' =>
                                                $user['status'] == 'Activo',
                                            'bg-amber-50 text-amber-600 border border-amber-100' =>
                                                $user['status'] == 'Pendiente',
                                        ])>
                                            {{ $user['status'] }}
                                        </span>
                                    </td>
                                    <td class="py-4 text-right">
                                        <button class="text-gray-300 hover:text-indigo-600 transition-colors">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Tax Calendar (Requested Section) -->
                <div class="lg:col-span-4 bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm flex flex-col">
                    <div class="flex items-center justify-between mb-8 px-2">
                        <h2
                            class="text-sm font-bold text-gray-800 flex items-center tracking-tight uppercase tracking-widest opacity-60">
                            Calendario Tributario
                        </h2>
                        <i class="fa-solid fa-calendar-check text-indigo-400 text-lg opacity-30"></i>
                    </div>

                    <div class="flex-1 space-y-4">
                        @foreach ($taxDates as $tax)
                            <div
                                class="flex items-center space-x-4 p-3 rounded-[24px] border border-gray-50 hover:bg-slate-50 transition-all cursor-pointer group">
                                <div @class([
                                    'w-12 h-12 rounded-2xl flex flex-col items-center justify-center flex-shrink-0 border transition-all group-hover:scale-105 shadow-sm',
                                    'bg-rose-50 text-rose-600 border-rose-100' => $tax['priority'] == 'Urgente',
                                    'bg-amber-50 text-amber-600 border-amber-100' => $tax['priority'] == 'Alta',
                                    'bg-indigo-50 text-indigo-600 border-indigo-100' => in_array(
                                        $tax['priority'],
                                        ['Media', 'Próximo']),
                                ])>
                                    <span
                                        class="text-[10px] font-black uppercase leading-none">{{ explode(' ', $tax['date'])[1] }}</span>
                                    <span
                                        class="text-lg font-black leading-tight">{{ explode(' ', $tax['date'])[0] }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-black text-slate-800 tracking-tight truncate">
                                        {{ $tax['title'] }}</p>
                                    <div class="flex items-center mt-0.5 space-x-2">
                                        <span
                                            class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">{{ $tax['type'] }}</span>
                                        <span class="w-1 h-1 rounded-full bg-gray-200"></span>
                                        <span class="text-[9px] font-black text-indigo-500 uppercase">En
                                            {{ $tax['days_left'] }} días</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endsection
</x-adminpanel::layouts.master>
