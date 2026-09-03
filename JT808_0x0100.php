<?php

use Jundayw\MessagePackCodec\Context\DecodeContext;
use Jundayw\MessagePackCodec\Context\EncodeContext;
use Jundayw\MessagePackCodec\Message\EncodeMessage;
use Jundayw\MessagePackCodec\MessagePackCodec;
use Jundayw\MessagePackCodec\Support\Format;
use Jundayw\MessagePackCodec\Type\BCDType;
use Jundayw\MessagePackCodec\Type\BitType;
use Jundayw\MessagePackCodec\Type\BytesType;
use Jundayw\MessagePackCodec\Type\StringType;
use Jundayw\MessagePackCodec\Type\UInt16Type;
use Jundayw\MessagePackCodec\Type\UInt8Type;

require __DIR__.'/vendor/autoload.php';

// 2019
$data = '7E0100405501000000000130625090600004003204D2434D534F46542E434E0000000000000000000000000000000000000000000000434D3830382D3130305942415555513058524F504442594D4C434B5553424E594134574C41505502B2E2412D30313147357A7E';
// // 2016
// $data = '7E0100002E0130625090600005003204D2434D534F46434D3830382D31303000000000000000000000005942415555513002B2E2412D30313147350C7E';

$data = substr($data, 2, -4);

$schema = [
    'MsgId'               => [
        'type' => UInt16Type::class,
    ],
    'MessageBodyProperty' => [
        'type'   => BitType::class,
        'format' => Format::UINT16_BIG,
        'fields' => [
            'Reserved'    => [15, 1],
            'VersionFlag' => [14, 1],
            'IsPackage'   => [13, 1],
            'Encrypt'     => [10, 3],
            'DataLength'  => [0, 10],
        ],
    ],
    'Version'             => [
        'type'   => UInt8Type::class,
        'decode' => function ($value, DecodeContext $context) {
            return match ($context->message()->get('MessageBodyProperty')['VersionFlag']) {
                1 => '2019',
                default => '2013'
            };
        },
        'length' => 0,
    ],
    'ProtocolVersion'     => [
        'type'   => UInt8Type::class,
        'length' => function (DecodeContext $context) {
            return match ($context->message()->get('Version')) {
                '2013' => 0,
                default => 1
            };
        },
    ],
    'TerminalPhoneNo'     => [
        'type'   => BCDType::class,
        'length' => function (DecodeContext $context) {
            return match ($context->message()->get('Version')) {
                '2013' => 6,
                default => 10
            };
        },
    ],
    'MsgNum'              => [
        'type' => UInt16Type::class,
    ],
    'PackageCount'        => [
        'type'   => UInt16Type::class,
        'length' => function (DecodeContext $context) {
            return $context->message()->get('MessageBodyProperty')['IsPackage'] == '1' ? 2 : 0;
        },
    ],
    'PackageIndex'        => [
        'type'   => UInt16Type::class,
        'length' => function (DecodeContext $context) {
            return $context->message()->get('MessageBodyProperty')['IsPackage'] == '1' ? 2 : 0;
        },
    ],
    'AreaID'              => [
        'type' => UInt16Type::class,
    ],
    'CityOrCountyId'      => [
        'type' => UInt16Type::class,
    ],
    'MakerId'             => [
        'type'     => StringType::class,
        'encoding' => 'GBK',
        'length'   => function (DecodeContext $context) {
            return match ($context->message()->get('Version')) {
                '2013' => 5,
                default => 11
            };
        },
        'decode'   => function ($value) {
            return trim($value, "\x00");
        },
    ],
    'TerminalModel'       => [
        'type'     => StringType::class,
        'encoding' => 'GBK',
        'length'   => function (DecodeContext $context) {
            return match ($context->message()->get('Version')) {
                '2013' => 20,
                default => 30
            };
        },
        'decode'   => function ($value) {
            return trim($value, "\x00");
        },
    ],
    'TerminalId'          => [
        'type'     => StringType::class,
        'encoding' => 'GBK',
        'length'   => function (DecodeContext $context) {
            return match ($context->message()->get('Version')) {
                '2013' => 7,
                default => 30
            };
        },
        'decode'   => function ($value) {
            return trim($value, "\x00");
        },
    ],
    'PlateColor'          => [
        'type' => UInt8Type::class,
    ],
    'PlateNo'             => [
        'type'     => StringType::class,
        'encoding' => 'GBK',
        'count'    => '*',
    ],
];

$binary = EncodeMessage::fromHex($data);

$message = MessagePackCodec::build()->decode($binary, $schema);

echo json_encode(
    $message->toArray(),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);

// {
//     "MsgId": 256,
//     "MessageBodyProperty": {
//     "Reserved": 0,
//         "VersionFlag": 1,
//         "IsPackage": 0,
//         "Encrypt": 0,
//         "DataLength": 85
//     },
//     "Version": "2019",
//     "ProtocolVersion": 1,
//     "TerminalPhoneNo": "00000000013062509060",
//     "MsgNum": 4,
//     "PackageCount": null,
//     "PackageIndex": null,
//     "AreaID": 50,
//     "CityOrCountyId": 1234,
//     "MakerId": "CMSOFT.CN",
//     "TerminalModel": "CM808-100",
//     "TerminalId": "YBAUUQ0XROPDBYMLCKUSBNYA4WLAPU",
//     "PlateColor": 2,
//     "PlateNo": "测A-011G5"
// }
