<?php

declare(strict_types=1);

namespace Phlib\SmsLength;

use Phlib\SmsLength\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SmsLengthTest extends TestCase
{
    /**
     * @see https://en.wikipedia.org/wiki/GSM_03.38
     * @var string[] Printable characters in GSM 03.38 7-bit default alphabet
     *               0x1B deliberately excluded as it's used to escape to extension table
     */
    private const GSM0338_BASIC =
        '@' . 'Δ' . ' ' . '0' . '¡' . 'P' . '¿' . 'p' .
        '£' . '_' . '!' . '1' . 'A' . 'Q' . 'a' . 'q' .
        '$' . 'Φ' . '"' . '2' . 'B' . 'R' . 'b' . 'r' .
        '¥' . 'Γ' . '#' . '3' . 'C' . 'S' . 'c' . 's' .
        'è' . 'Λ' . '¤' . '4' . 'D' . 'T' . 'd' . 't' .
        'é' . 'Ω' . '%' . '5' . 'E' . 'U' . 'e' . 'u' .
        'ù' . 'Π' . '&' . '6' . 'F' . 'V' . 'f' . 'v' .
        'ì' . 'Ψ' . "'" . '7' . 'G' . 'W' . 'g' . 'w' .
        'ò' . 'Σ' . '(' . '8' . 'H' . 'X' . 'h' . 'x' .
        'Ç' . 'Θ' . ')' . '9' . 'I' . 'Y' . 'i' . 'y' .
        "\n" . 'Ξ' . '*' . ':' . 'J' . 'Z' . 'j' . 'z' .
        'Ø' . '+' . ';' . 'K' . 'Ä' . 'k' . 'ä' .
        'ø' . 'Æ' . ',' . '<' . 'L' . 'Ö' . 'l' . 'ö' .
        "\r" . 'æ' . '-' . '=' . 'M' . 'Ñ' . 'm' . 'ñ' .
        'Å' . 'ß' . '.' . '>' . 'N' . 'Ü' . 'n' . 'ü' .
        'å' . 'É' . '/' . '?' . 'O' . '§' . 'o' . 'à'
    ;

    /**
     * @see https://en.wikipedia.org/wiki/GSM_03.38
     * @var string[] Printable characters in GSM 03.38 7-bit extension table
     */
    private const GSM0338_EXTENDED = '|^€{}[~]\\';

    /**
     * @dataProvider providerSize
     */
    public function testSize(
        string $content,
        string $encoding,
        int $characters,
        int $messageCount,
        int $upperBreak
    ): void {
        $size = new SmsLength($content);

        static::assertSame($encoding, $size->getEncoding());
        static::assertSame($characters, $size->getSize());
        static::assertSame($messageCount, $size->getMessageCount());
        static::assertSame($upperBreak, $size->getUpperBreakpoint());

        static::assertTrue($size->validate());
    }

    public function providerSize(): array
    {
        return [
            'gsm-basic' => [self::GSM0338_BASIC, SmsLength::ENCODING_7BIT, 127, 1, 160],
            'gsm-extended' => [self::GSM0338_EXTENDED, SmsLength::ENCODING_7BIT, 18, 1, 160],
            'simple-basic' => ['simple msg', SmsLength::ENCODING_7BIT, 10, 1, 160],
            'simple-extended' => ['simple msg plus € extended char', SmsLength::ENCODING_7BIT, 32, 1, 160],

            // http://www.fileformat.info/info/unicode/char/2022/index.htm
            'utf16-1' => ['simple msg plus • 1 UTF-16 code unit char', SmsLength::ENCODING_UCS2, 41, 1, 70],

            // http://www.fileformat.info/info/unicode/char/1f4f1/index.htm
            'utf16-2' => ["simple msg plus \xf0\x9f\x93\xb1 2 UTF-16 code unit char", SmsLength::ENCODING_UCS2, 42, 1, 70],

            // long 7-bit
            'long-gsm-simple' => [str_repeat('simple msg', 50), SmsLength::ENCODING_7BIT, 500, 4, 612],
            'long-gsm-exact' => [str_repeat('exact max', 153), SmsLength::ENCODING_7BIT, 1377, 9, 1377],

            // long 7-bit extended
            'long-gsm-ex-1' => [str_repeat(self::GSM0338_EXTENDED, 40), SmsLength::ENCODING_7BIT, 720, 5, 765],
            'long-gsm-ex-2' => [str_repeat(self::GSM0338_EXTENDED, 76), SmsLength::ENCODING_7BIT, 1368, 9, 1377],

            // long UCS-2
            'long-ucs-1' => [str_repeat('simple msg plus •', 20), SmsLength::ENCODING_UCS2, 340, 6, 402],
            'long-ucs-2' => [str_repeat("simple msg plus \xf0\x9f\x93\xb1", 20), SmsLength::ENCODING_UCS2, 360, 6, 402],
            'long-ucs-exact' => [str_repeat('exact•max', 67), SmsLength::ENCODING_UCS2, 603, 9, 603],

            // empty
            'empty' => ['', SmsLength::ENCODING_7BIT, 0, 1, 160],
        ];
    }

    /**
     * @dataProvider providerTooLarge
     * @medium Expect tests to take >1 but <10
     */
    public function testTooLarge(
        string $content,
        string $encoding,
        int $characters,
        int $messageCount,
        int $upperBreak
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message count cannot exceed 255');

        $size = new SmsLength($content);

        static::assertSame($encoding, $size->getEncoding());
        static::assertSame($characters, $size->getSize());
        static::assertSame($messageCount, $size->getMessageCount());
        static::assertSame($upperBreak, $size->getUpperBreakpoint());

        // Trigger exception
        $size->validate();
    }

    public function providerTooLarge(): array
    {
        // 7-bit max is 39015 (255 * 153)
        // ucs-2 max is 17085 (255 * 67)
        return [
            // long 7-bit, 10 * 3902 = 39020
            'basic' => [str_repeat('simple msg', 3902), SmsLength::ENCODING_7BIT, 39020, 256, 39168],

            // long 7-bit extended, 18 * 2168 = 39024
            'extended' => [str_repeat(self::GSM0338_EXTENDED, 2168), SmsLength::ENCODING_7BIT, 39024, 256, 39168],

            // long UCS-2 single, 17 * 1006 = 17102
            // long UCS-2 double, 18 * 950 = 17100
            'ucs-1' => [str_repeat('simple msg plus •', 1006), SmsLength::ENCODING_UCS2, 17102, 256, 17152],
            'ucs-2' => [str_repeat("simple msg plus \xf0\x9f\x93\xb1", 950), SmsLength::ENCODING_UCS2, 17100, 256, 17152],
        ];
    }

    /**
     * @dataProvider providerTruncate
     */
    public function testTruncate($content, $parts, $expected)
    {
        $original = new SmsLength($content);

        $new = $original->truncate($parts);

        $this->assertSame($parts, $new->getMessageCount());
        $this->assertSame($expected, $new->getMessageContent());
    }

    public function providerTruncate()
    {
        return [
            'message under one part' => [
                'message' => 'La La La',
                'parts' => 1,
                'expected' => 'La La La',
            ],
            'message over one part, gsm7' => [
                'message' => str_repeat('abcd', 45),
                'parts' => 1,
                'expected' => str_repeat('abcd', 40),
            ],
            'message over two part, gsm7' => [
                'message' => str_repeat('abcd', 100),
                'parts' => 2,
                'expected' => str_repeat('abcd', 76) . 'ab',
            ],
            'message over one part, gsm7 + ext' => [
                'message' => str_repeat('abcd[', 30),
                'parts' => 1,
                'expected' => str_repeat('abcd[', 26) . 'abcd', // each part is 6 chars, should be 26 reps + 4 chars leftover
            ],
            'message over one part, uc2 1 part char' => [
                'message' => str_repeat('•', 100),
                'parts' => 1,
                'expected' => str_repeat('•', 70),
            ],
            'message over one part, uc2 3 byte' => [
                'message' => str_repeat('⏩', 100),
                'parts' => 1,
                'expected' => str_repeat('⏩', 70),
            ],
            'message over one part, uc2 4 byte' => [
                'message' => str_repeat('🌐', 100),
                'parts' => 1,
                'expected' => str_repeat('🌐', 35),
            ],
            'message over two parts, uc2 4 byte' => [
                'message' => str_repeat('🌐', 200),
                'parts' => 2,
                'expected' => str_repeat('🌐', 66),
            ]
        ];
    }
}
