<?php

/*
 * Derived from PowerNukkitX (NetherTerrainStage.java)
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\surface;

use pocketmine\block\VanillaBlocks;
use pocketmine\world\format\Chunk;

final class HellSurfaceRule implements SurfaceRule
{
    private int $gravelId;
    private int $soulSandId;

    public function __construct()
    {
        $this->gravelId = VanillaBlocks::GRAVEL()->getStateId();
        $this->soulSandId = VanillaBlocks::SOUL_SAND()->getStateId();
    }

    public function apply(Chunk $chunk, int $x, int $y, int $z, SurfaceContext $context): void
    {
        if (!$context->isTop) {
            return;
        }

        if ($y > 31 && $y < 35 && $context->soulsandNoise >= -0.012) {
            $chunk->setBlockStateId($x, $y, $z, $this->gravelId);

            return;
        }

        if ($y <= 35 && $y >= 30 && $context->soulsandNoise >= -0.012) {
            $chunk->setBlockStateId($x, $y, $z, $this->soulSandId);
        }
    }
}
