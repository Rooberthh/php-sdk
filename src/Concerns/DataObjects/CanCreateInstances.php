<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Concerns\DataObjects;

use CuyZ\Valinor\Mapper\Configurator\MapKeysToCamelCase;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;

/**
 * Gives a Data Object the make() that builds it from an API response.
 *
 * It exists for SDK Authors to compose into their own Data Objects, so nothing
 * inside src/ uses it.
 *
 * @phpstan-ignore trait.unused
 */
trait CanCreateInstances
{
    /**
     * Build this object from a decoded API response.
     *
     * Every declared property must be present and of the right type, or this
     * throws. Properties the object does not declare are ignored, so an API
     * adding a field is not a breaking change for anyone using the SDK.
     *
     * @param  array<mixed, mixed> $data
     *
     * @throws MappingError
     */
    public static function make(array $data): static
    {
        // Held between calls: working out how to map a class means reading its
        // constructor through reflection, which is worth doing once rather
        // than once per object.
        static $mapper = null;

        $mapper = new MapperBuilder()
            ->configureWith(new MapKeysToCamelCase())
            ->allowSuperfluousKeys()
            ->mapper();

        return $mapper->map(static::class, $data);
    }
}
