<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\Type;
use Jundayw\MessagePackCodec\Support\Format;

class BitType extends Type
{
    /**
     * @inheritdoc
     *
     * @return Format
     */
    protected function default(Format|null $format = null): Format
    {
        if (is_null($format)) {
            return Format::UINT16_BIG;
        }

        return $format;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function encode(mixed $value, array $options = []): string
    {
        $values = 0;

        foreach ($options['fields'] ?? [] as $key => [$offset, $bits]) {
            $values |= ($value[$key] & (1 << $bits) - 1) << $offset;
        }

        return parent::encode($values, $options);
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    public function decode(string $binary, int $offset, array $options = []): array
    {
        $value = parent::decode($binary, $offset, $options);

        return array_map(
            fn(array $field) => ($value >> $field[0]) & (1 << $field[1]) - 1,
            $options['fields'] ?? []
        );
    }
}
