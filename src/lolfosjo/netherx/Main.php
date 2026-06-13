<?php

declare(strict_types=1);

namespace lolfosjo\netherx;

use lolfosjo\netherx\listener\EventListener;
use lolfosjo\netherx\nether\NetherGenerator;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;

final class Main extends PluginBase
{
    public function onLoad(): void
    {
        $generatorManager = GeneratorManager::getInstance();
        $generatorManager->addGenerator(NetherGenerator::class, 'netherx', fn () => null);
    }

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}
