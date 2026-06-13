<?php

/*
 * Derived from Glowstone.
 * Original code licensed under the MIT License.
 */

declare(strict_types=1);

namespace lolfosjo\netherx\nether;

use lolfosjo\netherx\noise\glowstone\PerlinOctaveGenerator;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Random;
use pocketmine\world\ChunkManager;
use pocketmine\world\format\Chunk;

class Density
{
    protected const COORDINATE_SCALE = 684.412;
    protected const HEIGHT_SCALE = 2053.236;
    protected const HEIGHT_NOISE_SCALE_X = 100.0;
    protected const HEIGHT_NOISE_SCALE_Z = 100.0;
    protected const DETAIL_NOISE_SCALE_X = 80.0;
    protected const DETAIL_NOISE_SCALE_Y = 60.0;
    protected const DETAIL_NOISE_SCALE_Z = 80.0;

    /** Must match Surface::LAVA_LEVEL. Lava fills below this Y during raw terrain generation. */
    private const LAVA_LEVEL = 32;

    private PerlinOctaveGenerator $octaveHeight;
    private PerlinOctaveGenerator $octaveRoughness;
    private PerlinOctaveGenerator $octaveRoughness2;
    private PerlinOctaveGenerator $octaveDetail;

    private int $netherrackId;
    private int $stillLavaId;

    private static ?array $nvTable = null;

    public function __construct(Random $noiseRand)
    {
        $this->octaveHeight = PerlinOctaveGenerator::fromRandomAndOctaves($noiseRand, 16, 5, 1, 5);
        $this->octaveHeight->x_scale = static::HEIGHT_NOISE_SCALE_X;
        $this->octaveHeight->z_scale = static::HEIGHT_NOISE_SCALE_Z;

        $this->octaveRoughness = PerlinOctaveGenerator::fromRandomAndOctaves($noiseRand, 16, 5, 17, 5);
        $this->octaveRoughness->x_scale = static::COORDINATE_SCALE;
        $this->octaveRoughness->y_scale = static::HEIGHT_SCALE;
        $this->octaveRoughness->z_scale = static::COORDINATE_SCALE;

        $this->octaveRoughness2 = PerlinOctaveGenerator::fromRandomAndOctaves($noiseRand, 16, 5, 17, 5);
        $this->octaveRoughness2->x_scale = static::COORDINATE_SCALE;
        $this->octaveRoughness2->y_scale = static::HEIGHT_SCALE;
        $this->octaveRoughness2->z_scale = static::COORDINATE_SCALE;

        $this->octaveDetail = PerlinOctaveGenerator::fromRandomAndOctaves($noiseRand, 8, 5, 17, 5);
        $this->octaveDetail->x_scale = static::COORDINATE_SCALE / static::DETAIL_NOISE_SCALE_X;
        $this->octaveDetail->y_scale = static::HEIGHT_SCALE / static::DETAIL_NOISE_SCALE_Y;
        $this->octaveDetail->z_scale = static::COORDINATE_SCALE / static::DETAIL_NOISE_SCALE_Z;

        $this->netherrackId = VanillaBlocks::NETHERRACK()->getStateId();
        $this->stillLavaId = VanillaBlocks::LAVA()->getStateId();

        if (null === self::$nvTable) {
            $kMax = $this->octaveDetail->size_y; // = 17
            $nv = [];
            for ($i = 0; $i < $kMax; ++$i) {
                $nv[$i] = cos($i * M_PI * 6.0 / $kMax) * 2.0;
                $nh = $i > $kMax / 2 ? $kMax - 1 - $i : $i;
                if ($nh < 4.0) {
                    $nh = 4.0 - $nh;
                    $nv[$i] -= $nh * $nh * $nh * 10.0;
                }
            }
            self::$nvTable = $nv;
        }
    }

