<?php

namespace Cfx\LaravelNestedAttributes\Traits;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;

final class SaveOneNestedRelation extends PersistableNestedRelation
{
  public function __construct(
    protected $model,
    protected Relation $relation,
    protected array $params,
    protected array $old_data,
    protected string $relationName
  ) {
    throw_unless(
      $relation instanceof HasOne || $relation instanceof MorphOne,
      "Only HasOne and MorphOne is supported"
    );
  }

  public function save(): bool
  {
    $relationModel = $this->getCurrentRelationModel();

    if ($relationModel === null) {
      return $this->createNewRelation();
    }

    if ($this->allowDestroyNestedAttributes($this->params)) {
      return $relationModel->delete();
    }

    return $relationModel->update($this->params);
  }

  private function getCurrentRelationModel()
  {
    if ($this->model->exists) {
      return null;
    }

    return $this->relation->first();
  }

  private function createNewRelation()
  {
    $data = collect($this->params)
      ->except($this->model->getKeyName())
      ->toArray();

    return $this->relation->create($data);
  }
}
