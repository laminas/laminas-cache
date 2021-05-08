<?php

/**
 * @see       https://github.com/laminas/laminas-cache for the canonical source repository
 */

declare(strict_types=1);

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
