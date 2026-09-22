<?php

namespace App\Modules\Shared\Models;

use App\Modules\Shared\Database\Factories\TenantFactory;
use App\Modules\Shared\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'domain', 'status'])]
class Tenant extends Model
{
    use HasUlids, HasFactory;

    protected $casts = [
        'status' => TenantStatus::class
    ];

    public function isActive(): bool
    {
        return $this->status === TenantStatus::ACTIVE;
    }

    protected static function newFactory()
    {
        return TenantFactory::new();
    }
}
