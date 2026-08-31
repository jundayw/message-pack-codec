<?php

namespace Jundayw\MessagePackCodec\Support;

enum Endian: string
{
    case HOST   = 'HOST';
    case BIG    = 'BIG';
    case LITTLE = 'LITTLE';
}
