<?php

namespace Jundayw\MessagePackCodec\Contract;

use Jundayw\MessagePackCodec\Support\Format;

interface Type
{
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
    public function encode(array $data, array $options = []): string;

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
    public function decode(string $buffer, int $offset, array $options = []): mixed;

    /**
     * Get the offset immediately after the last decoded value.
     *
     * @return int The next read offset.
     */
    public function next(): int;

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
    public function length(array $options = []): int|null;

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
    public function count(array $options = []): int|string|null;

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
    public function value(array $options = []): mixed;

    public function offset(array $options = []): int|null;

}
