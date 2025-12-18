<?php

namespace App\Enums;

enum MbEncoding: string
{
    // ASCII and Extended ASCII
    case ASCII = 'ASCII';
    case CP1252 = 'CP1252'; // Windows-1252, Western European
    case ISO_8859_1 = 'ISO-8859-1'; // Latin-1, Western European
    case ISO_8859_2 = 'ISO-8859-2'; // Latin-2, Central European
    case ISO_8859_3 = 'ISO-8859-3'; // Latin-3, South European
    case ISO_8859_4 = 'ISO-8859-4'; // Latin-4, North European
    case ISO_8859_5 = 'ISO-8859-5'; // Cyrillic
    case ISO_8859_6 = 'ISO-8859-6'; // Arabic
    case ISO_8859_7 = 'ISO-8859-7'; // Greek
    case ISO_8859_8 = 'ISO-8859-8'; // Hebrew
    case ISO_8859_9 = 'ISO-8859-9'; // Turkish
    case ISO_8859_10 = 'ISO-8859-10'; // Nordic
    case ISO_8859_13 = 'ISO-8859-13'; // Baltic Rim
    case ISO_8859_14 = 'ISO-8859-14'; // Celtic
    case ISO_8859_15 = 'ISO-8859-15'; // Latin-9 (updated Latin-1 with Euro sign)
    case ISO_8859_16 = 'ISO-8859-16'; // South-Eastern European

    // Unicode encodings
    case UTF_8 = 'UTF-8';
    case UTF_7 = 'UTF-7';
    case UTF_7_IMAP = 'UTF7-IMAP';
    case UTF_16 = 'UTF-16';
    case UTF_16BE = 'UTF-16BE';
    case UTF_16LE = 'UTF-16LE';
    case UTF_32 = 'UTF-32';
    case UTF_32BE = 'UTF-32BE';
    case UTF_32LE = 'UTF-32LE';

    // Japanese encodings
    case EUC_JP = 'EUC-JP';
    case SJIS = 'SJIS';
    case SJIS_WIN = 'SJIS-win';
    case JIS = 'JIS';
    case ISO_2022_JP = 'ISO-2022-JP';
    case CP932 = 'CP932'; // Microsoft's version of Shift-JIS

    // Chinese encodings
    case EUC_CN = 'EUC-CN';
    case GB2312 = 'GB2312';
    case GBK = 'GBK';
    case GB18030 = 'GB18030';
    case BIG5 = 'BIG5';
    case BIG5_HKSCS = 'BIG5-HKSCS';
    case EUC_TW = 'EUC-TW';
    case CP950 = 'CP950'; // Traditional Chinese (Big5)
    case CP936 = 'CP936'; // Simplified Chinese (GBK)

    // Korean encodings
    case EUC_KR = 'EUC-KR';
    case UHC = 'UHC'; // Unified Hangul Code
    case CP949 = 'CP949'; // Korean (EUC-KR extension)

    // Cyrillic encodings
    case KOI8_R = 'KOI8-R'; // Russian
    case KOI8_U = 'KOI8-U'; // Ukrainian
    case KOI8_RU = 'KOI8-RU';
    case CP1251 = 'CP1251'; // Windows-1251, Cyrillic

    // Other Windows encodings
    case CP1250 = 'CP1250'; // Central European
    case CP1253 = 'CP1253'; // Greek
    case CP1254 = 'CP1254'; // Turkish
    case CP1255 = 'CP1255'; // Hebrew
    case CP1256 = 'CP1256'; // Arabic
    case CP1257 = 'CP1257'; // Baltic

    // Other Asian encodings
    case TIS_620 = 'TIS-620'; // Thai
    case ISO_8859_11 = 'ISO-8859-11'; // Thai
    case CP874 = 'CP874'; // Thai (Windows)

    // Special/auto-detection
    case AUTO = 'auto'; // Auto-detect encoding
    case BASE64 = 'BASE64';
    case HTML_ENTITIES = 'HTML-ENTITIES';
    case QUOTED_PRINTABLE = 'Quoted-Printable';
    case UUENCODE = 'UUENCODE';

    /**
     * Check if this encoding is a Unicode encoding
     */
    public function isUnicode(): bool
    {
        return str_starts_with($this->value, 'UTF');
    }

    /**
     * Check if this encoding is a Windows codepage encoding
     */
    public function isWindowsEncoding(): bool
    {
        return str_starts_with($this->value, 'CP');
    }

    /**
     * Check if this encoding is an ISO-8859 encoding
     */
    public function isISO8859(): bool
    {
        return str_starts_with($this->value, 'ISO-8859');
    }

    /**
     * Check if this encoding is for Asian languages
     */
    public function isAsianEncoding(): bool
    {
        return in_array($this, [
            self::EUC_JP, self::SJIS, self::JIS, self::ISO_2022_JP,
            self::EUC_CN, self::GB2312, self::GBK, self::GB18030,
            self::BIG5, self::BIG5_HKSCS, self::EUC_TW,
            self::EUC_KR, self::UHC, self::CP949,
            self::TIS_620, self::ISO_8859_11, self::CP874
        ], true);
    }

