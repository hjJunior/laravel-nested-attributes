<?php

namespace Cfx\LaravelNestedAttributes\Traits;

abstract class PersistableNestedRelation
{
  protected $destroyNestedKey = "_destroy";

  abstract function save(): bool;

  protected function allowDestroyNestedAttributes($params): bool
  {
    return isset($params[$this->destroyNestedKey]) &&
      (bool) $params[$this->destroyNestedKey] == true;
  }
}
