<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\biome;

use lolfosjo\netherx\nether\populator\Populator;

final class BiomeDefinition
{
    /** @var Populator[] */
    private array $vegetationPopulators;

    private function __construct(array $vegetationPopulators)
    {
        $this->vegetationPopulators = $vegetationPopulators;
    }

    /** @return Populator[] */
    public function getVegetationPopulators(): array
    {
        return $this->vegetationPopulators;
    }

    public static function builder(): self
    {
        return new self([]);
    }

    public function addVegetation(Populator $populator): self
    {
        $copy = clone $this;
        $copy->vegetationPopulators = [...$this->vegetationPopulators, $populator];

        return $copy;
    }

    public function build(): self
    {
        return $this;
    }
}
