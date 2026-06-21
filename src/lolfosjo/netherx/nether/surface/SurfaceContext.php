<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\surface;

final class SurfaceContext
{
    public function __construct(
        public readonly float $stateNoise,
        public readonly float $patchNoise,
        public readonly float $soulsandNoise,
        public readonly float $netherwartNoise,
        public readonly bool $isTop,
        public readonly bool $isCeil,
        public readonly int $above,
        public readonly int $below,
    ) {}
}
