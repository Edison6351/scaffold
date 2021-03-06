<?php namespace zgldh\Scaffold\Installer\Model;

use Modules\Upload\Models\Upload;
use zgldh\Scaffold\Installer\HtmlFields\BaseField;
use zgldh\Scaffold\Installer\HtmlFields\Factory;

/**
 * Class ModuleInstaller
 * @package zgldh\Scaffold\Installer
 */
class FieldDefinition
{
    /**
     * 字段名 snake_case
     * @var string
     */
    private $name = '';
    /**
     * 数据库字段类型
     * @var string
     */
    private $dbType = '';
    /**
     * 索引类型 'index','unique'
     * @var null
     */
    private $indexType = null;
    /**
     * 数据库是否可以为null
     * @var bool
     */
    private $nullable = true;
    /**
     * 数据库/界面默认值
     * @var null
     */
    private $defaultValue = INF;

    /**
     * 界面控件类型
     * @var string|BaseField
     */
    private $htmlType = 'text';
    /**
     * 界面控件label名
     * @var string
     */
    private $label = '';
    /**
     * 是否必填
     * @var bool
     */
    private $required = false;
    /**
     * Laravel validate 验证机制
     * @var string
     */
    private $validations = '';
    /**
     * 是否可以排序
     * @var bool
     */
    private $sortable = true;
    /**
     * 是否显示在列表页
     * @var bool
     */
    private $inIndex = false;

    /**
     * 是否在列表中搜索
     * @var mixed
     */
    private $searchType = 'inherent';

    /**
     * 本字段和别的Model有什么关系
     * @var mixed
     */
    private $relationship = null;

    /**
     * 用于 relationship 的 where 过滤
     * @var null
     */
    private $relationshipWhere = null;

    /**
     * @var ModelDefinition
     */
    private $model = null;

    /**
     * FieldDefinition constructor.
     * @param string $name 'some_field_name'
     * @param string $dbType
     */
    public function __construct($name = '', $dbType = 'string')
    {
        $this->name = snake_case($name);
        $this->label = $name;
        $this->dbType = $dbType;
    }

    /**
     * @param string $name
     * @return FieldDefinition
     */
    public function name($name)
    {
        $this->name = snake_case($name);
        return $this;
    }

