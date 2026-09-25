<?php

declare(strict_types=1);

namespace lolfosjo\netherx\noise\multi;

use pocketmine\utils\Random;

/**
 * Gekoppeltes Doppel-Rauschen: Summe zweier Oktaven-Perlin-Quellen mit
 * leicht verschobener Frequenz, auf einen stabilen Wertebereich normiert.
 *
 * Eigenständige Implementierung, enthält keinen Code aus Minecraft.
 */
final class PairedPerlinNoise
{
    /** Streckung der Koordinaten für die zweite Quelle, damit sich Gitterstrukturen nicht decken. */
    private const SECOND_SOURCE_STRETCH = 1.0181268882175227;

    private const NORMALIZATION_BASE = 1.0 / 6.0;
    private const SPREAD_FACTOR = 0.1;

    private LayeredPerlinNoise $first;
    private LayeredPerlinNoise $second;

    private float $amplitude;
    private float $maxValue;

    /**
     * @param float[] $amplitudes Amplitude je Oktave (0.0 = Oktave überspringen).
     */
    public function __construct(Random $random, int $firstOctave, array $amplitudes)
    {
        $amplitudes = array_values($amplitudes);

        // Beide Quellen ziehen nacheinander aus demselben Random,
        // haben dadurch verschiedene Zustände.
        $this->first = new LayeredPerlinNoise($random, $firstOctave, $amplitudes);
        $this->second = new LayeredPerlinNoise($random, $firstOctave, $amplitudes);

        // Spanne der tatsächlich aktiven Oktaven bestimmen.
        $lowest = PHP_INT_MAX;
        $highest = PHP_INT_MIN;
        foreach ($amplitudes as $index => $value) {
            if (0.0 !== $value) {
                $lowest = min($lowest, $index);
                $highest = max($highest, $index);
            }
        }

        if (PHP_INT_MAX === $lowest) {
            throw new \InvalidArgumentException('At least one non-zero amplitude is required.');
        }

        $this->amplitude = self::NORMALIZATION_BASE / self::spreadFor($highest - $lowest);
        $this->maxValue = ($this->first->getMaxValue() + $this->second->getMaxValue()) * $this->amplitude;
    }

    public function sample(float $x, float $y, float $z): float
    {
        $sx = $x * self::SECOND_SOURCE_STRETCH;
        $sy = $y * self::SECOND_SOURCE_STRETCH;
        $sz = $z * self::SECOND_SOURCE_STRETCH;

        return ($this->first->sample($x, $y, $z) + $this->second->sample($sx, $sy, $sz)) * $this->amplitude;
    }

    public function getMaxValue(): float
    {
        return $this->maxValue;
    }

    /**
     * Hilfsfunktion für die Normierung in Abhängigkeit von der Oktaven-Spanne.
     */
    private static function spreadFor(int $octaveSpan): float
    {
        return self::SPREAD_FACTOR * (1.0 + 1.0 / ($octaveSpan + 1));
    }
}
