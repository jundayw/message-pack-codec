<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\AbstractType;
use Jundayw\MessagePackCodec\Support\Format;

class BitType extends AbstractType
{
    /**
     * @param array  $data   Context data
     * @param Format $format The format character used by PHP's pack/unpack functions.
     */
    public function __construct(
        array $data = [],
        Format $format = Format::UINT16_BIG,
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
        $format = $this->format($options['format'] ?? $this->format)->value;
        $count  = $this->count($options);
        $value  = $this->value($options) ?? $data[$options['name']] ?? [];

        $values = 0;
        foreach ($options['fields'] ?? [] as $key => [$offset, $bits]) {
            $values |= ($value[$key] & (1 << $bits) - 1) << $offset;
        }

        if (is_null($count)) {
            return pack($format, $values);
        }

        return is_array($values) ? pack("{$format}{$count}", ...$values) : pack("{$format}{$count}", $values);
    }

    /**
     * @inheritdoc
     *
     * @return array The decoded value, or null if decoding fails.
     */
    public function decode(string $buffer, int $offset, array $options = []): array
    {
        $value = parent::decode($buffer, $offset, $options);

        return array_map(
            fn(array $field) => ($value >> $field[0]) & ((1 << $field[1]) - 1),
            $options['fields'] ?? []
        );
    }

}
