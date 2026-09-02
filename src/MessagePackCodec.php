<?php

namespace Jundayw\MessagePackCodec;

use Jundayw\MessagePackCodec\Context\DecodeContext;
use Jundayw\MessagePackCodec\Context\EncodeContext;
use Jundayw\MessagePackCodec\Contract\Type;
use Jundayw\MessagePackCodec\Message\DecodeMessage;
use Jundayw\MessagePackCodec\Message\EncodeMessage;

class MessagePackCodec
{
    protected int $cursor = 0;

    public static function build(): MessagePackCodec
    {
        return new self;
    }

    public function encode(array $data, array $options = []): EncodeMessage
    {
        return $this->encodeContext(
            new EncodeContext($data, $options)
        );
    }

    protected function encodeContext(EncodeContext $context): EncodeMessage
    {
        foreach ($context->options as $key => $option) {
            if ($key === 'children') {
                $context->message[$key] = $this->encodeContext(
                    $context->child(
                        $context->data['children'] ?? [],
                        $option
                    )
                );
                continue;
            }

            if (is_null($instance = $this->newInstance($option))) {
                continue;
            }

            $option = $context->option($option);
            $option = $instance->options($context, $option);
            $name   = $instance->option('name', $option, $key);
            $value  = $instance->option('value', $option, $context->data[$name] ?? '');
            $value  = $instance->encode($value, $option);
            $encode = $instance->option('encode', $option);

            $context->message[$name] = $instance->callable($encode, $value, $context);
        }

        return $context->message;
    }

    public function decode(string $binary, array $options = [], int $offset = 0): DecodeMessage
    {
        return $this->decodeContext(
            new DecodeContext($binary, $options), $offset
        );
    }

    protected function decodeContext(DecodeContext $context, int $offset = 0): DecodeMessage
    {
        $this->cursor = $offset;

        foreach ($context->options as $key => $option) {
            if ($key === 'children') {
                $context->message[$key] = $this->decodeContext(
                    $context->child($context->binary, $option),
                    $this->next()
                );
                continue;
            }

            if (is_null($instance = $this->newInstance($option))) {
                continue;
            }

            $option = $context->option(['cursor' => $this->next()] + $option);
            $option = $instance->options($context, $option);
            $name   = $instance->option('name', $option, $key);
            $value  = $instance->decode($context->binary, $this->next(), $option);
            $decode = $instance->option('decode', $option);

            $context->message[$name] = $instance->callable($decode, $value, $context);
            $this->cursor            = $instance->next();
        }

        return $context->message;
    }

    public function next(): int
    {
        return $this->cursor;
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
