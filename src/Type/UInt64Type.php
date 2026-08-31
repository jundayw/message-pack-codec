<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\Type;
use Jundayw\MessagePackCodec\Support\Format;

class UInt64Type extends Type
{
    /**
     * @inheritdoc
     *
     * @return Format
     */
    protected function default(Format|null $format = null): Format
    {
        if (is_null($format)) {
            return Format::UINT64_BIG;
        }

        $host = $format->isBigHost() ? Format::UINT64_BIG : Format::UINT64_LITTLE;

        return $format->isHostEndian() ? $host : $format;
    }
}
