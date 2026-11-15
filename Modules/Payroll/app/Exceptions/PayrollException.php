<?php

namespace Modules\Payroll\Exceptions;

use DomainException;
use Modules\Payroll\Models\PayrollPeriod;

/**
 * Errores de negocio del cálculo de nómina que deben mostrarse al usuario tal cual.
 */
class PayrollException extends DomainException
{
    public static function periodLocked(PayrollPeriod $period): self
    {
        return new self(sprintf(
            'El período %s está %s y no admite cálculos ni ediciones.',
            $period->getDisplayName(),
            mb_strtolower($period->getStatusLabel())
        ));
    }

    public static function missingIndicators(PayrollPeriod $period): self
    {
        return new self(sprintf(
            'No hay indicadores previsionales cargados para %s (UF, UTM, topes y tramos). '
            .'Sincronízalos antes de calcular: php artisan remsis:sync-monthly-indicators --period=%04d-%02d',
            $period->getDisplayName(),
            $period->year,
            $period->month
        ));
    }
}
