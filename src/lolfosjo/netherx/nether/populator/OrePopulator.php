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

abstract class OrePopulator extends Populator
{
    public const CONCENTRATION_UNIFORM = 0;
    public const CONCENTRATION_TRIANGLE = 1;

    private int $netherrackStateId;

    public function __construct()
    {
        $this->netherrackStateId = VanillaBlocks::NETHERRACK()->getStateId();
    }

    abstract public function getOreBlock(int $replacedStateId): int;

    abstract public function getClusterCount(): int;

    abstract public function getClusterSize(): int;

    abstract public function getMinHeight(): int;

    abstract public function getMaxHeight(): int;

    public function getSkipAir(): float
    {
        return 0.0;
    }

    public function getConcentration(): int
    {
        return self::CONCENTRATION_UNIFORM;
    }

    public function isRare(): bool
    {
        return false;
    }

    public function canBeReplaced(int $stateId): bool
    {
        return $stateId === $this->netherrackStateId;
    }

    public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void
    {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (null === $chunk) {
            return;
        }

        $baseX = $chunkX << 4;
        $baseZ = $chunkZ << 4;
        $minY = $this->getMinHeight();
        $maxY = $this->getMaxHeight();
        $attempts = $this->resolveAttempts($random);

        for ($i = 0; $i < $attempts; ++$i) {
            $x = $baseX + $random->nextBoundedInt(16);
            $z = $baseZ + $random->nextBoundedInt(16);
            $y = $this->pickY($random, $minY, $maxY);

            $originState = $world->getBlockAt($x, $y, $z)->getStateId();
            if (!$this->canBeReplaced($originState)) {
                continue;
            }

            if (1 === $this->getClusterSize()) {
                $lx = $x & 0x0F;
                $lz = $z & 0x0F;
                if ($y >= 0 && $y < Chunk::MAX_SUBCHUNKS * 16) {
                    $chunk->setBlockStateId($lx, $y, $lz, $this->getOreBlock($originState));
                }
            } else {
                $veinBlocks = $this->spawnVein($world, $random, $x, $y, $z);

                if (0.0 !== $this->getSkipAir() && $this->veinTouchesAir($world, $veinBlocks)) {
                    if ($random->nextFloat() < $this->getSkipAir()) {
                        continue;
                    }
                }

                foreach ($veinBlocks as $entry) {
                    $lx = $entry['x'] & 0x0F;
                    $lz = $entry['z'] & 0x0F;
                    $vy = $entry['y'];
                    $targetChunk = $world->getChunk($entry['x'] >> 4, $entry['z'] >> 4);
                    if (null !== $targetChunk && $vy >= 0 && $vy < Chunk::MAX_SUBCHUNKS * 16) {
                        $targetChunk->setBlockStateId($lx, $vy, $lz, $entry['state']);
                    }
                }
            }
        }
    }

    private function resolveAttempts(Random $random): int
    {
        if (!$this->isRare()) {
            return $this->getClusterCount();
        }

        return 0 === $random->nextBoundedInt($this->getClusterCount()) ? 1 : 0;
    }

    private function pickY(Random $random, int $minY, int $maxY): int
    {
        $range = $maxY - $minY;
        if ($range <= 0) {
            return $minY;
        }

        if (self::CONCENTRATION_TRIANGLE === $this->getConcentration()) {
            return $minY + (int) (($random->nextBoundedInt($range + 1) + $random->nextBoundedInt($range + 1)) / 2);
        }

        return $minY + $random->nextBoundedInt($range + 1);
    }

    private function spawnVein(ChunkManager $world, Random $random, int $ox, int $oy, int $oz): array
    {
        $size = $this->getClusterSize();
        $piScaled = $random->nextFloat() * M_PI;

        $scaleMaxX = ($ox + 8) + sin($piScaled) * $size / 8.0;
        $scaleMinX = ($ox + 8) - sin($piScaled) * $size / 8.0;
        $scaleMaxZ = ($oz + 8) + cos($piScaled) * $size / 8.0;
        $scaleMinZ = ($oz + 8) - cos($piScaled) * $size / 8.0;
        $scaleMaxY = $oy + $random->nextBoundedInt(3) - 2;
        $scaleMinY = $oy + $random->nextBoundedInt(3) - 2;

        $blocks = [];

        for ($i = 0; $i < $size; ++$i) {
            $t = $i / $size;

            $scaleX = $scaleMaxX + ($scaleMinX - $scaleMaxX) * $t;
            $scaleY = $scaleMaxY + ($scaleMinY - $scaleMaxY) * $t;
            $scaleZ = $scaleMaxZ + ($scaleMinZ - $scaleMaxZ) * $t;

            $sineVal = sin(M_PI * $t) + 1.0;
            $randOffset = $random->nextFloat() * $size / 16.0;
            $radXZ = $sineVal * $randOffset + 1.0;
            $radY = $sineVal * $randOffset + 1.0;

            $minX = (int) floor($scaleX - $radXZ / 2.0);
            $minY = (int) floor($scaleY - $radY / 2.0);
            $minZ = (int) floor($scaleZ - $radXZ / 2.0);
            $maxX = (int) floor($scaleX + $radXZ / 2.0);
            $maxY = (int) floor($scaleY + $radY / 2.0);
            $maxZ = (int) floor($scaleZ + $radXZ / 2.0);

            for ($bx = $minX; $bx <= $maxX; ++$bx) {
                $xVal = ($bx + 0.5 - $scaleX) / ($radXZ / 2.0);
                if ($xVal * $xVal >= 1.0) {
                    continue;
                }

                for ($by = $minY; $by <= $maxY; ++$by) {
                    $yVal = ($by + 0.5 - $scaleY) / ($radY / 2.0);
                    if ($xVal * $xVal + $yVal * $yVal >= 1.0) {
                        continue;
                    }

                    for ($bz = $minZ; $bz <= $maxZ; ++$bz) {
                        $zVal = ($bz + 0.5 - $scaleZ) / ($radXZ / 2.0);
                        if ($xVal * $xVal + $yVal * $yVal + $zVal * $zVal >= 1.0) {
                            continue;
                        }

                        $original = $world->getBlockAt($bx, $by, $bz)->getStateId();
                        if (!$this->canBeReplaced($original)) {
                            continue;
                        }

                        $blocks[] = [
                            'x' => $bx,
                            'y' => $by,
                            'z' => $bz,
                            'state' => $this->getOreBlock($original),
                        ];
                    }
                }
            }
        }

        return $blocks;
    }

    private function veinTouchesAir(ChunkManager $world, array $veinBlocks): bool
    {
        $airId = VanillaBlocks::AIR()->getStateId();

        foreach ($veinBlocks as $entry) {
            $x = $entry['x'];
            $y = $entry['y'];
            $z = $entry['z'];

            $neighbors = [
                [$x + 1, $y, $z], [$x - 1, $y, $z],
                [$x, $y + 1, $z], [$x, $y - 1, $z],
                [$x, $y, $z + 1], [$x, $y, $z - 1],
            ];

            foreach ($neighbors as [$nx, $ny, $nz]) {
                if ($world->getBlockAt($nx, $ny, $nz)->getStateId() === $airId) {
                    return true;
                }
            }
        }

        return false;
    }
}
