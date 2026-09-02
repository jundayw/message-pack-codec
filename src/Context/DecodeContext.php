<?php

namespace Jundayw\MessagePackCodec\Context;

use Jundayw\MessagePackCodec\Contract\Context;
use Jundayw\MessagePackCodec\Message\Bag;
use Jundayw\MessagePackCodec\Message\DecodeMessage;

class DecodeContext implements Context
{
    protected DecodeContext|null $parent = null;

    protected DecodeMessage $message;
    protected Bag           $options;
    protected Bag           $schema;

    public function __construct(
        protected readonly string $binary,
        array $options = [],
        DecodeContext|null $parent = null,
    ) {
        $this->message = new DecodeMessage();
        $this->options = new Bag($options);
        $this->schema  = new Bag();
        $this->parent  = $parent;
    }

    /**
     * Get the context binary.
     *
     * @return string
     */
    public function binary(): string
    {
        return $this->binary;
    }

    /**
     * Get the context message.
     *
     * @return DecodeMessage
     */
    public function message(): DecodeMessage
    {
        return $this->message;
    }

    /**
     * Get the context options.
     *
     * @return Bag
     */
    public function options(): Bag
    {
        return $this->options;
    }

    /**
     * Get the context schema.
     *
     * @return Bag
     */
    public function schema(): Bag
    {
        return $this->schema;
    }

    /**
     * Get the parent context.
     *
     * @return static|null
     */
    public function parent(): ?static
    {
        return $this->parent;
    }

    /**
     * Create a child context.
     *
     * @param string $binary
     * @param array  $options
     *
     * @return static
     */
    public function child(string $binary, array $options = []): static
    {
        return new static(
            binary: $binary,
            options: $options,
            parent: $this,
        );
    }

}
