<?php

namespace Preetender\Finder;

use Illuminate\Container\Container;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class ApiResource extends JsonResource
{
    /**
     * Verificar se existe chave na query de consulta
     *
     * @param $param
     * @param $value
     * @return MissingValue|mixed
     */
    public function whenQueryParam($param, $value)
    {
        list($method, $key) = explode(',', $param);

        $query = Container::getInstance()->make('request')->all();

        $index = 0;

        $max = 10;
        
        do {
            if ($index === 0 && isset($query[$method]) && array_key_exists($key, $query[$method])) {
                return value($value);
            }

            $identified = "$method:$index";

            if (isset($query[$identified]) && array_key_exists($key, $query[$identified])) {
                return value($value);
            }

            $index++;
        } while ($index <= $max);

        return new MissingValue;
    }
}
