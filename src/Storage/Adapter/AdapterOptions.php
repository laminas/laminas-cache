<?php

namespace Laminas\Cache\Storage\Adapter;

use ArrayObject;
use Laminas\Cache\Exception;
use Laminas\Cache\Storage\Event;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\EventManager\EventsCapableInterface;
use Laminas\Stdlib\AbstractOptions;
use Laminas\Stdlib\ErrorHandler;
use Traversable;
use Webmozart\Assert\Assert;

use function array_change_key_case;
use function array_reverse;
use function array_shift;
use function is_array;
use function is_int;
use function iterator_to_array;
use function preg_match;
use function preg_replace_callback;
use function sprintf;
use function str_replace;
use function strtolower;

use const CASE_LOWER;
use const E_WARNING;

/**
 * Unless otherwise marked, all options in this class affect all adapters.
 *
 * @template-extends AbstractOptions<mixed>
 */
class AdapterOptions extends AbstractOptions
{
    // @codingStandardsIgnoreStart
    /**
     * Prioritized properties ordered by prio to be set first
     * in case a bulk of options sets set at once
     *
     * @var string[]
     */
    protected array $__prioritizedProperties__ = [];
    // @codingStandardsIgnoreEnd

    /**
     * The adapter using these options
     */
    protected ?StorageInterface $adapter = null;

    /**
     * Validate key against pattern
     */
    protected string $keyPattern = '';

    /**
     * Namespace option
     */
    protected string $namespace = 'laminascache';

    /**
     * Readable option
     */
    protected bool $readable = true;

    /**
     * TTL option
     *
     * @var int|float 0 means infinite or maximum of adapter
     */
    protected int|float $ttl = 0;

    /**
     * Writable option
     */
    protected bool $writable = true;

    /**
     * Adapter using this instance
     */
    public function setAdapter(?StorageInterface $adapter = null): self
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Set key pattern
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setKeyPattern(string $keyPattern): self
    {
        if ($this->keyPattern !== $keyPattern) {
            // validate pattern
            if ($keyPattern !== '') {
                ErrorHandler::start(E_WARNING);
                $result = preg_match($keyPattern, '');
                $error  = ErrorHandler::stop();
                if ($result === false) {
                    throw new Exception\InvalidArgumentException(sprintf(
                        'Invalid pattern "%s"%s',
                        $keyPattern,
                        $error ? ': ' . $error->getMessage() : ''
                    ), 0, $error);
                }
            }

            $this->triggerOptionEvent('key_pattern', $keyPattern);
            $this->keyPattern = $keyPattern;
        }

        return $this;
    }

    /**
     * Get key pattern
     */
    public function getKeyPattern(): string
    {
        return $this->keyPattern;
    }

    /**
     * Set namespace.
     */
    public function setNamespace(string $namespace): self
    {
        if ($this->namespace !== $namespace) {
            $this->triggerOptionEvent('namespace', $namespace);
            $this->namespace = $namespace;
        }

        return $this;
    }

    /**
     * Get namespace
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Enable/Disable reading data from cache.
     */
    public function setReadable(bool $readable): self
    {
        if ($this->readable !== $readable) {
            $this->triggerOptionEvent('readable', $readable);
            $this->readable = $readable;
        }
        return $this;
    }

    /**
     * If reading data from cache enabled.
     */
    public function getReadable(): bool
    {
        return $this->readable;
    }

    /**
     * Set time to live.
     *
     * @param numeric $ttl
     */
    public function setTtl(int|float|string $ttl): self
    {
        $ttl = $this->normalizeTtl($ttl);
        if ($this->ttl !== $ttl) {
            $this->triggerOptionEvent('ttl', $ttl);
            $this->ttl = $ttl;
        }
        return $this;
    }

    /**
     * Get time to live.
     */
    public function getTtl(): float|int
    {
        return $this->ttl;
    }

    /**
     * Enable/Disable writing data to cache.
     *
     * @return $this
     */
    public function setWritable(bool $writable): self
    {
        if ($this->writable !== $writable) {
            $this->triggerOptionEvent('writable', $writable);
            $this->writable = $writable;
        }
        return $this;
    }

    /**
     * If writing data to cache enabled.
     */
    public function getWritable(): bool
    {
        return $this->writable;
    }

    /**
     * Triggers an option event if this options instance has a connection to
     * an adapter implements EventsCapableInterface.
     */
    protected function triggerOptionEvent(string $optionName, mixed $optionValue): void
    {
        if ($this->adapter instanceof EventsCapableInterface) {
            $event = new Event('option', $this->adapter, new ArrayObject([$optionName => $optionValue]));
            $this->adapter->getEventManager()->triggerEvent($event);
        }
    }

    /**
     * Validates and normalize a TTL.
     *
     * @param numeric $ttl
     * @return non-negative-int|float $ttl
     * @throws Exception\InvalidArgumentException
     */
    protected function normalizeTtl(int|string|float $ttl): int|float
    {
        if (! is_int($ttl)) {
            $ttl = (float) $ttl;

            // convert to int if possible
            if ($ttl === (float) (int) $ttl) {
                $ttl = (int) $ttl;
            }
        }

        if ($ttl < 0) {
            throw new Exception\InvalidArgumentException("TTL can't be negative");
        }

        return $ttl;
    }

    /**
     * Cast to array
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $array     = [];
        $transform = static function ($letters): string {
            $letter = array_shift($letters);
            return '_' . strtolower($letter);
        };
        foreach ($this as $key => $value) {
            if ($key === '__strictMode__' || $key === '__prioritizedProperties__') {
                continue;
            }
            $normalizedKey         = preg_replace_callback('/([A-Z])/', $transform, $key);
            $array[$normalizedKey] = $value;
        }
        Assert::isMap($array);
        return $array;
    }

    /**
     * {@inheritdoc}
     *
     * NOTE: This method was overwritten just to support prioritized properties
     *       {@link https://github.com/zendframework/zf2/issues/6381}
     *
     * @param  iterable<string,mixed>|AbstractOptions $options
     * @throws Exception\InvalidArgumentException
     */
    public function setFromArray($options): self
    {
        if ($this->__prioritizedProperties__) {
            if ($options instanceof AbstractOptions) {
                $options = $options->toArray();
            }

            if ($options instanceof Traversable) {
                $options = iterator_to_array($options);
            } elseif (! is_array($options)) {
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        'Parameter provided to %s must be an %s, %s or %s',
                        __METHOD__,
                        'array',
                        'Traversable',
                        AbstractOptions::class
                    )
                );
            }

            // Sort prioritized options to top
            $options = array_change_key_case($options, CASE_LOWER);
            foreach (array_reverse($this->__prioritizedProperties__) as $key) {
                if (isset($options[$key])) {
                    $options = [$key => $options[$key]] + $options;
                } elseif (isset($options[$key = str_replace('_', '', $key)])) {
                    $options = [$key => $options[$key]] + $options;
                }
            }
        }

        parent::setFromArray($options);
        return $this;
    }
}
