<?php

declare(strict_types=1);

namespace lolfosjo\netherx\noise\multi;

use pocketmine\utils\Random;

/**
 * Mehrstufiges (Oktaven-)Perlin-Rauschen mit einstellbaren Amplituden.
 *
 * Eigenständige Implementierung auf Basis des klassischen Perlin-Noise-
 * Verfahrens (Ken Perlin, "Improved Noise", 2002). Enthält keinen Code
 * aus Minecraft.
 */
final class LayeredPerlinNoise
{
    /** Ab dieser Größe wird der Eingabewert zurückgefaltet, um Präzisionsverlust zu vermeiden. */
    private const WRAP_PERIOD = 33554432.0;

    /** @var array<int, PerlinLayer|null> Ebenen von grob (Index 0) nach fein. */
    private array $layers = [];

    /** @var float[] */
    private array $amplitudes;

    private int $firstOctave;
    private float $lacunarityBase;
    private float $persistenceBase;
    private float $maxValue;

    /**
     * @param float[] $amplitudes Amplitude je Oktave; 0.0 = Oktave überspringen.
     * @param int $firstOctave    Meist negativ (z. B. -7). Bestimmt die gröbste Frequenz: 2^firstOctave.
     */
    public function __construct(Random $random, int $firstOctave, array $amplitudes)
    {
        if ([] === $amplitudes) {
            throw new \InvalidArgumentException('At least one amplitude is required.');
        }

        $this->amplitudes = array_values($amplitudes);
        $this->firstOctave = $firstOctave;

        $count = count($this->amplitudes);

        foreach ($this->amplitudes as $i => $amplitude) {
            $this->layers[$i] = 0.0 !== $amplitude ? new PerlinLayer($random) : null;
        }

        // Frequenz der gröbsten Ebene und Gewichtung so wählen,
        // dass sich alle Ebenen zu einem normierten Bereich addieren.
        $this->lacunarityBase = 2.0 ** $firstOctave;
        $this->persistenceBase = (2.0 ** ($count - 1)) / ((2.0 ** $count) - 1.0);
        $this->maxValue = $this->computeMaxValue(2.0);
    }

    /**
     * Bequeme Fabrik: Oktaven als Liste von Ganzzahlen (z. B. [-7, -6]).
     *
     * @param int[] $octaves
     */
    public static function fromOctaves(Random $random, array $octaves): self
    {
        if ([] === $octaves) {
            throw new \InvalidArgumentException('At least one octave is required.');
        }

        sort($octaves);
        $first = $octaves[0];
        $last = $octaves[count($octaves) - 1];

        $amplitudes = array_fill(0, $last - $first + 1, 0.0);
        foreach ($octaves as $octave) {
            $amplitudes[$octave - $first] = 1.0;
        }

        return new self($random, $first, $amplitudes);
    }

    public function sample(float $x, float $y, float $z): float
    {
        $sum = 0.0;
        $frequency = $this->lacunarityBase;
        $weight = $this->persistenceBase;

        foreach ($this->layers as $i => $layer) {
            if (null !== $layer) {
                $value = $layer->sample(
                    self::wrap($x * $frequency),
                    self::wrap($y * $frequency),
                    self::wrap($z * $frequency),
                );
                $sum += $this->amplitudes[$i] * $value * $weight;
            }

            $frequency *= 2.0;
            $weight /= 2.0;
        }

        return $sum;
    }

    public function getMaxValue(): float
    {
        return $this->maxValue;
    }

    /**
     * Theoretisch größter Betrag, den die Summe erreichen kann,
     * wenn jede Ebene ihren Maximalwert $layerMax liefert.
     */
    public function computeMaxValue(float $layerMax): float
    {
        $total = 0.0;
        $weight = $this->persistenceBase;

        foreach ($this->layers as $i => $layer) {
            if (null !== $layer) {
                $total += $this->amplitudes[$i] * $layerMax * $weight;
            }
            $weight /= 2.0;
        }

        return $total;
    }

    public function getFirstOctave(): int
    {
        return $this->firstOctave;
    }

    /**
     * Faltet große Koordinaten zurück in einen Bereich, in dem
     * Fließkommazahlen noch genau genug sind.
     */
    private static function wrap(float $value): float
    {
        return $value - floor($value / self::WRAP_PERIOD + 0.5) * self::WRAP_PERIOD;
    }
}
