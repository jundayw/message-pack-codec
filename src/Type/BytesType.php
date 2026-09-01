<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\Type;
use Jundayw\MessagePackCodec\Support\Format;

class BytesType extends Type
{
    /**
     * @inheritdoc
     *
     * @return Format
     */
    protected function default(Format|null $format = null): Format
    {
        return Format::STRING_NUL;
    }

    public function encode(mixed $value, array $options = []): string
    {
        return $value;
    }

    public function decode(string $binary, int $offset, array $options = []): string
    {
        $offset    = $this->offset($options, $offset);

        return $this->cursor(strlen($binary), $offset, $options)->decodeValues([
            'value' => substr(
                $binary,
                $offset,
                $this->next() - $offset
            ),
        ], $options);
    }
}
