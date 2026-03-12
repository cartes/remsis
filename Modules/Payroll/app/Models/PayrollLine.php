<?php

namespace Modules\Payroll\Models;

/**
 * Legacy compatibility wrapper for code that still imports PayrollLine.
 *
 * @deprecated Use \Modules\Payroll\Models\Payroll instead.
 */
class PayrollLine extends Payroll
{
    protected $table = 'payrolls';
}
