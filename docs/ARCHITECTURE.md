Nope, excelente precisión, porque un contador externo sería un usuario que tiene un outsourcing o sea puede tener varios tenants, se entiende, y el no sería colaborador de la empresa si no un controlador total del Tenajt creado (no un súper-admin)
¡Sí, se entiende perfectamente! Y esta distinción es fundamental para la arquitectura multi-tenant de Remsys.

El contador externo es un usuario de la plataforma, pero no un colaborador de las empresas que administra. Puede gestionar varios tenants y tener control administrativo completo sobre ellos, sin convertirse en superadministrador global.

Eso nos obliga a separar tres conceptos:

Usuario: identidad que puede iniciar sesión en Remsys.

Colaborador: persona con vínculo laboral con una empresa.

Administrador de tenant: usuario autorizado para controlar una empresa o entorno empresarial, independientemente de su vínculo laboral.

Arquitectura conceptual
REMSYS
Plataforma SaaS multi-tenant

Superadmin

Administración global de la plataforma

Outsourcing

Administrador de varios tenants autorizados

Empresa A

Tenant
Empresa B

Tenant
Empresa C

Tenant
Ejemplo de un outsourcing que administra tres tenants independientes.
Cómo lo modelaría en Laravel
Entidad

Responsabilidad

users

Identidad global del usuario

tenants

Entornos empresariales independientes

tenant_user

Relación entre usuarios y tenants

employees

Colaboradores con vínculo laboral

roles / permissions

Autorizaciones dentro de cada tenant

outsourcing_accounts

Opcional: organización externa que administra múltiples tenants

La idea es que el contador externo pueda entrar a Remsys y ver las empresas que administra, cambiar de tenant y operar dentro de cada uno.

Por ejemplo, podría administrar remuneraciones, contratos, trabajadores, documentos, configuraciones y usuarios de Empresa A, sin tener acceso a Empresa D.

Control total del tenant no significa control total de Remsys. No podría modificar configuraciones globales, acceder a tenants ajenos ni gestionar la infraestructura SaaS.

Además, mantendría una protección especial: incluso un administrador de tenant debería estar sujeto a auditoría y restricciones sobre operaciones irreversibles.

Una decisión importante
No crearía un rol global llamado contador_externo con permisos ilimitados. Lo trataría como un usuario con membresías administrativas en múltiples tenants.

Eso permite que mañana una consultora de RRHH, un estudio contable o un proveedor de outsourcing de remuneraciones utilice exactamente el mismo modelo.

Y hay otro detalle: el outsourcing podría tener su propio equipo, con operadores que administren solamente determinadas empresas o funciones. Eso nos daría escalabilidad sin rediseñar toda la autorización.

Por ahora dejaría establecido este principio para el punto 0:

Remsys tendrá usuarios globales, colaboradores asociados a empresas y administradores externos con acceso delegado a uno o varios tenants. Las funciones y los permisos serán combinables, contextuales y no dependerán del cargo laboral.

Antes de pasar a otro aspecto, hay una distinción que conviene resolver: ¿para ti un tenant representa siempre una empresa con RUT propio, o un tenant podría representar un estudio contable que administra varias empresas con distintos RUT?

Es una decisión estructural que afecta la base de datos, la facturación del SaaS y toda la navegación.

Me entragas foundatios.md, para agregar en el sistema y todas las instrucciones que neceesite para que tengamos un desarrollo como el pensado
Voy a dejarlo como un documento rector para Remsys, pensado para incorporarlo directamente al repositorio y utilizarlo como contexto de desarrollo con Codex, Claude Code u otros agentes de IA.

El archivo será foundations.md, y no solamente describirá el producto: establecerá las reglas de arquitectura, experiencia de usuario, permisos, multi-tenancy, seguridad, remuneraciones chilenas y criterios de calidad que deberán respetarse durante el desarrollo.

