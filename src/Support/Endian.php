<?php

namespace Jundayw\MessagePackCodec\Support;

enum Endian: string
{
    case HOST   = 'HOST';
    case BIG    = 'BIG';
    case LITTLE = 'LITTLE';

    // public function value(): string
    // {
    //     return match ($this) {
    //         self::HOST => pack('S', 1) === "\x01\x00" ? self::LITTLE->value : self::BIG->value,
    //         default => $this->value,
    //     };
    // }
}
