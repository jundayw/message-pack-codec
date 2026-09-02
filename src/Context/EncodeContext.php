<?php

namespace Jundayw\MessagePackCodec\Context;

use Jundayw\MessagePackCodec\Contract\Context;
use Jundayw\MessagePackCodec\Message\Bag;
use Jundayw\MessagePackCodec\Message\EncodeMessage;

class EncodeContext implements Context
{
    protected EncodeContext|null $parent = null;

    public EncodeMessage $message;
    public Bag           $data;
    public Bag           $options;
    public Bag           $schema;

    public function __construct(
        array $data,
        array $options = [],
        EncodeContext|null $parent = null,
    ) {
        $this->message = new EncodeMessage();
        $this->data    = new Bag($data);
        $this->options = new Bag($options);
        $this->schema  = new Bag();
        $this->parent  = $parent;
    }

    /**
     * Get the context data.
     *
     * @return Bag
     */
    public function data(): Bag
    {
        return $this->data;
    }

    /**
     * Get the context message.
     *
     * @return EncodeMessage
     */
    public function message(): EncodeMessage
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
     * @param array $data
     * @param array $options
     *
     * @return static
     */
    public function child(array $data, array $options = []): static
    {
        return new static(
            data: $data,
            options: $options,
            parent: $this,
        );
    }

}