Hay una decisión que dejaré explícitamente pendiente: si cada tenant representa una empresa con RUT o un espacio de administración que puede contener varias empresas. No conviene fijar esa relación sin antes revisar cómo está implementada actualmente.

También incluiré instrucciones para que los agentes no reescriban funcionalidades existentes ni inventen reglas de cálculo laboral, y para que trabajen de forma incremental sobre Laravel 12.

Archivo listo
foundations.md

Markdown · 280 líneas · 20 KB

Descargar foundations.md

El documento incluye 14 secciones, entre ellas:

Visión y alcance de Remsys.

Usuarios, colaboradores, outsourcing y permisos combinables.

Arquitectura multi-tenant y seguridad.

Reglas para remuneraciones chilenas.

Portal del colaborador e intranet de RRHH.

UX, inteligibilidad, semántica y sintaxis.

Benchmark de Buk, Talana, Rex+ y otros.

Roadmap de desarrollo y criterios de aceptación.

15 instrucciones obligatorias para agentes de IA.

Registro de decisiones arquitectónicas pendientes.

Cómo incorporarlo
Guárdalo en la raíz del repositorio:

remsis/
├── app/
├── Modules/
├── database/
├── tests/
├── composer.json
├── README.md
└── foundations.md
Después, puedes darle esta instrucción a Codex o Claude Code:

Lee completamente foundations.md antes de realizar cualquier modificación.

Este documento establece los principios de producto, arquitectura, seguridad, experiencia de usuario y desarrollo de Remsys.

Primero analiza el estado actual del repositorio y contrástalo con las foundations.

No modifiques código todavía.

Entrégame un diagnóstico de compatibilidad, contradicciones, funcionalidades existentes, brechas y decisiones pendientes. Distingue claramente lo implementado de lo propuesto.

Propón un plan de implementación incremental, priorizando seguridad multi-tenant, exactitud de remuneraciones y experiencia de usuario.

No tomes decisiones arquitectónicas irreversibles sin consultarme.

Mi recomendación: primero incorpora este archivo y úsalo para realizar una auditoría de solo lectura. Después resolvemos las decisiones estructurales pendientes antes de comenzar a modificar Remsys.

Así evitamos que cada agente de IA interprete el proyecto de una manera distinta.

foundations.md
Documento
Antes de pasar a otro aspecto, hay una distinción que conviene resolver: ¿para ti un tenant representa siempre una empresa con RUT propio, o un tenant podría representar un estudio contable que administra varias empresas con distintos RUT?
Si, una empresa con rut propio, cada rut es una empresa, cada empresa es un tenant,
¡Perfecto! Entonces queda definida una de las reglas arquitectónicas fundamentales de Remsys:

1 empresa = 1 RUT = 1 tenant.

Esto significa que:

Tenant: representa una empresa identificada por su RUT chileno.

Empresa: tiene sus propios colaboradores, contratos, remuneraciones, documentos, configuraciones y permisos.

Outsourcing: es una organización o usuario externo que puede administrar múltiples tenants, pero no constituye un tenant por el solo hecho de prestar servicios.

Usuario: puede pertenecer a varios tenants, con diferentes permisos en cada uno.

Colaborador: está vinculado laboralmente a una empresa y puede tener funciones administrativas adicionales.

Ejemplo de funcionamiento
Estudio Contable ABC

Administrador externo · Sin tenant propio obligatorio

Empresa A

76.123.456-7

Tenant
Empresa B

77.234.567-8

Tenant
Empresa C

78.345.678-9

Tenant
RUT ficticios para ilustrar la estructura.
Implicaciones técnicas
En Laravel establecería estas invariantes:

tenants.rut debe ser único, normalizado y validado con dígito verificador.

Cada dato empresarial debe pertenecer inequívocamente a un tenant.

Los permisos deben evaluarse dentro del tenant activo.

Un usuario puede tener membresías en múltiples tenants.

