<?php

namespace Jundayw\MessagePackCodec\Type;

use Jundayw\MessagePackCodec\Concerns\AbstractType;
use Jundayw\MessagePackCodec\Support\Format;

class DoubleType extends AbstractType
{
    /**
     * @param array  $data   Context data
     * @param Format $format The format character used by PHP's pack/unpack functions.
     */
    public function __construct(
        array $data = [],
        Format $format = Format::DOUBLE_BIG,
    ) {
        parent::__construct($data, $format);
    }

}
