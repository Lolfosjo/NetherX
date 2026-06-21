<?php

declare(strict_types=1);

namespace lolfosjo\netherx\nether\biome;

final class BiomeRegistry
{
    /** @var array<int, BiomeDefinition> */
    private array $profiles = [];

    private readonly BiomeDefinition $defaultProfile;

    public function __construct()
    {
        $this->defaultProfile = BiomeDefinition::builder()->build();
    }

    public function register(int $biomeId, BiomeDefinition $profile): void
    {
        $this->profiles[$biomeId] = $profile;
    }

    public function get(int $biomeId): BiomeDefinition
    {
        return $this->profiles[$biomeId] ?? $this->defaultProfile;
    }

    public function has(int $biomeId): bool
    {
        return isset($this->profiles[$biomeId]);
    }

    public function getRegisteredBiomeIds(): array
    {
        return array_keys($this->profiles);
    }

    /** @return array<int, BiomeDefinition> biome ID => definition */
    public function all(): array
    {
        return $this->profiles;
    }
}
