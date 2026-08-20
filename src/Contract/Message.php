<?php

namespace Jundayw\MessagePackCodec\Contract;

interface Message
{
    public function toArray(callable|null $callback = null): array;

    public function toString(callable|null $callback = null): string;

}
