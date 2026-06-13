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

final class BasaltDeltaPillarPopulator extends Populator
{
    private int $basaltId;
    private int $blackstoneId;
    private int $airId;

    public function __construct()
    {
        $this->basaltId = VanillaBlocks::BASALT()->getStateId();
        $this->blackstoneId = VanillaBlocks::BLACKSTONE()->getStateId();
        $this->airId = VanillaBlocks::AIR()->getStateId();
    }

    public function populate(ChunkManager $world, int $chunkX, int $chunkZ, Random $random): void
    {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (null === $chunk) {
            return;
        }

        $amount = $random->nextBoundedInt(128) + 128;

        for ($i = 0; $i < $amount; ++$i) {
            $localX = $random->nextBoundedInt(16);
            $localZ = $random->nextBoundedInt(16);

            $surfaceYs = $this->getHighestWorkableBlocks($chunk, $localX, $localZ);
            foreach ($surfaceYs as $y) {
                if ($y <= 1 || $y >= 127) {
                    continue;
                }
                if (0 === $random->nextBoundedInt(5)) {
                    continue;
                }
                $height = $random->nextBoundedInt(5) + 1;
                for ($h = 0; $h < $height; ++$h) {
                    $yy = $y + $h;
                    if ($yy >= 127) {
                        break;
                    }
                    $chunk->setBlockStateId($localX, $yy, $localZ, $this->basaltId);
                }
            }
        }
    }

    private function getHighestWorkableBlocks(Chunk $chunk, int $localX, int $localZ): array
    {
        $results = [];
        for ($y = 126; $y >= 2; --$y) {
            if ($chunk->getBlockStateId($localX, $y, $localZ) === $this->blackstoneId) {
                if ($chunk->getBlockStateId($localX, $y + 1, $localZ) === $this->airId) {
                    $results[] = $y + 1;
                }
            }
        }

        return $results;
    }
}
