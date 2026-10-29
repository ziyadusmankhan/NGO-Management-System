<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Documents\Concerns\RegistersDocumentsAtomically;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProcurementDocument extends Model
{
    use RegistersDocumentsAtomically;

    protected $table = 'procurement_documents';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [];
    }

    public function procurement()
    {
        return $this->belongsTo(Procurement::class)->withTrashed();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
