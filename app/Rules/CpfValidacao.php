<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfValidacao implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        if (!preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $value)) {
            $fail ("CPF deve seguir o formato XXX.XXX.XXX-XX!");
        }

        $value = preg_replace( '/[^0-9]/is', '', $value );
        
        if (strlen($value) != 11) {
            $fail ("CPF inserido não é válido!");
        }

        if (preg_match('/(\d)\1{10}/', $value)) {
            $fail ("CPF inserido não é válido!");
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $value[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($value[$c] != $d) {
                $fail ("CPF inserido não é válido!");
            }
        }

        

        //Fonte: https://gist.github.com/rafael-neri/ab3e58803a08cb4def059fce4e3c0e40

    }
}
