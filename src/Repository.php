<?php

namespace Forecho\LaravelRepository;

use Forecho\LaravelRepository\Contracts\CriteriaInterface;
use Forecho\LaravelRepository\Contracts\RepositoryInterface;
use Forecho\LaravelRepository\Exceptions\RepositoryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @mixin Builder|Model
 */
abstract class Repository implements RepositoryInterface
{
    private Model|Builder $model;

    public string $modelClass;

    /**
     * @var array eg: ['status' => 'desc', 'id' => 'desc']
     */
    public array $defaultOrder = [];

    /**
     * @var array eg: ['type']
     */
    public array $groupBy = [];

    public array $partialMatchAttributes = [];

    public array $booleanAttributes = [];

    public bool $skipCriteria = false;

    /**
     * @link https://github.com/yiisoft/yii2/blob/56400add978427156673947e3c3d389674c93b56/framework/data/Sort.php#L247
     *
     * @var string the name of the parameter that specifies which attributes to be sorted
     * in which direction. Defaults to 'sort'.
     *
     * @see params
     */
    public string $sortParam = 'sort';

    /**
     * @var string the character used to separate different attributes that need to be sorted by.
     */
    public string $separator = ',';

    private Collection $criteria;

    /**
     * @throws RepositoryException
     */
    public function __construct()
    {
        $this->criteria = collect();
        $this->makeModel();
        $this->boot();
    }

    /**
     * The "booting" method of the repository.
     */
    public function boot()
    {
        //
    }

    /**
     * Create model instance.
     *
     * @throws RepositoryException
     */
    public function makeModel(): Builder
    {
        $model = app($this->modelClass);

        if (! $model instanceof Model) {
            throw new RepositoryException("Class {$this->modelClass} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }
        $this->model = $model->newQuery();

        if ($this->groupBy) {
            $this->model->groupBy($this->groupBy);
        }

        return $this->model;
    }

    public function attributeOrders(array $params, array $safeAttributes)
    {
        if (isset($params[$this->sortParam]) && is_scalar($params[$this->sortParam])) {
            $attributes = explode($this->separator, $params[$this->sortParam]);
            foreach ($attributes as $attribute) {
                $descending = false;
                if (strncmp($attribute, '-', 1) === 0) {
                    $descending = true;
                    $attribute = substr($attribute, 1);
                }
                if (in_array(ltrim($attribute, '-'), $safeAttributes, true)) {
                    $this->model->orderBy($attribute, $descending ? 'desc' : 'asc');
                }
            }
        } elseif ($this->defaultOrder) {
            foreach ($this->defaultOrder as $attribute => $direction) {
                $this->model->orderBy($attribute, $direction);
            }
        }
    }

    public function update(array $attributes, $id): Model
    {
        $model = $this->findOrFail($id);
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    public function delete($id = null): int
    {
        $model = $this->findOrFail($id);

        return $model->delete();
    }

    public function search(array $params): static
    {
        $attributes = $this->model->getModel()->getFillable();
        foreach ($params as $name => $value) {
            if (! in_array($name, $attributes)) {
                continue;
            }
            if ($name === $this->sortParam) {
                continue;
            }
            $value = trim($value);
            if ($value === '') {
                continue;
            }
            if (in_array($name, $this->booleanAttributes)) {
                $this->model->where($name, filter_var($value, FILTER_VALIDATE_BOOLEAN));
            } elseif (in_array($name, $this->partialMatchAttributes)) {
                $this->model->where($name, 'like', "%{$value}%");
            } else {
                $this->addCondition($name, $value);
            }
        }
        $this->attributeOrders($params, $attributes);

        return $this;
    }

    protected function addCondition(string $name, string $value)
    {
        switch (true) {
            case stripos($value, '>=') !== false:
                $this->model->where($name, '>=', substr($value, 2));
                break;
            case stripos($value, '<=') !== false:
                $this->model->where($name, '<=', substr($value, 2));
                break;
            case stripos($value, '<') !== false:
                $this->model->where($name, '<', substr($value, 1));
                break;
            case stripos($value, '>') !== false:
                $this->model->where($name, '>', substr($value, 1));
                break;

            case stripos($value, ',') !== false:
                $this->model->whereIn($name, explode(',', $value));
                break;
            case preg_match(config('repository.range_condition_reg'), $value, $matches) == 1:
                // select between
                if (isset($matches[1]) && isset($matches[2])) {
                    $this->model->whereBetween($name, [$matches[1], $matches[2]]);
                }
                break;
            default:
                $this->model->where($name, $value);
                break;
        }
    }

    /**
     * Push Criteria for filter the query
     *
     * @param  string  $criteria
     * @return $this
     *
     * @throws RepositoryException
     */
    public function pushCriteria(string $criteria): static
    {
        $criteria = app($criteria);
        if (! $criteria instanceof CriteriaInterface) {
            throw new RepositoryException('Class '.get_class($criteria).' must be an instance of Forecho\\LaravelRepository\\Contracts\\CriteriaInterface');
        }
        $this->criteria->push($criteria);

        return $this;
    }

    protected function applyCriteria(): static
    {
        if ($this->skipCriteria === true) {
            return $this;
        }

        foreach ($this->criteria as $c) {
            if ($c instanceof CriteriaInterface) {
                $this->model = $c->apply($this->model, $this);
            }
        }

        return $this;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and'): static
    {
        $this->model->where($column, $operator, $value, $boolean);

        return $this;
    }

    public function __call(string $method, array $arguments)
    {
        $this->applyCriteria();
        $result = call_user_func_array([$this->model, $method], $arguments);
        $this->model = app($this->modelClass)->newQuery();

        return $result;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return forward_static_call_array([static::class, $name], $arguments);
    }
}
