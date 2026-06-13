<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator\crimson;

use lolfosjo\netherx\nether\populator\Populator;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;

final class WeepingVinesCeilingPopulator extends Populator
{
    private int $airId;
    private int $weepingVinesId;
    private int $netherRackId;

    public function __construct()
    {
        $this->airId = VanillaBlocks::AIR()->getStateId();
        $this->weepingVinesId = VanillaBlocks::WEEPING_VINES()->getStateId();
        $this->netherRackId = VanillaBlocks::NETHERRACK()->getStateId();
    }

    public function populate(
        ChunkManager $world,
        int $chunkX,
        int $chunkZ,
        Random $random,
    ): void {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (null === $chunk) {
            return;
        }

        $amount = $random->nextBoundedInt(5) + 1;

        for ($i = 0; $i < $amount; ++$i) {
            $localX = $random->nextBoundedInt(Chunk::EDGE_LENGTH);
            $localZ = $random->nextBoundedInt(Chunk::EDGE_LENGTH);

            if (BiomeIds::CRIMSON_FOREST !== $chunk->getBiomeId($localX, 64, $localZ)) {
                continue;
            }

            $ceilingYs = $this->getCeilingWorkableBlocks($chunk, $localX, $localZ);

            foreach ($ceilingYs as $startY) {
                if ($startY <= 1) {
                    continue;
                }

                $maxLength = 0;
                for ($y = $startY; $y > 1; --$y) {
                    if ($chunk->getBlockStateId($localX, $y, $localZ) !== $this->airId) {
                        break;
                    }
                    ++$maxLength;
                }

                if (0 === $maxLength) {
                    continue;
                }

                $length = $maxLength > 1 ? $random->nextBoundedInt($maxLength) : 1;

                for ($yPos = $startY; $yPos > $startY - $length; --$yPos) {
                    $chunk->setBlockStateId($localX, $yPos, $localZ, $this->weepingVinesId);
                }
            }
        }
    }

    private function getCeilingWorkableBlocks(Chunk $chunk, int $localX, int $localZ): array
    {
        $ys = [];

        for ($y = 1; $y <= 127; ++$y) {
            $blockId = $chunk->getBlockStateId($localX, $y, $localZ);

            if ($blockId === $this->netherRackId) {
                $below = $chunk->getBlockStateId($localX, $y - 1, $localZ);
                if ($below === $this->airId) {
                    $ys[] = $y - 1;
                }
            }
        }

        return $ys;
    }
}