El acceso a un tenant nunca debe concederse únicamente porque alguien conozca su ID o RUT.

Un outsourcing puede administrar varios tenants, sin acceder a configuraciones globales de Remsys.

El historial laboral y las remuneraciones deben conservar su contexto empresarial original.

Una precisión adicional: una misma persona podría ser colaboradora de dos empresas diferentes, por lo que la identidad global del usuario no debe confundirse con su vínculo laboral.

Actualización de foundations.md
La decisión ya no debe figurar como pendiente. Debe quedar registrada así:

ADR-001 — Identidad del tenant

En Remsys, cada tenant representa exactamente una empresa identificada por un RUT chileno único. Los estudios contables, consultoras y proveedores de outsourcing pueden administrar múltiples tenants mediante relaciones de autorización, sin constituir por ello un tenant adicional. Los datos laborales, financieros y documentales deben permanecer aislados por tenant.

Esta será una regla obligatoria para las futuras decisiones de arquitectura y desarrollo.

Con esto ya tenemos resuelta la base del modelo multiempresa. Lo siguiente será contrastarla con la implementación actual del repositorio para determinar si requiere ajustes o si ya está correctamente representada.

Vamos, me ayudas con la redacciónde ARCHITECTURE.md
Vamos. Para ARCHITECTURE.md propongo algo más técnico que foundations.md: será el documento que explique cómo debe organizarse Remsys, cómo se relacionan sus módulos y qué reglas no pueden romperse.

La diferencia será:

foundations.md define qué estamos construyendo, para quién y bajo qué principios.

ARCHITECTURE.md define cómo se estructura, cómo se aíslan los tenants, cómo funcionan los permisos, cómo se procesan las remuneraciones y cómo deben integrarse los módulos.

Voy a incorporar la decisión que acabamos de cerrar: cada empresa con RUT propio es un tenant, mientras que los proveedores de outsourcing pueden administrar varios tenants sin ser colaboradores de esas empresas.

También distinguiré la arquitectura objetivo de lo que ya está verificado en el repositorio, para no presentar decisiones propuestas como si estuvieran implementadas.

ARCHITECTURE.md listo
Descargar ARCHITECTURE.md

Markdown · 295 líneas · 21 KB

El documento incluye 16 secciones, con especial atención a:

Multi-tenancy: una empresa, un RUT, un tenant.

Identidad y autorización: separación entre usuarios, colaboradores, vínculos laborales y administradores externos.

Outsourcing: administración de múltiples tenants sin privilegios de superadmin.

Arquitectura modular: responsabilidades de los nueve módulos identificados en Laravel.

Motor de remuneraciones: cálculos deterministas, versionados y auditables.

Experiencia de usuario: gestión empresarial, portal personal e intranet.

Seguridad: aislamiento de datos, permisos contextuales y prevención de accesos cruzados.

Desarrollo con IA: reglas para Codex y Claude Code.

Decisiones pendientes: aspectos que debemos contrastar con el código antes de implementarlos.

Incluí diagramas Mermaid, decisiones arquitectónicas (ADR), criterios de pruebas y una metodología para auditar el repositorio sin modificarlo.

Estructura documental recomendada
remsis/
├── app/
├── Modules/
├── database/
├── tests/
├── foundations.md
├── ARCHITECTURE.md
├── README.md
└── composer.json
Mi recomendación: una vez incorporado, nuestro siguiente paso debería ser la auditoría de arquitectura del repositorio. Ahí comprobaremos si la implementación actual respeta el modelo que acabamos de definir, especialmente en multi-tenancy, permisos y relaciones entre colaboradores y empresas.

No necesitamos reescribir Remsys: necesitamos identificar qué partes podemos conservar, cuáles debemos corregir y cuáles todavía tenemos que construir.

