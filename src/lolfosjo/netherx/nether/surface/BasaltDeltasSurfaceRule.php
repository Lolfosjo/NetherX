<?php

/*
 * Derived from PowerNukkitX (NetherTerrainStage.java)
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\surface;

use pocketmine\block\VanillaBlocks;
use pocketmine\world\format\Chunk;

final class BasaltDeltasSurfaceRule implements SurfaceRule
{
    private int $airId;
    private int $basaltId;
    private int $gravelId;
    private int $blackstoneId;

    public function __construct()
    {
        $this->airId = VanillaBlocks::AIR()->getStateId();
        $this->basaltId = VanillaBlocks::BASALT()->getStateId();
        $this->gravelId = VanillaBlocks::GRAVEL()->getStateId();
        $this->blackstoneId = VanillaBlocks::BLACKSTONE()->getStateId();
    }

    public function apply(Chunk $chunk, int $x, int $y, int $z, SurfaceContext $context): void
    {
        if ($context->isCeil) {
            $chunk->setBlockStateId($x, $y, $z, $this->basaltId);

            return;
        }

        if ($context->above === $this->airId) {
            if ($context->stateNoise >= 0
                || ($y <= 35 && $y >= 30 && $context->patchNoise >= -0.012)
            ) {
                $chunk->setBlockStateId($x, $y, $z, $this->gravelId);

                return;
            }
        }

        if ($context->isTop) {
            $chunk->setBlockStateId($x, $y, $z, $this->blackstoneId);
        }
    }
}
