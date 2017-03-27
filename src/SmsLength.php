<?php

namespace Phlib\SmsLength;

/**
 * @package    Phlib\SmsLength
 * @license    LGPL-3.0
 */
class SmsLength
{
    /**
     * @var string
     */
    private $messageContent;

    /**
     * Constructor
     *
     * @param string $messageContent
     */
    public function __construct($messageContent)
    {
        $this->messageContent = $messageContent;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return rand();
    }
}