ARCHITECTURE.md
Documento
REMSYS — Arquitectura del sistema
Estado: arquitectura objetivo / ADR inicial. No equivale a una auditoría de implementación.
Fecha: 2026-11-12
Repositorio: https://github.com/cartes/remsis
Documentos relacionados: `foundations.md`, README.md.

1. Propósito y alcance
   Este documento define los límites arquitectónicos, invariantes, responsabilidades de módulos, modelo de identidad y autorización, aislamiento multi-tenant, principios del motor de remuneraciones, integraciones, calidad y evolución de Remsys, una plataforma SaaS chilena de remuneraciones, gestión de personas e información interna.

Distinguir siempre:

Verificado: observado en archivos del repositorio.
Decidido: definición explícita del producto que debe respetarse.
Propuesto: diseño a validar antes de implementarlo.
Pendiente: decisión o hallazgo no confirmado.
Regla de adopción: antes de cambiar código, contrastar este documento con las migraciones, modelos, rutas, middleware, policies, servicios y pruebas existentes. No ejecutar migraciones destructivas para adaptar el código a este texto.

2. Estado técnico conocido y límites de verificación
   Según composer.json de la rama main, consultado el 12-11-2026:

Componente Estado conocido
Framework Laravel ^12.0
PHP ^8.4 (configuración de plataforma 8.4.0)
Modularidad nwidart/laravel-modules ^12.0
Autorización spatie/laravel-permission ^6.20
Autenticación base Laravel Breeze presente en require-dev
Módulos declarados en autoload AdminPanel, Attendance, Companies, Core, Documents, Employees, Payroll, Requests, Users
Los commits recientes mencionan multi-tenancy, sincronización de indicadores mensuales, contratos laborales, diccionario LRE y autorización canAccessCompany. La existencia de un commit o namespace no prueba que un flujo esté completo, sea seguro o funcione en producción.

No verificado aún: motor y versión real de base de datos en producción, estructura exacta de tablas, estrategia actual de tenancy, frontend efectivo, colas, almacenamiento, infraestructura y cobertura de pruebas. El README.md puede estar desactualizado respecto de composer.json; no usarlo como única fuente de verdad.

3. Decisiones arquitectónicas vinculantes
   ADR-001 — Una empresa, un RUT, un tenant (DECIDIDO)
   Invariante: 1 empresa = 1 RUT chileno = 1 tenant.

Cada tenant corresponde a una entidad empleadora identificada por un RUT único y válido.
El RUT se normaliza, se valida con su dígito verificador y se mantiene único a nivel de plataforma, sujeto a reglas explícitas de baja, transferencia o reactivación.
Toda operación empresarial debe ejecutarse dentro de un contexto de tenant inequívoco.
El tenant contiene o delimita colaboradores, contratos, asistencia, remuneraciones, documentos, solicitudes, configuraciones y comunicaciones de esa empresa.
Un estudio contable o proveedor de outsourcing no es automáticamente un tenant: puede administrar varios tenants mediante autorizaciones explícitas.
Un usuario externo con administración total de un tenant no es superadministrador de Remsys.
Precisión: un tenant es una frontera lógica de datos y autorización; esta decisión no obliga a usar una base de datos separada por tenant.

ADR-002 — Identidad separada del vínculo laboral (DECIDIDO)
User: identidad global, autenticación y preferencias de acceso.
Employee / vínculo laboral: persona asociada laboralmente a una empresa, con historial y condiciones de contratación.
TenantMembership: autorización de un usuario para actuar en un tenant, sea o no colaborador de esa empresa.
Un usuario puede tener múltiples membresías, distintos permisos por tenant y vínculos laborales en más de una empresa.
Un colaborador puede tener funciones de remuneraciones, RRHH, administración o comunicaciones; estas funciones son combinables y no se deducen automáticamente de su cargo contractual.
Un contador externo puede administrar tenants sin figurar en sus nóminas.
Los nombres de clases/tablas anteriores son conceptuales. Reutilizar las entidades existentes si cumplen las invariantes; no duplicar modelos por nomenclatura.

