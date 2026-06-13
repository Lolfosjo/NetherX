<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator;

use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;

final class GlowstonePopulator extends Populator
{
    private int $glowstoneId;
    private int $netherrackId;
    private int $airId;

    public function __construct(
        private readonly int $minClusterSize = 40,
        private readonly int $maxClusterSize = 60,
        private readonly int $spawnChance = 11,
    ) {
        $this->glowstoneId = VanillaBlocks::GLOWSTONE()->getStateId();
        $this->netherrackId = VanillaBlocks::NETHERRACK()->getStateId();
        $this->airId = VanillaBlocks::AIR()->getStateId();
    }

    public function populate(
        ChunkManager $world,
        int $chunkX,
        int $chunkZ,
        Random $random,
    ): void {
        if (0 !== $random->nextBoundedInt($this->spawnChance)) {
            return;
        }

        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (null === $chunk) {
            return;
        }

        $baseX = $chunkX << 4;
        $baseZ = $chunkZ << 4;

        $localX = $random->nextBoundedInt(Chunk::EDGE_LENGTH);
        $localZ = $random->nextBoundedInt(Chunk::EDGE_LENGTH);
        $worldX = $baseX + $localX;
        $worldZ = $baseZ + $localZ;

        $startY = $this->findCeilingAir($chunk, $localX, $localZ);
        if ($startY < 0) {
            return;
        }

        if ($chunk->getBlockStateId($localX, $startY, $localZ) === $this->netherrackId) {
            return;
        }

        $chunk->setBlockStateId($localX, $startY, $localZ, $this->glowstoneId);

        $count = $this->minClusterSize + $random->nextBoundedInt($this->maxClusterSize - $this->minClusterSize + 1);
        $cyclesNum = 0;

        while ($count > 0) {
            if ($cyclesNum >= 1500) {
                break;
            }

            $spawnX = $worldX + $random->nextBoundedInt(9) - $random->nextBoundedInt(9);
            $spawnY = $startY - $random->nextBoundedInt(9);
            $spawnZ = $worldZ + $random->nextBoundedInt(9) - $random->nextBoundedInt(9);

            if ($cyclesNum > 0 && 0 === $cyclesNum % 128) {
                $extraX = $worldX + $random->nextBoundedInt(7) - 3;
                $extraY = $startY - $random->nextBoundedInt(5);
                $extraZ = $worldZ + $random->nextBoundedInt(7) - 3;

                $this->setIfInChunk($world, $chunkX, $chunkZ, $extraX, $extraY, $extraZ);
                --$count;
            }

            if ($this->hasGlowstoneNeighbour($world, $spawnX, $spawnY, $spawnZ)) {
                $this->setIfInChunk($world, $chunkX, $chunkZ, $spawnX, $spawnY, $spawnZ);
                --$count;
            }

            ++$cyclesNum;
        }
    }

    private function findCeilingAir(Chunk $chunk, int $localX, int $localZ): int
    {
        for ($y = 125; $y >= 1; --$y) {
            if ($chunk->getBlockStateId($localX, $y, $localZ) === $this->airId) {
                return $y;
            }
        }

        return -1;
    }

    private function hasGlowstoneNeighbour(ChunkManager $world, int $x, int $y, int $z): bool
    {
        $offsets = [[1, 0, 0], [-1, 0, 0], [0, 1, 0], [0, -1, 0], [0, 0, 1], [0, 0, -1]];

        foreach ($offsets as [$dx, $dy, $dz]) {
            $ny = $y + $dy;
            if ($ny < 0 || $ny > 127) {
                continue;
            }
            if ($world->getBlockAt($x + $dx, $ny, $z + $dz)->getStateId() === $this->glowstoneId) {
                return true;
            }
        }

        return false;
    }

    private function setIfInChunk(
        ChunkManager $world,
        int $chunkX,
        int $chunkZ,
        int $worldX,
        int $worldY,
        int $worldZ,
    ): void {
        if ($worldY < 1 || $worldY > 125) {
            return;
        }

        $targetChunkX = $worldX >> 4;
        $targetChunkZ = $worldZ >> 4;

        if ($targetChunkX !== $chunkX || $targetChunkZ !== $chunkZ) {
            return;
        }

        $localX = $worldX & 0xF;
        $localZ = $worldZ & 0xF;

        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (null === $chunk) {
            return;
        }

        if ($chunk->getBlockStateId($localX, $worldY, $localZ) !== $this->airId) {
            return;
        }

        $chunk->setBlockStateId($localX, $worldY, $localZ, $this->glowstoneId);
    }
}
