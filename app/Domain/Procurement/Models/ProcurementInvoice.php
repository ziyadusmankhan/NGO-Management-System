<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProcurementInvoice extends Model
{
    protected $table = 'procurement_invoices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['invoiced_on' => 'date', 'due_on' => 'date'];
    }

    public function procurement()
    {
        return $this->belongsTo(Procurement::class)->withTrashed();
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }

    public function lines()
    {
        return $this->hasMany(ProcurementInvoiceLine::class, 'invoice_id');
    }
}
