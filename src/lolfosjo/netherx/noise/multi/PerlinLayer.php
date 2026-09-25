<?php

declare(strict_types=1);

namespace lolfosjo\netherx\noise\multi;

use pocketmine\utils\Random;

/**
 * Eine einzelne Ebene 3D-Gradientenrauschen (Perlin "Improved Noise").
 */
final class PerlinLayer
{
    /** @var int[] 512 Einträge (256 Permutationen, doppelt hintereinander). */
    private array $perm = [];

    private float $offsetX;
    private float $offsetY;
    private float $offsetZ;

    public function __construct(Random $random)
    {
        $this->offsetX = $random->nextFloat() * 256.0;
        $this->offsetY = $random->nextFloat() * 256.0;
        $this->offsetZ = $random->nextFloat() * 256.0;

        $p = range(0, 255);

        // Fisher-Yates-Mischung
        for ($i = 255; $i > 0; --$i) {
            $j = $random->nextBoundedInt($i + 1);
            [$p[$i], $p[$j]] = [$p[$j], $p[$i]];
        }

        for ($i = 0; $i < 512; ++$i) {
            $this->perm[$i] = $p[$i & 255];
        }
    }

    public function sample(float $x, float $y, float $z): float
    {
        $x += $this->offsetX;
        $y += $this->offsetY;
        $z += $this->offsetZ;

        $xi = (int) floor($x);
        $yi = (int) floor($y);
        $zi = (int) floor($z);

        $xf = $x - $xi;
        $yf = $y - $yi;
        $zf = $z - $zi;

        $xi &= 255;
        $yi &= 255;
        $zi &= 255;

        $u = self::fade($xf);
        $v = self::fade($yf);
        $w = self::fade($zf);

        $p = $this->perm;

        $a = $p[$xi] + $yi;
        $aa = $p[$a] + $zi;
        $ab = $p[$a + 1] + $zi;
        $b = $p[$xi + 1] + $yi;
        $ba = $p[$b] + $zi;
        $bb = $p[$b + 1] + $zi;

        return self::lerp(
            $w,
            self::lerp(
                $v,
                self::lerp($u, self::grad($p[$aa], $xf, $yf, $zf), self::grad($p[$ba], $xf - 1, $yf, $zf)),
                self::lerp($u, self::grad($p[$ab], $xf, $yf - 1, $zf), self::grad($p[$bb], $xf - 1, $yf - 1, $zf)),
            ),
            self::lerp(
                $v,
                self::lerp($u, self::grad($p[$aa + 1], $xf, $yf, $zf - 1), self::grad($p[$ba + 1], $xf - 1, $yf, $zf - 1)),
                self::lerp($u, self::grad($p[$ab + 1], $xf, $yf - 1, $zf - 1), self::grad($p[$bb + 1], $xf - 1, $yf - 1, $zf - 1)),
            ),
        );
    }

    private static function fade(float $t): float
    {
        return $t * $t * $t * ($t * ($t * 6.0 - 15.0) + 10.0);
    }

    private static function lerp(float $t, float $a, float $b): float
    {
        return $a + $t * ($b - $a);
    }

    private static function grad(int $hash, float $x, float $y, float $z): float
    {
        $h = $hash & 15;
        $u = $h < 8 ? $x : $y;
        $v = $h < 4 ? $y : (12 === $h || 14 === $h ? $x : $z);

        return ((0 === ($h & 1)) ? $u : -$u) + ((0 === ($h & 2)) ? $v : -$v);
    }
}
