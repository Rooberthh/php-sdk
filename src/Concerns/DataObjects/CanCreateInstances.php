<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Concerns\DataObjects;

use CuyZ\Valinor\Mapper\Configurator\ConvertKeysToCamelCase;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;
use Rooberthh\Sdk\Exceptions\UnableToHydrateResponse;

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
     * @throws UnableToHydrateResponse
     */
    public static function make(array $data): static
    {
        // Held between calls: working out how to map a class means reading its
        // constructor through reflection, which is worth doing once rather
        // than once per object.
        static $mapper = null;

        $mapper ??= (new MapperBuilder())
            // Responses arrive as snake_case; objects declare camelCase.
            ->configureWith(new ConvertKeysToCamelCase())
            // APIs add fields without warning; unknown keys must not break consumers.
            ->allowSuperfluousKeys()
            ->mapper();

        try {
            return $mapper->map(static::class, $data);
        } catch (MappingError $error) {
            throw UnableToHydrateResponse::for(static::class, $error);
        }
    }
}
