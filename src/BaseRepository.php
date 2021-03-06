<?php namespace zgldh\Scaffold;

use Exception;
use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * Created by PhpStorm.
 * User: zgldh
 * Date: 2016/12/30
 * Time: 0:08
 */
abstract class BaseRepository extends \Prettus\Repository\Eloquent\BaseRepository
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->scopeQuery(function ($model) {
            return $this->getModuleScope($model);
        });
    }

    /**
     * 模型Scope
     */
    protected function getModuleScope($model)
    {
        return $model;
    }

    /**
     * @param $model
     * @param array $with
     * @param null $filter
     * @return mixed
     */
    public function datatables($model = null, array $with = [], $filter = null)
    {
        if ($model == null) {
            $this->applyScope();
            $query = $this->model;
        } elseif (is_a($model, Builder::class)) {
            $query = $model;
        } elseif (is_a($model, \Illuminate\Database\Eloquent\Builder::class)) {
            $query = $model;
        } elseif (is_a($model, Model::class)) {
            $query = $model->newQuery();
        } elseif (is_string($model)) {
            $query = call_user_func($model . '::query');
        } else {
            $this->applyScope();
            $query = $this->model;
        }

        if (is_a($query, Model::class)) {
            $query = $query->newQuery();
        }

        if (count($with) == 1) {
            foreach ($with as $key => $value) {
                if ($value === 'undefined') {
                    unset($with[$key]);
                }
            }
        }

        if (count($with) > 0) {
            $query = call_user_func_array([$query, 'with'], $with);
        }

        $dt = \DataTables::eloquent($query);
        $dt->escapeColumns('~');

        $columns = \Request::input('columns', []);
        if (sizeof($columns) > 0) {
            $dt->filter(function ($query) use ($columns, $filter) {
                if (is_callable($filter)) {
                    $filter($query);
                }

                foreach ($columns as $column) {
                    $advanceSearches = array_get($column, 'search.advance');
                    if ($advanceSearches) {
                        $query->where(function ($q) use ($advanceSearches, $column) {
                            foreach ($advanceSearches as $operator => $value) {
                                $columnNames = explode('.', $column['name']);
                                $this->advanceSearch($q, $columnNames, $operator, $value);
                            }
                        });
                    }
                }
            }, true);
        } elseif ($filter) {
            $dt->filter($filter, true);
        }
        $result = $dt->make(true);
        $this->resetModel();
        return $result;
    }

    private function advanceSearch($query, $columnNames, $operator, $value)
    {
        if (sizeof($columnNames) == 1) {
            $columnName = $columnNames[0];
            if (is_array($value)) {
                foreach ($value as $valueItem) {
                    $query->orWhere($columnName, $operator, $valueItem);
                }
            } else {
                $query->where($columnName, $operator, $value);
            }
        } else {
            $columnName = array_shift($columnNames);
            $query->whereHas($columnName, function ($q) use ($columnNames, $operator, $value) {
                $this->advanceSearch($q, $columnNames, $operator, $value);
            });
        }
    }

    // Inherit from InfyOm\Generator\Common\BaseRepository

    public function findWithoutFail($id, $columns = ['*'])
    {
        try {
            return $this->find($id, $columns);
        } catch (Exception $e) {
            return;
        }
    }

    public function create(array $attributes)
    {
        // Have to skip presenter to get a model not some data
        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);
        $model = parent::create($attributes);
        $this->skipPresenter($temporarySkipPresenter);

        $model = $this->updateRelations($model, $attributes);
        $model->save();

        $result = $this->parserResult($model);
        $model = forward_static_call_array([$this->model(), 'find'], [$model->getKey()]);
        return $model;
    }

    public function update(array $attributes, $id)
    {
        // Have to skip presenter to get a model not some data
        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);
        $model = parent::update($attributes, $id);
        $this->skipPresenter($temporarySkipPresenter);

        $model = $this->updateRelations($model, $attributes);
        $model->save();

        return $this->parserResult($model);
    }


    public function updateRelations($model, $attributes)
    {
        $oldUploadIds = [];
        $newUploadIds = [];
        foreach ($attributes as $key => $val) {
            if (isset($model) &&
                method_exists($model, $key) &&
                is_a(@$model->$key(), 'Illuminate\Database\Eloquent\Relations\Relation')
            ) {
                $relation = $model->$key($key);

                // 处理上传
                $uploadModelClassName = config('upload.upload_model');
                $isRelatedUpload = false;
                if ($uploadModelClassName && is_a($relation->getRelated(), $uploadModelClassName)) {
                    $isRelatedUpload = true;
                }

                $methodClass = get_class($relation);
                switch ($methodClass) {
                    case 'Illuminate\Database\Eloquent\Relations\BelongsToMany':
                        $new_values = array_get($attributes, $key, []);
                        $new_values = $new_values ?: [];
                        if (array_search('', $new_values) !== false) {
                            unset($new_values[array_search('', $new_values)]);
                        }
                        $model->$key()->sync(array_values($new_values));
                        break;
                    case 'Illuminate\Database\Eloquent\Relations\BelongsTo':
                        $model_key = $model->$key()->getForeignKey();
//                        $new_value = array_get($attributes, $model_key, null);
                        $new_value = array_get($attributes, $key, null);
                        $new_value = $new_value == '' ? null : $new_value;
                        $new_value = is_array($new_value) ? array_get($new_value, 'id', null) : $new_value;
                        // 处理上传
                        if ($isRelatedUpload && $model->$model_key != $new_value) {
                            $oldUploadIds[] = $model->$model_key;
                            $newUploadIds[] = $new_value;
                        }

                        $model->$model_key = $new_value;
                        break;
                    case 'Illuminate\Database\Eloquent\Relations\HasOne':
                        break;
                    case 'Illuminate\Database\Eloquent\Relations\HasOneOrMany':
                        break;
                    case 'Illuminate\Database\Eloquent\Relations\HasMany':
                        $new_values = array_get($attributes, $key, []);
                        $new_values = $new_values ?: [];
                        if (array_search('', $new_values) !== false) {
                            unset($new_values[array_search('', $new_values)]);
                        }
                        $model_key = $model->$key($key)->getForeignKeyName();

                        foreach ($new_values as $index => $new_value) {
                            $new_values[$index] = $new_value['id'];
                        }

                        foreach ($model->$key as $rel) {
                            if (!in_array($rel->id, $new_values)) {
                                $rel->$model_key = null;
                                $rel->save();
                            }
                        }

                        if (count($new_values) > 0) {
                            $related = get_class($model->$key()->getRelated());
                            foreach ($new_values as $val) {
                                if (is_string($val) || is_numeric($val)) {
                                    $rel = $related::find($val);
                                    $rel->$model_key = $model->id;
                                    $rel->save();
                                }
                            }
                        }
                        break;
                }

                if ($isRelatedUpload) {
                    switch ($methodClass) {
                        case 'Illuminate\Database\Eloquent\Relations\MorphOne':
                            $new_value = array_get($attributes, $key, null);
                            // TODO
                            // 1. Check upload obj is uploaded by current user
                            // 2. Remove old one.
                            // 3. Update this upload obj to associate to this $model, setup a proper type
                            break;
                        case 'Illuminate\Database\Eloquent\Relations\MorphMany':
                            $new_values = array_get($attributes, $key, []);
                            // TODO
                            // 1. Unassociate old uploads to this $model
                            // 2. Loop
                            // 2. Remove old one.
                            // 3. Update this upload obj to associate to this $model, setup a proper type
                            break;
                    }
                }
            }
        }
        // 处理上传
        if ($oldUploadIds || $newUploadIds) {
            $this->cleanUpUpload($model, $newUploadIds, $oldUploadIds);
        }

        return $model;
    }

    private function cleanUpUpload($model, $newUploadIds = [], $oldUploadIds = [])
    {
        $uploadClassName = config('upload.upload_model');
        $userId = null;
        // 1. un associate old upload
        foreach ($oldUploadIds as $id) {
            $upload = forward_static_call($uploadClassName . '::find', $id);
            if ($upload) {
                $upload->uploadable()->dissociate();
                $upload->save();
            }
        }

        // 2. apply new upload
        foreach ($newUploadIds as $id) {
            $upload = forward_static_call($uploadClassName . '::find', $id);
            if ($upload) {
                $upload->uploadable()->associate($model);
                $upload->save();
                $userId = $upload->user_id;
            }
        }

        // 3. remove useless uploads
        if ($userId) {
            $query = call_user_func($uploadClassName . '::query');
            $uploads = $query->where('user_id', $userId)->whereNull('uploadable_id')->get();
            foreach ($uploads as $upload) {
                $upload->delete();
            }
        }
    }
}