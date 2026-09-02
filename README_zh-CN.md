# MessagePackCodec

**MessagePack Codec for PHP**

一个简单、高效且可扩展的 PHP 二进制编码与解码器。

中文 · [English](README.md)

[![GitHub Tag][GitHub Tag]][GitHub Tag URL]
[![Total Downloads][Total Downloads]][Packagist URL]
[![Packagist Version][Packagist Version]][Packagist URL]
[![Packagist PHP Version Support][Packagist PHP Version Support]][Repository URL]
[![Packagist License][Packagist License]][Repository URL]

---

## 安装

通过 Composer 安装：

```bash
composer require jundayw/message-pack-codec
```

---

## 使用

`MessagePackCodec` 使用 **声明式字段定义（Schema）** 描述二进制数据结构。

每个字段通过一组配置描述其编码和解码方式，例如：

* `type`：字段类型
* `format`：二进制格式及字节序
* `length`：字段长度
* `count`：字段数量
* `value`：字段值
* `encode`：编码前的数据转换
* `decode`：解码后的数据转换

因此，**Schema** 负责描述二进制数据结构，**Type** 负责描述单个字段的编码与解码规则。

这种设计可以将二进制协议的结构定义与具体的编解码实现解耦。

---

## Schema 选项

一个典型的字段定义如下：

```php
[
    'name'     => 'string',
    'type'     => Type::class,
    'format'   => Format::class,
    'value'    => 'mixed|callable(Context)',
    'count'    => 'int|callable(Context)',
    'length'   => 'int|callable(Context)',
    'offset'   => 'int|callable(Context)',
    'size'     => 'int|callable(Context)',
    'encoding' => 'string',
    'fields'   => [],
    'encode'   => 'callable(mixed, Context)',
    'decode'   => 'callable(mixed, Context)',
    'cursor'   => 'int',
]
```

### 选项说明

| 选项         | 类型                         | 说明                   |
|------------|----------------------------|----------------------|
| `name`     | `string`                   | 字段名称                 |
| `type`     | `string`                   | 字段使用的 Type 类，必填      |
| `format`   | `Format`                   | 二进制编码格式及字节序          |
| `value`    | `mixed\|callable(Context)` | 编码时使用的值              |
| `count`    | `int\|callable(Context)`   | 字段包含的值数量             |
| `length`   | `int\|callable(Context)`   | 字段占用的字节长度            |
| `offset`   | `int\|callable(Context)`   | 解码时的起始偏移量            |
| `size`     | `int\|callable(Context)`   | 单个元素占用的字节大小          |
| `encoding` | `string`                   | `StringType` 使用的字符编码 |
| `fields`   | `array`                    | `BitType` 的位字段定义     |
| `encode`   | `callable(mixed, Context)` | 编码前的数据转换             |
| `decode`   | `callable(mixed, Context)` | 解码后的数据转换             |
| `cursor`   | `int`                      | 当前二进制游标位置            |

当字段值依赖其他字段时，可以使用 `callable` 动态计算。

例如：

```php
'length' => [
    'type'  => UInt8Type::class,
    'value' => fn (EncodeContext $context) => strlen($context->data()->get('content')),
],
```

---

# 解码

使用关联数组定义二进制数据结构，然后调用 `decode()` 即可完成解码。

例如，定义一个包含 IP 地址、TCP/UDP 端口、频道信息、播放信息以及起止时间的二进制数据包：

```php
use Jundayw\MessagePackCodec\Context\DecodeContext;
use Jundayw\MessagePackCodec\Message\EncodeMessage;
use Jundayw\MessagePackCodec\MessagePackCodec;
use Jundayw\MessagePackCodec\Type\BCDType;
use Jundayw\MessagePackCodec\Type\StringType;
use Jundayw\MessagePackCodec\Type\UInt8Type;
use Jundayw\MessagePackCodec\Type\UInt16Type;

$schema = [
    'length' => [
        'type' => UInt8Type::class,
    ],
    'ip' => [
        'type'     => StringType::class,
        'encoding' => 'GBK',
        'length'   => fn (DecodeContext $context) => $context->message()->get('length'),
    ],
    'tcp' => [
        'type' => UInt16Type::class,
    ],
    'udp' => [
        'type' => UInt16Type::class,
    ],
    'channel_id' => [
        'type' => UInt8Type::class,
    ],
    'type' => [
        'type' => UInt8Type::class,
    ],
    'stream' => [
        'type' => UInt8Type::class,
    ],
    'storage' => [
        'type' => UInt8Type::class,
    ],
    'play' => [
        'type' => UInt8Type::class,
    ],
    'speed' => [
        'type' => UInt8Type::class,
    ],
    's_time' => [
        'type'   => BCDType::class,
        'length' => 6,
        'decode' => fn ($value) => DateTime::createFromFormat('ymdHis', $value)->format('20y-m-d H:i:s'),
    ],
    'e_time' => [
        'type'   => BCDType::class,
        'length' => 6,
        'decode' => 0,
    ],
];

$binary = EncodeMessage::fromHex(
    '093132372E302E302E3104360436010301010000260901123059000000000000'
);

$message = MessagePackCodec::build()->decode($binary, $schema);

var_dump(
    json_encode(
        $message->toArray(),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    )
);
```

