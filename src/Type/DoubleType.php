<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\Type;
use Jundayw\MessagePackCodec\Support\Format;

class DoubleType extends Type
{
    /**
     * @inheritdoc
     *
     * @return Format
     */
    protected function default(Format|null $format = null): Format
    {
        if (is_null($format)) {
            return Format::DOUBLE_BIG;
        }

        $host = $format->isBigHost() ? Format::DOUBLE_BIG : Format::DOUBLE_LITTLE;

        return $format->isHostEndian() ? $host : $format;
    }
}
