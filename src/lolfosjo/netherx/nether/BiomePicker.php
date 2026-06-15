<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether;

use lolfosjo\netherx\nether\BiomeSizePreset;
use lolfosjo\netherx\noise\bukkit\SimplexOctaveGenerator;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\utils\Random;
use pocketmine\world\format\Chunk;

class BiomePicker
{
    private const BIOME_PARAMETERS = [
        BiomeIds::HELL => [
            'temperature' => 0.0,
            'humidity'    => 0.0,
            'offset'      => 0.0,
        ],
        BiomeIds::SOULSAND_VALLEY => [
            'temperature' => 0.0,
            'humidity'    => -0.5,
            'offset'      => 0.0,
        ],
        BiomeIds::CRIMSON_FOREST => [
            'temperature' => 0.4,
            'humidity'    => 0.0,
            'offset'      => 0.0,
        ],
        BiomeIds::WARPED_FOREST => [
            'temperature' => 0.0,
            'humidity'    => 0.5,
            'offset'      => 0.375,
        ],
        BiomeIds::BASALT_DELTAS => [
            'temperature' => -0.5,
            'humidity'    => 0.0,
            'offset'      => 0.175,
        ],
    ];

    private SimplexOctaveGenerator $noiseTemperature;
    private SimplexOctaveGenerator $noiseHumidity;

    public function __construct(Random $random, BiomeSizePreset $preset)
    {
        $this->noiseTemperature = new SimplexOctaveGenerator($random, 2);
        $this->noiseTemperature->setScale($preset->temperatureScale());

        $this->noiseHumidity = new SimplexOctaveGenerator($random, 2);
        $this->noiseHumidity->setScale($preset->humidityScale());
    }

    public function selectBiome(float $worldX, float $worldZ): int
    {
        $temperature = $this->noiseTemperature->noise($worldX, 0, $worldZ, 0.5, 0.5, 1.0);
        $humidity    = $this->noiseHumidity->noise($worldX, 0, $worldZ, 0.5, 0.5, 1.0);

        $bestBiome    = BiomeIds::HELL;
        $bestDistance = PHP_FLOAT_MAX;

        foreach (self::BIOME_PARAMETERS as $biomeId => $params) {
            $distance = (($temperature - $params['temperature']) ** 2)
                      + (($humidity    - $params['humidity'])    ** 2)
                      + ((0.0          - $params['offset'])      ** 2);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestBiome    = $biomeId;
            }
        }

        return $bestBiome;
    }

    public function fillChunkBiomes(Chunk $chunk, int $chunkX, int $chunkZ, int $netherHeight): array
    {
        $baseX    = $chunkX * Chunk::EDGE_LENGTH;
        $baseZ    = $chunkZ * Chunk::EDGE_LENGTH;
        $biomeMap = [];

        for ($x = 0; $x < Chunk::EDGE_LENGTH; ++$x) {
            for ($z = 0; $z < Chunk::EDGE_LENGTH; ++$z) {
                $biomeId          = $this->selectBiome($baseX + $x, $baseZ + $z);
                $biomeMap[$x][$z] = $biomeId;

                for ($y = 0; $y < $netherHeight; ++$y) {
                    $chunk->setBiomeId($x, $y, $z, $biomeId);
                }
            }
        }

        return $biomeMap;
    }
}
