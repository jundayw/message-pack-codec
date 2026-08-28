<?php

namespace Jundayw\MessagePackCodec\Message;

use ArrayObject;
use Jundayw\MessagePackCodec\Contract\Message;

class EncodeMessage extends ArrayObject implements Message
{
    public function __construct(array $array = [])
    {
        parent::__construct($array, 2);
    }

    public function toArray(): array
    {
        return array_map(function ($data) {
            return $data instanceof EncodeMessage ? $data->toArray() : $data;
        }, $this->getArrayCopy());
    }

    public function toString(): string
    {
        return implode('', array_map(function ($value) {
            return $value instanceof EncodeMessage ? $value->toString() : $value;
        }, $this->getArrayCopy()));
    }

    public function toHex(): string
    {
        return implode('', array_map(function ($value) {
            return $value instanceof EncodeMessage ? $value->toString() : bin2hex($value);
        }, $this->getArrayCopy()));
    }

    public function __toString(): string
    {
        return $this->toString();
    }

}
