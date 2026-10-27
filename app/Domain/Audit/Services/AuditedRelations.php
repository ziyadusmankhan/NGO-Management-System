<?php

namespace App\Domain\Audit\Services;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AuditedRelations extends BelongsToMany
{
    private int $auditDepth = 0;

    public static function make($parent, string $related, string $table, string $foreign, string $other): self
    {
        return (new self((new $related)->newQuery(), $parent, $table, $foreign, $other, $parent->getKeyName(), 'id'))->withTimestamps();
    }

    private function audited(callable $operation)
    {
        if ($this->auditDepth) {
            return $operation();
        }

        return $this->parent->getConnection()->transaction(function () use ($operation) {
            $this->auditDepth++;
            try {
                $before = $this->newPivotQuery()->orderBy($this->relatedPivotKey)->pluck($this->relatedPivotKey)->all();
                $result = $operation();
                $after = $this->newPivotQuery()->orderBy($this->relatedPivotKey)->pluck($this->relatedPivotKey)->all();
                if ($before !== $after) {
                    $label = $this->table === 'user_roles' ? 'roles' : 'permissions';
                    $resolve = fn ($ids) => $this->related->newQuery()->whereKey($ids)->pluck('slug')->sort()->values()->all();
                    app(AuditRecorder::class)->record('accounts', $label.'_changed', ['subject_type' => get_class($this->parent), 'subject_id' => $this->parent->id, 'subject_reference' => class_basename($this->parent).' #'.$this->parent->id, 'before' => [$label => $resolve($before)], 'after' => [$label => $resolve($after)]]);
                }

                return $result;
            } finally {
                $this->auditDepth--;
            }
        });
    }

    public function sync($ids, $detaching = true)
    {
        return $this->audited(fn () => parent::sync($ids, $detaching));
    }

    public function attach($ids, array $attributes = [], $touch = true)
    {
        return $this->audited(fn () => parent::attach($ids, $attributes, $touch));
    }

    public function detach($ids = null, $touch = true)
    {
        return $this->audited(fn () => parent::detach($ids,$touch));
    }
}