ADR-003 — Roles y permisos contextuales (DECIDIDO)
Los roles son agrupaciones configurables de permisos, no tipos rígidos de usuario.
Los permisos se evalúan sobre el tenant activo y, cuando corresponda, sobre recursos y ámbitos más específicos.
La autorización debe aplicarse en backend mediante policies, gates o servicios verificables; ocultar botones en frontend no constituye control de acceso.
Un permiso para calcular no implica aprobar; aprobar no implica publicar; administrar un tenant no implica operar otros.
Accesos excepcionales, delegaciones y cambios de permisos requieren auditoría.
Un superadmin de plataforma es una capacidad distinta, explícita y restringida.
ADR-004 — Modularidad por dominio (DECIDIDO COMO PRINCIPIO)
Mantener límites de dominio claros y dependencias explícitas. No construir un monolito de controladores ni acoplar reglas laborales a Blade, JavaScript o componentes visuales.

ADR-005 — Cálculos deterministas, versionados y auditables (DECIDIDO COMO PRINCIPIO)
Los cálculos de remuneraciones deben poder reconstruirse con las mismas entradas, parámetros y versión de reglas. La IA puede asistir con explicación, búsqueda o validación, pero no reemplazar el motor determinista ni decidir autónomamente resultados legales.

4. Modelo conceptual de relaciones
   Cardinalidades propuestas: usuario N:M tenant mediante membresía; tenant 1:N vínculos laborales; un vínculo laboral puede tener varios contratos o versiones históricas; tenant 1:N períodos/procesos de remuneración. No asumir que una persona física equivale a un único contrato, empleo o usuario.

Pendiente de auditoría: decidir si existe una entidad Person independiente de User y Employee para representar personas sin cuenta, recontrataciones y múltiples vínculos; verificar compatibilidad con tablas actuales.

5. Fronteras de tenancy y seguridad
   5.1 Resolución de contexto
   Autenticar identidad global.
   Obtener tenants autorizados del usuario.
   Seleccionar tenant explícitamente o resolverlo mediante una ruta/subdominio validado.
   Verificar membresía vigente y ámbito de acceso en cada petición.
   Aplicar filtrado de tenant a consultas, comandos, archivos, eventos, colas, caché y exportaciones.
   Revalidar permisos al ejecutar acciones sensibles, incluso si el usuario cambió de tenant entre pantallas.
   Nunca confiar en un tenant_id recibido del cliente sin comprobar la autorización. Prohibir consultas empresariales sin scope cuando no exista una justificación técnica explícita, revisada y probada.

5.2 Estrategia de almacenamiento (PENDIENTE)
La opción de base compartida con tenant_id y restricciones compuestas es una propuesta, no una decisión tomada. Compararla con otras estrategias según infraestructura y código existente. Independientemente de la estrategia, exigir:

Claves foráneas e índices consistentes con el tenant.
Unicidad contextual (tenant_id + identificador local) cuando corresponda.
Prevención de asociaciones cruzadas entre recursos de tenants distintos.
Pruebas de aislamiento para lecturas, escrituras, archivos y procesos asíncronos.
Procedimiento de respaldo, restauración y exportación por tenant.
5.3 Outsourcing
Un proveedor externo puede administrar múltiples tenants autorizados. Si dispone de equipo, las delegaciones a operadores deben ser explícitas, revocables y auditables. La eventual entidad OutsourcingAccount es opcional y pendiente: no crearla sin una necesidad de negocio validada. Un operador no hereda acceso a todos los tenants por pertenecer al estudio.

5.4 Privacidad y seguridad
Aplicar mínimo privilegio, controles contra IDOR, cifrado en tránsito, gestión segura de secretos, protección de archivos privados, registro de accesos sensibles, política de retención y mecanismos de recuperación. Evitar datos personales o remuneraciones completas en logs, URLs, analítica y mensajes de error. Revisar obligaciones chilenas vigentes de protección de datos antes de afirmar cumplimiento normativo.