解码结果：

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

> `UInt16Type` 的具体数值取决于配置的字节序。如果协议明确规定使用大端或小端，应通过对应的 `Format` 进行配置。

---

## 动态长度

字段长度可以依赖之前已经解码的字段。

例如，第一个字节表示后续 IP 地址的长度：

```php
$schema = [
    'length' => [
        'type' => UInt8Type::class,
    ],
    'ip' => [
        'type'   => StringType::class,
        'length' => fn (DecodeContext $context) => $context->message()->get('length'),
    ],
];
```

对应的二进制数据：

```text
09 31 32 37 2E 30 2E 30 2E 31
```

解码过程为：

```text
length = 9
```

随后解码器自动读取 9 个字节：

```text
ip = "127.0.0.1"
```

整个过程不需要手动维护二进制偏移量。

这对于长度由前置字段决定的二进制协议非常有用。

---

# 编码

编码同样使用 Schema 描述数据结构。

与解码不同的是，编码时可以通过 `value` 根据当前数据动态计算字段值。

例如：

```php
$schema = [
    'length' => [
        'type'  => UInt8Type::class,
        'value' => fn (EncodeContext $context) =>
            strlen($context->data()->get('ip')),
    ],
    'ip' => [
        'type'     => StringType::class,
        'encoding' => 'GBK',
        'count'    => '*',
    ],
    'tcp' => [
        'type' => UInt16Type::class,
    ],
    'udp' => [
        'type' => UInt16Type::class,
    ],
    'channel_id' => [
        'type' => UInt8Type::class,
    ],
    'type' => [
        'type' => UInt8Type::class,
    ],
    'stream' => [
        'type' => UInt8Type::class,
    ],
    'storage' => [
        'type' => UInt8Type::class,
    ],
    'play' => [
        'type' => UInt8Type::class,
    ],
    'speed' => [
        'type' => UInt8Type::class,
    ],
    's_time' => [
        'type'   => BCDType::class,
        'length' => 6,
        'value'  => fn (EncodeContext $context) => DateTime::createFromFormat('Y-m-d H:i:s', $context->data()->get('s_time'))->format('ymdHis'),
    ],
    'e_time' => [
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

输出：

```text
093132372E302E302E3104360436010301010000260901123059000000000000
```

---

## 动态值

`value` 可以是固定值，也可以是动态回调。

### 固定值

```php
'version' => [
    'type'  => UInt8Type::class,
    'value' => 1,
],
```

### 动态值

```php
'length' => [
    'type'  => UInt8Type::class,
    'value' => fn (EncodeContext $context) => strlen($context->data()->get('content')),
],
```

动态值特别适用于协议中的计算字段，例如：

* 数据长度
* 数据包数量
* 校验和
* 序列号
* 时间戳
* 保留字段
* 标志位

---

# 数据转换

`encode` 和 `decode` 可以用于实现应用层数据与二进制数据之间的转换，而不需要创建新的 `Type`。

这样可以将：

> **二进制格式处理**

与：

> **业务数据转换**

进行分离。

---

## Decode 转换

例如，将 BCD 时间转换为 PHP 日期时间字符串：

```php
'start_time' => [
    'type'   => BCDType::class,
    'length' => 6,
    'decode' => fn ($value) => DateTime::createFromFormat('ymdHis', $value)->format('20y-m-d H:i:s'),
],
```

---

## Encode 转换

将日期时间字符串转换为 BCD 数据：

```php
'start_time' => [
    'type'   => BCDType::class,
    'length' => 6,
    'encode' => fn ($value) => DateTime::createFromFormat('Y-m-d H:i:s', $value)->format('ymdHis'),
],
```

因此：

```text
Application Value
       │
       ▼
   encode()
       │
       ▼
    Type
       │
       ▼
 Binary Data
