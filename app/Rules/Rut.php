<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Rut implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->isValidRut($value)) {
            $fail('El RUT ingresado no es válido.');
        }
    }

    /**
     * Validates a Chilean RUT.
     *
     * @param string|null $rut
     * @return bool
     */
    protected function isValidRut(?string $rut): bool
    {
        if (empty($rut)) {
            return true; // Use 'required' rule for presence check
        }

        // Clean common separators (dots, hyphens, spaces)
        $rut = preg_replace('/[^0-9kK]/', '', $rut);

        if (strlen($rut) < 2) {
            return false;
        }

        $dv = substr($rut, -1);
        $numero = substr($rut, 0, strlen($rut) - 1);

        if (!is_numeric($numero)) {
            return false;
        }

        $i = 2;
        $suma = 0;
        foreach (array_reverse(str_split($numero)) as $v) {
            if ($i == 8) {
                $i = 2;
            }
            $suma += $v * $i;
            $i++;
        }

        $dvr = 11 - ($suma % 11);

        if ($dvr == 11) {
            $dvr = 0;
        } elseif ($dvr == 10) {
            $dvr = 'K';
        }

        return strtoupper($dv) == strtoupper((string) $dvr);
    }
}
