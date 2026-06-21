<?php

/*
 * Derived from PowerNukkitX (NetherTerrainStage.java)
 * Ported from Java to PHP and modified.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether\surface;

use pocketmine\block\VanillaBlocks;
use pocketmine\world\format\Chunk;

final class CrimsonForestSurfaceRule implements SurfaceRule
{
    private int $airId;
    private int $netherWartBlockId;
    private int $crimsonNyliumId;

    public function __construct()
    {
        $this->airId = VanillaBlocks::AIR()->getStateId();
        $this->netherWartBlockId = VanillaBlocks::NETHER_WART_BLOCK()->getStateId();
        $this->crimsonNyliumId = VanillaBlocks::CRIMSON_NYLIUM()->getStateId();
    }

    public function apply(Chunk $chunk, int $x, int $y, int $z, SurfaceContext $context): void
    {
        if ($context->above === $this->airId
            && $y > 31
            && $context->stateNoise <= 0.54
        ) {
            $chunk->setBlockStateId(
                $x,
                $y,
                $z,
                $context->netherwartNoise >= 1.17 ? $this->netherWartBlockId : $this->crimsonNyliumId,
            );
        }
    }
}
