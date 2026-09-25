<?php

declare(strict_types=1);

namespace lolfosjo\netherx\generator;

use lolfosjo\netherx\generator\biome\BiomeDefinition;
use lolfosjo\netherx\generator\biome\BiomePicker;
use lolfosjo\netherx\generator\biome\BiomeRegistry;
use lolfosjo\netherx\generator\biome\BiomeSizePreset;
use lolfosjo\netherx\generator\biome\DefaultBiomes;
use lolfosjo\netherx\generator\Density;
use lolfosjo\netherx\generator\populator\AncientDebrisLargePopulator;
use lolfosjo\netherx\generator\populator\AncientDebrisSmallPopulator;
use lolfosjo\netherx\generator\populator\LavaOrePopulator;
use lolfosjo\netherx\generator\populator\MagmaPopulator;
use lolfosjo\netherx\generator\populator\NetherBlackstonePopulator;
use lolfosjo\netherx\generator\populator\NetherGoldOrePopulator;
use lolfosjo\netherx\generator\populator\NetherGravelPopulator;
use lolfosjo\netherx\generator\populator\NetherQuartzOrePopulator;
use lolfosjo\netherx\generator\populator\Populator;
use lolfosjo\netherx\generator\surface\Surface;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\Generator;

class NetherGenerator extends Generator
{
    private const NETHER_HEIGHT = 128;
    private const BIOME_SAMPLE_Y = 2;
    private const SEED_XOR = 0xDEADBEEF;
    private const BIOME_SEED_SALT = 0x42494F4D; // "BIOM"

    private int $bedrockRoughness = 5;

    private BiomeSizePreset $biomeSizePreset;
    private Density $densityGenerator;
    private Surface $surfaceGenerator;
    private BiomePicker $biomePicker;
    private BiomeRegistry $biomeRegistry;

    /** @var Populator[] */
    private array $generationPopulators = [];

    /** @var Populator[] */
    private array $populators = [];

    public function __construct(int $seed, string $preset)
    {
        parent::__construct($seed, $preset);

        $this->biomeSizePreset = $this->resolveBiomeSizePreset();

        // $this->seed ist konstant; $this->random->getSeed() ändert sich pro Chunk.
        $noiseRand = new Random($this->seed);

        $this->densityGenerator = new Density($noiseRand, $this->bedrockRoughness);

        $this->biomeRegistry = new BiomeRegistry();
        DefaultBiomes::register($this->biomeRegistry, $this->seed);

        $this->surfaceGenerator = new Surface($noiseRand, $this->biomeRegistry);

        $this->biomePicker = new BiomePicker(
            new Random($this->seed ^ self::BIOME_SEED_SALT),
            $this->biomeSizePreset,
            $this->biomeRegistry,
        );

        $this->addPopulator(new NetherQuartzOrePopulator());
        $this->addPopulator(new NetherGoldOrePopulator());
        $this->addPopulator(new LavaOrePopulator());
        $this->addPopulator(new NetherGravelPopulator());
        $this->addPopulator(new NetherBlackstonePopulator());
        $this->addPopulator(new MagmaPopulator());
        $this->addPopulator(new AncientDebrisLargePopulator());
        $this->addPopulator(new AncientDebrisSmallPopulator());
    }

    public function registerBiome(int $biomeId, BiomeDefinition $definition): void
    {
        $this->biomeRegistry->register($biomeId, $definition);
    }

    public function getBiomeRegistry(): BiomeRegistry
    {
        return $this->biomeRegistry;
    }

    public function getBedrockRoughness(): int
    {
        return $this->bedrockRoughness;
    }

    public function setBedrockRoughness(int $roughness): void
    {
        $this->bedrockRoughness = $roughness;

        $this->densityGenerator = new Density(new Random($this->seed), $roughness);
    }

    public function addNetherGenerationPopulator(Populator $populator): void
    {
        $this->generationPopulators[] = $populator;
    }

    public function addPopulator(Populator $populator): void
    {
        $this->populators[] = $populator;
    }

    public function generateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
        $this->random->setSeed(self::SEED_XOR ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);

        $chunk = $world->getChunk($chunkX, $chunkZ)
            ?? throw new \InvalidArgumentException("Chunk {$chunkX} {$chunkZ} does not yet exist");

        $this->densityGenerator->generateRawTerrain($world, $chunkX, $chunkZ, $this->random);
        $biomeMap = $this->biomePicker->fillChunkBiomes($chunk, $chunkX, $chunkZ, self::NETHER_HEIGHT);
        $this->surfaceGenerator->generateSurface($world, $chunkX, $chunkZ, $biomeMap);

        foreach ($this->generationPopulators as $populator) {
            $populator->populate($world, $chunkX, $chunkZ, $this->random);
        }
    }

    public function populateChunk(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
        $this->random->setSeed(self::SEED_XOR ^ ($chunkX << 8) ^ $chunkZ ^ $this->seed);

        foreach ($this->populators as $populator) {
            $populator->populate($world, $chunkX, $chunkZ, $this->random);
        }

        $this->applyVegetationPopulators($world, $chunkX, $chunkZ);
    }

    protected function resolveBiomeSizePreset(): BiomeSizePreset
    {
        return BiomeSizePreset::MEDIUM;
    }

    private function applyVegetationPopulators(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (null === $chunk) {
            return;
        }

        $seenProfiles = [];

        for ($x = 0; $x < Chunk::EDGE_LENGTH; ++$x) {
            for ($z = 0; $z < Chunk::EDGE_LENGTH; ++$z) {
                $biomeId = $chunk->getBiomeId($x, self::BIOME_SAMPLE_Y, $z);
                if (!isset($seenProfiles[$biomeId])) {
                    $seenProfiles[$biomeId] = $this->biomeRegistry->get($biomeId);
                }
            }
        }

        foreach ($seenProfiles as $profile) {
            foreach ($profile->getVegetationPopulators() as $populator) {
                $populator->populate($world, $chunkX, $chunkZ, $this->random);
            }
        }
    }
}