6. Mapa de dominios y responsabilidades
   Módulo existente Responsabilidad objetivo Límites relevantes
   Core Contexto de tenant, parámetros transversales, eventos compartidos No concentrar toda la lógica de negocio
   Companies Empresa/RUT, configuración corporativa, membresías o su coordinación No confundir empresa con outsourcing
   Users Identidad, autenticación, permisos y perfiles de acceso No almacenar cálculo de remuneraciones
   Employees Personas, vínculos laborales, fichas, contratos y trayectoria Separar datos personales de credenciales
   Payroll Períodos, haberes, descuentos, cálculo, aprobación, publicación y salidas Motor independiente de UI e integraciones
   Attendance Asistencia, incidencias y datos de tiempo Entregar novedades validadas, no calcular sueldo por sí solo
   Documents Contratos, anexos, liquidaciones, certificados y versiones Control de visibilidad y evidencia de publicación
   Requests Vacaciones, permisos, licencias y flujos de aprobación Estados y reglas de transición explícitos
   AdminPanel Experiencias de administración y paneles Orquestación/presentación, no reglas de dominio
   Capacidades transversales propuestas: notificaciones, comunicaciones internas, centro de conocimiento, auditoría, integraciones, indicadores y reportes. Pueden empezar dentro de módulos existentes; crear módulos nuevos solo cuando exista un límite de dominio real.

7. Contratos entre módulos
   Los módulos publican operaciones mediante servicios o casos de uso con entradas/salidas definidas; evitar que un controlador de un módulo manipule directamente tablas internas de otro.
   Los eventos de dominio deben describir hechos (PayrollCalculated, PayrollApproved, DocumentPublished), no instrucciones ambiguas.
   Los listeners y jobs deben ser idempotentes cuando puedan reintentarse.
   Los procesos asíncronos transportan un identificador de tenant validado y reconstituyen su contexto de manera segura.
   Las integraciones externas se encapsulan detrás de adaptadores; no dispersar llamadas HTTP por controladores.
   Versionar contratos públicos/API y registrar cambios incompatibles.
8. Motor de remuneraciones chilenas
   8.1 Flujo objetivo
   8.2 Invariantes
   Calcular por empresa y período, con reglas y parámetros aplicables a ese período.
   Mantener fuentes, fecha de vigencia y versión de indicadores y tablas normativas (por ejemplo, UF, UTM, topes, cotizaciones y otros parámetros aplicables).
   Registrar entradas, reglas utilizadas, resultados intermedios relevantes y resultado final; preservar evidencia suficiente para reproducir el cálculo.
   Diferenciar simulación, borrador, cálculo preliminar, aprobado, cerrado y publicado, con transiciones autorizadas.
   No sobrescribir silenciosamente liquidaciones cerradas: correcciones y reliquidaciones deben preservar trazabilidad y justificación.
   Tratar Previred, LRE, comprobantes y centralización contable como salidas o integraciones con validación propia; no presumir cumplimiento solo porque se genera un archivo.
   Resolver redondeos, precisión monetaria y fechas de vigencia con políticas explícitas y pruebas de casos límite.
   Las reglas laborales complejas deben ser validadas por especialistas y fuentes oficiales vigentes antes de activarse en producción.
   8.3 Calidad del cálculo
   Usar pruebas unitarias con escenarios documentados y resultados esperados validados, pruebas de regresión por período y casos de diferencias de centavos. Prohibir float para importes monetarios críticos cuando pueda introducir errores de precisión; escoger una representación decimal o entera documentada.

9. Experiencias de usuario y arquitectura de información
   Principio: todos los colaboradores acceden a su espacio personal; los usuarios externos autorizados pueden acceder a funciones administrativas sin ser colaboradores. Los menús dependen de permisos y contexto, no de etiquetas laborales fijas.

