<?php

namespace Jundayw\MessagePackCodec\Support;

enum Format: string
{
    /*
     * -------------------------------------------------------------------------
     * String
     * -------------------------------------------------------------------------
     */

    /**
     * NUL-padded string.
     */
    case STRING_NUL = 'a';

    /**
     * SPACE-padded string.
     */
    case STRING_SPACE = 'A';

    /**
     * NUL-terminated string.
     */
    case STRING_NUL_TERMINATED = 'Z';

    /*
     * -------------------------------------------------------------------------
     * Hexadecimal
     * -------------------------------------------------------------------------
     */

    /**
     * Hexadecimal string with low nibble first.
     */
    case HEX_LOW_FIRST = 'h';

    /**
     * Hexadecimal string with high nibble first.
     */
    case HEX_HIGH_FIRST = 'H';

    /*
     * -------------------------------------------------------------------------
     * Integer
     * -------------------------------------------------------------------------
     */

    /**
     * Signed 8-bit integer.
     */
    case INT8 = 'c';

    /**
     * Unsigned 8-bit integer.
     */
    case UINT8 = 'C';

    /**
     * Signed 16-bit integer in host byte order.
     */
    case INT16_HOST = 's';

    /**
     * Unsigned 16-bit integer in host byte order.
     */
    case UINT16_HOST = 'S';

    /**
     * Unsigned 16-bit integer in big-endian byte order.
     */
    case UINT16_BIG = 'n';

    /**
     * Unsigned 16-bit integer in little-endian byte order.
     */
    case UINT16_LITTLE = 'v';

    /**
     * Machine-dependent signed integer.
     */
    case INT_NATIVE = 'i';

    /**
     * Machine-dependent unsigned integer.
     */
    case UINT_NATIVE = 'I';

    /**
     * Signed 32-bit integer in host byte order.
     */
    case INT32_HOST = 'l';

    /**
     * Unsigned 32-bit integer in host byte order.
     */
    case UINT32_HOST = 'L';

    /**
     * Unsigned 32-bit integer in big-endian byte order.
     */
    case UINT32_BIG = 'N';

    /**
     * Unsigned 32-bit integer in little-endian byte order.
     */
    case UINT32_LITTLE = 'V';

    /**
     * Signed 64-bit integer in host byte order.
     */
    case INT64_HOST = 'q';

    /**
     * Unsigned 64-bit integer in host byte order.
     */
    case UINT64_HOST = 'Q';

    /**
     * Unsigned 64-bit integer in big-endian byte order.
     */
    case UINT64_BIG = 'J';

    /**
     * Unsigned 64-bit integer in little-endian byte order.
     */
    case UINT64_LITTLE = 'P';

    /*
     * -------------------------------------------------------------------------
     * Floating Point
     * -------------------------------------------------------------------------
     */

    /**
     * Single-precision floating-point value in host byte order.
     */
    case FLOAT_HOST = 'f';

    /**
     * Single-precision floating-point value in little-endian byte order.
     */
    case FLOAT_LITTLE = 'g';

    /**
     * Single-precision floating-point value in big-endian byte order.
     */
    case FLOAT_BIG = 'G';

    /**
     * Double-precision floating-point value in host byte order.
     */
    case DOUBLE_HOST = 'd';

    /**
     * Double-precision floating-point value in little-endian byte order.
     */
    case DOUBLE_LITTLE = 'e';

    /**
     * Double-precision floating-point value in big-endian byte order.
     */
    case DOUBLE_BIG = 'E';

    /*
     * -------------------------------------------------------------------------
     * Control
     * -------------------------------------------------------------------------
     */

    /**
     * NUL byte.
     */
    case NUL = 'x';

    /**
     * Back up one byte.
     */
    case BACK = 'X';

    /**
     * NUL-pad to an absolute position.
     */
    case POSITION = '@';

    /**
     * Get the size of the format in bytes.
     *
     * For variable-length and control formats, the size depends on
     * the repeat count or the current packing context.
     *
     * @return float|null
     */
    public function size(): ?float
    {
        return match ($this) {
            self::INT8,
            self::UINT8,
            self::NUL => 1,

            self::INT16_HOST,
            self::UINT16_HOST,
            self::UINT16_BIG,
            self::UINT16_LITTLE => 2,

            self::INT_NATIVE,
            self::UINT_NATIVE => PHP_INT_SIZE,

            self::INT32_HOST,
            self::UINT32_HOST,
            self::UINT32_BIG,
            self::UINT32_LITTLE,
            self::FLOAT_HOST,
            self::FLOAT_LITTLE,
            self::FLOAT_BIG => 4,

            self::INT64_HOST,
            self::UINT64_HOST,
            self::UINT64_BIG,
            self::UINT64_LITTLE,
            self::DOUBLE_HOST,
            self::DOUBLE_LITTLE,
            self::DOUBLE_BIG => 8,

            self::STRING_NUL,
            self::STRING_SPACE,
            self::STRING_NUL_TERMINATED,
            self::HEX_LOW_FIRST,
            self::HEX_HIGH_FIRST,
            self::BACK,
            self::POSITION => null,
        };
    }

    /**
     * Determine whether the format represents an integer.
     *
     * @return bool
     */
    public function isInteger(): bool
    {
        return match ($this) {
            self::INT8,
            self::UINT8,
            self::INT16_HOST,
            self::UINT16_HOST,
            self::UINT16_BIG,
            self::UINT16_LITTLE,
            self::INT_NATIVE,
            self::UINT_NATIVE,
            self::INT32_HOST,
            self::UINT32_HOST,
            self::UINT32_BIG,
            self::UINT32_LITTLE,
            self::INT64_HOST,
            self::UINT64_HOST,
            self::UINT64_BIG,
            self::UINT64_LITTLE => true,

            default => false,
        };
    }

