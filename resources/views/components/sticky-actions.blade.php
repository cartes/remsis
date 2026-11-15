{{--
    Barra de acciones fija al fondo del área visible (position: sticky).

    Mientras el final del formulario no esté a la vista, la barra queda pegada
    al borde inferior del contenedor con scroll; al llegar al final vuelve a su
    lugar natural. Para que funcione, ningún ancestro entre la barra y el
    contenedor que hace scroll debe tener `overflow-hidden` (usar `overflow-clip`).

    Uso:
        <x-sticky-actions class="-mx-6 -mb-6 px-6 mt-8 rounded-b-lg justify-between">
            <a href="...">Volver</a>
            <button type="submit">Guardar</button>
        </x-sticky-actions>
--}}
<div {{ $attributes->class([
    'sticky bottom-0 z-20 flex items-center gap-3 py-4',
    'bg-white/95 backdrop-blur-sm border-t border-gray-200',
    'shadow-[0_-10px_20px_-14px_rgba(15,23,42,0.35)]',
]) }}>
    {{ $slot }}
</div>
