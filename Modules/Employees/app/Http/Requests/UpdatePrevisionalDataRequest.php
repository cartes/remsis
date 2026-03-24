<?php

namespace Modules\Employees\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrevisionalDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'health_system'      => ['nullable', Rule::in(['fonasa', 'isapre'])],
            'isapre_id'          => ['nullable', 'exists:isapres,id'],
            'health_contribution'=> ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'afp_id'             => ['nullable', 'exists:afps,id'],
            'contract_type'      => ['nullable', Rule::in(['indefinido', 'plazo_fijo', 'obra_faena', 'honorarios'])],
            'apv_amount'         => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'health_system'       => 'sistema de salud',
            'isapre_id'           => 'Isapre',
            'health_contribution' => 'valor plan de salud',
            'afp_id'              => 'AFP',
            'contract_type'       => 'tipo de contrato',
            'apv_amount'          => 'monto APV',
        ];
    }
}
