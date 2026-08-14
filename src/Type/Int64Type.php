<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\IntegerType;
use Jundayw\MessagePackCodec\Support\Format;

class Int64Type extends IntegerType
{
    /**
     * @param array  $data   Context data
     * @param Format $format The format character used by PHP's pack/unpack functions.
     */
    public function __construct(
        array $data = [],
        Format $format = Format::INT64_HOST,
    ) {
        parent::__construct($data, $format);
    }

}
