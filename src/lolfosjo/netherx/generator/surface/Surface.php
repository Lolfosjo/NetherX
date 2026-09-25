<?php

/*
 * Derived from PowerNukkitX (NetherTerrainStage.java)
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\generator\surface;

use lolfosjo\netherx\generator\biome\BiomeRegistry;
use lolfosjo\netherx\noise\bukkit\SimplexOctaveGenerator;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;

class Surface
{
    public const LAVA_LEVEL = 31;
    private const SURFACE_MIN_Y = 1;
    private const SURFACE_MAX_Y = 126;
    private const SURFACE_SCAN_DEPTH = 4;

    private const SALT_NETHER_STATE = 0x4E455448;
    private const SALT_PATCH = 0x50415443;
    private const SALT_SOULSAND = 0x534F554C;
    private const SALT_NETHERWART = 0x4E455752;

    private int $netherrackId;
    private int $airId;

    private SimplexOctaveGenerator $netherStateNoise;
    private SimplexOctaveGenerator $patchNoise;
    private SimplexOctaveGenerator $soulsandNoise;
    private SimplexOctaveGenerator $netherwartNoise;

    private BiomeRegistry $biomeRegistry;

    public function __construct(Random $noiseRand, BiomeRegistry $biomeRegistry)
    {
        $this->netherrackId = VanillaBlocks::NETHERRACK()->getStateId();
        $this->airId = VanillaBlocks::AIR()->getStateId();

        $this->biomeRegistry = $biomeRegistry;

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

    public function generateSurface(ChunkManager $world, int $chunkX, int $chunkZ, array $biomeMap): void
    {
        /** @var Chunk $chunk */
        $chunk = $world->getChunk($chunkX, $chunkZ);

        $baseX = $chunkX << 4;
        $baseZ = $chunkZ << 4;

        $this->applyBiomeBlocks($chunk, $baseX, $baseZ, $biomeMap);
    }

    private function applyBiomeBlocks(Chunk $chunk, int $baseX, int $baseZ, array $biomeMap): void
    {
        $minY = self::SURFACE_MIN_Y;
        $maxY = self::SURFACE_MAX_Y;
        $depth = self::SURFACE_SCAN_DEPTH + 1;

        for ($x = 0; $x < Chunk::EDGE_LENGTH; ++$x) {
            for ($z = 0; $z < Chunk::EDGE_LENGTH; ++$z) {
                $biomeId = $biomeMap[$x][$z] ?? BiomeIds::HELL;

                $surfaceRule = $this->biomeRegistry->get($biomeId)->getSurfaceRule();
                if (null === $surfaceRule) {
                    continue;
                }

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
                        if ($yy >= $minY && $yy <= $maxY && ($ids[$yy] ?? $this->airId) === $this->airId) {
                            $isTop = true;

                            break;
                        }
                    }

                    $isCeil = false;
                    for ($i = 0; $i < $depth; ++$i) {
                        $yy = $y - $i;
                        if ($yy >= $minY && $yy <= $maxY && ($ids[$yy] ?? $this->airId) === $this->airId) {
                            $isCeil = true;

                            break;
                        }
                    }

                    $above = $ids[$y + 1] ?? $this->airId;
                    $below = $ids[$y - 1] ?? $this->airId;

                    $context = new SurfaceContext(
                        stateNoise: $nsNoise,
                        patchNoise: $patchN,
                        soulsandNoise: $soulsandN,
                        netherwartNoise: $netherwartN,
                        isTop: $isTop,
                        isCeil: $isCeil,
                        above: $above,
                        below: $below,
                    );

                    $surfaceRule->apply($chunk, $x, $y, $z, $context);
                }
            }
        }
    }
}
