<?php

namespace Phlib\SmsLength;

use Phlib\SmsLength\Exception\InvalidArgumentException;

/**
 * @package    Phlib\SmsLength
 * @license    LGPL-3.0
 */
class SmsLength
{
    /**
     * @var int Maximum characters in SMS with 7-bit encoding (3GPP TS 23.038 / GSM 03.38)
     */
    const MAXIMUM_CHARACTERS_7BIT_SINGLE = 160;

    /**
     * @var int Maximum characters in SMS with 7-bit encoding with UDH (3GPP TS 23.040)
     */
    const MAXIMUM_CHARACTERS_7BIT_CONCATENATED = 153;

    /**
     * @var int Maximum characters in SMS with UCS-2 encoding (3GPP TS 23.038 / GSM 03.38)
     */
    const MAXIMUM_CHARACTERS_UCS2_SINGLE = 70;

    /**
     * @var int Maximum characters in SMS with UCS-2 encoding with UDH (3GPP TS 23.040)
     */
    const MAXIMUM_CHARACTERS_UCS2_CONCATENATED = 67;

    /**
     * @var int Message cannot exceed size of 255 concatenated SMS (3GPP TS 23.040)
     */
    const MAXIMUM_CONCATENATED_SMS = 255;

    /**
     * @see https://en.wikipedia.org/wiki/GSM_03.38
     * @var string[] Printable characters in GSM 03.38 7-bit default alphabet
     *               0x1B deliberately excluded as it's used to escape to extension table
     */
    const GSM0338_BASIC = [
        '@'  , 'Δ' , ' ' , '0' , '¡' , 'P' , '¿' , 'p' ,
        '£'  , '_' , '!' , '1' , 'A' , 'Q' , 'a' , 'q' ,
        '$'  , 'Φ' , '"' , '2' , 'B' , 'R' , 'b' , 'r' ,
        '¥'  , 'Γ' , '#' , '3' , 'C' , 'S' , 'c' , 's' ,
        'è'  , 'Λ' , '¤' , '4' , 'D' , 'T' , 'd' , 't' ,
        'é'  , 'Ω' , '%' , '5' , 'E' , 'U' , 'e' , 'u' ,
        'ù'  , 'Π' , '&' , '6' , 'F' , 'V' , 'f' , 'v' ,
        'ì'  , 'Ψ' , "'" , '7' , 'G' , 'W' , 'g' , 'w' ,
        'ò'  , 'Σ' , '(' , '8' , 'H' , 'X' , 'h' , 'x' ,
        'Ç'  , 'Θ' , ')' , '9' , 'I' , 'Y' , 'i' , 'y' ,
        "\n" , 'Ξ' , '*' , ':' , 'J' , 'Z' , 'j' , 'z' ,
        'Ø'        , '+' , ';' , 'K' , 'Ä' , 'k' , 'ä' ,
        'ø'  , 'Æ' , ',' , '<' , 'L' , 'Ö' , 'l' , 'ö' ,
        "\r" , 'æ' , '-' , '=' , 'M' , 'Ñ' , 'm' , 'ñ' ,
        'Å'  , 'ß' , '.' , '>' , 'N' , 'Ü' , 'n' , 'ü' ,
        'å'  , 'É' , '/' , '?' , 'O' , '§' , 'o' , 'à'
    ];

    /**
     * @see https://en.wikipedia.org/wiki/GSM_03.38
     * @var string[] Printable characters in GSM 03.38 7-bit extension table
     */
    const GSM0338_EXTENDED = ['|', '^', '€', '{', '}', '[', '~', ']', '\\'];

    /**
     * @var string
     */
    private $encoding;

    /**
     * @var int
     */
    private $size;

    /**
     * @var int
     */
    private $messageCount;

    /**
     * Constructor
     *
     * @param string $messageContent
     * @throws InvalidArgumentException
     */
    public function __construct($messageContent)
    {
        $this->inspect($messageContent);
    }

    /**
     * Get name of GSM 03.38 encoding that would be required for the given content
     *
     * @return string '7-bit' or 'ucs-2'
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Get size of message as characters used in the determined encoding
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get number of messages that would be used to send the given content size
     *
     * @return int
     * @throws InvalidArgumentException
     */
    public function getMessageCount()
    {
        if ($this->messageCount > self::MAXIMUM_CONCATENATED_SMS) {
            throw new InvalidArgumentException('Message size exceeds maximum');
        }

        return $this->messageCount;
    }

    /**
     * Get upper breakpoint for the current message count
     *
     * @return int
     */
    public function getUpperBreakpoint()
    {
        $single = self::MAXIMUM_CHARACTERS_7BIT_SINGLE;
        $concat = self::MAXIMUM_CHARACTERS_7BIT_CONCATENATED;
        if ($this->encoding === 'ucs-2') {
            $single = self::MAXIMUM_CHARACTERS_UCS2_SINGLE;
            $concat = self::MAXIMUM_CHARACTERS_UCS2_CONCATENATED;
        }

        if ($this->size <= $single) {
            return $single;
        }

        return $this->messageCount * $concat;
    }

    /**
     * Parse content to discover size characteristics
     *
     * @param string $messageContent
     * @throws InvalidArgumentException
     */
    private function inspect($messageContent)
    {
        // If it's not UTF-8, then it's broken
        if (!mb_check_encoding($messageContent, 'UTF-8')) {
            throw new InvalidArgumentException('Content encoding could not be verified ' . $messageContent);
        }

        // Start counting characters in basic and extended
        // Extended chars count for two
        // Any character outside the 7-bit alphabet switches the entire encoding to UCS-2
        $this->encoding = '7-bit';
        $this->size = 0;
        $mbLength = mb_strlen($messageContent);
        for ($i = 0; $i < $mbLength; $i++) {
            $char = mb_substr($messageContent, $i, 1);
            if (in_array($char, self::GSM0338_BASIC)) {
                $this->size++;
            } elseif (in_array($char, self::GSM0338_EXTENDED)) {
                $this->size += 2;
            } else {
                $this->encoding = 'ucs-2';
                break;
            }
        }

        if ($this->encoding === 'ucs-2') {
            // For UCS-2 need to iterate the characters again
            // Those with two UTF-16 code points consume two characters in the SMS
            $this->size = 0;
            for ($i = 0; $i < $mbLength; $i++) {
                $char = mb_substr($messageContent, $i, 1);
                $utf16Hex = bin2hex(mb_convert_encoding($char, 'UTF-16'));
                $this->size += strlen($utf16Hex) / 4;
            }
        }

        // Message Count: Each SMS is slightly shorter if concatenation is required
        $singleSize = self::MAXIMUM_CHARACTERS_7BIT_SINGLE;
        $concatSize = self::MAXIMUM_CHARACTERS_7BIT_CONCATENATED;
        if ($this->encoding === 'ucs-2') {
            $singleSize = self::MAXIMUM_CHARACTERS_UCS2_SINGLE;
            $concatSize = self::MAXIMUM_CHARACTERS_UCS2_CONCATENATED;
        }

        $this->messageCount = 1;
        if ($this->size > $singleSize) {
            $this->messageCount = (int)ceil($this->size / $concatSize);
        }
    }
}
