<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\biome;

use lolfosjo\netherx\nether\populator\Populator;
use lolfosjo\netherx\nether\surface\SurfaceRule;

final class BiomeDefinition
{
    /** @var Populator[] */
    private array $vegetationPopulators;

    private function __construct(
        array $vegetationPopulators,
        private float $temperature,
        private float $humidity,
        private float $offset,
        private ?SurfaceRule $surfaceRule,
    ) {
        $this->vegetationPopulators = $vegetationPopulators;
    }

    /** @return Populator[] */
    public function getVegetationPopulators(): array
    {
        return $this->vegetationPopulators;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function getHumidity(): float
    {
        return $this->humidity;
    }

    public function getOffset(): float
    {
        return $this->offset;
    }

    public function getSurfaceRule(): ?SurfaceRule
    {
        return $this->surfaceRule;
    }

    public static function builder(): self
    {
        return new self([], 0.0, 0.0, 0.0, null);
    }

    public function addVegetation(Populator $populator): self
    {
        $copy = clone $this;
        $copy->vegetationPopulators = [...$this->vegetationPopulators, $populator];

        return $copy;
    }

    public function withClimate(float $temperature, float $humidity, float $offset = 0.0): self
    {
        $copy = clone $this;
        $copy->temperature = $temperature;
        $copy->humidity = $humidity;
        $copy->offset = $offset;

        return $copy;
    }

    public function withSurfaceRule(?SurfaceRule $rule): self
    {
        $copy = clone $this;
        $copy->surfaceRule = $rule;

        return $copy;
    }

    public function build(): self
    {
        return $this;
    }
}
