# phlib/sms-length

[![Build Status](https://img.shields.io/travis/phlib/sms-length/master.svg?style=flat-square)](https://travis-ci.org/phlib/sms-length)
[![Codecov](https://img.shields.io/codecov/c/github/phlib/sms-length.svg?style=flat-square)](https://codecov.io/gh/phlib/sms-length)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/sms-length.svg?style=flat-square)](https://packagist.org/packages/phlib/sms-length)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/sms-length.svg?style=flat-square)](https://packagist.org/packages/phlib/sms-length)
![Licence](https://img.shields.io/github/license/phlib/sms-length.svg?style=flat-square)

## Installation

```php
composer require phlib/sms-length
```

## Usage

Simple string which fits within GSM 03.38 7-bit alphabet:

```php
$smsLength = new \Phlib\SmsLength\SmsLength('simple message');
$smsLength->getSize(); // 14
$smsLength->getEncoding(); // '7-bit'
$smsLength->getMessageCount(); // 1
$smsLength->getUpperBreakpoint(); // 160
```

Message which contains characters forcing switch to using GSM 03.38 UCS-2:

```php
$smsLength = new \Phlib\SmsLength\SmsLength('message with • char requiring UCS-2');
$smsLength->getSize(); // 35
$smsLength->getEncoding(); // 'ucs-2'
$smsLength->getMessageCount(); // 1
$smsLength->getUpperBreakpoint(); // 70
```

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
