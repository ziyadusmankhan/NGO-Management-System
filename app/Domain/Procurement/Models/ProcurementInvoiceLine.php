<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProcurementInvoiceLine extends Model
{
    protected $table = 'procurement_invoice_lines';

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

    public function invoice()
    {
        return $this->belongsTo(ProcurementInvoice::class, 'invoice_id');
    }

    public function receipt()
    {
        return $this->belongsTo(ProcurementReceipt::class, 'receipt_id');
    }
}
