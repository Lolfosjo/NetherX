<?php

/*
 * Derived from PowerNukkitX
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator\crimson;

use lolfosjo\netherx\nether\populator\Populator;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\object\NetherTree;

final class HugeCrimsonFungusPopulator extends Populator
{
    private const MAX_Y = 126;
    private const MIN_Y = 1;

    private int $airId;
    private int $crimsonNyliumId;

    public function __construct(
        private readonly int $treeHeight = 7,
    ) {
        $this->airId = VanillaBlocks::AIR()->getStateId();
        $this->crimsonNyliumId = VanillaBlocks::CRIMSON_NYLIUM()->getStateId();
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

        $baseX = $chunkX << 4;
        $baseZ = $chunkZ << 4;

        $amount = $random->nextBoundedInt(6) + 4;

        for ($i = 0; $i < $amount; ++$i) {
            $localX = $random->nextBoundedInt(Chunk::EDGE_LENGTH);
            $localZ = $random->nextBoundedInt(Chunk::EDGE_LENGTH);

            $ys = $this->getHighestWorkableBlocks($chunk, $localX, $localZ);

            foreach ($ys as $y) {
                if ($y <= 1) {
                    continue;
                }
                if (1 === $random->nextBoundedInt(4)) {
                    continue;
                }

                $tree = new NetherTree(
                    VanillaBlocks::CRIMSON_STEM(),
                    VanillaBlocks::NETHER_WART_BLOCK(),
                    VanillaBlocks::SHROOMLIGHT(),
                    $this->treeHeight,
                    true,
                    false,
                );

                $transaction = $tree->getBlockTransaction($world, $baseX + $localX, $y, $baseZ + $localZ, $random);
                if (null === $transaction) {
                    continue;
                }

                $transaction->apply();
            }
        }
    }

    private function getHighestWorkableBlocks(Chunk $chunk, int $localX, int $localZ): array
    {
        $blockYs = [];
        for ($y = self::MAX_Y; $y > self::MIN_Y; --$y) {
            if ($chunk->getBlockStateId($localX, $y, $localZ) !== $this->crimsonNyliumId) {
                continue;
            }
            if ($chunk->getBlockStateId($localX, $y + 1, $localZ) !== $this->airId) {
                continue;
            }
            $blockYs[] = $y + 1;
        }

        return $blockYs;
    }
}