```

解码过程则相反：

```text
Binary Data
     │
     ▼
    Type
     │
     ▼
   decode()
     │
     ▼
Application Value
```

---

# Count

`count` 用于控制字段包含的值数量。

可以指定固定数量：

```php
'values' => [
    'type'  => UInt8Type::class,
    'count' => 4,
],
```

也可以动态计算：

```php
'values' => [
    'type'  => UInt8Type::class,
    'count' => fn (DecodeContext $context) => $context->message()->get('count'),
],
```

对于可变长度的数据，可以使用 `*`：

```php
'content' => [
    'type'  => StringType::class,
    'count' => '*',
],
```

具体是否支持 `*` 取决于对应的 `Type` 和 `Format`。

---

# Length

`length` 定义字段占用的字节数。

固定长度：

```php
'ip' => [
    'type'   => StringType::class,
    'length' => 15,
],
```

动态长度：

```php
'ip' => [
    'type'   => StringType::class,
    'length' => fn (DecodeContext $context) => $context->message()->get('length'),
],
```

这对于以下协议结构非常常见：

```text
┌────────┬──────────────────────┐
│ length │        payload       │
│ 1 byte │      N bytes         │
└────────┴──────────────────────┘
```

---

# String 编码

`StringType` 支持通过 `encoding` 进行字符编码转换。

例如使用 GBK：

```php
'device_name' => [
    'type'     => StringType::class,
    'encoding' => 'GBK',
    'length'   => 20,
],
```

使用 UTF-8：

```php
'device_name' => [
    'type'     => StringType::class,
    'encoding' => 'UTF-8',
    'length'   => 20,
],
```

对于一些使用 GBK 编码的中文物联网和车联网协议尤其有用。

---

# Bit Fields

`BitType` 用于描述存储在一个整数中的多个逻辑字段。

例如：

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

对应的位布局：

```text
┌──────────┬─────────┬────────────┬─────────┬────────────────┐
│ reserved │ version │ subpackage │ encrypt │   bodyLength   │
│  2 bits  │  1 bit  │   1 bit    │ 2 bits  │    10 bits     │
└──────────┴─────────┴────────────┴─────────┴────────────────┘
```

通过这种方式，可以直接按照字段名称访问位字段，而不需要在业务代码中手动执行位运算。

例如协议头中常见的：

* 版本号
* 加密标志
* 分包标志
* 保留位
* 消息长度
* 状态标志

都可以通过 `BitType` 进行描述。

---

# Cursor 和 Offset

`MessagePackCodec` 在解码过程中会按照 Schema 中字段的顺序自动移动二进制游标。

例如：

```php
$schema = [
    'length' => [
        'type' => UInt8Type::class,
    ],
    'body' => [
        'type'   => StringType::class,
        'length' => fn (DecodeContext $context) => $context->message()->get('length'),
    ],
];
```

对应的数据结构：

```text
┌────────┬─────────────────────────┐
│ length │           body          │
│ 1 byte │         N bytes         │
└────────┴─────────────────────────┘
          ↑
        cursor
```

解码器会自动完成：

```text
cursor = 0
    │
    ├── decode length
    │
    ▼
cursor = 1
    │
    ├── decode body
    │
    ▼
cursor = 1 + length
```

通常情况下不需要手动维护游标。

在需要从指定位置开始读取时，可以通过 `offset` 或 `cursor` 控制读取位置。

---

# Schema 复用

Schema 本质上是一个二进制数据结构定义，因此可以重复使用。

例如：

```php
$codec = MessagePackCodec::build();

$message1 = $codec->decode($binary1, $schema);
$message2 = $codec->decode($binary2, $schema);
$message3 = $codec->decode($binary3, $schema);
```

编码同样可以复用：

```php
$codec = MessagePackCodec::build();

