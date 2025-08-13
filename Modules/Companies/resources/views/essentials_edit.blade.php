<x-adminpanel::layouts.master>
  @section('title','Editar datos esenciales')

  @section('content')
  <div class="max-w-2xl bg-white rounded shadow p-6">
    <form method="POST" action="{{ route('companies.essentials.update', $company) }}" class="space-y-5">
      @csrf
      @method('PUT')

      <div>
        <label class="block font-medium mb-1">Razón social <span class="text-red-500">*</span></label>
        <input name="razon_social" value="{{ old('razon_social',$company->razon_social) }}"
               class="w-full border rounded p-2">
        @error('razon_social') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block font-medium mb-1">RUT <span class="text-red-500">*</span></label>
        <input name="rut" value="{{ old('rut',$company->rut) }}"
               class="w-full border rounded p-2">
        @error('rut') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
      </div>

      <div class="flex justify-between">
        <a href="{{ route('companies.edit',$company) }}" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Cancelar</a>
        <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar esenciales</button>
      </div>
    </form>
  </div>
  @endsection
</x-adminpanel::layouts.master>
