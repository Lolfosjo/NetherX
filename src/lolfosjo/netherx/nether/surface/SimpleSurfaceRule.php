<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\surface;

use pocketmine\world\format\Chunk;

final class SimpleSurfaceRule implements SurfaceRule
{
    public function __construct(
        private readonly ?int $topBlock = null,
        private readonly ?int $ceilingBlock = null,
        private readonly ?int $patchBlock = null,
        private readonly int $patchMinY = 30,
        private readonly int $patchMaxY = 35,
        private readonly float $patchThreshold = -0.012,
    ) {}

    public function apply(Chunk $chunk, int $x, int $y, int $z, SurfaceContext $context): void
    {
        if ($context->isCeil && null !== $this->ceilingBlock) {
            $chunk->setBlockStateId($x, $y, $z, $this->ceilingBlock);

            return;
        }

        if ($context->isTop) {
            if (null !== $this->patchBlock
                && $y >= $this->patchMinY
                && $y <= $this->patchMaxY
                && $context->patchNoise >= $this->patchThreshold
            ) {
                $chunk->setBlockStateId($x, $y, $z, $this->patchBlock);

                return;
            }

            if (null !== $this->topBlock) {
                $chunk->setBlockStateId($x, $y, $z, $this->topBlock);
            }
        }
    }
}
