<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator;

use pocketmine\block\VanillaBlocks;

final class MagmaPopulator extends OrePopulator
{
    public function getOreBlock(int $replacedStateId): int
    {
        return VanillaBlocks::MAGMA()->getStateId();
    }

    public function getClusterCount(): int
    {
        return 9;
    }

    public function getClusterSize(): int
    {
        return 20;
    }

    public function getMinHeight(): int
    {
        return 23;
    }

    public function getMaxHeight(): int
    {
        return 37;
    }
}
