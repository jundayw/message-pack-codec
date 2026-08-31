<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\Type;
use Jundayw\MessagePackCodec\Support\Format;

class BCDType extends Type
{
    /**
     * @inheritdoc
     *
     * @return Format
     */
    protected function default(Format|null $format = null): Format
    {
        if (is_null($format)) {
            return Format::HEX_HIGH_FIRST;
        }

        return $format;
    }

    /**
     * @inheritdoc
     *
     * @return float
     */
    protected function size(array $options = []): float
    {
        return 0.5;
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    protected function encodeValues(mixed $value, array $options = []): array
    {
        $size   = $this->size($options);
        $length = $this->length($options, $size);

        return array_map(
            callback: fn($value) => strlen($value) === $length ? $value : str_pad($value, $length / $size, '0', STR_PAD_LEFT),
            array: is_array($value) ? $value : [$value]
        );
    }
}
