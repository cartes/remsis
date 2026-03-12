# Employee image design

## Problema

La ficha de nomina de empleados hoy concentra la edicion operativa del registro `Employee`, pero no permite gestionar una imagen visible del trabajador.

El proyecto ya soporta fotos de perfil en `users.profile_photo`, con almacenamiento en disco `public` y visualizacion en layouts y perfil del usuario. Sin embargo, ese flujo no esta integrado en la gestion diaria de empleados desde empresa.

Eso obliga a salir del contexto de RRHH para mantener la foto y deja la nomina sin un identificador visual util en el modal y en el listado.

## Objetivo

Permitir subir y actualizar la foto del empleado directamente desde la ficha de nomina, reutilizando `User.profile_photo` como unica fuente de verdad.

## Decision de diseno

### Fuente de verdad

La imagen del empleado no vivira en `employees`.

Se reutilizara el campo existente `users.profile_photo`, ya que:

- cada `Employee` pertenece a un `User`,
- el sistema ya tiene migracion, almacenamiento y vistas que consumen ese campo,
- evita duplicar estado entre perfil de usuario y ficha laboral.

### Punto de edicion

La carga y cambio de foto ocurrira dentro del modal `Ficha de Nomina`, en la pestana `Personales`.

No se agregara una pantalla nueva ni se movera la edicion al perfil del usuario como flujo principal.

## Alcance

### Incluye

- Mostrar avatar del empleado en el modal de nomina.
- Permitir subir o reemplazar la foto desde el mismo modal.
- Persistir la imagen en `users.profile_photo`.
- Refrescar la respuesta del modal con `employee.user`.
- Mostrar un mini avatar en el listado de empleados junto al nombre.
- Mantener fallback visual con iniciales cuando no exista foto.
- Eliminar la foto anterior del disco al reemplazarla.
- Cubrir el flujo con pruebas backend.

### No incluye

- Crear una columna nueva en `employees`.
- Soportar multiples imagenes por empleado.
- Incorporar recorte, drag and drop o edicion avanzada.
- Agregar borrado explicito de foto en esta fase.
- Cambiar el flujo del perfil general del usuario.

## Arquitectura propuesta

### Frontend

En `companies::partials.payroll_modal`, dentro de `Personales`, se agregara un bloque superior con:

- avatar circular,
- nombre visible,
- boton para seleccionar archivo,
- texto de apoyo para formatos aceptados.

La seleccion del archivo se manejara aparte del objeto JSON actual de `selectedEmployee`, porque el archivo no puede serializarse correctamente en el `axios.put(..., this.selectedEmployee)` existente.

La actualizacion del modal pasara a enviar `FormData`, combinando:

- los campos actuales del empleado,
- el archivo opcional `profile_photo`.

Como el request incluira archivos, el frontend no enviara un `PUT` JSON puro. La implementacion debera usar `multipart/form-data` via `POST` con `_method=PUT`, o una variante equivalente compatible con Laravel/PHP para asegurar que el archivo llegue correctamente al controlador.

En `companies::employees`, el listado mostrara una miniatura al lado del nombre. Si no hay foto, se renderizaran iniciales del empleado.

### Backend

`CompanyEmployeeController@updatePayroll` se ampliara para aceptar `profile_photo` como archivo opcional y actualizar el `user` relacionado del empleado.

El flujo esperado sera:

1. validar datos del empleado y el archivo;
2. actualizar el registro `Employee` con los campos laborales/personales actuales;
3. si existe archivo, guardar primero la nueva imagen en `profile-photos/` del disco `public`;
4. persistir la ruta nueva en `users.profile_photo`;
5. solo despues de una persistencia exitosa, eliminar la foto previa del `User` en disco `public`;
6. devolver `employee->fresh('user')`.

No se creara un endpoint nuevo si el `PUT` actual puede absorber el cambio de manera clara. La meta es preservar la UX y el contrato del modal, ampliandolo de forma compatible.

## Flujo de datos

1. El usuario abre la ficha desde el listado de nomina.
2. `getPayroll()` devuelve `employee` con su `user`, incluida la ruta actual de foto si existe.
3. El modal muestra preview o fallback.
4. El usuario selecciona una imagen y guarda.
5. El frontend envia `multipart/form-data`.
6. El backend actualiza `Employee` y `User`.
7. La respuesta refresca el estado Alpine con la foto nueva.
8. El listado refleja la miniatura actualizada mediante recarga de la vista al cerrar el modal, o mediante actualizacion explicita del item ya renderizado; esta fase recomienda mantener el comportamiento simple y confiable con reload si no existe estado Alpine por fila.

## UX y comportamiento

- Sin foto: se muestran iniciales en un avatar neutro.
- Con foto: se muestra preview circular en modal y listado.
- Boton:
  - `Subir foto` cuando no exista imagen;
  - `Cambiar foto` cuando ya exista.
- La carga de foto ocurre junto con el boton actual de guardar, sin introducir un segundo paso.
- Los errores de validacion del archivo se mostraran dentro del esquema actual de errores del modal.
- El frontend no debe consumir directamente la ruta cruda guardada en `users.profile_photo`; debe trabajar con una URL resoluble para imagen publica, idealmente exponiendo `profile_photo_url` o transformando la ruta con `Storage::url(...)` en el punto donde se serializa la respuesta.

## Validacion y seguridad

- `profile_photo`: nullable, image, mimes:jpg,jpeg,png,webp, max:2048.
- Solo se permitira operar sobre el `user` relacionado al `Employee` ya resuelto por ruta scopeada.
- La implementacion debe preservar el chequeo de acceso del usuario autenticado al `company`/`employee` ya enlazado por tenancy y no introducir rutas paralelas fuera de ese contexto.
- La eliminacion del archivo anterior ocurrira solo cuando exista una nueva imagen valida para persistir.
- No se agregaran catches silenciosos; errores de validacion o storage deben emerger como respuesta fallida del request.

## Testing

Se agregaran pruebas para cubrir:

- actualizacion de ficha con upload de imagen;
- persistencia de la ruta en `users.profile_photo`;
- eliminacion de la imagen anterior al reemplazarla;
- respuesta JSON con `employee.user.profile_photo`;
- respuesta JSON con un valor utilizable para renderizar la imagen publica;
- preservacion del comportamiento actual cuando no se envia archivo.
- error `422` cuando el archivo no cumple reglas;
- no eliminacion de la foto anterior si la nueva persistencia falla;
- rechazo del flujo si existe un mismatch de tenancy/acceso.

## Riesgos y notas

- El modal actual usa JSON plano con Alpine; migrarlo a `FormData` debe hacerse sin romper validaciones ni toasts existentes.
- `Modules\Users\Models\User` hoy no expone `profile_photo` en `$fillable`; si la actualizacion usa asignacion masiva, habra que ajustarlo o asignar el atributo de forma explicita.
- El listado deberia consumir la foto desde `employee.user.profile_photo`, no desde `employee`, para mantener una sola fuente de verdad.
- Las consultas que hoy cargan `user:id,name,email,status` tendran que incluir `profile_photo` y, si se expone, el valor derivado para URL publica.

## Resultado esperado

Al finalizar esta fase, RRHH podra gestionar la foto del empleado desde la misma ficha de nomina, el sistema seguira usando `users.profile_photo` como campo canonico y el listado de empleados ganara una referencia visual inmediata sin duplicar datos.
