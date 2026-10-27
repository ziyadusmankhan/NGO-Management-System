<?php

namespace App\Domain\Audit\Services;

use App\Domain\Committees\Models\Committee;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AuditedMemberships extends Builder
{
    public static function query(): self
    {
        $c = DB::connection();

        return (new self($c, $c->getQueryGrammar(), $c->getPostProcessor()))->from('committee_members');
    }

    public function insert(array $values)
    {
        return $this->connection->transaction(function () use ($values) {
            $result = parent::insert($values);
            $rows = isset($values['committee_id']) ? [$values] : $values;
            foreach ($rows as $row) {
                $this->record([], $row, 'membership_assigned');
            }

            return $result;
        });
    }

    public function update(array $values)
    {
        return $this->connection->transaction(function () use ($values) {
            $before = (clone $this)->lockForUpdate()->get();
            $result = parent::update($values);
            foreach ($before as $row) {
                $after = DB::table('committee_members')->where('id', $row->id)->first();
                if ((array) $row !== (array) $after) {
                    $this->record((array) $row, (array) $after, 'membership_updated');
                }
            }

            return $result;
        });
    }

    public function delete($id = null)
    {
        return $this->connection->transaction(function () use ($id) {
            $before = (clone $this)->when($id, fn ($q) => $q->where('id', $id))->lockForUpdate()->get();
            $result = parent::delete($id);
            foreach ($before as $row) {
                $this->record((array) $row, [], 'membership_removed');
            }

            return $result;
        });
    }

    private function record(array $before, array $after, string $action): void
    {
        $row = $after ?: $before;
        app(AuditRecorder::class)->record('organization', $action, ['subject_type' => Committee::class, 'subject_id' => $row['committee_id'], 'subject_reference' => 'Committee #'.$row['committee_id'], 'before' => $before, 'after' => $after]);
    }
}
