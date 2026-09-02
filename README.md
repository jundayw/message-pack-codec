<a id="readme-top"></a>

# MessagePackCodec

MessagePack Codec, Built for PHP.

A simple, fast, and extensible binary encoder and decoder for PHP.

[中文](README_zh-CN.md) · English

[![GitHub Tag][GitHub Tag]][GitHub Tag URL]
[![Total Downloads][Total Downloads]][Packagist URL]
[![Packagist Version][Packagist Version]][Packagist URL]
[![Packagist PHP Version Support][Packagist PHP Version Support]][Repository URL]
[![Packagist License][Packagist License]][Repository URL]

<!-- TABLE OF CONTENTS -->
<details>
    <summary>Table of Contents</summary>
    <ol>
        <li><a href="#installation">Installation</a></li>
        <li><a href="#usage">Usage</a></li>
        <li><a href="#contributing">Contributing</a></li>
        <li><a href="#contributors">Contributors</a></li>
        <li><a href="#license">License</a></li>
    </ol>
</details>

<!-- INSTALLATION -->

## Installation

You can install the package via [Composer]:

```bash
composer require jundayw/message-pack-codec
```

<p align="right">[<a href="#readme-top">back to top</a>]</p>

<!-- USAGE EXAMPLES -->

## Usage

`MessagePackCodec` uses a declarative field definition to describe how binary data should be encoded and decoded.

Each field is defined by a set of options, such as `type`, `format`, `length`, `count`, `value`, and custom `encode` / `decode` callbacks.

### Options

```php
[
    'name'     => 'string',
    'type'     => Type::class,          // Required
    'format'   => Format::class,
    'value'    => 'mixed|callable(Context)',
    'count'    => 'int|callable(Context)',
    'length'   => 'int|callable(Context)',
    'offset'   => 'int|callable(Context)',
    'size'     => 'int|callable(Context)',
    'encoding' => 'string',             // StringType only
    'fields'   => [                     // BitType only
        'field' => ['offset', 'length'],
    ],
    'encode'   => 'callable(mixed, Context)',
    'decode'   => 'callable(mixed, Context)',
    'cursor'   => 'int',
]
```

### Option Reference

| Option     | Type                       | Description                                           |
|------------|----------------------------|-------------------------------------------------------|
| `name`     | `string`                   | Field name                                            |
| `type`     | `string`                   | Type class used for encoding/decoding. Required       |
| `format`   | `Format`                   | Binary format used by the type                        |
| `value`    | `mixed\|callable(Context)` | Value to encode. A callable receives the current data |
| `count`    | `int\|callable(Context)`   | Number of values to encode/decode                     |
| `length`   | `int\|callable(Context)`   | Length of the field in bytes                          |
| `offset`   | `int\|callable(Context)`   | Offset used when reading the field                    |
| `size`     | `int\|callable(Context)`   | Size of the field                                     |
| `encoding` | `string`                   | Character encoding for `StringType`                   |
| `fields`   | `array`                    | Bit-field definitions for `BitType`                   |
| `encode`   | `callable(mixed, Context)` | Custom value transformation before encoding           |
| `decode`   | `callable(mixed, Context)` | Custom value transformation after decoding            |
| `cursor`   | `int`                      | Initial/current binary cursor position                |

> `callable` options are useful when a field depends on previously decoded or encoded values.

---

## Decode

To decode binary data, define the structure of the binary packet using an associative array.

For example, the following packet contains an IP address, TCP/UDP ports, channel information, playback information, and start/end times.

