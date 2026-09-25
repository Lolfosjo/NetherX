<?php

declare(strict_types=1);

namespace lolfosjo\netherx\generator\biome;

enum BiomeSizePreset: string
{
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';

    public function firstOctave(): int
    {
        return match ($this) {
            self::SMALL => -7,
            self::MEDIUM => -8,
            self::LARGE => -9,
        };
    }
}
