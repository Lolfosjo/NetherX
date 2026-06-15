<?php

declare(strict_types=1);

namespace lolfosjo\netherx\listener;

use lolfosjo\netherx\nether\NetherGenerator;
use pocketmine\block\Bed;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\world\Explosion;
use pocketmine\world\generator\GeneratorManager;
use pocketmine\world\Position;

class EventListener implements Listener
{
    public function onInteract(PlayerInteractEvent $event): void
    {
        if (PlayerInteractEvent::RIGHT_CLICK_BLOCK !== $event->getAction()) {
            return;
        }

        $player = $event->getPlayer();
        $world = $player->getWorld();

        $generatorName = strtolower($world->getProvider()->getWorldData()->getGenerator());
        $registeredName = GeneratorManager::getInstance()->getGeneratorName(NetherGenerator::class);

        if ($generatorName !== $registeredName) {
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
}
