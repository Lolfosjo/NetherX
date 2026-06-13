<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;

final class NetherVegetationPopulator extends Populator
{
    private const SURFACE_SCAN_DEPTH = 5;
    private const MAX_Y = 126;
    private const MIN_Y = 1;

    private int $plantStateId;
    private int $airId;

    /** @var array<int, true> */
    private array $allowedGroundIds;

    public function __construct(
        Block $plant,
        array $allowedGround = [],
        private int $attemptsPerChunk = 8,
        private bool $cluster = false,
        private int $clusterRadius = 4,
        private int $clusterSize = 8,
    ) {
        $this->plantStateId = $plant->getStateId();
        $this->airId = VanillaBlocks::AIR()->getStateId();

        $this->allowedGroundIds = [];
        foreach ($allowedGround as $groundBlock) {
            $this->allowedGroundIds[$groundBlock->getStateId()] = true;
        }
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

        if ($this->cluster) {
            $this->populateClustered($chunk, $random);
        } else {
            $this->populateScattered($chunk, $random);
        }
    }

    private function populateScattered(Chunk $chunk, Random $random): void
    {
        for ($i = 0; $i < $this->attemptsPerChunk; ++$i) {
            $localX = $random->nextBoundedInt(Chunk::EDGE_LENGTH);
            $localZ = $random->nextBoundedInt(Chunk::EDGE_LENGTH);
            $this->tryPlace($chunk, $localX, $localZ);
        }
    }

    private function populateClustered(Chunk $chunk, Random $random): void
    {
        $diameter = $this->clusterRadius * 2 + 1;

        for ($i = 0; $i < $this->attemptsPerChunk; ++$i) {
            $anchorX = $random->nextBoundedInt(Chunk::EDGE_LENGTH);
            $anchorZ = $random->nextBoundedInt(Chunk::EDGE_LENGTH);

            for ($j = 0; $j < $this->clusterSize; ++$j) {
                $localX = $anchorX + $random->nextBoundedInt($diameter) - $this->clusterRadius;
                $localZ = $anchorZ + $random->nextBoundedInt($diameter) - $this->clusterRadius;

                $localX = max(0, min(Chunk::EDGE_LENGTH - 1, $localX));
                $localZ = max(0, min(Chunk::EDGE_LENGTH - 1, $localZ));

                $this->tryPlace($chunk, $localX, $localZ);
            }
        }
    }

    private function tryPlace(Chunk $chunk, int $localX, int $localZ): void
    {
        $surfaceY = $this->findFloorSurfaceY($chunk, $localX, $localZ);
        if ($surfaceY < 0) {
            return;
        }

        $plantY = $surfaceY + 1;
        if ($plantY > self::MAX_Y) {
            return;
        }

        if ([] !== $this->allowedGroundIds) {
            $groundId = $chunk->getBlockStateId($localX, $surfaceY, $localZ);
            if (!isset($this->allowedGroundIds[$groundId])) {
                return;
            }
        }

        if ($chunk->getBlockStateId($localX, $plantY, $localZ) !== $this->airId) {
            return;
        }

        $chunk->setBlockStateId($localX, $plantY, $localZ, $this->plantStateId);
    }

    private function findFloorSurfaceY(Chunk $chunk, int $localX, int $localZ): int
    {
        for ($y = self::MAX_Y; $y >= self::MIN_Y; --$y) {
            if ($chunk->getBlockStateId($localX, $y, $localZ) === $this->airId) {
                continue;
            }
            if ($this->isTop($chunk, $localX, $y, $localZ)) {
                return $y;
            }
        }

        return -1;
    }

    private function isTop(Chunk $chunk, int $x, int $y, int $z): bool
    {
        for ($i = 1; $i <= self::SURFACE_SCAN_DEPTH; ++$i) {
            $yy = $y + $i;
            if ($yy > self::MAX_Y) {
                break;
            }
            if ($chunk->getBlockStateId($x, $yy, $z) === $this->airId) {
                return true;
            }
        }

        return false;
    }
}
