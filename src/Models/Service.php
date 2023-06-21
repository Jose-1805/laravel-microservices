<?php

namespace Jose1805\LaravelMicroservices\Models;

use Jose1805\LaravelMicroservices\Traits\BackgroundRequestGenerator;
use Jose1805\LaravelMicroservices\Traits\ServiceConsumer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Service extends Model
{
    use HasApiTokens;
    use HasUuids;
    use ServiceConsumer;
    use BackgroundRequestGenerator;


    protected $fillable = [
        'name',
        'base_uri',
        'path',
        'access_token',
        'queue',
    ];
}
