<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\Type;
use Jundayw\MessagePackCodec\Support\Format;

class StringType extends Type
{
    /**
     * @inheritdoc
     *
     * @return Format
     */
    protected function default(Format|null $format = null): Format
    {
        if (is_null($format)) {
            return Format::STRING_NUL;
        }

        return $format;
    }

    /**
     * @inheritdoc
     *
     * @return array
     */
    protected function encodeValues(mixed $value, array $options = []): array
    {
        $encoding = $this->option('encoding', $options, 'UTF-8');

        return array_map(
            callback: fn($value) => mb_convert_encoding($value, $encoding, 'UTF-8'),
            array: is_array($value) ? $value : [$value]
        );
    }

    /**
     * @inheritdoc
     *
     * @return mixed
     */
    protected function decodeValues(array $elements, array $options = []): mixed
    {
        $encoding = $this->option('encoding', $options, 'UTF-8');

        if (array_key_exists('value', $elements)) {
            return mb_convert_encoding($elements['value'], 'UTF-8', $encoding);
        }

        return array_map(
            callback: fn($value) => mb_convert_encoding($value, 'UTF-8', $encoding),
            array: array_values($elements)
        );
    }
}
