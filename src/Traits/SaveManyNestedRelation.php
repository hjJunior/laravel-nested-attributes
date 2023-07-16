<?php

namespace Cfx\LaravelNestedAttributes\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;

final class SaveManyNestedRelation extends PersistableNestedRelation
{
  public function __construct(
    protected $model,
    protected Relation $relation,
    protected array $list,
    protected array $old_data,
    protected string $relationName
  ) {
    throw_unless(
      $relation instanceof HasMany || $relation instanceof MorphMany,
      "Only HasMany and MorphMany is supported"
    );
  }

  public function save(): bool
  {
    foreach ($this->list as $params) {
      $relationModel = $this->getCurrentRelationModel($params);

      if ($relationModel === null) {
        $this->createNewRelation($params);
        continue;
      }

      if ($this->allowDestroyNestedAttributes($params)) {
        $relationModel->delete();
        continue;
      }

      $relationModel->update($params);
    }

    return true;
  }

  private function resetRelationQueryBuilder()
  {
    $this->relation = $this->model->{$this->relationName}();
  }

  private function getCurrentRelationModel($params)
  {
    $this->resetRelationQueryBuilder();

    if (!isset($params["id"])) {
      return null;
    }

    return $this->relation->find($params["id"]);
  }

  private function createNewRelation($params)
  {
    $data = collect($params)
      ->except($this->model->getKeyName())
      ->toArray();

    return $this->relation->create($data);
  }
}
