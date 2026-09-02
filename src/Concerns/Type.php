<?php

namespace Jundayw\MessagePackCodec\Concerns;

use Jundayw\MessagePackCodec\Contract\Context;
use Jundayw\MessagePackCodec\Contract\Type as TypeContract;
use Jundayw\MessagePackCodec\Support\Format;

/**
 * Base class for binary data types.
 *
 * Provides common functionality for encoding, decoding, format handling,
 * option resolution, element counting, length calculation, and cursor
 * management.
 *
 * The concrete type is responsible for providing its default {@see Format}
 * through the {@see default()} method.
 */
abstract class Type implements TypeContract
{
    /**
     * The binary format used by the type.
     *
     * This format is used to generate the PHP pack()/unpack() format
     * character.
     */
    protected Format $format;

    /**
     * The current cursor position after decoding.
     *
     * The cursor represents the offset immediately after the last
     * decoded value.
     */
    protected int $cursor = 0;

    /**
     * Create a new type instance.
     *
     * @param Format|null $format The binary format to use. When null,
     *                            the concrete type's default format is used.
     */
    public function __construct(
        Format|null $format = null,
    ) {
        $this->format = $this->default($format);
    }

    /**
     * Resolve option values.
     *
     * Callable options are evaluated with the given context, while non-callable
     * options are returned as-is.
     *
     * The `encode` and `decode` options are preserved without being evaluated,
     * allowing them to be invoked explicitly through {@see callable()} when
     * required.
     *
     * @param Context $context Runtime context passed to callable options.
     * @param array   $option  Options to resolve.
     *
     * @return array The resolved options with callable values evaluated,
     *               except for the preserved `encode` and `decode` options.
     */
    public function options(Context $context, array $option = []): array
    {
        $preserved = array_flip([
            'encode',
            'decode',
        ]);

        return array_map(function ($value) use ($context) {
                return is_callable($value) ? call_user_func($value, $context) : $value;
            }, array_diff_key($option, $preserved)) + array_intersect_key($option, $preserved);
    }

    /**
     * Resolve and invoke a callable.
     *
     * When the given value is callable, it is invoked with the value and
     * runtime context as arguments. Otherwise, the original value is returned
     * unchanged.
     *
     * This method is typically used to explicitly invoke preserved callable
     * options such as `encode` and `decode`.
     *
     * @param mixed   $callable The callable to invoke.
     * @param mixed   $value    The value passed to the callable.
     * @param Context $context  Runtime context passed to the callable.
     *
     * @return mixed The value returned by the callable, or the original value
     *               when the given value is not callable.
     */
    public function callable(mixed $callable, mixed $value, Context $context): mixed
    {
        return is_callable($callable)
            ? call_user_func($callable, $value, $context)
            : $value;
    }

    /**
     * Encode a value into binary data.
     *
     * The concrete type determines the PHP pack() format through
     * {@see character()}, while {@see encodeValues()} normalizes the
     * input value into arguments accepted by pack().
     *
     * @param mixed $value   The value to encode.
     * @param array $options Encoding options.
     *
     * @return string The encoded binary data.
     */
    public function encode(mixed $value, array $options = []): string
    {
        // var_dump(
        //     unpack('n*value', pack('n2', 1, 2)),
        //     unpack('n3value', pack('n*', 1, 2, 3, 4)),
        //     unpack('a*value', pack('a3', 'string')),
        //     unpack('a*value', pack('a*', 'string')),
        //     unpack('a6value', pack('a*', 'string')),
        // );
        return pack($this->character($options), ...$this->encodeValues($value, $options));
    }

    /**
     * Get the number of elements to encode or decode.
     *
     * The count can be:
     *
     * - null: derive the count from length and element size;
     * - '*': consume all remaining binary data;
     * - int: explicitly specify the number of elements.
     *
     * @param array $options Encoding or decoding options.
     *
     * @return int|string|null The configured element count.
     */
    protected function count(array $options = []): int|string|null
    {
        return $this->option('count', $options);
    }

    /**
     * Normalize a value into arguments for pack().
     *
     * Scalar values are wrapped in an array, while arrays are passed
     * through unchanged.
     *
     * Concrete types may override this method when special value
     * normalization is required.
     *
     * @param mixed $value   The value to normalize.
     * @param array $options Encoding options.
     *
     * @return array Values passed to pack().
     */
    protected function encodeValues(mixed $value, array $options = []): array
    {
        return is_array($value) ? $value : [$value];
    }

