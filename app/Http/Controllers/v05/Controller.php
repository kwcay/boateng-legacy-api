<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Http\Controllers\v0_5;

use Auth;
use Session;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller as BaseController;

abstract class Controller extends BaseController
{
    const ERR_STR_INVALID_ID = 'Invalid identifier.';

    /**
     * Internal name used to map controller to its model, views, etc.
     *
     * @var string
     */
    protected $name;


    protected $defaultQueryLimit = 20;


    protected $supportedOrderColumns = ['id' => 'ID'];


    protected $defaultOrderColumn = 'id';


    protected $defaultOrderDirection = 'desc';

    /**
     * Returns a listing of the resource.
     *
     * @return Illuminate\Http\Response
     */
    public function index()
    {
        return $this->indexFromBuilder($this->getModel());
    }

     /**
      * Returns a listing of the resource using the provided query builder.
      *
      * @todo   Restrict access based on roles.
      * @param  Illuminate\Database\Eloquent\Model|Illuminate\Database\Query\Builder $builder
      * @param  array $queryParams
      * @return Illuminate\Http\Response
      */
    public function indexFromBuilder($builder, array $queryParams = [])
    {
        // Add trashed items.
        if (in_array(SoftDeletes::class, class_uses_recursive(get_class($builder)))) {
            $builder = $builder->withTrashed();
        }

        // Query parameters
        $total = $builder->count();

        // Limit
        $limit = (int) $this->request->get('limit', $this->defaultQueryLimit);
        $limit = max($limit, 1);
        $limit = min($limit, $total);

        // Limit options
        $limits = [];
        if ($total > 10) {
            $limits[10] = 10;
        }
        if ($total > 20) {
            $limits[20] = 20;
        }
        if ($total > 30) {
            $limits[30] = 30;
        }
        if ($total > 50) {
            $limits[50] = 50;
        }
        if ($total > 100) {
            $limits[100] = 100;
        }
        if ($total < 200) {
            $limits[$total] = $total;
        }

        // Ordering of results
        $orders = collect($this->supportedOrderColumns);
        $order  = $this->request->get('order', $this->defaultOrderColumn);
        $order  = $orders->has($order) ? $order : $this->defaultOrderColumn;

        // Direction of ordering
        $dirs   = collect(['asc' => 'ascending', 'desc' => 'descending']);
        $dir    = strtolower($this->request->get('dir', $this->defaultOrderDirection));
        $dir    = $dirs->has($dir) ? $dir : $this->defaultOrderDirection;

        // Paginator.
        $page   = $this->request->get('page', 1);
        $paginator = $builder->orderBy($order, $dir)->paginate($limit, ['*'], 'page', $page);

        // Paginator attributes.
        $paginator->appends('limit', $limit);
        $paginator->appends('order', $order);
        $paginator->appends('dir', $dir);

        if (count($queryParams)) {
            foreach ($queryParams as $param) {
                $paginator->appends($param, $this->request->get($param));
            }
        }

        return [
            'total'         => $paginator->total(),
            'limit'         => $paginator->perPage(),
            'limitOptions'  => collect($limits)->keys(),
            'order'         => $order,
            'orderOptions'  => $orders,
            'orderDir'      => $dir,
            'orderDirOptions'   => $dirs,
            'currentPage'   => $paginator->currentPage(),
            'lastPage'      => $paginator->lastPage(),
            'nextPage'      => $paginator->nextPageUrl(),
            'prevPage'      => $paginator->previousPageUrl(),
            'from'          => $paginator->firstItem(),
            'to'            => $paginator->lastItem(),
            'data'          => $paginator->items(),
        ];
    }

    /**
     * Performs a search based on the given query.
     *
     * @param  string $query
     * @return Illuminate\Http\Response
     */
    public function search($query)
    {
        $model = $this->getModel();

        // Retrieve search parameters.
        $options = [
            'offset' => $this->request->input('offset', 0),
            'limit'  => $this->request->input('limit', $model::SEARCH_LIMIT),
            'lang'   => $this->request->input('lang', ''),
        ];

        // Perform search.
        return [
            'results' => $model->search($query, $options)
        ];
    }

    /**
     * Counts the # of records.
     *
     * @return Illuminate\Http\Response
     */
    public function count()
    {
        return $this->getModel()->count();
    }

    /**
     * Shows the specified resource.
     *
     * @todo   Restrict access based on roles.
     * @param  int|string $id
     * @return Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! $model = $this->getModelInstance($id)) {
            return response('Resource Not Found', 404);
        }

        return $model;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Illuminate\Http\Response
     */
    public function store()
    {
        return response('Not Implemented.', 501);
    }