Dos grandes espacios de experiencia:

Gestión empresarial: administración de empresas, colaboradores, RRHH, remuneraciones, documentos, indicadores y comunicaciones según permisos.
Mi Remsys: información personal, liquidaciones, contratos, vacaciones, solicitudes, beneficios y comunicaciones internas para quien tenga vínculo laboral.
Un usuario con ambos tipos de acceso puede alternar contextos de forma explícita, con identificación visible de la empresa activa. Un outsourcing sin vínculo laboral no debe recibir un portal personal de empleado ficticio.

Lenguaje y accesibilidad
Usar términos laborales chilenos precisos, acompañados de ayuda contextual para no especialistas.
Preferir verbos claros: Calcular remuneraciones, Revisar diferencias, Aprobar nómina, Publicar liquidaciones.
Distinguir acciones reversibles e irreversibles, indicar consecuencias y ofrecer confirmaciones proporcionadas al riesgo.
Estados visibles, errores accionables, carga/progreso comprensible, navegación por teclado y accesibilidad acorde con WCAG aplicable.
No depender exclusivamente de colores, iconos o jerga técnica.
Comparar flujos con Buk, Talana, Rex+, Nubox y otras referencias, pero no copiar interfaces ni asumir características no observadas. 10. Intranet y conocimiento de RRHH
Separar al menos tres ámbitos de contenido:

Información personal privada: liquidaciones, contratos, certificados y solicitudes del colaborador.
Contenido interno del tenant: noticias, políticas, reglamentos, beneficios, procedimientos y comunicaciones para audiencias autorizadas.
Conocimiento general de RRHH: artículos y orientación normativa, con fuentes, vigencia, responsable editorial y fecha de revisión.
El contenido puede tener estados draft, review, published, archived (nombres ilustrativos), audiencia, autoría y auditoría. La publicación interna nunca debe filtrarse a otro tenant. La información normativa no debe presentarse como asesoría legal definitiva ni confundirse con parámetros de cálculo.

11. Integraciones e indicadores
    Encapsular fuentes de indicadores económicos/previsionales en proveedores intercambiables, con fecha de consulta, vigencia, fuente, validación y mecanismo de corrección.
    Diferenciar dato obtenido, dato validado y dato aplicado en una nómina.
    Manejar fallos de proveedores con reintentos acotados, alertas y bloqueo de cálculos cuando falten parámetros críticos; nunca sustituir silenciosamente por valores inventados.
    Diseñar exportaciones para LRE, Previred, bancos y contabilidad con esquemas versionados, validación, bitácora de generación y controles de acceso.
    Mantener secretos y credenciales por entorno y, cuando corresponda, por tenant.
12. Operación, observabilidad y despliegue
    Propuestas sujetas a la infraestructura real:

Entornos separados de desarrollo, staging y producción.
Migraciones revisadas y reversibles cuando sea posible; respaldos probados antes de cambios críticos.
Colas para importaciones, exportaciones, sincronizaciones y procesamiento pesado; jobs con tenant explícito.
Logs estructurados con correlación de solicitud, módulo, tenant y operación, sin exponer datos sensibles.
Métricas de fallos de cálculo, tiempo de procesamiento, jobs fallidos y errores de integración.
Almacenamiento persistente y privado para documentos; no depender del filesystem efímero del contenedor.
Despliegues con chequeos de salud, rollback y pruebas mínimas posteriores. 13. Estrategia de pruebas y definición de terminado
Cada cambio relevante debe contemplar:

