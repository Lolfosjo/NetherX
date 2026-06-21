<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\variant;

use lolfosjo\netherx\nether\biome\BiomeSizePreset;
use lolfosjo\netherx\nether\NetherGenerator;

class NetherGeneratorLarge extends NetherGenerator
{
    protected function resolveBiomeSizePreset(): BiomeSizePreset
    {
        return BiomeSizePreset::LARGE;
    }
}
