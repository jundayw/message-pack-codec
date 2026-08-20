<?php

namespace Jundayw\MessagePackCodec;

use Jundayw\MessagePackCodec\Contract\Message;
use Jundayw\MessagePackCodec\Contract\Type;
use Jundayw\MessagePackCodec\Message\DecodeMessage;
use Jundayw\MessagePackCodec\Message\EncodeMessage;

class MessagePackCodec
{
    public function __construct(
        protected array $data = [],
        protected int $offset = 0,
    ) {
        //
    }

    public static function build(array $data = [], int $offset = 0): MessagePackCodec
    {
        return new self($data, $offset);
    }

    public function encode(array $data, array $options = []): Message
    {
        $message = new EncodeMessage();

        foreach ($options as $key => $option) {
            $name = $option['name'] ?? $key;
            if (is_array($option['children'] ?? null)) {
                $message[$name] = $this->encode($data['children'] ?? [], $option['children']);
            }
            if (false === is_null($instance = $this->newInstance($option))) {
                $message[$name] = $instance->encode($data, $option);
            }
        }

        return $message;
    }

    public function decode(string $buffer, array $options = [], int $offset = 0): Message
    {
        $this->offset = $offset ?: $this->offset;
        $message      = new DecodeMessage();

        foreach ($options as $key => $option) {
            $name = $option['name'] ?? $key;
            if (is_array($children = $option['children'] ?? null)) {
                $message[$key] = $this->decode($buffer, $children, $this->offset);
            }
            if (false === is_null($instance = $this->newInstance($option))) {
                $offset         = $instance->offset($option) ?? $this->offset;
                $message[$name] = $instance->decode($buffer, $offset, $option);
                $this->offset   = $instance->next();
            }
        }

        return $message;
    }

    protected function newInstance(array $option = []): ?Type
    {
        if (class_exists($class = $option['type'] ?? null)) {
            return new $class($this->data);
        }

        return null;
    }
}
