<?php

namespace App\Modules\Shared\Database\Factories;

use App\Modules\Shared\Enums\TenantStatus;
use App\Modules\Shared\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Tenant::class;
    public function definition(): array
    {
        $companyName = fake()->unique()->company();
        return [
            'name' => $companyName,
            'slug' => Str::slug($companyName),
            'domain' => fake()->optional()->domainName(),
            'status' => TenantStatus::ACTIVE
        ];
    }
}
