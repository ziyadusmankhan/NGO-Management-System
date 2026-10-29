<?php

namespace App\Domain\Qard\Models;

use App\Domain\Documents\Concerns\RegistersDocumentsAtomically;
use Illuminate\Database\Eloquent\Model;

class QardDocument extends Model
{
    use RegistersDocumentsAtomically;

    protected $guarded = ['id'];
}
