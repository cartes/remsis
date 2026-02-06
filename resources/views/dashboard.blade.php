<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-3xl text-brand-dark leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-16">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="bg-gray-50 overflow-hidden shadow-sm rounded-card border border-gray-100">
                <div class="p-10">
                    <h3 class="text-2xl font-semibold text-brand-dark mb-4">Bienvenido a REMSYS</h3>
                    <p class="text-gray-600 text-lg">
                        {{ __("You're logged in!") }} Gestiona las remuneraciones de tu empresa de forma simple y al
                        día.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
