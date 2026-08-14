<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\IntegerType;
use Jundayw\MessagePackCodec\Support\Format;

class UIntType extends IntegerType
{
    /**
     * @param array  $data   Context data
     * @param Format $format The format character used by PHP's pack/unpack functions.
     */
    public function __construct(
        array $data = [],
        Format $format = Format::UINT_NATIVE,
    ) {
        parent::__construct($data, $format);
    }

}
