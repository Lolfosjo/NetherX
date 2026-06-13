<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator\basaltdelta;

use lolfosjo\netherx\nether\populator\Populator;
use lolfosjo\netherx\noise\glowstone\PerlinOctaveGenerator;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;

final class BasaltDeltaLavaPopulator extends Populator
{
    private int $basaltId;
    private int $blackstoneId;
    private int $magmaId;
    private int $gravelId;
    private int $lavaId;
    private int $flowingLavaId;
    private int $airId;

    private PerlinOctaveGenerator $surfaceNoise;
    private PerlinOctaveGenerator $surfaceSecNoise;

    public function __construct(int $worldSeed)
    {
        $this->basaltId = VanillaBlocks::BASALT()->getStateId();
        $this->blackstoneId = VanillaBlocks::BLACKSTONE()->getStateId();
        $this->magmaId = VanillaBlocks::MAGMA()->getStateId();
        $this->gravelId = VanillaBlocks::GRAVEL()->getStateId();
        $this->lavaId = VanillaBlocks::LAVA()->getStateId();
        $this->flowingLavaId = VanillaBlocks::LAVA()->getFlowingForm()->getStateId();
        $this->airId = VanillaBlocks::AIR()->getStateId();

        $rand = new Random($worldSeed);
        $this->surfaceNoise = PerlinOctaveGenerator::fromRandomAndOctaves($rand, 2, 16, 128, 16);
        $this->surfaceNoise->setScale(0.0625);

        $rand2 = new Random($worldSeed + 1);
        $this->surfaceSecNoise = PerlinOctaveGenerator::fromRandomAndOctaves($rand2, 2, 16, 128, 16);
        $this->surfaceSecNoise->setScale(0.0625);
    }

    public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void
    {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (null === $chunk) {
            return;
        }

        $baseX = $chunkX << 4;
        $baseZ = $chunkZ << 4;
        $sizeY = 128;
        $sizeZ = 16;

        $noiseMain = $this->surfaceNoise->getFractalBrownianMotion($baseX, 0, $baseZ, 2.0, 0.5);
        $noiseSec = $this->surfaceSecNoise->getFractalBrownianMotion($baseX, 0, $baseZ, 2.0, 0.5);

        $amount = $random->nextBoundedInt(64) + 64;
        for ($i = 0; $i < $amount; ++$i) {
            $localX = $random->nextBoundedInt(16);
            $localZ = $random->nextBoundedInt(16);
            $absX = $baseX + $localX;
            $absZ = $baseZ + $localZ;

            $ys = $this->getWorkableYsForLavaLake($world, $chunk, $localX, $localZ, $absX, $absZ);
            foreach ($ys as $y) {
                if ($y < 1 || $y >= 127) {
                    continue;
                }
                $chunk->setBlockStateId($localX, $y, $localZ, $this->flowingLavaId);
            }
        }

        for ($localX = 0; $localX < 16; ++$localX) {
            for ($localZ = 0; $localZ < 16; ++$localZ) {
                $absX = $baseX + $localX;
                $absZ = $baseZ + $localZ;

                for ($y = 1; $y < 127; ++$y) {
                    if ($chunk->getBlockStateId($localX, $y, $localZ) !== $this->gravelId) {
                        continue;
                    }

                    $idx = ($localX * $sizeY + $y) * $sizeZ + $localZ;
                    $secNoise = $noiseSec[$idx];
                    $mainNoise = $noiseMain[$idx];

                    if ($secNoise < -0.9) {
                        $target = $this->blackstoneId;
                    } elseif ($secNoise < 0.8) {
                        $target = $this->basaltId;
                    } else {
                        $target = $this->magmaId;
                    }

                    if ($mainNoise > 0.0) {
                        $chunk->setBlockStateId($localX, $y, $localZ, $target);
                    } else {
                        $hasAirNeighbor = false;
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
                            if (null !== $neighborChunk && $neighborChunk->getBlockStateId($lx, $y, $lz) === $this->airId) {
                                $hasAirNeighbor = true;

                                break;
                            }
                        }
                        if ($hasAirNeighbor) {
                            $chunk->setBlockStateId($localX, $y, $localZ, $target);
                        } else {
                            $chunk->setBlockStateId($localX, $y, $localZ, $this->lavaId);
                        }
                    }
                }
            }
        }
    }

    private function getWorkableYsForLavaLake(ChunkManager $world, Chunk $chunk, int $localX, int $localZ, int $absX, int $absZ): array
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

            $allSolid = true;
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
                if (null === $neighborChunk || $neighborChunk->getBlockStateId($lx, $y, $lz) === $this->airId) {
                    $allSolid = false;

                    break;
                }
            }
            if ($allSolid) {
                $results[] = $y;
            }
        }

        return $results;
    }
}
