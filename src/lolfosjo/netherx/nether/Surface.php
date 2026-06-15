<?php

/*
 * Derived from PowerNukkitX (NetherTerrainStage.java)
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether;

use lolfosjo\netherx\noise\bukkit\SimplexOctaveGenerator;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;

class Surface
{
    public const LAVA_LEVEL = 31;
    private const NETHER_HEIGHT = 128;
    private const BEDROCK_FLOOR = 0;
    private const BEDROCK_CEIL = 127;
    private const SURFACE_SCAN_DEPTH = 4;

    private const SALT_NETHER_STATE = 0x4E455448;
    private const SALT_PATCH = 0x50415443;
    private const SALT_SOULSAND = 0x534F554C;
    private const SALT_NETHERWART = 0x4E455752;

    private int $bedrockId;
    private int $netherrackId;
    private int $airId;
    private int $basaltId;
    private int $blackstoneId;
    private int $gravelId;
    private int $soulSandId;
    private int $soulSoilId;
    private int $warpedWartBlockId;
    private int $warpedNyliumId;
    private int $netherWartBlockId;
    private int $crimsonNyliumId;

    private SimplexOctaveGenerator $netherStateNoise;
    private SimplexOctaveGenerator $patchNoise;
    private SimplexOctaveGenerator $soulsandNoise;
    private SimplexOctaveGenerator $netherwartNoise;

    private int $bedrockRoughness;

    public function __construct(Random $noiseRand, int $bedrockRoughness = 5)
    {
        $this->bedrockId = VanillaBlocks::BEDROCK()->getStateId();
        $this->netherrackId = VanillaBlocks::NETHERRACK()->getStateId();
        $this->airId = VanillaBlocks::AIR()->getStateId();
        $this->basaltId = VanillaBlocks::BASALT()->getStateId();
        $this->blackstoneId = VanillaBlocks::BLACKSTONE()->getStateId();
        $this->gravelId = VanillaBlocks::GRAVEL()->getStateId();
        $this->soulSandId = VanillaBlocks::SOUL_SAND()->getStateId();
        $this->soulSoilId = VanillaBlocks::SOUL_SOIL()->getStateId();
        $this->warpedWartBlockId = VanillaBlocks::WARPED_WART_BLOCK()->getStateId();
        $this->warpedNyliumId = VanillaBlocks::WARPED_NYLIUM()->getStateId();
        $this->netherWartBlockId = VanillaBlocks::NETHER_WART_BLOCK()->getStateId();
        $this->crimsonNyliumId = VanillaBlocks::CRIMSON_NYLIUM()->getStateId();

        $this->bedrockRoughness = $bedrockRoughness;

        $baseSeed = $noiseRand->getSeed();

        $this->netherStateNoise = new SimplexOctaveGenerator(new Random($baseSeed ^ self::SALT_NETHER_STATE), 1);
        $this->netherStateNoise->setScale(1 / 64.0);

        $this->patchNoise = new SimplexOctaveGenerator(new Random($baseSeed ^ self::SALT_PATCH), 1);
        $this->patchNoise->setScale(1 / 32.0);

        $this->soulsandNoise = new SimplexOctaveGenerator(new Random($baseSeed ^ self::SALT_SOULSAND), 1);
        $this->soulsandNoise->setScale(1 / 32.0);

        $this->netherwartNoise = new SimplexOctaveGenerator(new Random($baseSeed ^ self::SALT_NETHERWART), 1);
        $this->netherwartNoise->setScale(1 / 16.0);
    }

    public function generateSurface(ChunkManager $world, int $chunkX, int $chunkZ, Random $random, array $biomeMap): void
    {
        /** @var Chunk $chunk */
        $chunk = $world->getChunk($chunkX, $chunkZ);

        $baseX = $chunkX << 4;
        $baseZ = $chunkZ << 4;

        $this->applyBiomeBlocks($chunk, $baseX, $baseZ, $biomeMap);
        $this->placeBedrock($chunk, $random);
    }

    private function applyBiomeBlocks(Chunk $chunk, int $baseX, int $baseZ, array $biomeMap): void
    {
        $minY = self::BEDROCK_FLOOR + 1;
        $maxY = self::BEDROCK_CEIL - 1;
        $depth = self::SURFACE_SCAN_DEPTH + 1;

        for ($x = 0; $x < Chunk::EDGE_LENGTH; ++$x) {
            for ($z = 0; $z < Chunk::EDGE_LENGTH; ++$z) {
                $biomeId = $biomeMap[$x][$z] ?? BiomeIds::HELL;
                $nx = $x + $baseX;
                $nz = $z + $baseZ;

                $ids = [];
                for ($y = $minY; $y <= $maxY; ++$y) {
                    $ids[$y] = $chunk->getBlockStateId($x, $y, $z);
                }

                $nsNoise = $this->netherStateNoise->noise($nx, 64, $nz, 0.5, 0.5, 1.0);
                $patchN = $this->patchNoise->noise($nx, 64, $nz, 0.5, 0.5, 1.0);
                $soulsandN = $this->soulsandNoise->noise($nx, 64, $nz, 0.5, 0.5, 1.0);
                $netherwartN = $this->netherwartNoise->noise($nx, 64, $nz, 0.5, 0.5, 1.0);

                for ($y = $minY; $y < $maxY; ++$y) {
                    if ($ids[$y] !== $this->netherrackId) {
                        continue;
                    }

                    $isTop = false;
                    for ($i = 0; $i < $depth; ++$i) {
                        $yy = $y + $i;
                        if ($yy >= $minY && $yy <= self::BEDROCK_CEIL && ($ids[$yy] ?? $this->airId) === $this->airId) {
                            $isTop = true;

                            break;
                        }
                    }

                    $isCeil = false;
                    for ($i = 0; $i < $depth; ++$i) {
                        $yy = $y - $i;
                        if ($yy >= $minY && $yy <= self::BEDROCK_CEIL && ($ids[$yy] ?? $this->airId) === $this->airId) {
                            $isCeil = true;

                            break;
                        }
                    }

                    $above = $ids[$y + 1] ?? $this->airId;

                    switch ($biomeId) {
                        case BiomeIds::BASALT_DELTAS:
                            $this->applyBasaltDeltas($chunk, $x, $y, $z, $nsNoise, $patchN, $isTop, $isCeil, $above);

                            break;

                        case BiomeIds::SOULSAND_VALLEY:
                            $this->applySoulsandValley($chunk, $x, $y, $z, $nsNoise, $patchN, $isTop, $isCeil);

                            break;

                        case BiomeIds::WARPED_FOREST:
                            $this->applyWarpedForest($chunk, $x, $y, $z, $nsNoise, $netherwartN, $above);

                            break;

                        case BiomeIds::CRIMSON_FOREST:
                            $this->applyCrimsonForest($chunk, $x, $y, $z, $nsNoise, $netherwartN, $above);

                            break;

                        case BiomeIds::HELL:
                            $this->applyHell($chunk, $x, $y, $z, $soulsandN, $isTop);

                            break;
                    }
                }
            }
        }
    }

    private function applyBasaltDeltas(
        Chunk $chunk,
        int $x,
        int $y,
        int $z,
        float $nsNoise,
        float $patchNoise,
        bool $isTop,
        bool $isCeil,
        int $above,
    ): void {
        if ($isCeil) {
            $chunk->setBlockStateId($x, $y, $z, $this->basaltId);

            return;
        }

        if ($above === $this->airId) {
            if ($nsNoise >= 0
                || ($y <= 35 && $y >= 30 && $patchNoise >= -0.012)
            ) {
                $chunk->setBlockStateId($x, $y, $z, $this->gravelId);

                return;
            }
        }

        if ($isTop || $isCeil) {
            $chunk->setBlockStateId($x, $y, $z, $this->blackstoneId);
        }
    }

    private function applySoulsandValley(
        Chunk $chunk,
        int $x,
        int $y,
        int $z,
        float $nsNoise,
        float $patchNoise,
        bool $isTop,
        bool $isCeil,
    ): void {
        if ($isCeil) {
            $chunk->setBlockStateId(
                $x,
                $y,
                $z,
                $nsNoise >= 0 ? $this->soulSandId : $this->soulSoilId,
            );

            return;
        }

        if ($isTop) {
            if ($nsNoise >= 0
                || ($y <= 35 && $y >= 30 && $patchNoise >= -0.012)
            ) {
                $chunk->setBlockStateId($x, $y, $z, $this->soulSandId);
            } else {
                $chunk->setBlockStateId($x, $y, $z, $this->soulSoilId);
            }
        }
    }

    private function applyWarpedForest(
        Chunk $chunk,
        int $x,
        int $y,
        int $z,
        float $nsNoise,
        float $netherwartNoise,
        int $above,
    ): void {
        if ($above === $this->airId
            && $y > 31
            && $nsNoise <= 0.28
        ) {
            $chunk->setBlockStateId(
                $x,
                $y,
                $z,
                $netherwartNoise >= 1.17 ? $this->warpedWartBlockId : $this->warpedNyliumId,
            );
        }
    }

    private function applyCrimsonForest(
        Chunk $chunk,
        int $x,
        int $y,
        int $z,
        float $nsNoise,
        float $netherwartNoise,
        int $above,
    ): void {
        if ($above === $this->airId
            && $y > 31
            && $nsNoise <= 0.54
        ) {
            $chunk->setBlockStateId(
                $x,
                $y,
                $z,
                $netherwartNoise >= 1.17 ? $this->netherWartBlockId : $this->crimsonNyliumId,
            );
        }
    }

    private function applyHell(
        Chunk $chunk,
        int $x,
        int $y,
        int $z,
        float $soulsandNoise,
        bool $isTop,
    ): void {
        if (!$isTop) {
            return;
        }

        if ($y > 31 && $y < 35 && $soulsandNoise >= -0.012) {
            $chunk->setBlockStateId($x, $y, $z, $this->gravelId);

            return;
        }

        if ($y <= 35 && $y >= 30 && $soulsandNoise >= -0.012) {
            $chunk->setBlockStateId($x, $y, $z, $this->soulSandId);
        }
    }

    private function placeBedrock(Chunk $chunk, Random $random): void
    {
        $roughness = $this->bedrockRoughness;

        for ($x = 0; $x < Chunk::EDGE_LENGTH; ++$x) {
            for ($z = 0; $z < Chunk::EDGE_LENGTH; ++$z) {
                $chunk->setBlockStateId($x, self::BEDROCK_FLOOR, $z, $this->bedrockId);
                $chunk->setBlockStateId($x, self::BEDROCK_CEIL, $z, $this->bedrockId);

                for ($i = 1; $i <= $roughness; ++$i) {
                    $y = self::BEDROCK_CEIL - $i;
                    if ($random->nextBoundedInt($roughness + 1) < ($roughness + 1 - $i)) {
                        $chunk->setBlockStateId($x, $y, $z, $this->bedrockId);
                    }
                }

                for ($i = 1; $i <= $roughness; ++$i) {
                    if ($random->nextBoundedInt($roughness + 1) < ($roughness + 1 - $i)) {
                        $chunk->setBlockStateId($x, self::BEDROCK_FLOOR + $i, $z, $this->bedrockId);
                    }
                }
            }
        }
    }
}
