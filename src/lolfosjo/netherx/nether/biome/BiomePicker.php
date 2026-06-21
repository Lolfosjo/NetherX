<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\biome;

use lolfosjo\netherx\noise\bukkit\SimplexOctaveGenerator;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;
use pocketmine\world\format\Chunk;

class BiomePicker
{
    private SimplexOctaveGenerator $noiseTemperature;
    private SimplexOctaveGenerator $noiseHumidity;
    private SimplexOctaveGenerator $noiseOffset;
    private BiomeRegistry $biomeRegistry;

    public function __construct(Random $random, BiomeSizePreset $preset, BiomeRegistry $biomeRegistry)
    {
        $this->noiseTemperature = new SimplexOctaveGenerator($random, 2);
        $this->noiseTemperature->setScale($preset->temperatureScale());

        $this->noiseHumidity = new SimplexOctaveGenerator($random, 2);
        $this->noiseHumidity->setScale($preset->humidityScale());

        $this->noiseOffset = new SimplexOctaveGenerator($random, 2);
        $this->noiseOffset->setScale($preset->temperatureScale());

        $this->biomeRegistry = $biomeRegistry;
    }

    public function selectBiome(float $worldX, float $worldZ): int
    {
        $temperature = $this->noiseTemperature->noise($worldX, 0, $worldZ, 0.5, 0.5, 1.0);
        $humidity = $this->noiseHumidity->noise($worldX, 0, $worldZ, 0.5, 0.5, 1.0);
        $offset = $this->noiseOffset->noise($worldX, 0, $worldZ, 0.5, 0.5, 1.0);

        $biomeIds = $this->biomeRegistry->getRegisteredBiomeIds();

        if ([] === $biomeIds) {
            return BiomeIds::HELL;
        }

        $bestBiome = $biomeIds[0];
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($biomeIds as $biomeId) {
            $definition = $this->biomeRegistry->get($biomeId);
            $distance = (($temperature - $definition->getTemperature()) ** 2)
                      + (($humidity - $definition->getHumidity()) ** 2)
                      + (($offset - $definition->getOffset()) ** 2);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestBiome = $biomeId;
            }
        }

        return $bestBiome;
    }

    public function fillChunkBiomes(Chunk $chunk, int $chunkX, int $chunkZ, int $netherHeight): array
    {
        $baseX = $chunkX * Chunk::EDGE_LENGTH;
        $baseZ = $chunkZ * Chunk::EDGE_LENGTH;
        $biomeMap = [];

        for ($x = 0; $x < Chunk::EDGE_LENGTH; ++$x) {
            for ($z = 0; $z < Chunk::EDGE_LENGTH; ++$z) {
                $biomeId = $this->selectBiome($baseX + $x, $baseZ + $z);
                $biomeMap[$x][$z] = $biomeId;

                for ($y = 0; $y < $netherHeight; ++$y) {
                    $chunk->setBiomeId($x, $y, $z, $biomeId);
                }
            }
        }

        return $biomeMap;
    }
}
