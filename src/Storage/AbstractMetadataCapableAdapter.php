<?php

// @phpcs:disable SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable


declare(strict_types=1);

namespace Laminas\Cache\Storage;

use ArrayObject;
use Exception;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Webmozart\Assert\Assert;

use function is_array;
use function is_object;

/**
 * @template TOptions of AdapterOptions
 * @template TMetadata of object
 * @template-extends AbstractAdapter<TOptions>
 * @template-implements MetadataCapableInterface<TMetadata>
 */
abstract class AbstractMetadataCapableAdapter extends AbstractAdapter implements MetadataCapableInterface
{
    public function getMetadata(string $key): ?object
    {
        if (! $this->getOptions()->getReadable()) {
            return null;
        }

        $this->assertValidKey($key);
        $args = new ArrayObject([
            'key' => $key,
        ]);

        try {
            $eventRs = $this->triggerPre(__FUNCTION__, $args);
            $key     = $args['key'];
            $this->assertValidKey($key);
            $result = $eventRs->stopped()
                ? $eventRs->last()
                : $this->internalGetMetadata($key);

            $result = $this->triggerPost(__FUNCTION__, $args, $result);
            if ($result !== null && ! is_object($result)) {
                return null;
            }

            /**
             * NOTE: We do trust the event handling here and assume that it will return an instance of Metadata
             *       and thus does not modify the type.
             *
             * @var TMetadata|null $result
             */
            return $result;
        } catch (Exception $exception) {
            $result = null;
            $result = $this->triggerThrowable(__FUNCTION__, $args, $result, $exception);
            Assert::nullOrObject($result);

            /**
             * NOTE: We do trust the event handling here and assume that it will return an instance of Metadata
             *       and thus does not modify the type.
             *
             * @var TMetadata|null $result
             */
            return $result;
        }
    }

    /**
     * Internal method to get metadata of an item.
     *
     * @return TMetadata|null Metadata on success, null on failure or in case metadata is not accessible.
     * @throws ExceptionInterface
     */
    abstract protected function internalGetMetadata(string $normalizedKey): ?object;

    public function getMetadatas(array $keys): array
    {
        if (! $this->getOptions()->getReadable()) {
            return [];
        }

        $keys = $this->normalizeKeys($keys);
        $args = new ArrayObject([
            'keys' => $keys,
        ]);

        try {
            $eventRs = $this->triggerPre(__FUNCTION__, $args);
            $keys    = $this->normalizeKeys($args['keys']);

            $result = $eventRs->stopped()
            ? $eventRs->last()
            : $this->internalGetMetadatas($keys);

            if (! is_array($result)) {
                return [];
            }

            $result = $this->triggerPost(__FUNCTION__, $args, $result);
            Assert::isMap($result);
            Assert::allObject($result);

            /**
             * NOTE: We do trust the event handling here and assume that it will return a map of instances of Metadata
             *      and thus does not modify the type.
             *
             * @var array<string,TMetadata> $result
             */
            return $result;
        } catch (Exception $exception) {
            $result = [];
            $result = $this->triggerThrowable(__FUNCTION__, $args, $result, $exception);
            Assert::isArray($result);
            Assert::allObject($result);

            /**
             * NOTE: We do trust the event handling here and assume that it will return a map of instances of Metadata
             *      and thus does not modify the type.
             *
             * @var array<string,TMetadata> $result
             */
            return $result;
        }
    }

    /**
     * Internal method to get multiple metadata
     *
     * @param array<string> $normalizedKeys
     * @return array<string,TMetadata> Associative array of keys and metadata
     * @throws ExceptionInterface
     */
    protected function internalGetMetadatas(array $normalizedKeys): array
    {
        $result = [];
        foreach ($normalizedKeys as $normalizedKey) {
            $metadata = $this->internalGetMetadata($normalizedKey);
            if ($metadata === null) {
                continue;
            }

            $result[$normalizedKey] = $metadata;
        }

        return $result;
    }
}
