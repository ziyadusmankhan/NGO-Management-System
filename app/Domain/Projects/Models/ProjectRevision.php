<?php

namespace App\Domain\Projects\Models;

use App\Domain\Committees\Models\Committee;
use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectRevision extends Model
{
    protected $guarded = ['id'];

    use ReadsDecimalMoney;

    protected array $moneyColumns = ['budget'];

    protected function casts(): array
    {
        return ['planned_start' => 'date', 'planned_completion' => 'date', 'submitted_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id')->withTrashed();
    }

    public function committee()
    {
        return $this->belongsTo(Committee::class)->withTrashed();
    }

    public function project()
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
