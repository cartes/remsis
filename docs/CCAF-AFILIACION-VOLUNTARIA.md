# Afiliación voluntaria a CCAF

Revisión de fuentes realizada el 13 de noviembre de 2026.

La empresa privada puede estar sin afiliación a una Caja de Compensación de Asignación Familiar. El artículo 13 de la Ley N.º 18.833 permite la afiliación de las entidades empleadoras y remite al artículo 11 para el acuerdo de sus trabajadores. La SUSESO confirma expresamente el carácter voluntario para el empleador privado en el Dictamen 3874-2017, cuya ficha indica vigencia «No Alterado». [Artículo 13](https://www.suseso.gob.cl/620/w3-propertyvalue-74240.html), [artículo 11](https://www.suseso.gob.cl/620/w3-propertyvalue-74234.html), [Dictamen 3874-2017](https://www.suseso.gob.cl/612/w3-article-39970.html).

El procedimiento de afiliación se inicia por la entidad empleadora y contempla la expresión de voluntad de los trabajadores. El Compendio de SUSESO detalla las votaciones y las formalidades aplicables. [Compendio, procedimiento de afiliación](https://www.suseso.gob.cl/620/w3-propertyvalue-605093.html).

## Aplicación en Remsis

- Los formularios de creación y edición de empresa ofrecen «Sin Caja de Compensación».
- Esta opción envía un valor vacío que Laravel convierte a null y guarda en companies.ccaf_id, conforme a la validación nullable existente.
- Las empresas con ccaf_id nulo muestran esta opción seleccionada. Elegir una caja guarda su identificador del catálogo.
- Seleccionar esta opción registra la situación de la empresa dentro de Remsis. Una desafiliación efectiva debe cumplir su procedimiento y fecha de vigencia conforme a la normativa. [Ley N.º 18.833, artículo 16](https://www.suseso.gob.cl/612/w3-article-460669.html).

La revisión del cálculo actual muestra que PayrollCalculationService copia la CCAF del colaborador a la liquidación. La configuración de CCAF de la empresa y la del colaborador son campos independientes en la implementación actual.
