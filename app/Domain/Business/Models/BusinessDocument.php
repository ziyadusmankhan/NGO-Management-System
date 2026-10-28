<?php

namespace App\Domain\Business\Models;

use App\Domain\Documents\Concerns\RegistersDocumentsAtomically;
use Illuminate\Database\Eloquent\Model;

class BusinessDocument extends Model
{
    use RegistersDocumentsAtomically;

    protected $guarded = ['id'];
}