    /**
     * Decode binary data.
     *
     * The specified offset is used as the starting position. The generated
     * format character determines how much data is decoded. After decoding,
     * the internal cursor is updated to the position immediately following
     * the decoded data.
     *
     * @param string $binary  Binary data to decode.
     * @param int    $offset  Default decoding offset.
     * @param array  $options Decoding options.
     *
     * @return mixed The decoded value or values.
     */
    public function decode(string $binary, int $offset, array $options = []): mixed
    {
        // var_dump(
        //     [unpack('H*', $pack = pack('H12', str_pad('123456789', 12, '0', STR_PAD_LEFT))), $pack, bin2hex($pack)],
        //     [unpack('n*value', $pack = pack('n2', 1, 2)), $pack],
        //     [unpack('n3value', $pack = pack('n*', 1, 2, 3, 4)), $pack],
        //     [unpack('a*value', $pack = pack('a3', 'string')), $pack],
        //     [unpack('a*value', $pack = pack('a*', 'string')), $pack],
        //     [unpack('a6value', $pack = pack('a*', 'string')), $pack],
        // );
        $offset    = $this->offset($options, $offset);
        $character = $this->character($options);
        $elements  = unpack("{$character}value", $binary, $offset);

        return $this->cursor(strlen($binary), $offset, $options)->decodeValues($elements ?: [], $options);
    }

    /**
     * Resolve the starting offset for decoding.
     *
     * The `offset` option takes precedence over the supplied default offset.
     *
     * @param array $options Decoding options.
     * @param int   $default Default offset.
     *
     * @return int The effective decoding offset.
     */
    protected function offset(array $options = [], int $default = 0): int
    {
        return $this->option('offset', $options, $default);
    }

    /**
     * Get the size of a single encoded element.
     *
     * When the `size` option is not provided, the size defined by the
     * current {@see Format} is used. If the format does not define a size,
     * 1 byte is assumed.
     *
     * @param array $options Encoding or decoding options.
     *
     * @return float The size of a single element in bytes.
     */
    protected function size(array $options = []): float
    {
        return $this->option('size', $options, $this->format()->size() ?? 1);
    }

    /**
     * Get the byte length of the encoded or decoded value.
     *
     * When no explicit length is provided, the given default value is used.
     *
     * @param array     $options Encoding or decoding options.
     * @param float|int $default Default length.
     *
     * @return float|int The effective byte length.
     */
    protected function length(array $options = [], float|int $default = 0): float|int
    {
        return $this->option('length', $options, $default);
    }

    /**
     * Update the decoding cursor.
     *
     * The consumed length is determined according to the configured count:
     *
     * - `*`: consume all remaining bytes;
     * - `null`: use the configured length, defaulting to one element size;
     * - integer: consume `count × size` bytes.
     *
     * The resulting cursor is the offset immediately following the
     * decoded value.
     *
     * @param int   $length  Total binary length.
     * @param int   $offset  Starting offset.
     * @param array $options Decoding options.
     *
     * @return static The current type instance.
     */
    protected function cursor(int $length, int $offset, array $options = []): static
    {
        $count  = $this->count($options);
        $size   = $this->size($options);
        $length = match ($count) {
            '*' => $length - $offset,
            null => $this->length($options, $size),
            default => $count * $size,
        };

        $this->cursor = $offset + $length;

        return $this;
    }

    /**
     * Normalize decoded values.
     *
     * When unpack() returns a named `value` element, that value is returned
     * directly. Otherwise, the decoded elements are returned as a sequential
     * array.
     *
     * Concrete types may override this method to perform additional
     * conversion or normalization.
     *
     * @param array $elements Decoded values returned by unpack().
     * @param array $options  Decoding options.
     *
     * @return mixed The normalized decoded value.
     */
    protected function decodeValues(array $elements, array $options = []): mixed
    {
        return array_key_exists('value', $elements) ? $elements['value'] : array_values($elements);
    }

    /**
     * Get the current decoding cursor.
     *
     * The returned value is the offset immediately after the last
     * decoded value and can be used as the starting offset for the
     * next type.
     *
     * @return int The current cursor position.
     */
    public function next(): int
    {
        return $this->cursor;
    }

    /**
     * Get an option value.
     *
     * Returns the configured option when present; otherwise returns
     * the specified default value.
     *
     * @param string $name    Option name.
     * @param array  $options Options.
     * @param mixed  $default Default value.
     *
     * @return mixed The resolved option value.
     */
    public function option(string $name, array $options = [], mixed $default = null): mixed
    {
        return $options[$name] ?? $default;
    }

    /**
     * Get the current binary format.
     *
     * @return Format The format used by this type.
     */
    public function format(): Format
    {
        return $this->format;
    }

    /**
     * Build the PHP pack()/unpack() format character.
     *
     * The resulting format consists of the format character followed
     * by the calculated element count.
     *
     * For example:
     *
     * - `C1`
     * - `n2`
     * - `a6`
     * - `H*`
     *
     * When no explicit count is configured, the count is calculated
     * from length and element size.
     *
     * @param array $options Encoding or decoding options.
     *
     * @return string The PHP pack()/unpack() format string.
     */
    public function character(array $options = []): string
    {
        $count  = $this->count($options);
        $size   = $this->size($options);
        $length = $this->length($options, $size);
        $format = $this->format();

        if ($count === null) {
            $count = $length / $size;
        }

        return $format->value.$count;
    }

    /**
     * Get the default format for the type.
     *
     * Concrete types must implement this method to define their
     * default binary format.
     *
     * When a format is explicitly supplied to the constructor,
     * implementations should normally return that format unchanged.
     *
     * @param Format|null $format Explicitly configured format.
     *
     * @return Format The format to use.
     */
    abstract protected function default(Format|null $format = null): Format;

}
