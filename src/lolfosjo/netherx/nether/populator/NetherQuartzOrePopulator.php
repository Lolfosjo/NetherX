<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator;

use pocketmine\block\VanillaBlocks;

final class NetherQuartzOrePopulator extends OrePopulator
{
    public function getOreBlock(int $replacedStateId): int
    {
        return VanillaBlocks::NETHER_QUARTZ_ORE()->getStateId();
    }

    public function getClusterCount(): int
    {
        return 20;
    }

    public function getClusterSize(): int
    {
        return 14;
    }

    public function getMinHeight(): int
    {
        return 10;
    }

    public function getMaxHeight(): int
    {
        return 117;
    }
}
