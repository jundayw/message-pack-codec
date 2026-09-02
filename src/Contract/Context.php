<?php

namespace Jundayw\MessagePackCodec\Contract;

interface Context
{
    /**
     * Get the context message.
     *
     * @return Bag
     */
    public function message(): Bag;

    /**
     * Get the context options.
     *
     * @return Bag
     */
    public function options(): Bag;

    /**
     * Get the context schema.
     *
     * @return Bag
     */
    public function schema(): Bag;

    /**
     * Get the parent context.
     *
     * @return static|null
     */
    public function parent(): ?static;

}
