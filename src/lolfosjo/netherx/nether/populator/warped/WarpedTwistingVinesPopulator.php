<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator\warped;

use lolfosjo\netherx\nether\populator\Populator;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;

final class WarpedTwistingVinesPopulator extends Populator
{
    private int $airId;
    private int $twistingVinesId;
    private int $warpedNyliumId;
    private int $warpedWartBlockId;

    public function __construct()
    {
        $this->airId = VanillaBlocks::AIR()->getStateId();
        $this->twistingVinesId = VanillaBlocks::TWISTING_VINES()->getStateId();
        $this->warpedNyliumId = VanillaBlocks::WARPED_NYLIUM()->getStateId();
        $this->warpedWartBlockId = VanillaBlocks::WARPED_WART_BLOCK()->getStateId();
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

        $amount = $random->nextBoundedInt(6) + 2;

        for ($i = 0; $i < $amount; ++$i) {
            $localX = $random->nextBoundedInt(Chunk::EDGE_LENGTH);
            $localZ = $random->nextBoundedInt(Chunk::EDGE_LENGTH);

            if (BiomeIds::WARPED_FOREST !== $chunk->getBiomeId($localX, 2, $localZ)) {
                continue;
            }

            $workableYs = $this->getHighestWorkableBlocks($chunk, $localX, $localZ);

            foreach ($workableYs as $y) {
                if ($y <= 1) {
                    continue;
                }

                if (0 === $random->nextBoundedInt(5)) {
                    continue;
                }

                $endY = $this->getHighestEndingBlock($chunk, $localX, $y, $localZ);
                $range = $endY - $y + 1;
                $amountToPlace = $range > 0 ? $random->nextBoundedInt($range) : 0;

                for ($yPos = $y; $yPos < $y + (int) ($amountToPlace / 2); ++$yPos) {
                    if ($yPos >= 127) {
                        break;
                    }
                    $chunk->setBlockStateId($localX, $yPos, $localZ, $this->twistingVinesId);
                }
            }
        }
    }

    private function getHighestEndingBlock(Chunk $chunk, int $localX, int $startY, int $localZ): int
    {
        for ($y = $startY; $y < 128; ++$y) {
            $blockId = $chunk->getBlockStateId($localX, $y, $localZ);
            $belowId = $y > 0 ? $chunk->getBlockStateId($localX, $y - 1, $localZ) : $this->airId;

            $isSolid = $blockId !== $this->airId;
            $belowAir = $belowId === $this->airId;

            if ($belowAir && $isSolid) {
                break;
            }
        }

        return $y - 1;
    }

    private function getHighestWorkableBlocks(Chunk $chunk, int $localX, int $localZ): array
    {
        $ys = [];

        for ($y = 128; $y > 0; --$y) {
            $blockId = $chunk->getBlockStateId($localX, $y, $localZ);

            if ($blockId === $this->warpedNyliumId || $blockId === $this->warpedWartBlockId) {
                $above = $chunk->getBlockStateId($localX, $y + 1, $localZ);
                if ($above === $this->airId) {
                    $ys[] = $y + 1;
                }
            }
        }

        return $ys;
    }
}
