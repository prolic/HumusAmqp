# Change Log

## [v1.4.2](https://github.com/prolic/HumusAmqp/tree/v1.4.2)

[Full Changelog](https://github.com/prolic/HumusAmqp/compare/v1.4.1...v1.4.2)

**Implemented enhancements:**

- Use AMQPChannel::close\(\) in AbstractConsumer [\#72](https://github.com/prolic/HumusAmqp/pull/72) ([genhoi](https://github.com/genhoi))

## [v1.4.1](https://github.com/prolic/HumusAmqp/tree/v1.4.1) (2018-11-02)
[Full Changelog](https://github.com/prolic/HumusAmqp/compare/v1.4.0...v1.4.1)

**Merged pull requests:**

- allow beberlei/assert v3 [\#71](https://github.com/prolic/HumusAmqp/pull/71) ([basz](https://github.com/basz))

## [v1.4.0](https://github.com/prolic/HumusAmqp/tree/v1.4.0) (2018-03-03)
[Full Changelog](https://github.com/prolic/HumusAmqp/compare/v1.3.0...v1.4.0)

**Implemented enhancements:**

- handle json-rpc responses without id [\#68](https://github.com/prolic/HumusAmqp/pull/68) ([prolic](https://github.com/prolic))
- bump suggested driver requirements [\#63](https://github.com/prolic/HumusAmqp/pull/63) ([prolic](https://github.com/prolic))
- test php 7.2 in travis [\#62](https://github.com/prolic/HumusAmqp/pull/62) ([prolic](https://github.com/prolic))
- always declare a queue in factory, even with auto\_setup\_fabric disabled [\#61](https://github.com/prolic/HumusAmqp/pull/61) ([prolic](https://github.com/prolic))
- json rpc server returns trace if enabled [\#60](https://github.com/prolic/HumusAmqp/pull/60) ([prolic](https://github.com/prolic))
- allow marc-mabe/php-enum 3.0 [\#59](https://github.com/prolic/HumusAmqp/pull/59) ([prolic](https://github.com/prolic))

**Fixed bugs:**

- handle json-rpc responses without id [\#68](https://github.com/prolic/HumusAmqp/pull/68) ([prolic](https://github.com/prolic))

**Merged pull requests:**

- adds a test for empty json rpc response collections [\#66](https://github.com/prolic/HumusAmqp/pull/66) ([oqq](https://github.com/oqq))
- updates rpc docs to match current mandatory options from factory [\#55](https://github.com/prolic/HumusAmqp/pull/55) ([oqq](https://github.com/oqq))

## [v1.3.0](https://github.com/prolic/HumusAmqp/tree/v1.3.0) (2017-09-14)
[Full Changelog](https://github.com/prolic/HumusAmqp/compare/v1.2.0...v1.3.0)

**Implemented enhancements:**

- Customizable the delivery result when handling exception [\#49](https://github.com/prolic/HumusAmqp/issues/49)
- Update to use psr-11 container [\#43](https://github.com/prolic/HumusAmqp/issues/43)
- build amqp extension from source [\#54](https://github.com/prolic/HumusAmqp/pull/54) ([prolic](https://github.com/prolic))
- Added ability to change strategy of rejection a message [\#52](https://github.com/prolic/HumusAmqp/pull/52) ([yethee](https://github.com/yethee))
- Update to use psr-11 container [\#51](https://github.com/prolic/HumusAmqp/pull/51) ([prolic](https://github.com/prolic))
- JsonProducer should check for errors [\#47](https://github.com/prolic/HumusAmqp/pull/47) ([prolic](https://github.com/prolic))

**Fixed bugs:**

- Q: ssl Connection on php-lib requires validation and client certs [\#9](https://github.com/prolic/HumusAmqp/issues/9)
- Changed command switch back to c from n, looks like this was switched… [\#53](https://github.com/prolic/HumusAmqp/pull/53) ([cwoskoski](https://github.com/cwoskoski))

**Closed issues:**

- Consider implementing amqp-interop [\#50](https://github.com/prolic/HumusAmqp/issues/50)
- JsonProducer should check for errors [\#46](https://github.com/prolic/HumusAmqp/issues/46)
- Compatibility with latest ConfigurationTrait [\#44](https://github.com/prolic/HumusAmqp/issues/44)

**Merged pull requests:**

- update coding style [\#48](https://github.com/prolic/HumusAmqp/pull/48) ([prolic](https://github.com/prolic))

## [v1.2.0](https://github.com/prolic/HumusAmqp/tree/v1.2.0) (2017-06-18)
[Full Changelog](https://github.com/prolic/HumusAmqp/compare/v1.1.0...v1.2.0)

**Implemented enhancements:**

- More docs on JSON RPC Server & Client [\#32](https://github.com/prolic/HumusAmqp/issues/32)
- Documentation for humus-amqp-config setup [\#23](https://github.com/prolic/HumusAmqp/issues/23)
- fix exchange binding / unbinding for php-amqplib [\#38](https://github.com/prolic/HumusAmqp/pull/38) ([prolic](https://github.com/prolic))

**Fixed bugs:**

- fix exchange binding / unbinding for php-amqplib [\#38](https://github.com/prolic/HumusAmqp/pull/38) ([prolic](https://github.com/prolic))
- fix waitForConfirm in php-amqplib [\#36](https://github.com/prolic/HumusAmqp/pull/36) ([prolic](https://github.com/prolic))
- fix ssl connection for php amqplib [\#35](https://github.com/prolic/HumusAmqp/pull/35) ([prolic](https://github.com/prolic))

**Closed issues:**

- Link to gitter chat in readme does not work [\#42](https://github.com/prolic/HumusAmqp/issues/42)
- Need help to get a queue object [\#33](https://github.com/prolic/HumusAmqp/issues/33)

**Merged pull requests:**

- Connection type allowed only for php-amqplib driver [\#37](https://github.com/prolic/HumusAmqp/pull/37) ([marinovdf](https://github.com/marinovdf))

## [v1.1.0](https://github.com/prolic/HumusAmqp/tree/v1.1.0) (2017-02-07)
[Full Changelog](https://github.com/prolic/HumusAmqp/compare/v1.0.0...v1.1.0)

**Implemented enhancements:**

- JSON RPC Server & Client docs [\#31](https://github.com/prolic/HumusAmqp/pull/31) ([prolic](https://github.com/prolic))
- Add support for PHP 7.1 [\#29](https://github.com/prolic/HumusAmqp/pull/29) ([prolic](https://github.com/prolic))
- php-amqp can now handle exception codes [\#27](https://github.com/prolic/HumusAmqp/pull/27) ([prolic](https://github.com/prolic))

**Fixed bugs:**

- Allowed different service names for exchanges [\#26](https://github.com/prolic/HumusAmqp/pull/26) ([thomasvargiu](https://github.com/thomasvargiu))

**Merged pull requests:**

- adds exchange name as array key in rpc client factory [\#30](https://github.com/prolic/HumusAmqp/pull/30) ([oqq](https://github.com/oqq))

## [v1.0.0](https://github.com/prolic/HumusAmqp/tree/v1.0.0) (2016-08-13)
[Full Changelog](https://github.com/prolic/HumusAmqp/compare/v1.0.0-BETA.4...v1.0.0)

**Implemented enhancements:**

- Add AMQPLazySocketConnection for php-amqplib [\#21](https://github.com/prolic/HumusAmqp/issues/21)
- Add documentation [\#11](https://github.com/prolic/HumusAmqp/issues/11)

## [v1.0.0-BETA.4](https://github.com/prolic/HumusAmqp/tree/v1.0.0-BETA.4) (2016-07-31)
[Full Changelog](https://github.com/prolic/HumusAmqp/compare/v1.0.0-BETA.3...v1.0.0-BETA.4)

## [v1.0.0-BETA.3](https://github.com/prolic/HumusAmqp/tree/v1.0.0-BETA.3) (2016-07-16)
[Full Changelog](https://github.com/prolic/HumusAmqp/compare/v1.0.0-BETA.2...v1.0.0-BETA.3)

**Implemented enhancements:**

- Allow queue to bind to multiple exchanges [\#20](https://github.com/prolic/HumusAmqp/issues/20)

## [v1.0.0-BETA.2](https://github.com/prolic/HumusAmqp/tree/v1.0.0-BETA.2) (2016-07-09)
[Full Changelog](https://github.com/prolic/HumusAmqp/compare/v1.0.0-BETA.1...v1.0.0-BETA.2)

**Fixed bugs:**

- catch \(\Throwable $e\) //to catch also new PHP7 errors [\#13](https://github.com/prolic/HumusAmqp/issues/13)
- Argument 1 must be of the type integer, string given [\#16](https://github.com/prolic/HumusAmqp/pull/16) ([basz](https://github.com/basz))

**Closed issues:**

- direct exchanges and messageName filters [\#17](https://github.com/prolic/HumusAmqp/issues/17)
- option with shortcut "n" already exists [\#15](https://github.com/prolic/HumusAmqp/issues/15)

**Merged pull requests:**

- alias humus-amqp to vendor/bin [\#14](https://github.com/prolic/HumusAmqp/pull/14) ([basz](https://github.com/basz))

## [v1.0.0-BETA.1](https://github.com/prolic/HumusAmqp/tree/v1.0.0-BETA.1) (2016-06-07)
**Implemented enhancements:**

- Implement JSON-RPC 2.0 specifications [\#10](https://github.com/prolic/HumusAmqp/issues/10)
- Add functional tests [\#6](https://github.com/prolic/HumusAmqp/issues/6)
- Add unit tests [\#5](https://github.com/prolic/HumusAmqp/issues/5)
- Make use of driver-independent amqp constants [\#3](https://github.com/prolic/HumusAmqp/issues/3)
- Add drivers [\#2](https://github.com/prolic/HumusAmqp/pull/2) ([prolic](https://github.com/prolic))

**Fixed bugs:**

- option cacert vs ca\_cert [\#8](https://github.com/prolic/HumusAmqp/issues/8)

**Merged pull requests:**

- Added a way to get default attributes. [\#1](https://github.com/prolic/HumusAmqp/pull/1) ([bweston92](https://github.com/bweston92))



\* *This Change Log was automatically generated by [github_changelog_generator](https://github.com/skywinder/Github-Changelog-Generator)*
