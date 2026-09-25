<?php

declare(strict_types=1);

namespace lolfosjo\netherx\generator\variant;

use lolfosjo\netherx\generator\biome\BiomeSizePreset;
use lolfosjo\netherx\generator\NetherGenerator;

class NetherGeneratorLarge extends NetherGenerator
{
    protected function resolveBiomeSizePreset(): BiomeSizePreset
    {
        return BiomeSizePreset::LARGE;
    }
}
