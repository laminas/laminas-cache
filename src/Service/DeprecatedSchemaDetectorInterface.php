<?php

namespace Laminas\Cache\Service;

/**
 * @internal
 */
interface DeprecatedSchemaDetectorInterface
{
    /**
     * @param array<string,mixed> $configuration
     */
    public function isDeprecatedStorageFactorySchema(array $configuration): bool;
}
