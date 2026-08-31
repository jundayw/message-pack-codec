<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\Type;
use Jundayw\MessagePackCodec\Support\Format;

class UInt16Type extends Type
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

        $host = $format->isBigHost() ? Format::UINT16_BIG : Format::UINT16_LITTLE;

        return $format->isHostEndian() ? $host : $format;
    }
}
