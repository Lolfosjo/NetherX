<?php

declare(strict_types=1);

namespace lolfosjo\netherx\generator\biome;

use lolfosjo\netherx\noise\multi\PairedPerlinNoise;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;
use pocketmine\world\format\Chunk;

class BiomePicker
{
    private PairedPerlinNoise $noiseTemperature;
    private PairedPerlinNoise $noiseHumidity;
    private BiomeRegistry $biomeRegistry;

    public function __construct(Random $random, BiomeSizePreset $preset, BiomeRegistry $biomeRegistry)
    {
        $octave = $preset->firstOctave();

        $this->noiseTemperature = new PairedPerlinNoise($random, $octave, [1.0, 0.5]);
        $this->noiseHumidity = new PairedPerlinNoise($random, $octave, [1.0, 0.5]);

        $this->biomeRegistry = $biomeRegistry;
    }

    public function selectBiome(float $worldX, float $worldZ): int
    {
        $temperature = $this->noiseTemperature->sample($worldX, 0.0, $worldZ);
        $humidity = $this->noiseHumidity->sample($worldX, 0.0, $worldZ);

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
                      + ($definition->getOffset() ** 2);

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