$message1 = $codec->encode($data1, $schema);
$message2 = $codec->encode($data2, $schema);
```

因此，可以将协议定义单独保存：

```php
$schema = [
    // protocol definition
];
```

然后在不同的数据包中重复使用。

---

# Encode 与 Decode

同一个 Schema 可以同时用于编码和解码。

例如：

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

但是，对于复杂协议，不建议强制让 Encode 和 Decode 共用完全相同的 Schema。

原因在于：

* Encode 使用 `EncodeContext`
* Decode 使用 `DecodeContext`
* Encode 中的 `value` 通常依赖原始业务数据
* Decode 中的动态字段通常依赖已经解析的消息数据
* `encode` 与 `decode` 回调的语义也有所不同

因此，对于复杂协议，可以根据实际需要分别定义 Encode Schema 和 Decode Schema。

---

# 完整示例

一个典型的二进制协议可以定义为：

```php
$schema = [
    'length' => [
        'type'  => UInt8Type::class,
        'value' => fn (EncodeContext $context) => strlen($context->data()->get('content')),
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
        'value' => fn ($value) => DateTime::createFromFormat('Y-m-d H:i:s', $value)->format('ymdHis'),
        'decode' => fn ($value) => DateTime::createFromFormat('ymdHis', $value)->format('20y-m-d H:i:s'),
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

---

# 设计理念

`MessagePackCodec` 的核心设计可以概括为：

```text
                 Schema
                    │
                    ▼
          ┌──────────────────┐
          │ MessagePackCodec │
          └────────┬─────────┘
                   │
          ┌────────┴────────┐
          ▼                 ▼
    EncodeContext     DecodeContext
          │                 │
          ▼                 ▼
     EncodeMessage     DecodeMessage
          │                 │
          └────────┬────────┘
                   ▼
                  Type
                   │
                   ▼
              Binary Data
```

核心职责分离如下：

### Schema

描述：

> **数据是什么结构**

例如：

```php
[
    'length' => [
        'type' => UInt8Type::class,
    ],
]
```

### Type

描述：

> **一个值如何进行二进制编码和解码**

### Context

描述：

> **当前一次编码或解码过程中的运行环境**

例如：

```php
$context->data()
$context->options()
$context->message()
```

DecodeContext 还提供：

```php
$context->binary()
$context->cursor()
```

### Message

描述：

> **当前编码或解码产生的数据结果**

因此整个系统遵循：

> **Schema defines the structure, Type defines the binary representation, Context provides runtime state, and Message contains the result.**

即：

> **Schema 定义结构，Type 定义二进制表示，Context 提供运行时环境，Message 保存最终结果。**

这种设计使 `MessagePackCodec` 本身不需要理解具体协议。

因此，同一个 Codec 可以用于：

* TCP 二进制协议
* UDP 二进制协议
* IoT 协议
* 车联网协议
* JT/T 808
* 自定义设备协议
* 文件二进制格式
* 网络数据包
* 二进制消息结构

而无需将具体协议逻辑写入 Codec 核心。

---

# 贡献

欢迎提交 Issue、Pull Request 或其他形式的贡献。

如果你有任何能够改进项目的想法，可以：

1. Fork 本项目
2. 创建 Feature 分支

```bash
git checkout -b feature/AmazingFeature
```

3. 提交修改

```bash
git commit -m "Add some AmazingFeature"
```

4. 推送到远程仓库

```bash
git push origin feature/AmazingFeature
```

5. 创建 Pull Request

如果项目对你有所帮助，也欢迎 Star。

---

# Contributors

感谢所有参与项目贡献的开发者。

<a href="https://github.com/jundayw/message-pack-codec/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=jundayw/message-pack-codec" alt="Contributors" />
</a>

任何形式的贡献都非常欢迎。

---

# License

本项目基于 [MIT License] 开源。

[GitHub Tag]: https://img.shields.io/github/v/tag/jundayw/message-pack-codec

[Total Downloads]: https://img.shields.io/packagist/dt/jundayw/message-pack-codec?style=flat-square

[Packagist Version]: https://img.shields.io/packagist/v/jundayw/message-pack-codec

[Packagist PHP Version Support]: https://img.shields.io/packagist/php-v/jundayw/message-pack-codec

[Packagist License]: https://img.shields.io/github/license/jundayw/message-pack-codec

[GitHub Tag URL]: https://github.com/jundayw/message-pack-codec/tags

[Packagist URL]: https://packagist.org/packages/jundayw/message-pack-codec

[Repository URL]: https://github.com/jundayw/message-pack-codec

[MIT License]: https://github.com/jundayw/message-pack-codec/blob/main/LICENSE
