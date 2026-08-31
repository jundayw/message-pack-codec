<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\Type;
use Jundayw\MessagePackCodec\Support\Format;

class UInt32Type extends Type
{
    /**
     * @inheritdoc
     *
     * @return Format
     */
    protected function default(Format|null $format = null): Format
    {
        if (is_null($format)) {
            return Format::UINT32_BIG;
        }

        $host   = $format->isBigHost() ? Format::UINT32_BIG : Format::UINT32_LITTLE;

        return $format->isHostEndian() ? $host : $format;
    }
}