    /**
     * Determine whether the format represents a 64-bit integer.
     *
     * @return bool
     */
    public function isInt64(): bool
    {
        return match ($this) {
            self::INT64_HOST,
            self::UINT64_HOST,
            self::UINT64_BIG,
            self::UINT64_LITTLE => true,

            default => false,
        };
    }

    /**
     * Determine whether the format represents a floating-point value.
     *
     * @return bool
     */
    public function isFloat(): bool
    {
        return match ($this) {
            self::FLOAT_HOST,
            self::FLOAT_LITTLE,
            self::FLOAT_BIG,
            self::DOUBLE_HOST,
            self::DOUBLE_LITTLE,
            self::DOUBLE_BIG => true,

            default => false,
        };
    }

    /**
     * Determine whether the format represents a single-precision float.
     *
     * @return bool
     */
    public function isFloat32(): bool
    {
        return match ($this) {
            self::FLOAT_HOST,
            self::FLOAT_LITTLE,
            self::FLOAT_BIG => true,

            default => false,
        };
    }

    /**
     * Determine whether the format represents a double-precision float.
     *
     * @return bool
     */
    public function isFloat64(): bool
    {
        return match ($this) {
            self::DOUBLE_HOST,
            self::DOUBLE_LITTLE,
            self::DOUBLE_BIG => true,

            default => false,
        };
    }

    /**
     * Determine whether the format represents a string.
     *
     * @return bool
     */
    public function isString(): bool
    {
        return match ($this) {
            self::STRING_NUL,
            self::STRING_SPACE,
            self::STRING_NUL_TERMINATED => true,

            default => false,
        };
    }

    /**
     * Determine whether the format represents hexadecimal data.
     *
     * @return bool
     */
    public function isHex(): bool
    {
        return match ($this) {
            self::HEX_LOW_FIRST,
            self::HEX_HIGH_FIRST => true,

            default => false,
        };
    }

    /**
     * Determine whether the format is a control format.
     *
     * @return bool
     */
    public function isControl(): bool
    {
        return match ($this) {
            self::NUL,
            self::BACK,
            self::POSITION => true,

            default => false,
        };
    }

    /**
     * Determine whether the format is signed.
     *
     * @return bool
     */
    public function isSigned(): bool
    {
        return match ($this) {
            self::INT8,
            self::INT16_HOST,
            self::INT_NATIVE,
            self::INT32_HOST,
            self::INT64_HOST => true,

            default => false,
        };
    }

    /**
     * Determine whether the format is unsigned.
     *
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return match ($this) {
            self::UINT8,
            self::UINT16_HOST,
            self::UINT16_BIG,
            self::UINT16_LITTLE,
            self::UINT_NATIVE,
            self::UINT32_HOST,
            self::UINT32_BIG,
            self::UINT32_LITTLE,
            self::UINT64_HOST,
            self::UINT64_BIG,
            self::UINT64_LITTLE => true,

            default => false,
        };
    }

    /**
     * Get the bit width of the format.
     *
     * @return int|null
     */
    public function bits(): ?int
    {
        if (is_null($size = $this->size())) {
            return null;
        }

        return $size * 8;
    }

    /**
     * Get the byte order of the format.
     *
     * @return Endian|null
     */
    public function endianness(): ?Endian
    {
        return match ($this) {
            self::INT8,
            self::UINT8,
            self::INT_NATIVE,
            self::UINT_NATIVE,
            self::STRING_NUL,
            self::STRING_SPACE,
            self::STRING_NUL_TERMINATED,
            self::HEX_LOW_FIRST,
            self::HEX_HIGH_FIRST,
            self::NUL,
            self::BACK,
            self::POSITION => null,

            self::INT16_HOST,
            self::UINT16_HOST,
            self::INT32_HOST,
            self::UINT32_HOST,
            self::INT64_HOST,
            self::UINT64_HOST,
            self::FLOAT_HOST,
            self::DOUBLE_HOST => Endian::HOST,

            self::UINT16_BIG,
            self::UINT32_BIG,
            self::UINT64_BIG,
            self::FLOAT_BIG,
            self::DOUBLE_BIG => Endian::BIG,

            self::UINT16_LITTLE,
            self::UINT32_LITTLE,
            self::UINT64_LITTLE,
            self::FLOAT_LITTLE,
            self::DOUBLE_LITTLE => Endian::LITTLE,
        };
    }

    /**
     * Determine whether the format uses host byte order.
     *
     * @return bool
     */
    public function isHostEndian(): bool
    {
        return $this->endianness() === Endian::HOST;
    }

    /**
     * Determine whether the format uses big-endian byte order.
     *
     * @return bool
     */
    public function isBigEndian(): bool
    {
        return $this->endianness() === Endian::BIG;
    }

    /**
     * Determine whether the format uses little-endian byte order.
     *
     * @return bool
     */
    public function isLittleEndian(): bool
    {
        return $this->endianness() === Endian::LITTLE;
    }

    /**
     * Determine if the runtime environment uses little-endian byte order
     *
     * @return bool
     */
    public function isLittleHost(): bool
    {
        return pack('S', 1) === "\x01\x00";
    }

    /**
     * Determine if the runtime environment uses big-endian byte order
     *
     * @return bool
     */
    public function isBigHost(): bool
    {
        return pack('S', 1) === "\x00\x01";
    }
}
