<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\AbstractType;
use Jundayw\MessagePackCodec\Support\Format;

class HexType extends AbstractType
{
    /**
     * @param array  $data   Context data
     * @param Format $format The format character used by PHP's pack/unpack functions.
     */
    public function __construct(
        array $data = [],
        Format $format = Format::HEX_HIGH_FIRST,
    ) {
        parent::__construct($data, $format);
    }

}
