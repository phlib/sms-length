# phlib/sms-length

[![Code Checks](https://img.shields.io/github/workflow/status/phlib/sms-length/CodeChecks?logo=github)](https://github.com/phlib/sms-length/actions/workflows/code-checks.yml)
[![Codecov](https://img.shields.io/codecov/c/github/phlib/sms-length.svg?logo=codecov)](https://codecov.io/gh/phlib/sms-length)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/sms-length.svg?logo=packagist)](https://packagist.org/packages/phlib/sms-length)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/sms-length.svg?logo=packagist)](https://packagist.org/packages/phlib/sms-length)
![Licence](https://img.shields.io/github/license/phlib/sms-length.svg)

Calculate SMS GSM 03.38 message encoding and length, and number of concatenated SMS required

## Installation

```php
composer require phlib/sms-length
```

## Usage

Simple string which fits within GSM 03.38 7-bit alphabet:

```php
$smsLength = new \Phlib\SmsLength\SmsLength('simple message');
$smsLength->validate(); // Throw exceptions for any validation failures
$smsLength->getSize(); // 14
$smsLength->getEncoding(); // '7-bit'
$smsLength->getMessageCount(); // 1
$smsLength->getUpperBreakpoint(); // 160
```

Message which contains characters forcing switch to using GSM 03.38 UCS-2:

```php
$smsLength = new \Phlib\SmsLength\SmsLength('message with • char requiring UCS-2');
$smsLength->validate(); // Throw exceptions for any validation failures
$smsLength->getSize(); // 35
$smsLength->getEncoding(); // 'ucs-2'
$smsLength->getMessageCount(); // 1
$smsLength->getUpperBreakpoint(); // 70
```

## Background

In the course of adding an SMS module to our Commercial software, we have found
need for the SMS length properties which we've included in this package.

Our third-party SMS gateway provider's API will of course error if the message
length exceeds the maximum allowed, and handles the correct concatenation and
encoding for the given UTF-8 string.

However we want to be able to inform our users of message size and limits while
they're building their SMS campaign (in advance of a live send). Plus we also
need to show how many concatenated SMS they will use per contact, and therefore
indicate pricing.

The unit tests and inspections made by this package are based on
[GSM 03.38 / 3GPP 23.038](https://en.wikipedia.org/wiki/GSM_03.38) for encoding
and [GSM 03.40 / 3GPP 23.040](https://en.wikipedia.org/wiki/GSM_03.40) for
[concatenated SMS](https://en.wikipedia.org/wiki/Concatenated_SMS). We have also
referred to documentation provided by third-party SMS gateway providers such as
[MessageBird](https://support.messagebird.com/hc/en-us/articles/208739745-How-long-can-a-text-message-be-)
and [Messente](https://messente.com/documentation/tools/sms-length-calculator).

## License

This package is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
