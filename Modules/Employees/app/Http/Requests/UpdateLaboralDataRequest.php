<?php

namespace Modules\Employees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLaboralDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'position'      => ['nullable', 'string', 'max:255'],
            'cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'hire_date'     => ['nullable', 'date'],
            'salary'        => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'salary_type'   => ['nullable', Rule::in(['mensual', 'quincenal', 'semanal'])],
            'is_in_payroll' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'position'      => 'cargo',
            'cost_center_id' => 'departamento/área',
            'hire_date'     => 'fecha de ingreso',
            'salary'        => 'sueldo base',
            'salary_type'   => 'tipo de remuneración',
            'is_in_payroll' => 'genera liquidación',
        ];
    }
}
