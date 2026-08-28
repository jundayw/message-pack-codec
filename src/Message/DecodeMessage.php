<?php

namespace Jundayw\MessagePackCodec\Message;

use ArrayObject;
use Jundayw\MessagePackCodec\Contract\Message;

class DecodeMessage extends ArrayObject implements Message
{
    public function __construct(array $array = [])
    {
        parent::__construct($array, 2);
    }

    public function toArray(): array
    {
        return array_map(fn($data) => $data instanceof DecodeMessage ? $data->toArray() : $data, $this->getArrayCopy());
    }

}
