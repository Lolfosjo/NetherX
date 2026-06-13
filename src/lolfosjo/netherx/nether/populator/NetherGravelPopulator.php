<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator;

use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;

class NetherGravelPopulator extends OrePopulator
{
    public function getOreBlock(int $replacedStateId): int
    {
        return VanillaBlocks::GRAVEL()->getStateId();
    }

    public function getClusterCount(): int
    {
        return 2;
    }

    public function getClusterSize(): int
    {
        return 33;
    }

    public function getMinHeight(): int
    {
        return 5;
    }

    public function getMaxHeight(): int
    {
        return 36;
    }

    public function canBeReplaced(int $stateId): bool
    {
        return parent::canBeReplaced($stateId);
    }

    public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void
    {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (null === $chunk) {
            return;
        }

        $centerX = ($chunkX << 4) + 8;
        $centerZ = ($chunkZ << 4) + 8;
        $centerY = (int) (($this->getMinHeight() + $this->getMaxHeight()) / 2);

        if (BiomeIds::BASALT_DELTAS === $chunk->getBiomeId($centerX & 0x0F, $centerY, $centerZ & 0x0F)) {
            return;
        }

        parent::populate($world, $chunkX, $chunkZ, $random);
    }
}
