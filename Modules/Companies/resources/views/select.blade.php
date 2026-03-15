<x-adminpanel::layouts.master>
    @section('title', 'Seleccionar Empresa')

    @section('content')
        <div class="min-h-[80vh] flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl w-full space-y-12">
                <div class="text-center">
                    <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight">
                        Bienvenido de nuevo
                    </h2>
                    <p class="mt-4 text-xl text-gray-500">
                        Selecciona la empresa con la que deseas trabajar hoy
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($companies as $company)
                        <div class="relative group">
                            <form action="{{ route('companies.selected', $company) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                    class="w-full text-left bg-white rounded-3xl border-2 border-transparent hover:border-blue-500 hover:shadow-2xl transition-all duration-300 p-8 flex flex-col h-full shadow-sm">
                                    <div class="flex items-center justify-between mb-6">
                                        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                                            <i class="fas fa-building text-3xl"></i>
                                        </div>
                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                            <i class="fas fa-arrow-right text-blue-600 text-xl"></i>
                                        </div>
                                    </div>
                                    
                                    <h3 class="text-xl font-bold text-gray-900 mb-2 truncate group-hover:text-blue-600 transition-colors">
                                        {{ $company->razon_social ?? $company->name }}
                                    </h3>
                                    
                                    <div class="mt-auto pt-4 border-t border-gray-50 flex flex-col gap-2">
                                        <div class="flex items-center text-sm text-gray-500 font-mono">
                                            <i class="fas fa-id-card w-5 opacity-40"></i>
                                            {{ $company->rut }}
                                        </div>
                                        @if($company->nombre_fantasia)
                                            <div class="flex items-center text-sm text-gray-400 italic">
                                                <i class="fas fa-tag w-5 opacity-40"></i>
                                                {{ $company->nombre_fantasia }}
                                            </div>
                                        @endif
                                    </div>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="text-center pt-8">
                    <p class="text-gray-400 text-sm">
                        ¿No ves tu empresa? Contacta con el administrador del sistema.
                    </p>
                </div>
            </div>
        </div>
    @endsection
</x-adminpanel::layouts.master>
