<?php

namespace App\Domain\Projects\Models;

use App\Domain\Finance\Support\ReadsDecimalMoney;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProjectBudgetVersion extends Model
{
    protected $guarded = ['id'];

    use ReadsDecimalMoney;

    protected function casts(): array
    {
        return ['price_checks' => 'array'];
    }

    public function item()
    {
        return $this->belongsTo(ProjectBudgetItem::class, 'budget_item_id');
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
