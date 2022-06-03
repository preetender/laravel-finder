<?php

namespace Preetender\Finder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Preetender\Finder\Concerns\Map;

final class Interceptor
{
    use Map;

    protected Builder|Model $eloquent;

    public const PARSE_INT = [
        'limit',
        'paginate',
        'take',
        'offset',
        'skip'
    ];


    public function __construct(protected Request $request)
    {
    }

    /**
     * Obtem instancia da requisição atual.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Mapear query string.
     *
     * @param $model
     * @param $newInstance
     * @return mixed
     */
    public function watch($model, $newInstance = false)
    {
        $eloquent = $newInstance ? (new ReflectionClass($model))->newInstance() : $model;

        $fillable = $eloquent instanceof Model ? $eloquent->getFillable() : $eloquent->getModel()->getFillable();

        $params = $this->request->query->all();

        if (count($params) > 0) {

            foreach(Arr::wrap($params) as $method => $parameters) {

                if (preg_match("/^(or)?where((Not)?(Null))$|^(limit|take|offset|skip)$/m", $method)) {
                    $value = [
                        'array' => fn() => array_keys($parameters)[0],
                        'string' => fn() => $parameters
                    ][gettype($parameters)]();

                    if(in_array($method, self::PARSE_INT)) {
                        $value = (int) $value;
                    }

                    $eloquent = $eloquent->{$method}($value);

                    continue;
                }

                if(preg_match("/^(or)?where(Time|Date|Year|Month|Day)?$/m", $method)) {
                    $params = $this->prepareConditionals($parameters);

                    $eloquent = $eloquent->{$method}($params);

                    continue;
                }

                if(preg_match("/^(or)?where((Not)?(In|Between))?$/m", $method)) {

                    foreach($parameters as $column => $values) {
                        $eloquent = $eloquent->{$method}($column, explode(',', $values));
                    }

                    continue;
                }

                if(!method_exists($this, $method)) {

                    abort_if($method !== "strict" && $this->request->has('strict'), 412, "query {$method} not allowed");

                    continue;
                }

                $eloquent = call_user_func_array([$this, $method], [$eloquent, $parameters, $fillable]);
            }
        }

        return $this->request->has('paginate') ? $eloquent->paginate(intval($this->request->paginate)) : $eloquent->get();
    }

    /**
     * @param $value
     * @return mixed
     */
    private function select($eloquent, $value): mixed
    {
        $fields = explode(',', $value);

        return $eloquent->select(...$fields);
    }

    /**
     * @param $column
     * @param $values
     */
    private function orderBy($eloquent, $columns): mixed
    {
        foreach($columns as $column => $value) {
            $eloquent = $eloquent->orderBy($column, $value);
        }

        return $eloquent;
    }

    /**
     * @param $column
     * @param $values
     * @return mixed
     */
    private function groupBy($eloquent, $columns): mixed
    {
        return $eloquent->groupBy($columns);
    }

    /**
     * @param $action
     * @param null $value
     * @return mixed
     */
    private function scope($eloquent, $scopes): mixed
    {
        foreach($scopes as $scope => $parameters) {
            $eloquent = $eloquent->{$scope}($parameters);
        }

        return $eloquent;
    }

    /**
     * @param $relation
     * @param $values
     * @return mixed
     */
    private function with($eloquent, $with): mixed
    {
        foreach($with as $relation => $value) {
            $eloquent = $eloquent->with("{$relation}:$value");
        }

        return $eloquent;
    }
}