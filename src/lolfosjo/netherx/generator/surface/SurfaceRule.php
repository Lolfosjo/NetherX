<?php

declare(strict_types=1);

namespace lolfosjo\netherx\generator\surface;

use pocketmine\world\format\Chunk;

interface SurfaceRule
{
    public function apply(Chunk $chunk, int $x, int $y, int $z, SurfaceContext $context): void;
}
