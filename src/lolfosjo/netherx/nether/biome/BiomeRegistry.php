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
        // Empty profile – pure netherrack fill, no vegetation
        $this->defaultProfile = BiomeDefinition::builder()->build();
    }

    /**
     * Register a profile for the given biome ID.
     * Calling this twice for the same ID overwrites the previous registration.
     */
    public function register(int $biomeId, BiomeDefinition $profile): void
    {
        $this->profiles[$biomeId] = $profile;
    }

    /**
     * Retrieve the profile for a biome, falling back to the default (plain
     * netherrack, no vegetation) if nothing is registered.
     */
    public function get(int $biomeId): BiomeDefinition
    {
        return $this->profiles[$biomeId] ?? $this->defaultProfile;
    }
}
