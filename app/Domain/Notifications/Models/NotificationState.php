<?php

namespace App\Domain\Notifications\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotificationState extends Model
{
    protected $table = 'user_notification_states';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'dismissed_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
