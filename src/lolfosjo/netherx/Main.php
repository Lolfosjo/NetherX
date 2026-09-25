<?php

declare(strict_types=1);

namespace lolfosjo\netherx;

use bStats\PocketmineMp\Metrics;
use lolfosjo\netherx\listener\EventListener;
use lolfosjo\netherx\generator\NetherGenerator;
use lolfosjo\netherx\generator\variant\NetherGeneratorLarge;
use lolfosjo\netherx\generator\variant\NetherGeneratorSmall;
use lolfosjo\netherx\noise\glowstone\SimplexNoise;
use pocketmine\plugin\PluginBase;
use pocketmine\world\generator\GeneratorManager;

final class Main extends PluginBase
{
    public function onLoad(): void
    {
        $gm = GeneratorManager::getInstance();

        $gm->addGenerator(NetherGenerator::class, 'netherx', fn () => null);
        $gm->addGenerator(NetherGeneratorSmall::class, 'netherx_small', fn () => null);
        $gm->addGenerator(NetherGeneratorLarge::class, 'netherx_large', fn () => null);
    }

    public function onEnable(): void
    {
        $this->registerMetrics();
        $this->saveDefaultConfig();
        $this->reloadConfig();

        $config = $this->getConfig();
        $bedExplosionEnabled = (bool) $config->get('bed-explosion', true);
        $blockWaterPlacement = (bool) $config->get('block-water-placement', true);

        SimplexNoise::init();

        $this->getServer()->getPluginManager()->registerEvents(
            new EventListener($bedExplosionEnabled, $blockWaterPlacement),
            $this,
        );
    }

   	private function registerMetrics() : void {
		$metrics = new Metrics($this, 33686);
	}
}
