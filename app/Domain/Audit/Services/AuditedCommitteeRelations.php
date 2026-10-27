<?php

namespace App\Domain\Audit\Services;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AuditedCommitteeRelations extends BelongsToMany
{
    private int $auditDepth = 0;

    public static function make($parent, string $related, string $foreign, string $other): self
    {
        return (new self((new $related)->newQuery(), $parent, 'committee_members', $foreign, $other, 'id', 'id'))->withTimestamps();
    }

    private function audited(callable $operation)
    {
        if ($this->auditDepth) {
            return $operation();
        }

        return $this->parent->getConnection()->transaction(function () use ($operation) {
            $this->auditDepth++;
            try {
                $before = $this->newPivotQuery()->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
                $result = $operation();
                $after = $this->newPivotQuery()->orderBy('id')->get()->map(fn ($r) => (array) $r)->all();
                if ($before !== $after) {
                    app(AuditRecorder::class)->record('organization', 'committee_membership_changed', ['subject_type' => get_class($this->parent), 'subject_id' => $this->parent->id, 'subject_reference' => class_basename($this->parent).' #'.$this->parent->id, 'before' => ['items' => $before], 'after' => ['items' => $after]]);
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
        return $this->audited(fn () => parent::detach($ids, $touch));
    }

    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        return $this->audited(fn () => parent::updateExistingPivot($id,$attributes,$touch));
    }
}