Unitarias: reglas de dominio, cálculos, validación de RUT, transiciones de estados.
Integración: persistencia, módulos, servicios externos simulados y jobs.
Autorización: usuario sin acceso, colaborador, administrador de un tenant, outsourcing multi-tenant, superadmin; acceso cruzado siempre denegado.
E2E: alta de empresa, alta de colaborador, contrato, novedades, cálculo, revisión, aprobación, publicación y consulta personal.
Regresión normativa: períodos históricos, cambios de indicadores y casos límite.
Accesibilidad y UX: formularios, navegación, mensajes y operaciones sensibles.
Una funcionalidad no está terminada solo porque la pantalla se renderice. Requiere reglas correctas, permisos, validaciones, trazabilidad, pruebas, manejo de errores y documentación de cambios.

14. Reglas para Codex, Claude Code y otros agentes
    Leer foundations.md y ARCHITECTURE.md antes de proponer cambios.
    Inspeccionar código real y declarar qué está verificado, supuesto o pendiente.
    Antes de editar, presentar impacto por módulo, datos, permisos, UX y pruebas.
    No cambiar la relación empresa/RUT/tenant sin aprobación explícita.
    No confundir usuario, colaborador, cargo, función, rol y permiso.
    No introducir accesos globales por comodidad ni omitir scope de tenant.
    No duplicar servicios o tablas existentes sin demostrar necesidad.
    No inventar tasas, topes, fórmulas ni interpretaciones de normativa chilena.
    No permitir que una IA genere importes legales definitivos sin motor determinista validado.
    No modificar datos de producción, ejecutar migraciones destructivas ni desplegar sin autorización.
    Trabajar en cambios pequeños, con pruebas y descripción de riesgos.
    Mantener contratos entre módulos y documentar decisiones arquitectónicas (ADR).
    Proponer primero una alternativa compatible con el código existente.
    Si hay conflicto entre documentos y código, informar y solicitar decisión; no resolverlo silenciosamente.
    Nunca afirmar que un requisito está implementado sin evidencia verificable.
15. Decisiones abiertas y auditoría requerida
    ID Pregunta Estado
    ADR-006 ¿Base compartida con tenant_id, esquemas o bases separadas? Pendiente de auditoría
    ADR-007 ¿Modelo Person separado de User y Employee? Pendiente
    ADR-008 ¿Cómo representar empresas administradas por equipos de outsourcing? Pendiente
    ADR-009 ¿Qué frontend, sistema de componentes y estrategia de API se consolidarán? Pendiente
    ADR-010 ¿Qué procesos de remuneraciones están realmente implementados y probados? Pendiente
    ADR-011 ¿Qué política de versionado y conservación tendrán documentos y nóminas? Pendiente
    ADR-012 ¿Qué infraestructura, motor de BD, almacenamiento y colas se usan realmente? Pendiente
    ADR-013 ¿Qué datos y operaciones estarán disponibles a terceros mediante API? Pendiente
    Auditoría inicial obligatoria (solo lectura)
    Levantar árbol de módulos, dependencias, rutas y middlewares.
    Inventariar migraciones, tablas, claves, índices y relaciones.
    Rastrear resolución de tenant y cada control de autorización.
    Identificar servicios de cálculo, fuentes de indicadores, contratos y LRE.
    Revisar frontend, navegación y flujos completos por tipo de usuario.
    Ejecutar o revisar pruebas disponibles en entorno seguro.
    Entregar mapa implementado / parcial / ausente / riesgoso con evidencia de archivo y línea.
    Proponer ADR y roadmap priorizado sin modificar código hasta recibir aprobación.
16. Jerarquía y mantenimiento documental
    En decisiones técnicas, respetar legislación aplicable verificada, seguridad, decisiones explícitas del propietario y comportamiento contractual existente; si hay contradicción, documentarla y escalarla. foundations.md define visión y principios; este archivo define restricciones técnicas y decisiones; los ADR posteriores precisan elecciones particulares. Mantener fecha, motivación, alternativas, consecuencias y estado de cada ADR.

Este documento es una arquitectura objetivo. Su adopción requiere verificar el repositorio y cerrar las decisiones pendientes, no asumir que todo lo descrito ya existe.
