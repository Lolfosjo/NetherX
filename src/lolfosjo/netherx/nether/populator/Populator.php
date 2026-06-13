<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\populator;

use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;

abstract class Populator
{
    /**
     * Important: populators should only write inside the chunk column they're given.
     * Writing into neighbouring chunks can cause race conditions (neighbour might not be generated yet).
     */
    abstract public function populate(
        ChunkManager $world,
        int $chunkX,
        int $chunkZ,
        Random $random,
    ): void;
}
