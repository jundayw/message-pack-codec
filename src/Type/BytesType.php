<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\AbstractType;
use Jundayw\MessagePackCodec\Support\Format;

class BytesType extends AbstractType
{
    /**
     * @param array  $data   Context data
     * @param Format $format The format character used by PHP's pack/unpack functions.
     */
    public function __construct(
        array $data = [],
        Format $format = Format::STRING_NUL,
    ) {
        parent::__construct($data, $format);
    }

    /**
     * @inheritdoc
     *
     * @return string The encoded binary data.
     */
    public function encode(array $data, array $options = []): string
    {
        return $this->value($options) ?? $data[$options['name']] ?? '';
    }

    /**
     * @inheritdoc
     *
     * @return string The decoded value, or null if decoding fails.
     */
    public function decode(string $buffer, int $offset, array $options = []): string
    {
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
        var_dump([
            strlen($buffer), $offset, $length,
        ]);
        return substr($buffer, $offset, $length);
    }

}
