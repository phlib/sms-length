# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- Add specific support for PHP v8
- Type declarations have been added to all method parameters and return types.
### Removed
- **BC break**: Removed support for PHP versions <= v7.2 as they are no longer
  [actively supported](https://php.net/supported-versions.php) by the PHP project.

## [1.1.0] - 2017-04-18
### Added
- New `validate()` method to check the content string matches the specification.
### Changed
- Updated construct docblock to make it clear that the SMS message content
should be provided as a UTF-8 string. In most cases this should not require any
implementation changes as UTF-8 is the
[default charset for PHP](http://php.net/manual/en/ini.core.php#ini.default-charset).
- The exception thrown for message content which exceeds the maximum allowed
number of concatenated SMS (255) has been moved from the inspection on construct
to a dedicated `validate()` method. This allows the implementation to still read
the other stats from the constructed object. (The constructor will still throw
an exception if the message string contained characters that cannot be parsed).

## [1.0.0] - 2017-03-28
Initial Release
