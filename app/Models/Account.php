<?php

namespace App\Models;

use App\Traits\ActivityTrait;
use Spatie\Activitylog\LogOptions;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Account extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, ActivityTrait, CentralConnection;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'accounts';

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'label',
            'name',
            'address',
            'email',
            'currency_symbol',
            'currency_format',
            'decimal_places',
            'date_format',
            'fy_start',
            'fy_end',
            'created_at',
            'updated_at',
        ];
    }

    protected $fillable = [
        'id',
        'label',
        'name',
        'address',
        'email',
        'currency_symbol',
        'currency_format',
        'decimal_places',
        'date_format',
        'fy_start',
        'fy_end',
    ];

    protected $casts = [
        'fy_start' => 'date',
        'fy_end' => 'date',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

}
