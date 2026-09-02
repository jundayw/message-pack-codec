<?php

namespace Jundayw\MessagePackCodec\Contract;

interface Bag
{
    public function get(string|int $name, mixed $default = null): mixed;

    public function set(string|int $name, mixed $value): static;

    public function has(string|int $name): bool;

    public function all(): array;

}
