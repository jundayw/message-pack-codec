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

    public function toArray(callable|null $callback = null): array
    {
        $callback = $callback ?? fn($msg) => $msg;

        return array_map($callback, $this->getArrayCopy());
    }

    public function toString(callable|null $callback = null): string
    {
        return implode('', $this->toArray($callback));
    }
}
