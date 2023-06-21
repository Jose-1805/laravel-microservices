<?php

namespace Jose1805\LaravelMicroservices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'type',
        'token',
        'user_id',
    ];

    /**
     * Usuario asociado al token
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
