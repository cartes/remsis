<?php

/*
|--------------------------------------------------------------------------
| Tabla de vigencias de parámetros previsionales
|--------------------------------------------------------------------------
|
| Fuente única de los valores legales que no entrega la API de indicadores
| (UF, UTM e IPC sí se sincronizan en línea). Cada entrada rige desde su período
| (YYYY-MM) hasta que exista una entrada posterior. Al sincronizar un mes,
| MonthlyIndicatorSyncService copia al indicador la entrada vigente.
|
| Para actualizar un valor (reajuste del ingreso mínimo, nuevos topes en enero,
| alza de la cotización del empleador cada agosto, asignación familiar, comisiones
| de AFP), agrega una entrada nueva con el período desde el que rige; no edites
| las anteriores, porque respaldan liquidaciones ya calculadas.
|
| Las tasas están en porcentaje. Los períodos anteriores a la primera entrada
| usan esa primera entrada: revisa el indicador antes de liquidar meses previos.
|
*/

return [
    'periods' => [
        '2026-11' => [
            'source' => 'Previred, indicadores previsionales para remuneraciones de noviembre de 2026',

            // Rentas mínimas imponibles
            'imm_value' => 553553,
            'imm_under_18_over_65' => 412938,
            'imm_non_remunerational' => 356815,

            // Rentas topes imponibles, en UF del último día del mes de la remuneración
            'cap_afp_uf' => 90.0,
            'cap_ips_uf' => 60.0,
            'cap_afc_uf' => 135.2,

            // Cotizaciones de cargo del empleador (Ley 21.735): 3,5% en total entre agosto de 2026 y julio de 2027
            'employer_afp_rate' => 0.10,               // capitalización individual en la AFP
            'employer_protected_return_rate' => 0.90,  // cotización con rentabilidad protegida
            'employer_life_expectancy_rate' => 0.71,   // compensación por expectativa de vida
            'sis_rate' => 1.79,                        // seguro de invalidez y sobrevivencia

            // Seguro de cesantía (Ley 19.728)
            'afc_indefinite_employee_rate' => 0.60,
            'afc_indefinite_employer_rate' => 2.40,
            'afc_fixed_employer_rate' => 3.00,
            'afc_indefinite_11y_employer_rate' => 0.80, // indefinido con 11 años o más: el trabajador no cotiza

            // Seguro de accidentes del trabajo (tasa básica; la adicional depende de cada empresa) y Ley SANNA
            'mutual_base_rate' => 0.90,
            'sanna_rate' => 0.03,

            // Comisión de cada AFP sobre la remuneración imponible (se suma al 10% obligatorio)
            'afp_commissions' => [
                'capital' => 1.44,
                'cuprum' => 1.44,
                'habitat' => 1.27,
                'planvital' => 1.16,
                'provida' => 1.45,
                'modelo' => 0.58,
                'uno' => 0.46,
            ],

            // Asignación familiar: monto por carga según renta
            'family_allowance' => [
                ['bracket_letter' => 'A', 'income_from' => 0, 'income_to' => 649039, 'amount_per_dependent' => 22601],
                ['bracket_letter' => 'B', 'income_from' => 649040, 'income_to' => 947990, 'amount_per_dependent' => 13870],
                ['bracket_letter' => 'C', 'income_from' => 947991, 'income_to' => 1478539, 'amount_per_dependent' => 4382],
                ['bracket_letter' => 'D', 'income_from' => 1478540, 'income_to' => null, 'amount_per_dependent' => 0],
            ],
        ],
    ],
];