    /**
     * Updates the specified resource in storage.
     *
     * @param  int|string $id
     * @return Illuminate\Http\Response
     */
    public function update($id)
    {
        return response('Not Implemented.', 501);
    }

    /**
     * Removes or trashes the specified resource from storage.
     *
     * @todo   Restrict access based on roles.
     * @param  int $id
     * @return Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return response('Not Implemented.', 501);
    }

    /**
     * Restores a soft-deleted model.
     *
     * @todo   Restrict access based on roles.
     * @param  int|string $id
     * @return Illuminate\Http\Response
     */
    public function restore($id)
    {
        // Retrieve model.
        $className = $this->getModelClassName();
        if (! $model = $className::findTrashed($id)) {
            abort(404);
        }

        // Make sure the model can soft-delete.
        if (! in_array(SoftDeletes::class, class_uses_recursive(get_class($model)))) {
            abort(400);
        }

        return response('Not Implemented.', 501);
    }

    /**
     * Permanently deletes the specified resource from storage.
     *
     * @todo   Restrict access based on roles.
     * @param  int $id
     * @return Illuminate\Http\Response
     */
    public function forceDestroy($id)
    {
        return response('Not Implemented.', 501);
    }

    /**
     * Retrieves the relations and attributes that may be appended to a model.
     *
     * @param array|string $embed   The properties to be appended to a model.
     * @param array $appendable     Those properties which aren't database relations.
     * @return array
     */
    protected function getEmbedArray($embed = null, array $appendable = [])
    {
        // Relations and attributes to append.
        $separator = isset($this->embedSeparator) ? $this->embedSeparator : ',';
        $embed = is_string($embed) ? @explode($separator, $embed) : (array) $embed;

        // Extract the attributes from the list of embeds.
        $attributes = array_intersect($appendable, $embed);

        // Separate the database relations from the appendable attributes.
        foreach ($embed as $key => $embedable) {
            // Remove invalid relations.
            $embedable = preg_replace('/[^0-9a-z_]/i', '', $embedable);
            if (empty($embedable)) {
                unset($embed[$key]);
            }

            if (in_array($embedable, $attributes)) {
                unset($embed[$key]);
            }
        }

        return [
            'relations' => collect($embed),
            'attributes' => collect($attributes),
        ];
    }

    /**
     * Applies the appendable attributes to the model.
     *
     * @abstract    Was meant to be exported to frnkly/laravel-embeds.
     *
     * @param mixed $model      Model to append attributes to.
     * @param array $attributes Attributes to append to the model.
     * @return void
     */
    protected function applyEmbedableAttributes($model, array $attributes = null)
    {
        // TODO: support passing a colletion of models.
        if (is_a($model, 'Illuminate\Support\Collection')) {
        }

        // Retrieve list of appendable attributes.
        if (is_null($attributes)) {
            $className = get_class($model);
            if (isset($className::$embedableAttributes) && is_array($className::$embedableAttributes)) {
                $attributes = $className::$embedableAttributes;
            }

            // ...
            elseif (isset($model->embedableAttributes) && is_array($model->embedableAttributes)) {
                $attributes = $model->embedableAttributes;
            }

            // ...
            else {
                $attributes = [];
            }
        }

        // Append extra attributes.
        if (count($attributes)) {
            foreach ($attributes as $accessor) {
                // TODO: support model types other than Illuminate\Database\Eloquent\Model
                $model->setAttribute($accessor, $model->$accessor);
            }
        }
    }

    /**
     * Retrieves the attributes to be updated.
     *
     * @return Illuminate\Support\Collection
     */
    protected function getAttributesFromRequest()
    {
        $className = $this->getModelClassName();

        return $this->request->all(array_flip((new $className)->validationRules));
    }

    protected function error($message, $statusCode)
    {
        // TODO: use $this->response or don't even inject it in the constructor

    }

    /**
     * Determines the class name of the model associated with this controller.
     *
     * @return string
     */
    protected function getModelClassName()
    {
        return '\\App\\Models\\'.ucfirst($this->name);
    }

    /**
     * Retrieves an instance of a model by ID.
     *
     * @param  int $id
     * @return Illuminate\Database\Eloquent\Model|null
     */
    protected function getModelInstance($id)
    {
        $className = $this->getModelClassName();

        return $className::find($id);
    }

    /**
     * Creates a new instance of the model associated with this controller.
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    protected function getModel()
    {
        $className = $this->getModelClassName();

        return new $className;
    }
}
