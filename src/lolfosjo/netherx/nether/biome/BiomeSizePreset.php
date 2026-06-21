<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\biome;

enum BiomeSizePreset: string
{
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';

    public function temperatureScale(): float
    {
        return match ($this) {
            self::SMALL => 1 / 256.0,
            self::MEDIUM => 1 / 512.0,
            self::LARGE => 1 / 1024.0,
        };
    }

    public function humidityScale(): float
    {
        return match ($this) {
            self::SMALL => 1 / 128.0,
            self::MEDIUM => 1 / 256.0,
            self::LARGE => 1 / 512.0,
        };
    }
}
