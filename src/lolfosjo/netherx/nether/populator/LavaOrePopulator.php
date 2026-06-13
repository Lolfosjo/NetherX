<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator;

use pocketmine\block\VanillaBlocks;

final class LavaOrePopulator extends OrePopulator
{
    public function getOreBlock(int $replacedStateId): int
    {
        return VanillaBlocks::LAVA()->getStateId();
    }

    public function getClusterCount(): int
    {
        return 32;
    }

    public function getClusterSize(): int
    {
        return 1;
    }

    public function getMinHeight(): int
    {
        return 1;
    }

    public function getMaxHeight(): int
    {
        return 32;
    }
}