    /**
     * Name as snake_case
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * For English reading name
     * @return string
     */
    public function getReadingName()
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $this->name));
        return $value;
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        $schema = [
            $this->getName(),
            $this->getDbType()
        ];
        if ($this->isNullable()) {
            $schema[] = 'nullable';
        }
        $defaultValue = $this->getDefaultValue();
        if ($defaultValue !== INF) {
            if (is_string($defaultValue)) {
                $schema[] = "default('{$defaultValue})'";
            } else {
                $schema[] = "default({$defaultValue})";
            }
        }
        $indexType = $this->getIndexType();
        if ($indexType) {
            $schema[] = $indexType;
        }

        $schema[] = "comment('{$this->getDbComment()}')";

        $schema = join(':', $schema);
        return $schema;
    }

    private function getDbComment()
    {
        return $this->label;
    }

    /**
     * @param string $dbType
     * @return FieldDefinition
     */
    public function dbType($dbType)
    {
        $this->dbType = $dbType;
        return $this;
    }

    /**
     * @return string
     */
    public function getDbType()
    {
        return $this->dbType;
    }

    /**
     * @return string
     */
    public function getCastType()
    {
        $castType = 'string';
        $dbType = preg_split('/[:\(]/', $this->dbType);
        switch ($dbType[0]) {
            case 'string':
            case 'text':
            case 'mediumText':
            case 'longText':
            case 'uuid':
            case 'char':
            case 'enum':
            case 'ipAddress':
            case 'macAddress':
            case 'binary':
                $castType = 'string';
                break;

            case 'json':
            case 'jsonb':
                $castType = 'array';
                break;

            case 'boolean':
                $castType = 'boolean';
                break;

            case 'date':
                $castType = 'date';
                break;

            case 'dateTime':
            case 'dateTimeTz':
            case 'time':
            case 'timeTz':
                $castType = 'datetime';
                break;

            case 'timestamp':
            case 'timestampTz':
                $castType = 'timestamp';
                break;

            case 'bigIncrements':
            case 'bigInteger':
            case 'increments':
            case 'integer':
            case 'mediumIncrements':
            case 'mediumInteger':
            case 'smallIncrements':
            case 'smallInteger':
            case 'tinyInteger':
            case 'unsignedBigInteger':
            case 'unsignedInteger':
            case 'unsignedMediumInteger':
            case 'unsignedSmallInteger':
            case 'unsignedTinyInteger':
                $castType = 'integer';
                break;

            case 'decimal':
            case 'double':
            case 'float':
                $castType = 'float';
                break;

//            case 'rememberToken':
//            case 'nullableTimestamps':
//            case 'softDeletes':
//            case 'timestamps':
//            case 'timestampsTz':

        }
        return $castType;
    }

    /**
     * @return null
     */
    public function getIndexType()
    {
        return $this->indexType;
    }

    /**
     * @return bool
     */
    public function isUnique()
    {
        return $this->indexType === 'unique';
    }

    /**
     * @return bool
     */
    public function isNullable()
    {
        return $this->nullable;
    }

    /**
     * @return null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param null $defaultValue
     * @return FieldDefinition
     */
    public function defaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
        if ($defaultValue === null) {
            $this->nullable();
            $this->required(false);
        }
        return $this;
    }

    /**
     * @param bool $nullable
     * @return FieldDefinition
     */
    public function nullable($nullable = true)
    {
        $this->nullable = $nullable;
        return $this;
    }

    /**
     * @param null $indexType 'index','unique'
     * @return FieldDefinition
     */
    public function index($indexType)
    {
        $this->indexType = $indexType;
        return $this;
    }

    /**
     * Set this field as unique indexType
     * @return $this
     */
    public function unique()
    {
        $this->indexType = 'unique';
        return $this;
    }

    /**
     * @param string|BaseField $htmlType
     * @return FieldDefinition
     */
    public function htmlType($htmlType)
    {
        $this->htmlType = $htmlType;
        return $this;
    }

    /**
     * @return BaseField
     */
    public function getHtmlType()
    {
        $baseField = Factory::getField($this->htmlType);
        $baseField->setField($this);
        $baseField->setLabel($this->getLabel());
        $baseField->setProperty($this->getName());
        return $baseField;
    }

    /**
     * 是否本字段需要计算
     * @return bool
     */
    public function isRenderFromComputed()
    {
        return count($this->getHtmlType()->getOptions()) > 0;
    }

    /**
     * @param string $label
     * @return FieldDefinition
     */
    public function label($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param bool $required
     * @return FieldDefinition
     */
    public function required($required = true)
    {
        $this->required = $required;
        if ($required) {
            $this->nullable(false);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param string $validations
     * @return FieldDefinition
     */
    public function validations($validations)
    {
        $this->validations = $validations;
        return $this;
    }

    /**
     * @return string
     */
    public function getValidations()
    {
        return $this->validations;
    }

    /**
     * @return FieldDefinition
     */
    public function notSortable()
    {
        $this->sortable = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSortable()
    {
        return $this->sortable;
    }

    /**
     * @param bool $inIndex
     * @return FieldDefinition
     */
    public function inIndex($inIndex = true)
    {
        $this->inIndex = $inIndex;
        return $this;
    }

    /**
     * @return bool
     */
    public function isInIndex()
    {
        return $this->inIndex;
    }

    /**
     * @return FieldDefinition
     */
    public function noSearch()
    {
        $this->searchType = false;
        return $this;
    }

    /**
     * If this field is not searchable
     * @return bool
     */
    public function isNotSearchable()
    {
        return $this->searchType === false;
    }

    /**
     * @param  mixed $searchType
     * @return FieldDefinition
     */
    public function searchType($searchType)
    {
        $this->searchType = $searchType;
        return $this;
    }

    /**
     * @return BaseField
     */
    public function getSearchType()
    {
        $baseField = Factory::getField($this->htmlType);
        $baseField->setField($this);
        $baseField->setLabel($this->getLabel());
        $baseField->setProperty($this->getName());
        return $baseField;
    }

    /**
     * @return mixed
     */
    public function getRelationship()
    {
        if ($this->relationship) {
            return json_decode($this->relationship, true);
        }
        return null;
    }

    /**
     * @return mixed
     */
    public function getRelationshipName()
    {
        if ($this->relationship) {
            $relationship = json_decode($this->relationship, true);
            if ($this->isRelatingMultiple()) {
                $relatedName = $this->getName();
            } elseif ($this->isRelationship(['morphOne', 'morphMany'])) {
                $relatedName = $this->getName();
            } else {
                $relatedName = snake_case(basename($relationship[0]));
            }
            return $relatedName;
        }
        return null;
    }

    /**
     * 是 $relations 的其中之一
     * @param $relations
     * @return bool
     */
    public function isRelationship($relations)
    {
        $relationship = $this->getRelationship();
        if ($relationship && in_array($relationship['type'], $relations)) {
            // Don't generate table field.
            return true;
        }
        return false;
    }

    /**
     * 该 field 不必出现在这个模型的数据表字段中
     * @return bool
     */
    public function isNotTableField()
    {
        return $this->isRelationship(['hasOne', 'hasMany', 'hasManyThrough', 'morphOne', 'morphMany']);
    }

    /**
     * 该 field 对应的关系是不是多个对象？
     * @return bool
     */
    public function isRelatingMultiple()
    {
        return $this->isRelationship(['hasMany', 'hasManyThrough', 'belongsToMany', 'morphMany', 'morphToMany', 'morphedByMany']);
    }

    /**
     * @param mixed $relationship
     * @return FieldDefinition
     */
    public function setRelationship($relationship)
    {
        $this->relationship = $relationship;
        return $this;
    }


    /**
     * Define a one-to-many relationship.
     *
     * @param  string $related
     * @param  string $foreignKey
     * @param  string $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $this->htmlType('select');
        $args = func_get_args();
        $args['type'] = 'hasMany';
        $this->relationship = json_encode($args);
    }

    /**
     * Define a has-many-through relationship.
     *
     * @param  string $related
     * @param  string $through
     * @param  string|null $firstKey
     * @param  string|null $secondKey
     * @param  string|null $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function hasManyThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null)
    {
        $this->htmlType('select');
        $args = func_get_args();
        $args['type'] = 'hasManyThrough';
        $this->relationship = json_encode($args);
    }

    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param  string $related
     * @param  string $name
     * @param  string $type
     * @param  string $id
     * @param  string $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null)
    {
        $this->htmlType('select');
        $args = func_get_args();
        $args['type'] = 'morphMany';
        $this->relationship = json_encode($args);
    }

    /**
     * Define a many-to-many relationship.
     *
     * @param  string $related
     * @param  string $table
     * @param  string $foreignPivotKey
     * @param  string $relatedPivotKey
     * @param  string $parentKey
     * @param  string $relatedKey
     * @param  string $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToMany(
        $related,
        $table = null,
        $foreignPivotKey = null,
        $relatedPivotKey = null,
        $parentKey = null,
        $relatedKey = null,
        $relation = null
    )
    {
        $this->htmlType('select');
        $args = func_get_args();
        $args['type'] = 'belongsToMany';
        $this->relationship = json_encode($args);
    }

    /**
     * Define a polymorphic many-to-many relationship.
     *
     * @param  string $related
     * @param  string $name
     * @param  string $table
     * @param  string $foreignKey
     * @param  string $relatedKey
     * @param  bool $inverse
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function morphToMany(
        $related,
        $name,
        $table = null,
        $foreignKey = null,
        $relatedKey = null,
        $inverse = false
    )
    {
        $this->htmlType('select');
        $args = func_get_args();
        $args['type'] = 'morphToMany';
        $this->relationship = json_encode($args);
    }

    /**
     * Define a polymorphic, inverse many-to-many relationship.
     *
     * @param  string $related
     * @param  string $name
     * @param  string $table
     * @param  string $foreignKey
     * @param  string $relatedKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function morphedByMany($related, $name, $table = null, $foreignKey = null, $relatedKey = null)
    {
        $this->htmlType('select');
        $args = func_get_args();
        $args['type'] = 'morphedByMany';
        $this->relationship = json_encode($args);
    }


    /**
     * Define a one-to-one relationship.
     *
     * @param  string $related
     * @param  string $foreignKey
     * @param  string $localKey
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $args = func_get_args();
        $args['type'] = 'hasOne';
        $this->relationship = json_encode($args);
    }

    /**
     * Define a polymorphic one-to-one relationship.
     *
     * @param  string $related
     * @param  string $name
     * @param  string $type
     * @param  string $id
     * @param  string $localKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function morphOne($related, $name, $type = null, $id = null, $localKey = null)
    {
        $args = func_get_args();
        $args['type'] = 'morphOne';
        $this->relationship = json_encode($args);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     *
     * @param  string $related
     * @param  string $foreignKey
     * @param  string $ownerKey
     * @param  string $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        $args = func_get_args();
        $args['type'] = 'belongsTo';
        $this->relationship = json_encode($args);
    }

    /**
     * Define a polymorphic, inverse one-to-one or many relationship.
     *
     * @param  string $name
     * @param  string $type
     * @param  string $id
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function morphTo($name = null, $type = null, $id = null)
    {
        $args = func_get_args();
        $args['type'] = 'morphTo';
        $this->relationship = json_encode($args);
    }

    /**
     * Generate validation rule for this field.
     * @return array|string
     */
    public function getRule()
    {
        $rules = [];
        if ($this->isRequired()) {
            $rules[] = 'required';
        }
        if ($this->getValidations()) {
            $rules[] = $this->getValidations();
        }

        $rules = join('|', $rules);
        return $rules;
    }

    /**
     * Get field language set term
     * @param bool $forVueJS
     * @return string
     */
    public function getFieldLang($forVueJS = false)
    {
        $model = $this->getModel();
        $langTerm = $model->getModelLang();
        $langTerm .= ".fields.{$this->getName()}";
        if ($forVueJS) {
            $langTerm = $this->decorateFieldLangForVue($langTerm);
        }
        return $langTerm;
    }

    protected function decorateFieldLangForVue($input)
    {
        $input = str_replace('::t.', '.', $input);
        $input = "\$t('{$input}')";
        return $input;
    }

    /**
     * @param ModelDefinition $model
     * @return FieldDefinition
     */
    public function setModel(ModelDefinition $model): FieldDefinition
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return ModelDefinition
     */
    public function getModel(): ModelDefinition
    {
        return $this->model;
    }

    /**
     * 当前 field 是一个 relation， morphOne 一个 upload 对象
     */
    public function upload()
    {
        $this->htmlType('upload');
        $this->morphOne(Upload::class, 'uploadable');
        $this->where('uploads.type', $this->getName());
        $this->addVueEditorComponent('UploadComponent', 'Modules/Upload/resources/assets/Components/Upload.vue');
        return $this;
    }

    /**
     * 当前 field 是一个 relation， morphMany 多个 upload 对象
     */
    public function uploads()
    {
        $this->htmlType('uploads');
        $this->morphMany(Upload::class, 'uploadable');
        $this->where('uploads.type', $this->getName());
// TODO UploadsComponent
//        $this->addVueEditorComponent('UploadsComponent','Modules/Upload/resources/assets/Components/Uploads.vue');
        return $this;
    }

    /**
     * 当前 field 是一个 relation， morphOne 一个 upload 对象
     * 上传一张图片
     */
    public function uploadImage()
    {
        $this->htmlType('uploadImage');
        $this->morphOne(Upload::class, 'uploadable');
        $this->where('uploads.type', $this->getName());
// TODO UploadImageComponent
//        $this->addVueEditorComponent('UploadImageComponent','Modules/Upload/resources/assets/Components/UploadImage.vue');
        return $this;
    }

    /**
     * 当前 field 是一个 relation， morphMany 多个 upload 对象
     * 上传多张图片
     */
    public function uploadImages()
    {
        $this->htmlType('uploadImages');
        $this->morphMany(Upload::class, 'uploadable');
        $this->where('uploads.type', $this->getName());
// TODO UploadImagesComponent
//        $this->addVueEditorComponent('UploadImagesComponent','Modules/Upload/resources/assets/Components/UploadImages.vue');
        return $this;
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $boolean
     * @return FieldDefinition
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $args = func_get_args();
        $this->relationshipWhere = json_encode($args);
        return $this;
    }

    /**
     * @return null
     */
    public function getWhere()
    {
        if ($this->relationshipWhere) {
            return json_decode($this->relationshipWhere, true);
        }
        return null;
    }

    /**
     * 增加一个 Vue Editor 页面要载入的 component
     * @param $name
     * @param $path
     */
    public function addVueEditorComponent($name, $path)
    {
        $this->getModel()->addVueEditorComponents($name, $path);
    }
}