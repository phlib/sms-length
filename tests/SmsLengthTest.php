<?php

namespace Phlib\SmsLength;

use Phlib\SmsLength\Exception\InvalidArgumentException;

class SmsLengthTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @see https://en.wikipedia.org/wiki/GSM_03.38
     * @var string[] Printable characters in GSM 03.38 7-bit default alphabet
     *               0x1B deliberately excluded as it's used to escape to extension table
     */
    const GSM0338_BASIC =
        '@'  . 'Δ' . ' ' . '0' . '¡' . 'P' . '¿' . 'p' .
        '£'  . '_' . '!' . '1' . 'A' . 'Q' . 'a' . 'q' .
        '$'  . 'Φ' . '"' . '2' . 'B' . 'R' . 'b' . 'r' .
        '¥'  . 'Γ' . '#' . '3' . 'C' . 'S' . 'c' . 's' .
        'è'  . 'Λ' . '¤' . '4' . 'D' . 'T' . 'd' . 't' .
        'é'  . 'Ω' . '%' . '5' . 'E' . 'U' . 'e' . 'u' .
        'ù'  . 'Π' . '&' . '6' . 'F' . 'V' . 'f' . 'v' .
        'ì'  . 'Ψ' . "'" . '7' . 'G' . 'W' . 'g' . 'w' .
        'ò'  . 'Σ' . '(' . '8' . 'H' . 'X' . 'h' . 'x' .
        'Ç'  . 'Θ' . ')' . '9' . 'I' . 'Y' . 'i' . 'y' .
        "\n" . 'Ξ' . '*' . ':' . 'J' . 'Z' . 'j' . 'z' .
        'Ø'        . '+' . ';' . 'K' . 'Ä' . 'k' . 'ä' .
        'ø'  . 'Æ' . ',' . '<' . 'L' . 'Ö' . 'l' . 'ö' .
        "\r" . 'æ' . '-' . '=' . 'M' . 'Ñ' . 'm' . 'ñ' .
        'Å'  . 'ß' . '.' . '>' . 'N' . 'Ü' . 'n' . 'ü' .
        'å'  . 'É' . '/' . '?' . 'O' . '§' . 'o' . 'à'
    ;

    /**
     * @see https://en.wikipedia.org/wiki/GSM_03.38
     * @var string[] Printable characters in GSM 03.38 7-bit extension table
     */
    const GSM0338_EXTENDED = '|^€{}[~]\\';

    /**
     * @dataProvider providerSize
     * @param string $content
     * @param string $encoding
     * @param int $characters
     * @param int $messageCount
     * @param int $upperBreak
     */
    public function testSize($content, $encoding, $characters, $messageCount, $upperBreak)
    {
        $size = new SmsLength($content);

        $this->assertSame($encoding, $size->getEncoding());
        $this->assertSame($characters, $size->getSize());
        $this->assertSame($messageCount, $size->getMessageCount());
        $this->assertSame($upperBreak, $size->getUpperBreakpoint());

        $this->assertTrue($size->validate());
    }

    public function providerSize()
    {
        return [
            [self::GSM0338_BASIC, '7-bit', 127, 1, 160],
            [self::GSM0338_EXTENDED, '7-bit', 18, 1, 160],
            ['simple msg', '7-bit', 10, 1, 160],
            ['simple msg plus € extended char', '7-bit', 32, 1, 160],

            // http://www.fileformat.info/info/unicode/char/2022/index.htm
            ['simple msg plus • 1 UTF-16 code unit char', 'ucs-2', 41, 1, 70],

            // http://www.fileformat.info/info/unicode/char/1f4f1/index.htm
            ["simple msg plus \xf0\x9f\x93\xb1 2 UTF-16 code unit char", 'ucs-2', 42, 1, 70],

            // long 7-bit
            [str_repeat('simple msg', 50), '7-bit', 500, 4, 612],
            [str_repeat('exact max', 153), '7-bit', 1377, 9, 1377],

            // long 7-bit extended
            [str_repeat(self::GSM0338_EXTENDED, 40), '7-bit', 720, 5, 765],
            [str_repeat(self::GSM0338_EXTENDED, 76), '7-bit', 1368, 9, 1377],

            // long UCS-2
            [str_repeat("simple msg plus •", 20), 'ucs-2', 340, 6, 402],
            [str_repeat("simple msg plus \xf0\x9f\x93\xb1", 20), 'ucs-2', 360, 6, 402],
            [str_repeat("exact•max", 67), 'ucs-2', 603, 9, 603],

            // empty
            ['', '7-bit', 0, 1, 160],

            [
                'The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog. The quick brown f[x jumps over the lazy dog. The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the.',
                '7-bit',
                306,
                3,
                459,
            ],
            [
                str_repeat('🌐', 67),
                'ucs-2',
                134,
                3,
                201
            ],
        ];
    }

    /**
     * @dataProvider providerTooLarge
     * @param string $content
     * @param string $encoding
     * @param int $characters
     * @param int $messageCount
     * @param int $upperBreak
     * @medium Expect tests to take >1 but <10
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Message count cannot exceed 255
     */
    public function testTooLarge($content, $encoding, $characters, $messageCount, $upperBreak)
    {
        $size = new SmsLength($content);

        $this->assertSame($encoding, $size->getEncoding());
        $this->assertSame($characters, $size->getSize());
        $this->assertSame($messageCount, $size->getMessageCount());
        $this->assertSame($upperBreak, $size->getUpperBreakpoint());

        // Trigger exception
        $size->validate();
    }

    public function providerTooLarge()
    {
        // 7-bit max is 39015 (255 * 153)
        // ucs-2 max is 17085 (255 * 67)
        return [
            // long 7-bit, 10 * 3902 = 39020
            [str_repeat('simple msg', 3902), '7-bit', 39020, 256, 39168],

            // long 7-bit extended, 18 * 2168 = 39024
            [str_repeat(self::GSM0338_EXTENDED, 2168), '7-bit', 39024, 256, 39168],

            // long UCS-2 single, 17 * 1006 = 17102
            // long UCS-2 double, 18 * 950 = 17100
            [str_repeat("simple msg plus •", 1006), 'ucs-2', 17102, 256, 17152],
            [str_repeat("simple msg plus \xf0\x9f\x93\xb1", 950), 'ucs-2', 17100, 256, 17152]
        ];
    }
}
