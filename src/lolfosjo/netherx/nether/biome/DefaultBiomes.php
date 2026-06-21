<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\biome;

use lolfosjo\netherx\nether\populator\basaltdelta\BasaltDeltaLavaPopulator;
use lolfosjo\netherx\nether\populator\basaltdelta\BasaltDeltaMagmaPopulator;
use lolfosjo\netherx\nether\populator\basaltdelta\BasaltDeltaPillarPopulator;
use lolfosjo\netherx\nether\populator\crimson\HugeCrimsonFungusPopulator;
use lolfosjo\netherx\nether\populator\crimson\WeepingVinesCeilingPopulator;
use lolfosjo\netherx\nether\populator\GlowstonePopulator;
use lolfosjo\netherx\nether\populator\NetherVegetationPopulator;
use lolfosjo\netherx\nether\populator\warped\HugeWarpedFungusPopulator;
use lolfosjo\netherx\nether\populator\warped\WarpedTwistingVinesPopulator;
use lolfosjo\netherx\nether\surface\BasaltDeltasSurfaceRule;
use lolfosjo\netherx\nether\surface\CrimsonForestSurfaceRule;
use lolfosjo\netherx\nether\surface\HellSurfaceRule;
use lolfosjo\netherx\nether\surface\SoulsandValleySurfaceRule;
use lolfosjo\netherx\nether\surface\WarpedForestSurfaceRule;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;

class DefaultBiomes
{
    public static function register(BiomeRegistry $registry, int $seed, Random $random): void
    {
        // TODO: rebalance these values (currently non-vanilla tuning)
        $randomHeight = $random->nextBoundedInt(6) + 7;
        $randomCluster = $random->nextBoundedInt(5) + 1;
        $glowstone = new GlowstonePopulator(
            minClusterSize: 40,
            maxClusterSize: 60,
            spawnChance: 11,
        );

        $registry->register(
            BiomeIds::HELL,
            BiomeDefinition::builder()
                ->withClimate(temperature: 0.0, humidity: 0.0, offset: 0.0)
                ->withSurfaceRule(new HellSurfaceRule())
                ->addVegetation($glowstone)
                ->addVegetation(new NetherVegetationPopulator(
                    plant: VanillaBlocks::FIRE(),
                    allowedGround: [VanillaBlocks::NETHERRACK()],
                    attemptsPerChunk: 1,
                ))
                ->build(),
        );

        $registry->register(
            BiomeIds::SOULSAND_VALLEY,
            BiomeDefinition::builder()
                ->withClimate(temperature: 0.0, humidity: -0.5, offset: 0.0)
                ->withSurfaceRule(new SoulsandValleySurfaceRule())
                ->addVegetation($glowstone)
                ->addVegetation(new NetherVegetationPopulator(
                    plant: VanillaBlocks::SOUL_FIRE(),
                    allowedGround: [VanillaBlocks::SOUL_SAND()],
                    attemptsPerChunk: 2,
                ))
                ->build(),
        );

        $registry->register(
            BiomeIds::CRIMSON_FOREST,
            BiomeDefinition::builder()
                ->withClimate(temperature: 0.4, humidity: 0.0, offset: 0.0)
                ->withSurfaceRule(new CrimsonForestSurfaceRule())
                ->addVegetation(new HugeCrimsonFungusPopulator(
                    treeHeight: $randomHeight,
                ))
                ->addVegetation(new WeepingVinesCeilingPopulator())
                ->addVegetation($glowstone)
                ->addVegetation(new NetherVegetationPopulator(
                    plant: VanillaBlocks::CRIMSON_FUNGUS(),
                    allowedGround: [VanillaBlocks::CRIMSON_NYLIUM()],
                    attemptsPerChunk: 4,
                ))
                ->addVegetation(new NetherVegetationPopulator(
                    plant: VanillaBlocks::WARPED_FUNGUS(),
                    allowedGround: [VanillaBlocks::CRIMSON_NYLIUM()],
                    attemptsPerChunk: 1,
                ))
                ->addVegetation(new NetherVegetationPopulator(
                    plant: VanillaBlocks::CRIMSON_ROOTS(),
                    allowedGround: [VanillaBlocks::CRIMSON_NYLIUM()],
                    attemptsPerChunk: 6,
                    cluster: true,
                    clusterRadius: 4,
                    clusterSize: $randomCluster,
                ))
                ->build(),
        );

        $registry->register(
            BiomeIds::WARPED_FOREST,
            BiomeDefinition::builder()
                ->withClimate(temperature: 0.0, humidity: 0.5, offset: 0.375)
                ->withSurfaceRule(new WarpedForestSurfaceRule())
                ->addVegetation(new WarpedTwistingVinesPopulator())
                ->addVegetation(new HugeWarpedFungusPopulator(
                    treeHeight: $randomHeight,
                ))
                ->addVegetation($glowstone)
                ->addVegetation(new NetherVegetationPopulator(
                    plant: VanillaBlocks::WARPED_FUNGUS(),
                    allowedGround: [VanillaBlocks::WARPED_NYLIUM()],
                    attemptsPerChunk: 4,
                ))
                ->addVegetation(new NetherVegetationPopulator(
                    plant: VanillaBlocks::CRIMSON_FUNGUS(),
                    allowedGround: [VanillaBlocks::WARPED_NYLIUM()],
                    attemptsPerChunk: 1,
                ))
                ->addVegetation(new NetherVegetationPopulator(
                    plant: VanillaBlocks::NETHER_SPROUTS(),
                    allowedGround: [VanillaBlocks::WARPED_NYLIUM()],
                    attemptsPerChunk: 8,
                ))
                ->addVegetation(new NetherVegetationPopulator(
                    plant: VanillaBlocks::CRIMSON_ROOTS(),
                    allowedGround: [VanillaBlocks::WARPED_NYLIUM()],
                    attemptsPerChunk: 1,
                ))
                ->addVegetation(new NetherVegetationPopulator(
                    plant: VanillaBlocks::WARPED_ROOTS(),
                    allowedGround: [VanillaBlocks::WARPED_NYLIUM()],
                    attemptsPerChunk: 6,
                    cluster: true,
                    clusterRadius: 4,
                    clusterSize: $randomCluster,
                ))
                ->build(),
        );

        $registry->register(
            BiomeIds::BASALT_DELTAS,
            BiomeDefinition::builder()
                ->withClimate(temperature: -0.5, humidity: 0.0, offset: 0.175)
                ->withSurfaceRule(new BasaltDeltasSurfaceRule())
                ->addVegetation(new BasaltDeltaMagmaPopulator())
                ->addVegetation(new BasaltDeltaPillarPopulator())
                ->addVegetation(new BasaltDeltaLavaPopulator($seed))
                ->build(),
        );
    }
}
