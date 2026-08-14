<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\IntegerType;
use Jundayw\MessagePackCodec\Support\Format;

class IntType extends IntegerType
{
    /**
     * @param array  $data   Context data
     * @param Format $format The format character used by PHP's pack/unpack functions.
     */
    public function __construct(
        array $data = [],
        Format $format = Format::INT_NATIVE,
    ) {
        parent::__construct($data, $format);
    }

}
