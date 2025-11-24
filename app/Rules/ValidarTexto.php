<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidarTexto implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $texto, Closure $fail): void
    {
        $palabras = explode(' ', $texto);
        foreach ($palabras as $palabra) {
            $caracteres = str_split($palabra);
            foreach ($caracteres as $caracter) {
                if (!preg_match('/^[a-zA-ZáéíóúñÁÉÍÓÚÑ]$/', $caracter)) {
                    $fail("El :attribute introducido no es válido");//Si un solo caracter no es válido, el conjunto será falso.
                }
            }
        }
    }
}