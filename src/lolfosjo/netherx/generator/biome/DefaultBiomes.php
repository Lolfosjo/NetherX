<?php

declare(strict_types=1);

namespace lolfosjo\netherx\generator\biome;

use lolfosjo\netherx\generator\populator\basaltdelta\BasaltDeltaLavaPopulator;
use lolfosjo\netherx\generator\populator\basaltdelta\BasaltDeltaMagmaPopulator;
use lolfosjo\netherx\generator\populator\basaltdelta\BasaltDeltaPillarPopulator;
use lolfosjo\netherx\generator\populator\crimson\HugeCrimsonFungusPopulator;
use lolfosjo\netherx\generator\populator\crimson\WeepingVinesCeilingPopulator;
use lolfosjo\netherx\generator\populator\GlowstonePopulator;
use lolfosjo\netherx\generator\populator\NetherVegetationPopulator;
use lolfosjo\netherx\generator\populator\warped\HugeWarpedFungusPopulator;
use lolfosjo\netherx\generator\populator\warped\WarpedTwistingVinesPopulator;
use lolfosjo\netherx\generator\surface\BasaltDeltasSurfaceRule;
use lolfosjo\netherx\generator\surface\CrimsonForestSurfaceRule;
use lolfosjo\netherx\generator\surface\HellSurfaceRule;
use lolfosjo\netherx\generator\surface\SoulsandValleySurfaceRule;
use lolfosjo\netherx\generator\surface\WarpedForestSurfaceRule;
use pocketmine\block\VanillaBlocks;
use pocketmine\data\bedrock\BiomeIds;

class DefaultBiomes
{
    private const FUNGUS_MIN_HEIGHT = 7;
    private const FUNGUS_MAX_HEIGHT = 12;
    private const ROOTS_MIN_CLUSTER = 3;
    private const ROOTS_MAX_CLUSTER = 10;

    public static function register(BiomeRegistry $registry, int $seed): void
    {
        // TODO: rebalance these values (currently non-vanilla tuning)
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
                    minHeight: self::FUNGUS_MIN_HEIGHT,
                    maxHeight: self::FUNGUS_MAX_HEIGHT,
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
                    minClusterSize: self::ROOTS_MIN_CLUSTER,
                    maxClusterSize: self::ROOTS_MAX_CLUSTER,
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
                    minHeight: self::FUNGUS_MIN_HEIGHT,
                    maxHeight: self::FUNGUS_MAX_HEIGHT,
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
                    minClusterSize: self::ROOTS_MIN_CLUSTER,
                    maxClusterSize: self::ROOTS_MAX_CLUSTER,
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
