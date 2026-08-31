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
            return $value instanceof EncodeMessage ? $value->toHex() : bin2hex($value);
        }, $this->getArrayCopy()));
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function fromHex(string ...$string): static
    {
        return new self(array_map(fn($value) => hex2bin($value), func_get_args()));
    }

    public static function build(string ...$binary_string): static
    {
        return new self(array_map(fn($value) => $value instanceof EncodeMessage ? $value->toString() : $value, func_get_args()));
    }
}
