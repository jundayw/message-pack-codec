<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\IntegerType;
use Jundayw\MessagePackCodec\Support\Format;

class UInt8Type extends IntegerType
{
    /**
     * @param array  $data   Context data
     * @param Format $format The format character used by PHP's pack/unpack functions.
     */
    public function __construct(
        array $data = [],
        Format $format = Format::UINT8,
    ) {
        parent::__construct($data, $format);
    }

}
