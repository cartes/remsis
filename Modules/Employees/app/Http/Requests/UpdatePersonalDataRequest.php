<?php

namespace Modules\Employees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonalDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee');

        return [
            'first_name'  => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'rut'         => ['required', 'string', 'max:20', Rule::unique('employees', 'rut')->ignore($employeeId)],
            'email'       => ['nullable', 'email', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_date'  => ['nullable', 'date', 'before:today'],
            'gender'      => ['nullable', Rule::in(['male', 'female', 'other'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name'  => 'nombres',
            'last_name'   => 'apellidos',
            'rut'         => 'RUT',
            'email'       => 'correo electrónico',
            'nationality' => 'nacionalidad',
            'birth_date'  => 'fecha de nacimiento',
            'gender'      => 'género',
        ];
    }
}
