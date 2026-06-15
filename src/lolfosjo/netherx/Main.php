<?php

declare(strict_types=1);

namespace lolfosjo\netherx;

use lolfosjo\netherx\listener\EventListener;
use lolfosjo\netherx\nether\NetherGenerator;
use lolfosjo\netherx\nether\variant\NetherGeneratorSmall;
use lolfosjo\netherx\nether\variant\NetherGeneratorLarge;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;

final class Main extends PluginBase
{
    public function onLoad(): void
    {
        $gm = GeneratorManager::getInstance();

        $gm->addGenerator(NetherGenerator::class, 'netherx', fn() => null);
        $gm->addGenerator(NetherGeneratorSmall::class, 'netherx_small', fn() => null);
        $gm->addGenerator(NetherGeneratorLarge::class, 'netherx_large', fn() => null);
    }

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}