    /**
     * Get encoding name for display
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::ASCII => 'ASCII',
            self::CP1252 => 'Windows-1252 (Western European)',
            self::ISO_8859_1 => 'ISO-8859-1 (Latin-1)',
            self::UTF_8 => 'UTF-8',
            self::UTF_16 => 'UTF-16',
            self::UTF_32 => 'UTF-32',
            self::EUC_JP => 'EUC-JP (Japanese)',
            self::SJIS => 'Shift-JIS (Japanese)',
            self::GB2312 => 'GB2312 (Simplified Chinese)',
            self::BIG5 => 'Big5 (Traditional Chinese)',
            self::EUC_KR => 'EUC-KR (Korean)',
            self::CP1251 => 'Windows-1251 (Cyrillic)',
            self::KOI8_R => 'KOI8-R (Russian)',
            default => $this->value,
        };
    }

    /**
     * Get common aliases for this encoding
     */
    public function getAliases(): array
    {
        return match($this) {
            self::ISO_8859_1 => ['LATIN1', 'LATIN-1'],
            self::CP1252 => ['WIN1252', 'WINDOWS-1252'],
            self::CP1251 => ['WIN1251', 'WINDOWS-1251'],
            self::SJIS => ['SHIFT_JIS', 'SHIFTJIS'],
            self::CP932 => ['SHIFT_JIS', 'SHIFTJIS'],
            self::CP936 => ['GBK'],
            self::CP949 => ['UHC'],
            self::CP950 => ['BIG5'],
            self::ISO_8859_15 => ['LATIN9', 'LATIN-9'],
            default => [],
        };
    }

    /**
     * Find encoding by alias or value (case-insensitive)
     */
    public static function tryFromAlias(string $value): ?self
    {
        $value = strtoupper(trim($value));

        // Check common aliases first
        $fromAlias = match($value) {
            'LATIN1', 'LATIN-1' => self::ISO_8859_1,
            'WIN1252', 'WINDOWS-1252' => self::CP1252,
            'WIN1251', 'WINDOWS-1251' => self::CP1251,
            'WIN1250', 'WINDOWS-1250' => self::CP1250,
            'WIN1253', 'WINDOWS-1253' => self::CP1253,
            'WIN1254', 'WINDOWS-1254' => self::CP1254,
            'WIN1255', 'WINDOWS-1255' => self::CP1255,
            'WIN1256', 'WINDOWS-1256' => self::CP1256,
            'WIN1257', 'WINDOWS-1257' => self::CP1257,
            'SHIFT_JIS', 'SHIFTJIS' => self::SJIS,
            'LATIN9', 'LATIN-9' => self::ISO_8859_15,
            default => null,
        };

        if ($fromAlias !== null) {
            return $fromAlias;
        }

        // Check exact enum values
        foreach (self::cases() as $case) {
            if (strtoupper($case->value) === $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Get all available encodings as an array of values
     */
    public static function getAllValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all encodings grouped by category
     */
    public static function getGroupedEncodings(): array
    {
        return [
            'ASCII & Extended ASCII' => [
                self::ASCII,
                self::ISO_8859_1,
                self::ISO_8859_2,
                self::ISO_8859_3,
                self::ISO_8859_4,
                self::ISO_8859_5,
                self::ISO_8859_6,
                self::ISO_8859_7,
                self::ISO_8859_8,
                self::ISO_8859_9,
                self::ISO_8859_10,
                self::ISO_8859_13,
                self::ISO_8859_14,
                self::ISO_8859_15,
                self::ISO_8859_16,
            ],
            'Unicode' => [
                self::UTF_8,
                self::UTF_7,
                self::UTF_7_IMAP,
                self::UTF_16,
                self::UTF_16BE,
                self::UTF_16LE,
                self::UTF_32,
                self::UTF_32BE,
                self::UTF_32LE,
            ],
            'Windows Codepages' => [
                self::CP1252,
                self::CP1251,
                self::CP1250,
                self::CP1253,
                self::CP1254,
                self::CP1255,
                self::CP1256,
                self::CP1257,
                self::CP932,
                self::CP936,
                self::CP949,
                self::CP950,
                self::CP874,
            ],
            'Japanese' => [
                self::EUC_JP,
                self::SJIS,
                self::SJIS_WIN,
                self::JIS,
                self::ISO_2022_JP,
                self::CP932,
            ],
            'Chinese' => [
                self::EUC_CN,
                self::GB2312,
                self::GBK,
                self::GB18030,
                self::BIG5,
                self::BIG5_HKSCS,
                self::EUC_TW,
                self::CP936,
                self::CP950,
            ],
            'Korean' => [
                self::EUC_KR,
                self::UHC,
                self::CP949,
            ],
            'Cyrillic' => [
                self::KOI8_R,
                self::KOI8_U,
                self::KOI8_RU,
                self::CP1251,
                self::ISO_8859_5,
            ],
            'Other Asian' => [
                self::TIS_620,
                self::ISO_8859_11,
                self::CP874,
            ],
            'Special' => [
                self::AUTO,
                self::BASE64,
                self::HTML_ENTITIES,
                self::QUOTED_PRINTABLE,
                self::UUENCODE,
            ],
        ];
    }

    /**
     * Check if the encoding is supported by mbstring extension
     */
    public function isMbSupported(): bool
    {
        return in_array($this->value, mb_list_encodings(), true);
    }

    /**
     * Convert string from this encoding to UTF-8
     */
    public function toUtf8(string $string): string|false
    {
        if ($this === self::UTF_8) {
            return $string;
        }

        return mb_convert_encoding($string, 'UTF-8', $this->value);
    }

    /**
     * Convert string from UTF-8 to this encoding
     */
    public function fromUtf8(string $string): string|false
    {
        if ($this === self::UTF_8) {
            return $string;
        }

        return mb_convert_encoding($string, $this->value, 'UTF-8');
    }
}