    public function generateRawTerrain(ChunkManager $world, int $chunkX, int $chunkZ): void
    {
        $density = $this->buildDensityGrid($chunkX << 2, $chunkZ << 2);

        $chunk = $world->getChunk($chunkX, $chunkZ);

        $coordBitSize = Chunk::COORD_BIT_SIZE;

        for ($i = 0; $i < 4; ++$i) {
            for ($j = 0; $j < 4; ++$j) {
                for ($k = 0; $k < 16; ++$k) {
                    $d1 = $density[($k << 6) | ($j << 3) | $i];
                    $d2 = $density[($k << 6) | ($j << 3) | ($i + 1)];
                    $d3 = $density[($k << 6) | (($j + 1) << 3) | $i];
                    $d4 = $density[($k << 6) | (($j + 1) << 3) | ($i + 1)];
                    $d5 = ($density[(($k + 1) << 6) | ($j << 3) | $i] - $d1) / 8;
                    $d6 = ($density[(($k + 1) << 6) | ($j << 3) | ($i + 1)] - $d2) / 8;
                    $d7 = ($density[(($k + 1) << 6) | (($j + 1) << 3) | $i] - $d3) / 8;
                    $d8 = ($density[(($k + 1) << 6) | (($j + 1) << 3) | ($i + 1)] - $d4) / 8;

                    for ($l = 0; $l < 8; ++$l) {
                        $d9 = $d1;
                        $d10 = $d3;

                        $yPos = $l + ($k << 3);
                        $yBlockPos = $yPos & 0xF;
                        $subChunk = $chunk->getSubChunk($yPos >> $coordBitSize);

                        $iBase = $i << 2;
                        $jBase = $j << 2;

                        for ($m = 0; $m < 4; ++$m) {
                            $dens = $d9;
                            $mOff = $m + $iBase;
                            $dStep = ($d10 - $d9) / 4;

                            for ($n = 0; $n < 4; ++$n) {
                                if ($dens > 0) {
                                    $subChunk->setBlockStateId($mOff, $yBlockPos, $n + $jBase, $this->netherrackId);
                                } elseif ($yPos < self::LAVA_LEVEL) {
                                    $subChunk->setBlockStateId($mOff, $yBlockPos, $n + $jBase, $this->stillLavaId);
                                }
                                $dens += $dStep;
                            }
                            $d9 += ($d2 - $d1) / 4;
                            $d10 += ($d4 - $d3) / 4;
                        }
                        $d1 += $d5;
                        $d3 += $d7;
                        $d2 += $d6;
                        $d4 += $d8;
                    }
                }
            }
        }
    }

    private function buildDensityGrid(int $x, int $z): array
    {
        $heightNoise = $this->octaveHeight->getFractalBrownianMotion($x, 0, $z, 0.5, 2.0);
        $roughnessNoise = $this->octaveRoughness->getFractalBrownianMotion($x, 0, $z, 0.5, 2.0);
        $roughness2Noise = $this->octaveRoughness2->getFractalBrownianMotion($x, 0, $z, 0.5, 2.0);
        $detailNoise = $this->octaveDetail->getFractalBrownianMotion($x, 0, $z, 0.5, 2.0);

        $nv = self::$nvTable;
        $kMax = count($nv);

        $index = 0;
        $indexHeight = 0;
        $density = [];

        $kCap = $kMax - 4;

        for ($i = 0; $i < 5; ++$i) {
            for ($j = 0; $j < 5; ++$j) {
                $noiseH = $heightNoise[$indexHeight++] / 8000.0;
                if ($noiseH < 0) {
                    $noiseH = -$noiseH;
                }
                $noiseH = $noiseH * 3.0 - 3.0;
                if ($noiseH < 0) {
                    $noiseH = max($noiseH * 0.5, -1.0) / 1.4 * 0.5;
                } else {
                    $noiseH = min($noiseH, 1.0) / 6.0;
                }
                $noiseH *= $kMax / 16.0;

                for ($k = 0; $k < $kMax; ++$k) {
                    $noiseR = $roughnessNoise[$index] / 512.0;
                    $noiseR2 = $roughness2Noise[$index] / 512.0;
                    $noiseD = ($detailNoise[$index] / 10.0 + 1.0) / 2.0;

                    if ($noiseD < 0) {
                        $dens = $noiseR;
                    } elseif ($noiseD > 1) {
                        $dens = $noiseR2;
                    } else {
                        $dens = $noiseR + ($noiseR2 - $noiseR) * $noiseD;
                    }

                    $dens -= $nv[$k];
                    $dens += $noiseH;

                    if ($k > $kCap) {
                        $lowering = ($k - $kCap) / 3.0;
                        $dens = $dens * (1.0 - $lowering) + (-10.0) * $lowering;
                    }

                    $density[($k << 6) | ($j << 3) | $i] = $dens;
                    ++$index;
                }
            }
        }

        return $density;
    }
}
