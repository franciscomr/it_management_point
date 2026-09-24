<?php

namespace App\Modules\Auth\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::BLOCKED => 'Blocked',
        };
    }

    public function values(): array
    {
        return array_column(static::cases(), 'value');
    }

    /**
     * Devuelve un arreglo para usarse en Selects.
     * @return list<array{value:string,label:string}>
     */
    public static function options(): array
    {
        return collect(static::cases())
            ->map(fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ])
            ->values()
            ->all();
    }
}
