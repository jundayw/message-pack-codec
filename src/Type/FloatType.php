<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\Type;
use Jundayw\MessagePackCodec\Support\Format;

class FloatType extends Type
{
    /**
     * @inheritdoc
     *
     * @return Format
     */
    protected function default(Format|null $format = null): Format
    {
        if (is_null($format)) {
            return Format::FLOAT_BIG;
        }

        $host = $format->isBigHost() ? Format::FLOAT_BIG : Format::FLOAT_LITTLE;

        return $format->isHostEndian() ? $host : $format;
    }
}
