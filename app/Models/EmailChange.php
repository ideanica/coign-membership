<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailChange extends Model
{
    protected $fillable = ['user_id', 'old_email', 'new_email', 'code_hash', 'session_hash', 'attempts', 'expires_at', 'sent_at'];

    protected $hidden = ['code_hash', 'session_hash'];

    protected function casts(): array
    {
        return ['attempts' => 'integer', 'expires_at' => 'immutable_datetime', 'sent_at' => 'immutable_datetime'];
    }
}
