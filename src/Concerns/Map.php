<?php

namespace Preetender\Finder\Concerns;

use Illuminate\Support\Str;

trait Map
{
    /**
     * Extrair parametros e montar query.
     *
     * @param $parameters
     * @return array
     */
    protected function prepareConditionals($parameters): array
    {
        $params = [];

        $map = fn($column, $parameters) => match (count($parameters)) {
            1 => [$column, '=', $parameters[0]],
            2 => [$column, $parameters[0], $parameters[1]],
            default => [$column, ...$parameters]
        };

        foreach ($parameters as $key => $value) {
            $values = explode(',', $value);

            $params[] = $map(Str::snake($key), $values);
        }

        return $params;
    }
    
    /**
     * Remover caracteres inválidos para montar a sintaxe.
     *
     * @return string
     */
    protected function prepareRaw($expression): string
    {
        return str_replace(['[', ']', '__'], ['', '', ' '], $expression);
    }
}