```php
use Jundayw\MessagePackCodec\Context\DecodeContext;
use Jundayw\MessagePackCodec\Message\EncodeMessage;
use Jundayw\MessagePackCodec\MessagePackCodec;
use Jundayw\MessagePackCodec\Type\BCDType;
use Jundayw\MessagePackCodec\Type\StringType;
use Jundayw\MessagePackCodec\Type\UInt8Type;
use Jundayw\MessagePackCodec\Type\UInt16Type;

$schema = [
    'length'     => [
        'type' => UInt8Type::class,
    ],
    'ip'         => [
        'type'     => StringType::class,
        'encoding' => 'GBK',
        'length'   => fn(DecodeContext $context) => $context->message()->get('length'),
    ],
    'tcp'        => [
        'type' => UInt16Type::class,
    ],
    'udp'        => [
        'type' => UInt16Type::class,
    ],
    'channel_id' => [
        'type' => UInt8Type::class,
    ],
    'type'       => [
        'type' => UInt8Type::class,
    ],
    'stream'     => [
        'type' => UInt8Type::class,
    ],
    'storage'    => [
        'type' => UInt8Type::class,
    ],
    'play'       => [
        'type' => UInt8Type::class,
    ],
    'speed'      => [
        'type' => UInt8Type::class,
    ],
    's_time'     => [
        'type'   => BCDType::class,
        'length' => 6,
        'decode' => fn($value) => DateTime::createFromFormat('ymdHis', $value)->format('20y-m-d H:i:s'),
    ],
    'e_time'     => [
        'type'   => BCDType::class,
        'length' => 6,
        'decode' => 0,
    ],
];

$binary = EncodeMessage::fromHex('093132372E302E302E3104360436010301010000260901123059000000000000');

$message = MessagePackCodec::build()->decode($binary, $schema);

var_dump(
    json_encode(
        $message->toArray(),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    )
);
```

Output:

```json
{
    "length": 9,
    "ip": "127.0.0.1",
    "tcp": 259,
    "udp": 769,
    "channel_id": 1,
    "type": 3,
    "stream": 1,
    "storage": 1,
    "play": 0,
    "speed": 0,
    "s_time": "2026-09-01 12:30:59",
    "e_time": "0000-00-00 00:00:00"
}
```

> The actual numeric values depend on the configured byte order of `UInt16Type`. If the protocol specifies big-endian or little-endian explicitly, configure the corresponding `Format`.

### Dynamic Length

A field can reference a previously decoded field.

For example, if the first byte specifies the length of the following IP address:

```php
$schema = [
    'length' => [
        'type' => UInt8Type::class,
    ],

    'ip' => [
        'type'   => StringType::class,
        'length' => fn(Context $context) => $context->message()->get('length'),
    ],
];
```

For the binary data:

```text
09 31 32 37 2E 30 2E 30 2E 31
```

The decoder first reads:

```text
length = 9
```

and then uses that value to determine that the `ip` field occupies 9 bytes:

```text
ip = "127.0.0.1"
```

This allows packet definitions to describe variable-length protocols without manually managing the binary cursor.

---

## Encode

Encoding uses the same field definition approach.

The main difference is that `value` can be used to calculate a field dynamically from the input data.

```php
use Jundayw\MessagePackCodec\Context\EncodeContext;
use Jundayw\MessagePackCodec\MessagePackCodec;
use Jundayw\MessagePackCodec\Type\BCDType;
use Jundayw\MessagePackCodec\Type\StringType;
use Jundayw\MessagePackCodec\Type\UInt8Type;
use Jundayw\MessagePackCodec\Type\UInt16Type;

$schema = [
    'length'     => [
        'type'  => UInt8Type::class,
        'value' => fn(EncodeContext $context) => strlen($context->data()->get('ip')),
    ],
    'ip'         => [
        'type'     => StringType::class,
        'encoding' => 'GBK',
        'count'    => '*',
    ],
    'tcp'        => [
        'type' => UInt16Type::class,
    ],
    'udp'        => [
        'type' => UInt16Type::class,
    ],
    'channel_id' => [
        'type' => UInt8Type::class,
    ],
    'type'       => [
        'type' => UInt8Type::class,
    ],
    'stream'     => [
        'type' => UInt8Type::class,
    ],
    'storage'    => [
        'type' => UInt8Type::class,
    ],
    'play'       => [
        'type' => UInt8Type::class,
    ],
    'speed'      => [
        'type' => UInt8Type::class,
    ],
    's_time'     => [
        'type'   => BCDType::class,
        'length' => 6,
        'value'  => fn(EncodeContext $context) => DateTime::createFromFormat('Y-m-d H:i:s', $context->data()->get('s_time'))->format('ymdHis'),
    ],
    'e_time'     => [
        'type'   => BCDType::class,
        'length' => 6,
    ],
];

$data = [
    'ip'         => '127.0.0.1',
    'tcp'        => 1078,
    'udp'        => 1078,
    'channel_id' => 1,
    'type'       => 3,
    'stream'     => 1,
    'storage'    => 1,
    'play'       => 0,
    'speed'      => 0,
    's_time'     => '2026-09-01 12:30:59',
    'e_time'     => 0,
];

$message = MessagePackCodec::build()->encode($data, $schema);

var_dump($message->toHex());
```

