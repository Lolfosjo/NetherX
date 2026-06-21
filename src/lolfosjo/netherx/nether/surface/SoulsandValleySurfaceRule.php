<?php

/*
 * Derived from PowerNukkitX (NetherTerrainStage.java)
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\surface;

use pocketmine\block\VanillaBlocks;
use pocketmine\world\format\Chunk;

final class SoulsandValleySurfaceRule implements SurfaceRule
{
    private int $soulSandId;
    private int $soulSoilId;

    public function __construct()
    {
        $this->soulSandId = VanillaBlocks::SOUL_SAND()->getStateId();
        $this->soulSoilId = VanillaBlocks::SOUL_SOIL()->getStateId();
    }

    public function apply(Chunk $chunk, int $x, int $y, int $z, SurfaceContext $context): void
    {
        if ($context->isCeil) {
            $chunk->setBlockStateId(
                $x,
                $y,
                $z,
                $context->stateNoise >= 0 ? $this->soulSandId : $this->soulSoilId,
            );

            return;
        }

        if ($context->isTop) {
            if ($context->stateNoise >= 0
                || ($y <= 35 && $y >= 30 && $context->patchNoise >= -0.012)
            ) {
                $chunk->setBlockStateId($x, $y, $z, $this->soulSandId);
            } else {
                $chunk->setBlockStateId($x, $y, $z, $this->soulSoilId);
            }
        }
    }
}
