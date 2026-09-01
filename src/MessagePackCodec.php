<?php

namespace Jundayw\MessagePackCodec;

use Jundayw\MessagePackCodec\Contract\Type;
use Jundayw\MessagePackCodec\Message\DecodeMessage;
use Jundayw\MessagePackCodec\Message\EncodeMessage;

class MessagePackCodec
{
    public static function build(): MessagePackCodec
    {
        return new self;
    }

    public function encode(array $data, array $options = []): EncodeMessage
    {
        $message = new EncodeMessage();

        foreach ($options as $key => $option) {
            if ($key === 'children' && array_key_exists('children', $data)) {
                $message[$key] = $this->encode($data['children'], $option);
            }
            if (is_null($instance = $this->newInstance($option))) {
                continue;
            }
            $option         = $instance->options([$data, $options, $message], $option);
            $name           = $instance->option('name', $option, $key);
            $value          = $instance->option('value', $option, $data[$name] ?? '');
            $value          = $instance->encode($value, $option);
            $message[$name] = $instance->callable('encode', $value, $option, $data);
        }

        return $message;
    }

    protected int $cursor = 0;

    public function decode(string $binary, array $options = [], int $offset = 0): DecodeMessage
    {
        $message = new DecodeMessage();

        $this->cursor = $offset;

        foreach ($options as $key => $option) {
            if ($key === 'children') {
                $message[$key] = $this->decode($binary, $option, $this->cursor);
            }
            if (is_null($instance = $this->newInstance($option))) {
                continue;
            }
            $option         = ['cursor' => $this->cursor] + $option;
            $option         = $instance->options([$message, $options], $option);
            $name           = $instance->option('name', $option, $key);
            $value          = $instance->decode($binary, $this->cursor, $option);
            $message[$name] = $instance->callable('decode', $value, $option, $message);
            $this->cursor   = $instance->next();
        }

        return $message;
    }

    protected function newInstance(array $option = []): ?Type
    {
        if (array_key_exists('type', $option) &&
            class_exists($class = $option['type']) &&
            is_subclass_of($class, Type::class)) {
            return new $class($option['format'] ?? null);
        }

        return null;
    }
}