Output:

```text
093132372E302E302E3104360436010301010000260901123059000000000000
```

### Dynamic Value

`value` can be either a static value or a callable.

Static value:

```php
'version' => [
    'type'  => UInt8Type::class,
    'value' => 1,
],
```

Dynamic value:

```php
'length' => [
    'type'  => UInt8Type::class,
    'value' => fn (Context $context) => strlen($context->data()->get('content')),
],
```

This is particularly useful for protocol fields such as:

* body length
* packet count
* checksum
* sequence number
* timestamp
* reserved fields
* calculated flags

---

## Value Transformation

The `encode` and `decode` options can be used to transform values without implementing a new `Type`.

### Decode Transformation

For example, convert a BCD timestamp into a PHP date-time string:

```php
'start_time' => [
    'type'   => BCDType::class,
    'length' => 6,
    'decode' => fn($value) => DateTime::createFromFormat('ymdHis', $value)->format('20y-m-d H:i:s'),
],
```

### Encode Transformation

Convert a date-time string into BCD-compatible data:

```php
'start_time' => [
    'type'   => BCDType::class,
    'length' => 6,
    'encode' => fn($value) => DateTime::createFromFormat('Y-m-d H:i:s', $value)->format('ymdHis'),
],
```

This keeps binary representation logic inside the `Type`, while application-level value conversion remains in the schema.

---

## Count

`count` controls the number of values handled by a field.

It can be a fixed integer:

```php
'values' => [
    'type'  => UInt8Type::class,
    'count' => 4,
],
```

or a dynamic callable:

```php
'values' => [
    'type'  => UInt8Type::class,
    'count' => fn (Context $context) => $context->data()->get('count'),
],
```

For variable-length strings, `*` can be used when supported by the corresponding type:

```php
'content' => [
    'type'  => StringType::class,
    'count' => '*',
],
```

---

## Length

`length` defines the number of bytes occupied by a field.

A fixed length:

```php
'ip' => [
    'type'   => StringType::class,
    'length' => 15,
],
```

A dynamic length:

```php
'ip' => [
    'type'   => StringType::class,
    'length' => fn (Context $context) => $context->message()->get('length'),
],
```

This is useful for binary protocols where a length field precedes a variable-length payload.

---

## String Encoding

`StringType` supports character encoding conversion through the `encoding` option.

```php
'device_name' => [
    'type'     => StringType::class,
    'encoding' => 'GBK',
    'length'   => 20,
],
```

For UTF-8:

```php
'device_name' => [
    'type'     => StringType::class,
    'encoding' => 'UTF-8',
    'length'   => 20,
],
```

This is useful for protocols commonly using GBK, such as some Chinese IoT and vehicle telematics protocols.

---

## Bit Fields

`BitType` can describe multiple logical fields stored inside a single integer.

For example:

```php
'flags' => [
    'type' => BitType::class,
    'fields' => [
        'reserved'   => [14, 2],
        'version'    => [13, 1],
        'subpackage' => [12, 1],
        'encrypt'    => [10, 2],
        'bodyLength' => [0, 10],
    ],
],
```

The definition above describes:

```text
┌──────────┬─────────┬─────────────┬─────────┬────────────────┐
│ reserved │ version │ subpackage  │ encrypt │   bodyLength   │
│  2 bits  │  1 bit  │    1 bit    │ 2 bits  │    10 bits     │
└──────────┴─────────┴─────────────┴─────────┴────────────────┘
```

The fields can then be accessed by name rather than manually performing bitwise operations.

This is especially useful for protocol headers containing:

* version
* encryption flags
* subpackage flags
* reserved bits
* message length
* status flags

---

## Cursor and Offset

The codec maintains a binary cursor while decoding sequential fields.

For example:

```php
$schema = [
    'length' => [
        'type' => UInt8Type::class,
    ],

    'body' => [
        'type'   => StringType::class,
        'length' => fn (Context $context) => $context->message()->get('length'),
    ],
];
```

The cursor moves automatically:

```text
┌────────┬─────────────────────────┐
│ length │          body           │
│ 1 byte │       N bytes           │
└────────┴─────────────────────────┘
          ↑
        cursor
```

