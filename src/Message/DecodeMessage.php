<?php

namespace Jundayw\MessagePackCodec\Message;

class DecodeMessage extends Bag
{
    public function toArray(): array
    {
        return array_map(fn($data) => $data instanceof DecodeMessage ? $data->toArray() : $data, $this->all());
    }

}
