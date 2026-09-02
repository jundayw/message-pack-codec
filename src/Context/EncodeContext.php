<?php

namespace Jundayw\MessagePackCodec\Context;

use Jundayw\MessagePackCodec\Contract\Context;
use Jundayw\MessagePackCodec\Message\EncodeMessage;

class EncodeContext implements Context
{
    protected EncodeContext $parent;
    public EncodeMessage    $message;
    public array            $option = [];

    public function __construct(
        public readonly array $data,
        public readonly array $options = [],
        EncodeContext|null $parent = null,
    ) {
        $this->message = new EncodeMessage();
        $this->parent  = $parent ?? $this;
    }

    public function option(array $option): array
    {
        return $this->option = $option;
    }

    public function parent(): EncodeContext
    {
        return $this->parent;
    }

    public function child(array $data, array $options = []): static
    {
        return new static(
            data: $data,
            options: $options,
            parent: $this,
        );
    }

}
