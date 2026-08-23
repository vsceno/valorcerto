<?php

namespace App\Http\Requests\Concerns;

trait NormalizaNumeros
{
    /**
     * Aceita tanto o formato brasileiro digitado ("1.234,56") quanto o formato
     * enviado por inputs numéricos do navegador ("1234.56"), sem corromper
     * nenhum dos dois. A vírgula é o que identifica o formato brasileiro.
     */
    protected function paraDecimal(mixed $valor): mixed
    {
        if (! is_string($valor) || trim($valor) === '') {
            return $valor;
        }

        $valor = trim($valor);

        if (str_contains($valor, ',')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return $valor;
    }

    /**
     * @param  array<int, string>  $campos
     * @return array<string, mixed>
     */
    protected function decimais(array $campos): array
    {
        $normalizados = [];

        foreach ($campos as $campo) {
            if ($this->has($campo)) {
                $normalizados[$campo] = $this->paraDecimal($this->input($campo));
            }
        }

        return $normalizados;
    }
}
