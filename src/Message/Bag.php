<?php

namespace Jundayw\MessagePackCodec\Message;

use Jundayw\MessagePackCodec\Contract\Bag as BagContract;

class Bag implements BagContract
{
    public function __construct(
        protected array $items = [],
    ) {
        //
    }

    public static function make(array $items = []): static
    {
        return new Bag($items);
    }

    public function get(string|int $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->items)
            ? $this->items[$name]
            : $default;
    }

    public function set(string|int $name, mixed $value): static
    {
        $this->items[$name] = $value;

        return $this;
    }

    public function has(string|int $name): bool
    {
        return array_key_exists($name, $this->items);
    }

    public function all(): array
    {
        return $this->items;
    }

}
