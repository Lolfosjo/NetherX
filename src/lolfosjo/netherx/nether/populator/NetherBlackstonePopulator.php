<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator;

use pocketmine\block\VanillaBlocks;

class NetherBlackstonePopulator extends NetherGravelPopulator
{
    public function getOreBlock(int $replacedStateId): int
    {
        return VanillaBlocks::BLACKSTONE()->getStateId();
    }
}
