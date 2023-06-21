<?php

namespace Jose1805\LaravelMicroservices\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait PaginateModels
{
    /**
     * Lista de registros paginada y con filtros aplicados
     *
     * @param Builder $builder
     * @param Request $request
     * @param integer $per_page
     * @return LengthAwarePaginator
     */
    public function paginate(Builder $builder, Request $request, $per_page = 10): LengthAwarePaginator
    {
        return $this->applyFilters($builder, $request)->paginate($request->per_page ?? $per_page)
        ->withQueryString()
        ->withPath(config('services.api_gateway.public_url'));
    }

    /**
     * Aplica filtros de búsqueda y orden a la consulta
     *
     * @param Builder $builder
     * @param Request $request
     * @return Builder
     */
    public function applyFilters(Builder $builder, Request $request): Builder
    {
        $builder = $this->applySearch($builder, $request->search);
        $builder = $this->applySort($builder, $request->sort, $request->direction);
        return $builder;
    }

    /**
     * Filtra consulta con los campos configurados en el modelo y un valor de búsqueda
     *
     * @param Builder $builder
     * @param string $search
     * @return Builder
     */
    public function applySearch(Builder $builder, string $search = null): Builder
    {
        $search_columns = property_exists($this, 'search_columns') && gettype($this->search_columns) == 'array' ?
            $this->search_columns
            : (new ($builder->getModel())())->search_columns;

        if ($search && $search_columns && gettype($search_columns) == 'array') {
            $like = '%'.$search.'%';
            $builder = $builder->where(function ($q) use ($like, $search_columns, $builder) {
                $i = 0;
                foreach($search_columns as $field) {
                    if($i == 0) {
                        $q->where($this->formatFieldName($builder, $field), 'LIKE', $like);
                        $i++;
                    } else {
                        $q->orWhere($this->formatFieldName($builder, $field), 'LIKE', $like);
                    }
                }
            });
        }

        return $builder;
    }

    /**
     * Aplica el orden a una consulta de acuerdo a un campo y la configuración del modelo
     *
     * @param Builder $builder
     * @param string $sort
     * @param string $direction
     * @return Builder
     */
    public function applySort(Builder $builder, string $sort = null, string $direction = null): Builder
    {

        $sort_columns = property_exists($this, 'sort_columns') && gettype($this->sort_columns) == 'array' ?
            $this->sort_columns
            : (new ($builder->getModel())())->sort_columns;

        if($sort_columns && gettype($sort_columns) == 'array') {
            if(in_array($sort, $sort_columns)) {
                $builder = $builder->orderBy($this->formatFieldName($builder, $sort), $direction == 'DESC' ? 'DESC' : 'ASC');
            }
        }
        return $builder;
    }

    /**
     * Nombre de la tabla del modelo asociado en un Builder
     *
     * @param Builder $builder
     * @return string
     */
    public function getTableName(Builder $builder): string
    {
        return (new ($builder->getModel())())->getTable();
    }

    /**
     * Formato correcto de un campo en una consulta (table.column)
     *
     * @param Builder $builder
     * @param string $field
     * @return string
     */
    public function formatFieldName(Builder $builder, string $field): string
    {
        $result = '';
        $data = explode('.', $field);
        if(count($data) > 1) {
            $result = $field;
        } else {
            $result = $this->getTableName($builder).'.'.$field;
        }
        return $result;
    }
}
