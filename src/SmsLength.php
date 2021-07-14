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
     * @var string
     */
    private $messageContent;

    /**
     * Constructor
     *
     * @param string $messageContent SMS message content (UTF-8)
     *
     * @throws InvalidArgumentException
     */
    public function __construct($messageContent)
    {
        $this->messageContent = $messageContent;
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
     */
    public function getMessageCount()
    {
        return $this->messageCount;
    }

    /**
     * Get number of message content
     *
     * @return string
     */
    public function getMessageContent()
    {
        return $this->messageContent;
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
     * Check the message content is valid for an SMS
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validate()
    {
        if ($this->messageCount > self::MAXIMUM_CONCATENATED_SMS) {
            throw new InvalidArgumentException('Message count cannot exceed ' . self::MAXIMUM_CONCATENATED_SMS);
        }

        return true;
    }

    /**
     * Return a new instance with the message truncated to a set part count
     *
     * @param int $parts
     *
     * @return self
     */
    public function truncate($parts)
    {
        if ($this->messageCount <= $parts) {
            return $this;
        }

        if ($this->encoding === '7-bit') {
            return new self($this->truncate7Bit($this->messageContent, $parts));
        }

        return new self($this->truncateUcs2($this->messageContent, $parts));
    }

    private function truncate7Bit($message, $parts)
    {
        $size = 0;
        $newMessage = '';

        $mbLength = mb_strlen($message, 'UTF-8');

        for ($i = 0; $i < $mbLength; $i++) {
            $char = mb_substr($message, $i, 1, 'UTF-8');

            if (in_array($char, self::GSM0338_BASIC)) {
                $charSize = 1;
            } elseif (in_array($char, self::GSM0338_EXTENDED)) {
                $charSize = 2;
            } else {
                continue;
            }

            if ($parts === 1 && $size + $charSize > self::MAXIMUM_CHARACTERS_7BIT_SINGLE) {
                return $newMessage;
            }

            if ($parts > 1 && $size + $charSize > $parts * self::MAXIMUM_CHARACTERS_7BIT_CONCATENATED) {
                return $newMessage;
            }

            $size += $charSize;
            $newMessage .= $char;
        }

        return $newMessage;
    }

    private function truncateUcs2($message, $parts)
    {
        $size = 0;
        $newMessage = '';

        $mbLength = mb_strlen($message, 'UTF-8');

        for ($i = 0; $i < $mbLength; $i++) {
            $char = mb_substr($message, $i, 1, 'UTF-8');
            $utf16Hex = bin2hex(mb_convert_encoding($char, 'UTF-16', 'UTF-8'));
            $charSize = strlen($utf16Hex) / 4;

            if ($parts === 1 && $size + $charSize > self::MAXIMUM_CHARACTERS_UCS2_SINGLE) {
                return $newMessage;
            }

            if ($parts > 1 && $size + $charSize >= $parts *  self::MAXIMUM_CHARACTERS_UCS2_CONCATENATED) {
                return $newMessage;
            }

            $size += $charSize;
            $newMessage .= $char;
        }

        return $newMessage;
    }

    /**
     * Parse content to discover size characteristics
     *
     * @param string $messageContent
     *
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
        $mbLength = mb_strlen($messageContent, 'UTF-8');
        for ($i = 0; $i < $mbLength; $i++) {
            $char = mb_substr($messageContent, $i, 1, 'UTF-8');
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
                $char = mb_substr($messageContent, $i, 1, 'UTF-8');
                $utf16Hex = bin2hex(mb_convert_encoding($char, 'UTF-16', 'UTF-8'));
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
