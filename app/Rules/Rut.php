<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Rut implements ValidationRule
{
    /**
     * Normaliza un RUT chileno al formato canónico sin puntos y con guion (ej: 76123456-K).
     */
    public static function normalize(?string $rut): ?string
    {
        if ($rut === null || trim($rut) === '') {
            return null;
        }

        $cleaned = preg_replace('/[^0-9kK]/', '', $rut) ?? '';

        if ($cleaned === '') {
            return null;
        }

        if (strlen($cleaned) < 2) {
            return strtoupper($cleaned);
        }

        $dv = strtoupper(substr($cleaned, -1));
        $numero = ltrim(substr($cleaned, 0, -1), '0');

        if ($numero === '') {
            $numero = '0';
        }

        return "{$numero}-{$dv}";
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && $value !== null) {
            $fail('El RUT ingresado no es válido.');

            return;
        }

        if (! $this->isValidRut($value)) {
            $fail('El RUT ingresado no es válido.');
        }
    }

    /**
     * Validates a Chilean RUT.
     */
    public function isValidRut(?string $rut): bool
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

        if (! is_numeric($numero)) {
            return false;
        }

        $i = 2;
        $suma = 0;
        foreach (array_reverse(str_split($numero)) as $v) {
            if ($i == 8) {
                $i = 2;
            }
            $suma += (int) $v * $i;
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
