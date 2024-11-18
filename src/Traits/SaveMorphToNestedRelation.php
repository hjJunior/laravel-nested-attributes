<?php

namespace Cfx\LaravelNestedAttributes\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;

// todo: support "Custom Polymorphic Types"
// https://laravel.com/docs/9.x/eloquent-relationships#custom-polymorphic-types
final class SaveMorphToNestedRelation extends PersistableNestedRelation
{
    public function __construct(
        protected Model $model,
        protected MorphTo $relation,
        protected array $params,
        protected array $old_data,
        protected string $relationName,
        protected array $options
    ) {}

    public function save(): bool
    {
        $relationModel = $this->getCurrentRelationModel();

        if ($relationModel === null) {
            return $this->createNewMorphTo();
        }

        if ($this->didPolymorphicChanged()) {
            return $relationModel->delete() && $this->createNewMorphTo();
        }

        return $relationModel->update($this->params);
    }

    private function getCurrentRelationModel()
    {
        $foreignKeyName = $this->relation->getForeignKeyName();
        $morphType = $this->relation->getMorphType();

        $old_morph_class = Arr::get($this->old_data, $morphType);
        $old_morph_id = Arr::get($this->old_data, $foreignKeyName);

        if (! $old_morph_class || ! $old_morph_id) {
            return null;
        }

        return $old_morph_class::find($old_morph_id);
    }

    private function createNewMorphTo()
    {
        $data = collect($this->params)
            ->except($this->model->getKeyName())
            ->toArray();

        $morph = $this->relation->create($data);
        $this->relation->associate($morph);

        $this->model->parentSave($this->options);

        return true;
    }

    private function didPolymorphicChanged(): bool
    {
        $current_type = get_class($this->getCurrentRelationModel());
        $new_type = $this->model->getAttribute($this->relation->getMorphType());

        return $current_type !== $new_type;
    }
}
