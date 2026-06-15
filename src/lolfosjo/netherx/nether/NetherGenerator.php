<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether;

use lolfosjo\netherx\nether\biome\BiomeRegistry;
use lolfosjo\netherx\nether\biome\DefaultBiomes;
use lolfosjo\netherx\nether\populator\AncientDebrisLargePopulator;
use lolfosjo\netherx\nether\populator\AncientDebrisSmallPopulator;
use lolfosjo\netherx\nether\populator\LavaOrePopulator;
use lolfosjo\netherx\nether\populator\MagmaPopulator;
use lolfosjo\netherx\nether\populator\NetherBlackstonePopulator;
use lolfosjo\netherx\nether\populator\NetherGoldOrePopulator;
use lolfosjo\netherx\nether\populator\NetherGravelPopulator;
use lolfosjo\netherx\nether\populator\NetherQuartzOrePopulator;
use lolfosjo\netherx\nether\populator\Populator;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;
use pocketmine\world\generator\Generator;

class NetherGenerator extends Generator
{
    private const NETHER_HEIGHT = 128;
    private const BEDROCK_FLOOR = 0;
    private const SEED_XOR      = 0xDEADBEEF;

    private int $bedrockRoughness = 5;

    private BiomeSizePreset $biomeSizePreset;
    private Density         $densityGenerator;
    private Surface         $surfaceGenerator;
    private BiomePicker     $biomePicker;
    private BiomeRegistry   $biomeRegistry;

    /** @var Populator[] */
    private array $generationPopulators = [];

    /** @var Populator[] */
    private array $populators = [];

    public function __construct(int $seed, string $preset)
    {
        parent::__construct($seed, $preset);

        $this->biomeSizePreset = $this->resolveBiomeSizePreset();

        $noiseRand = new Random($this->random->getSeed());

        $this->densityGenerator = new Density($noiseRand);
        $this->surfaceGenerator = new Surface($noiseRand, $this->bedrockRoughness);
        $this->biomePicker      = new BiomePicker($this->random, $this->biomeSizePreset);

        $this->biomeRegistry = new BiomeRegistry();
        DefaultBiomes::register($this->biomeRegistry, $this->seed, $this->random);

        $this->addPopulator(new NetherQuartzOrePopulator());
        $this->addPopulator(new NetherGoldOrePopulator());
        $this->addPopulator(new LavaOrePopulator());
        $this->addPopulator(new NetherGravelPopulator());
        $this->addPopulator(new NetherBlackstonePopulator());
        $this->addPopulator(new MagmaPopulator());
        $this->addPopulator(new AncientDebrisLargePopulator());
        $this->addPopulator(new AncientDebrisSmallPopulator());
    }

    protected function resolveBiomeSizePreset(): BiomeSizePreset
    {
        return BiomeSizePreset::MEDIUM;
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
        $this->surfaceGenerator = new Surface(
            new Random($this->random->getSeed()),
            $roughness,
        );
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

        $this->densityGenerator->generateRawTerrain($world, $chunkX, $chunkZ);
        $biomeMap = $this->biomePicker->fillChunkBiomes($chunk, $chunkX, $chunkZ, self::NETHER_HEIGHT);
        $this->surfaceGenerator->generateSurface($world, $chunkX, $chunkZ, $this->random, $biomeMap);

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

    private function applyVegetationPopulators(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
        $chunk = $world->getChunk($chunkX, $chunkZ);
        if (null === $chunk) {
            return;
        }

        $seenProfiles = [];

        for ($x = 0; $x < Chunk::EDGE_LENGTH; ++$x) {
            for ($z = 0; $z < Chunk::EDGE_LENGTH; ++$z) {
                $biomeId = $chunk->getBiomeId($x, self::BEDROCK_FLOOR + 2, $z);
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
