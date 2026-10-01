<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Langganan web push per perangkat (endpoint unik). */
class PushSubscription extends Model
{
    protected $table = 'push_subscriptions';

    protected $fillable = [
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'device',
    ];
}
