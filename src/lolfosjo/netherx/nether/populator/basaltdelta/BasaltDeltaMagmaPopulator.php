<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator\basaltdelta;

use lolfosjo\netherx\nether\populator\Populator;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;

final class BasaltDeltaMagmaPopulator extends Populator
{
    private int $basaltId;
    private int $blackstoneId;
    private int $magmaId;
    private int $lavaId;
    private int $flowingLavaId;
    private int $airId;

    public function __construct()
    {
        $this->basaltId = VanillaBlocks::BASALT()->getStateId();
        $this->blackstoneId = VanillaBlocks::BLACKSTONE()->getStateId();
        $this->magmaId = VanillaBlocks::MAGMA()->getStateId();
        $this->lavaId = VanillaBlocks::LAVA()->getStateId();
        $this->flowingLavaId = VanillaBlocks::LAVA()->getFlowingForm()->getStateId();
        $this->airId = VanillaBlocks::AIR()->getStateId();
    }

    public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void
    {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (null === $chunk) {
            return;
        }

        $amount = $random->nextBoundedInt(4) + 20;

        for ($i = 0; $i < $amount; ++$i) {
            $localX = $random->nextBoundedInt(16);
            $localZ = $random->nextBoundedInt(16);
            $absX = ($chunkX << 4) + $localX;
            $absZ = ($chunkZ << 4) + $localZ;

            $ys = $this->getWorkableYs($world, $chunk, $localX, $localZ, $absX, $absZ);
            foreach ($ys as $y) {
                if ($y < 1 || $y >= 127) {
                    continue;
                }
                $chunk->setBlockStateId($localX, $y, $localZ, $this->magmaId);
            }
        }
    }

    private function getWorkableYs(ChunkManager $world, Chunk $chunk, int $localX, int $localZ, int $absX, int $absZ): array
    {
        $results = [];
        for ($y = 126; $y >= 1; --$y) {
            $block = $chunk->getBlockStateId($localX, $y, $localZ);
            if ($block !== $this->basaltId && $block !== $this->blackstoneId) {
                continue;
            }
            if ($chunk->getBlockStateId($localX, $y + 1, $localZ) !== $this->airId) {
                continue;
            }

            $hasLava = false;
            $neighbors = [
                [$absX + 1, $absZ], [$absX - 1, $absZ],
                [$absX, $absZ + 1], [$absX, $absZ - 1],
            ];
            foreach ($neighbors as [$nx, $nz]) {
                $cx = $nx >> 4;
                $cz = $nz >> 4;
                $lx = $nx & 0x0F;
                $lz = $nz & 0x0F;
                $neighborChunk = $world->getChunk($cx, $cz);
                if (null !== $neighborChunk) {
                    $nbId = $neighborChunk->getBlockStateId($lx, $y, $lz);
                    if ($nbId === $this->lavaId || $nbId === $this->flowingLavaId) {
                        $hasLava = true;

                        break;
                    }
                }
            }
            if ($hasLava) {
                $results[] = $y;
            }
        }

        return $results;
    }
}
