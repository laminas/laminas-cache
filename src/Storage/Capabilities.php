<?php

namespace Laminas\Cache\Storage;

/**
 * @psalm-type DataTypeConversionType = 'null'|'boolean'|'integer'|'double'|'string'|'array'|'object'|'resource'
 * @psalm-type SupportedDataTypesArrayShape = array{
 *  'NULL'?: bool|DataTypeConversionType,
 *  'boolean'?: bool|DataTypeConversionType,
 *  'integer'?: bool|DataTypeConversionType,
 *  'double'?: bool|DataTypeConversionType,
 *  'string'?: bool|DataTypeConversionType,
 *  'array'?: bool|DataTypeConversionType,
 *  'object'?: bool|DataTypeConversionType,
 *  'resource'?: bool|DataTypeConversionType,
 * }
 */
final readonly class Capabilities
{
    public const int UNKNOWN_KEY_LENGTH    = -1;
    public const int UNLIMITED_KEY_LENGTH  = 0;
    private const array DEFAULT_DATA_TYPES = [
        'NULL'     => false,
        'boolean'  => false,
        'integer'  => false,
        'double'   => false,
        'string'   => true,
        'array'    => false,
        'object'   => false,
        'resource' => false,
    ];

    /**
     * @param int<-1,max> $maxKeyLength
     * @param SupportedDataTypesArrayShape $supportedDataTypes
     */
    public function __construct(
        /**
         * Maximum supported key length for the cache backend
         */
        public int $maxKeyLength = self::UNKNOWN_KEY_LENGTH,
        /**
         * Whether the cache backend supports TTL
         */
        public bool $ttlSupported = false,
        public bool $namespaceIsPrefix = true,
        /**
         * Contains the supported data types.
         * Depending on the cache backend in use, the type remains as is, is converted to a different type or is not
         * supported at all.
         */
        public array $supportedDataTypes = self::DEFAULT_DATA_TYPES,
        public int|float $ttlPrecision = 1,
        public bool $usesRequestTime = false,
    ) {
    }
}
