<?php

namespace Jundayw\MessagePackCodec\Context;

use Jundayw\MessagePackCodec\Contract\Context;
use Jundayw\MessagePackCodec\Message\DecodeMessage;

class DecodeContext implements Context
{
    protected DecodeContext $parent;
    public DecodeMessage    $message;
    public array            $option = [];

    public function __construct(
        public readonly string $binary,
        public readonly array $options = [],
        DecodeContext|null $parent = null,
    ) {
        $this->message = new DecodeMessage();
        $this->parent  = $parent ?? $this;
    }

    public function option(array $option): array
    {
        return $this->option = $option;
    }

    public function parent(): DecodeContext
    {
        return $this->parent;
    }

    public function child(string $binary, array $options = []): static
    {
        return new static(
            binary: $binary,
            options: $options,
            parent: $this,
        );
    }

}
