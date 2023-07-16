<?php

namespace Cfx\LaravelNestedAttributes\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

// Based on https://github.com/mits87/eloquent-nested-attributes/blob/0e90a3c906fd97985144ca2cb35ea015bd44590a/src/Traits/HasNestedAttributesTrait.php
trait HasNestedAttributesTrait
{
    private $old_data;

    protected $acceptNestedAttributesFor = [];

    protected $destroyNestedKey = '_destroy';

    protected $supportedRelations = [
        MorphTo::class => SaveMorphToNestedRelation::class,
        HasOne::class => SaveOneNestedRelation::class,
        MorphOne::class => SaveOneNestedRelation::class,
        HasMany::class => SaveManyNestedRelation::class,
        MorphMany::class => SaveManyNestedRelation::class,
    ];

    public function getAcceptNestedAttributesFor(): array
    {
        return $this->acceptNestedAttributesFor;
    }

    public function fill(array $attributes): self
    {
        if (! empty($this->nested)) {
            $this->acceptNestedAttributesFor = [];

            foreach ($this->nested as $attr) {
                if (isset($attributes[$attr])) {
                    $this->acceptNestedAttributesFor[$attr] = $attributes[$attr];
                    unset($attributes[$attr]);
                }
            }
        }

        return parent::fill($attributes);
    }

    public function save(array $options = []): bool
    {
        DB::beginTransaction();

        $this->old_data = $this->getOriginal();

        if (! parent::save($options)) {
            return false;
        }

        foreach ($this->getAcceptNestedAttributesFor() as $attribute => $stack) {
            $relationName = $this->getRelationNameForAttribute($attribute);

            $this->getPersistable($relationName, $stack)->save();
        }

        parent::save($options);

        DB::commit();

        return true;
    }

    private function getRelationNameForAttribute($attribute)
    {
        $methodName = (string) str($attribute)->camel();

        throw_unless(
            method_exists($this, $methodName),
            "The nested attribute relation '$methodName' does not exists."
        );

        return $methodName;
    }

    private function getPersistable(
        $relationName,
        $params
    ): PersistableNestedRelation {
        $relation = $this->{$relationName}();

        $persistable_model = collect($this->supportedRelations)->firstWhere(
            fn ($_, $relationClass) => $relation instanceof $relationClass
        );

        throw_if(
            $persistable_model === null,
            get_class($relation).' is not supported'
        );

        return new $persistable_model(
            $this,
            $relation,
            $params,
            $this->old_data,
            $relationName
        );
    }
}
