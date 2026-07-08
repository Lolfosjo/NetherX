<?php

declare(strict_types=1);

namespace lolfosjo\netherx\listener;

use lolfosjo\netherx\nether\NetherGenerator;
use pocketmine\block\Bed;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Water;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\Explosion;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\Position;
use pocketmine\world\World;

class EventListener implements Listener
{
    public function __construct(
        private readonly bool $bedExplosionEnabled = true,
        private readonly bool $blockWaterPlacement = true,
    ) {
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        if (!$this->bedExplosionEnabled) {
            return;
        }

        if (PlayerInteractEvent::RIGHT_CLICK_BLOCK !== $event->getAction()) {
            return;
        }

        $player = $event->getPlayer();
        $world = $player->getWorld();

        if (!$this->isNetherXWorld($world)) {
            return;
        }

        $block = $event->getBlock();

        if (!$block instanceof Bed) {
            return;
        }

        $event->cancel();

        $bedPos = $block->getPosition();

        $otherHalf = $block->getOtherHalf();

        if (null !== $otherHalf) {
            $world->setBlock($otherHalf->getPosition(), VanillaBlocks::AIR());
        }

        $world->setBlock($bedPos, VanillaBlocks::AIR());

        $explosion = new Explosion(
            Position::fromObject($bedPos, $world),
            5.0,
            $player,
        );

        $explosion->explodeA();
        $explosion->explodeB();
    }

    public function onBlockUpdate(BlockUpdateEvent $event): void
    {
        if (!$this->blockWaterPlacement) {
            return;
        }

        $block = $event->getBlock();

        if (!$block instanceof Water) {
            return;
        }

        $world = $block->getPosition()->getWorld();

        if (!$this->isNetherXWorld($world)) {
            return;
        }

        $world->setBlock($block->getPosition(), VanillaBlocks::AIR());
    }

    private function isNetherXWorld(World $world): bool
    {
        $generatorName = strtolower($world->getProvider()->getWorldData()->getGenerator());
        $registeredName = GeneratorManager::getInstance()->getGeneratorName(NetherGenerator::class);

        return $generatorName === $registeredName;
    }
}