When required, `cursor` or `offset` can be used to control the starting position or reading location.

---

## Reusing Schemas

Because the packet structure is represented as an array, the same schema can be reused for multiple messages:

```php
$codec = MessagePackCodec::build();

$message1 = $codec->decode($binary1, $schema);
$message2 = $codec->decode($binary2, $schema);
$message3 = $codec->decode($binary3, $schema);
```

Likewise, an encoding schema can be reused:

```php
$codec = MessagePackCodec::build();

$message1 = $codec->encode($data1, $schema);
$message2 = $codec->encode($data2, $schema);
```

This makes the schema itself a reusable description of the binary protocol.

---

## Encode and Decode in One Schema

> It is not recommended that encoder and decoder definitions use the same schema; the callback parameters in the Option Reference will differ.

For simple protocols, a schema can be used for both encoding and decoding.

```php
$schema = [
    'id' => [
        'type' => UInt8Type::class,
    ],

    'name' => [
        'type'   => StringType::class,
        'length' => 16,
    ],

    'status' => [
        'type' => UInt8Type::class,
    ],
];

$codec = MessagePackCodec::build();

$encoded = $codec->encode($data, $schema);

$decoded = $codec->decode($encoded, $schema);
```

This allows a protocol structure to be defined once and used as both an encoder and decoder definition.

---

## Complete Example

A typical binary protocol definition may look like:

```php
$schema = [
    'length' => [
        'type'  => UInt8Type::class,
        'value' => fn(Context $context) => strlen($context->data()->get('content')),
    ],

    'content' => [
        'type'     => StringType::class,
        'encoding' => 'GBK',
        'count'    => '*',
    ],

    'sequence' => [
        'type' => UInt16Type::class,
    ],

    'timestamp' => [
        'type'   => BCDType::class,
        'length' => 6,
        'value' => fn($value) => DateTime::createFromFormat('Y-m-d H:i:s', $value)->format('ymdHis'),
        'decode' => fn($value) => DateTime::createFromFormat('ymdHis', $value)->format('20y-m-d H:i:s'),
    ],
];

$data = [
    'content'   => 'Hello',
    'sequence'  => 100,
    'timestamp' => '2026-09-01 12:30:59',
];

$codec = MessagePackCodec::build();

$encoded = $codec->encode($data, $schema);

echo $encoded->toHex();

$decoded = $codec->decode($encoded, $schema);

print_r($decoded->toArray());
```

The key idea is that **the schema describes the binary layout, while `Type` describes how an individual value is encoded or decoded**.

This separation allows the same codec to support different binary protocols without introducing protocol-specific logic into the codec itself.

<!-- CONTRIBUTING -->

## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

<p align="right">[<a href="#readme-top">back to top</a>]</p>

<!-- CONTRIBUTORS -->

## Contributors

Thanks goes to these wonderful people:

<a href="https://github.com/jundayw/message-pack-codec/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=jundayw/message-pack-codec" alt="contrib.rocks image" />
</a>

Contributions of any kind are welcome!

<p align="right">[<a href="#readme-top">back to top</a>]</p>

<!-- LICENSE -->

## License

Distributed under the MIT License (MIT). Please see [License File] for more information.

<p align="right">[<a href="#readme-top">back to top</a>]</p>

[GitHub Tag]: https://img.shields.io/github/v/tag/jundayw/message-pack-codec

[Total Downloads]: https://img.shields.io/packagist/dt/jundayw/message-pack-codec?style=flat-square

[Packagist Version]: https://img.shields.io/packagist/v/jundayw/message-pack-codec

[Packagist PHP Version Support]: https://img.shields.io/packagist/php-v/jundayw/message-pack-codec

[Packagist License]: https://img.shields.io/github/license/jundayw/message-pack-codec

[GitHub Tag URL]: https://github.com/jundayw/message-pack-codec/tags

[Packagist URL]: https://packagist.org/packages/jundayw/message-pack-codec

[Repository URL]: https://github.com/jundayw/message-pack-codec

[GitHub Open Issues]: https://github.com/jundayw/message-pack-codec/issues

[Composer]: https://getcomposer.org

[License File]: https://github.com/jundayw/message-pack-codec/blob/main/LICENSE
