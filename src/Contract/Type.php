<?php

namespace Jundayw\MessagePackCodec\Contract;

use Jundayw\MessagePackCodec\Support\Format;

interface Type
{
    /**
     * Resolve option values.
     *
     * Callable options are evaluated with the given arguments, while
     * scalar or object options are returned as-is.
     *
     * This is useful when an option depends on runtime encoding or
     * decoding arguments.
     *
     * @param array $arguments Arguments passed to callable options.
     * @param array $option    Options to resolve.
     *
     * @return array The resolved option values.
     */
    public function options(array $arguments = [], array $option = []): array;

    /**
     * Resolve and invoke a callable option.
     *
     * When the specified option is callable, it is invoked with the given
     * value followed by any additional arguments. Otherwise, the original
     * value is returned unchanged.
     *
     * @param string $name         The option name containing the callable.
     * @param mixed  $value        The value passed to the callable.
     * @param array  $option       Options containing the callable.
     * @param mixed  ...$arguments Additional arguments passed to the callable.
     *
     * @return mixed The value returned by the callable, or the original value
     *               when the option is not callable.
     */
    public function callable(string $name, mixed $value, array $option = [], mixed ...$arguments): mixed;

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
    public function encode(mixed $value, array $options = []): string;

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
    public function decode(string $binary, int $offset, array $options = []): mixed;

    /**
     * Get the current decoding cursor.
     *
     * The returned value is the offset immediately after the last
     * decoded value and can be used as the starting offset for the
     * next type.
     *
     * @return int The current cursor position.
     */
    public function next(): int;

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
    public function option(string $name, array $options = [], mixed $default = null): mixed;

    /**
     * Get the current binary format.
     *
     * @return Format The format used by this type.
     */
    public function format(): Format;

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
    public function character(array $options = []): string;

}
