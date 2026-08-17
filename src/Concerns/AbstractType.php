<?php

namespace Jundayw\MessagePackCodec\Concerns;

use Jundayw\MessagePackCodec\Contract\Type;
use Jundayw\MessagePackCodec\Support\Format;

abstract class AbstractType implements Type
{
    /**
     * The offset immediately after the last decoded value.
     *
     * @var int
     */
    protected int $current = 0;

    /**
     * @param array  $data   Context data
     * @param Format $format The format character used by PHP's pack/unpack functions.
     */
    public function __construct(
        protected array $data,
        protected Format $format,
    ) {
        //
    }

    /**
     * Encode a value into its binary representation.
     *
     * @param array $data
     * @param array{
     *      name?:string,
     *      type?:string,
     *      format?:Format,
     *      length?:int|callable,
     *      count?:int|string|callable
     *  }           $options Encoding or decoding options.
     *
     * @return string The encoded binary data.
     */
    public function encode(array $data, array $options = []): string
    {
        $format = $this->format($options['format'] ?? $this->format)->value;
        $count  = $this->count($options);
        $value  = $this->value($options) ?? $data[$options['name']] ?? null;
        $value  = $this->encoding($value, $options);

        if (is_null($count)) {
            return pack($format, $value);
        }

        return is_array($value) ? pack("{$format}{$count}", ...$value) : pack("{$format}{$count}", $value);
    }

    /**
     * Resolve the binary format used for encoding and decoding.
     *
     * The format provided in the options takes precedence over the
     * default format configured for the current type.
     *
     * @param Format $format
     *
     * @return Format
     */
    protected function format(Format $format): Format
    {
        return $format;
    }

    /**
     * Decode a value from the binary buffer.
     *
     * The current offset is used as the starting position.
     * The internal cursor is updated to the offset immediately
     * after the decoded value.
     *
     * @param string $buffer  The binary buffer.
     * @param int    $offset  The current read offset, passed by reference.
     * @param array{
     *     name?:string,
     *     type?:string,
     *     format?:Format,
     *     length?:int|callable,
     *     count?:int|string|callable
     * }             $options Encoding or decoding options.
     *
     * @return mixed The decoded value, or null if decoding fails.
     */
    public function decode(string $buffer, int $offset, array $options = []): mixed
    {
        $format = $this->format($options['format'] ?? $this->format)->value;
        $count  = $this->count($options);
        $length = $this->length($options);

        $length = match (true) {
            is_null($length) => match (true) {
                is_int($count) => $count,
                default => strlen(substr($buffer, $offset)),
            },
            default => match (true) {
                is_int($count) => $count * $length,
                default => $length,
            },
        };

        $this->current = $offset + $length;

        $value = match (true) {
            is_int($count) => unpack("{$format}{$count}", substr($buffer, $offset, $length)),
            is_string($count) => unpack("{$format}*value", substr($buffer, $offset, $length))['value'],
            default => unpack("{$format}value", substr($buffer, $offset, $length))['value'],
        };

        return $this->decoding($value, $options);
    }

    /**
     * Converts string values to the target character encoding when the format
     * represents a string type.
     *
     * Non-string formats are returned unchanged. Array values are processed
     * recursively, allowing nested arrays to be encoded consistently.
     *
     * @param mixed $value   The value to be encoded.
     * @param array $options Encoding and format options.
     *
     * @return mixed The encoded value.
     */
    protected function encoding(mixed $value, array $options = []): mixed
    {
        $format = $this->format($options['format'] ?? $this->format);

        if (false === $format->isString()) {
            return $value;
        }

        if (is_array($value)) {
            $value = array_map(function ($v) use ($format, $options) {
                if (is_array($v)) {
                    return $this->encoding($v, $options);
                }
                return mb_convert_encoding($v, $options['encoding'] ?? 'UTF-8', 'UTF-8');
            }, $value);
        }

        return $value;
    }

    /**
     * Converts string values to the target character decoding when the format
     * represents a string type.
     *
     * Non-string formats are returned unchanged. Array values are processed
     * recursively, allowing nested arrays to be decoded consistently.
     *
     * @param mixed $value   The value to be encoded.
     * @param array $options Decoding and format options.
     *
     * @return mixed The decoded value.
     */
    protected function decoding(mixed $value, array $options = []): mixed
    {
        $format = $this->format($options['format'] ?? $this->format);

        if (false === $format->isString()) {
            return $value;
        }

        if (is_array($value)) {
            $value = array_map(function ($v) use ($format, $options) {
                if (is_array($v)) {
                    return $this->encoding($v, $options);
                }
                return mb_convert_encoding($v, 'UTF-8', $options['encoding'] ?? 'UTF-8');
            }, $value);
        }

        return $value;
    }

    /**
     * Get the offset immediately after the last decoded value.
     *
     * @return int The next read offset.
     */
    public function next(): int
    {
        return $this->current;
    }

    /**
     * Resolves the number of bytes occupied by encoded value.
     *
     * An explicitly supplied `length` option takes precedence over
     * the format's default size. The length may be provided as a
     * callable, in which case the current data context is passed
     * to the callable.
     *
     * @param array{
     *     format?:Format,
     *     length?:int|callable,
     * } $options Encoding or decoding options.
     *
     * @return int|null Number of bytes occupied by the value, or null
     *                 when no explicit length can be resolved.
     */
    public function length(array $options = []): int|null
    {
        if (array_key_exists('length', $options)) {
            return is_callable($length = $options['length']) ? call_user_func($length, $this->data) : $length;
        }

        return $this->format($options['format'] ?? $this->format)->size();
    }

    /**
     * Resolves the number of values represented by the current field.
     *
     * The `count` option may be a fixed integer, a string expression,
     * or a callable resolved against the codec's data context.
     *
     * If no count is configured, null is returned, indicating that
     * the field represents a single value.
     *
     * @param array{
     *     count?:int|string|callable
     * } $options Encoding or decoding options.
     *
     * @return int|string|null Resolved value count, or null when the
     *                         field represents a single value.
     */
    public function count(array $options = []): int|string|null
    {
        if (array_key_exists('count', $options)) {
            return is_callable($count = $options['count']) ? call_user_func($count, $this->data) : $count;
        }

        return null;
    }

    /**
     * Resolves the value that should be encoded.
     *
     * The `value` option takes precedence over the value stored in
     * the input data. A callable value is evaluated using the codec's
     * data context.
     *
     * Returning null indicates that no explicit value was provided,
     * allowing the caller to fall back to the field value.
     *
     * @param array{
     *     value?:mixed|callable
     * } $options Encoding or decoding options.
     *
     * @return mixed The resolved value, or null when no value option
     *               is configured.
     */
    public function value(array $options = []): mixed
    {
        if (array_key_exists('value', $options)) {
            return is_callable($value = $options['value']) ? call_user_func($value, $this->data) : $value;
        }

        return null;
    }

}
