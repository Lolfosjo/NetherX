<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator;

use pocketmine\block\VanillaBlocks;

final class AncientDebrisLargePopulator extends AncientDebrisSmallPopulator
{
    public function getOreBlock(int $replacedStateId): int
    {
        return VanillaBlocks::ANCIENT_DEBRIS()->getStateId();
    }

    public function getClusterCount(): int
    {
        return 2;
    }

    public function getClusterSize(): int
    {
        return 3;
    }

    public function getMinHeight(): int
    {
        return 23;
    }
}
