# Authentication Validator

`Zend\Authentication\Validator\Authentication` provides a [zend-validator](https://github.com/zendframework/zend-validator)
`ValidatorInterface` implementation, which can be used within an
[input filter](https://github.com/zendframework/zend-inputfilter) or
[form](https://github.com/zendframework/zend-form), or anywhere you
you simply want a true/false value to determine whether or not authentication
credentials were provided.

The available configuration options include:

- `adapter`: an instance of `Zend\Authentication\Adapter\AdapterInterface`.
- `identity`: the identity or name of the identity field in the provided context.
- `credential`: credential or the name of the credential field in the provided context.
- `service`: an instance of `Zend\Authentication\AuthenticationService`.
- `code_map`: map of authentication attempt result codes to validator message keys. 

## Usage

```php
use My\Authentication\Adapter;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Validator\Authentication as AuthenticationValidator;

$service   = new AuthenticationService();
$adapter   = new Adapter();
$validator = new AuthenticationValidator([
    'service' => $service,
    'adapter' => $adapter,
]);

$validator->setCredential('myCredentialContext');
$validator->isValid('myIdentity', [
     'myCredentialContext' => 'myCredential',
]);
```

## Configuring custom authentication result codes and messages

Constructor configuration option `code_map` is a map of custom authentication result
codes to validation messages keys.

`code_map` can specify custom validation message key. New message template
will be registered for that key, which can further be customized
using `Validator::setMessage()` method or `messages` configuration option.

```php
use Zend\Authentication\Validator\Authentication as AuthenticationValidator;

$validator = new AuthenticationValidator([
    'code_map' => [
        // map custom result code to existing message
        -990 => AuthenticationValidator::IDENTITY_NOT_FOUND,
        // map custom result code to a new message type
        -991 => 'custom_error_message_key',
    ],
    'messages' => [
        // provide message template for custom message type defined above
        'custom_error_message_key' => 'Custom Error Happened'
    ],
]);

$validator->setMessage('Custom Error Happened', 'custom_error_message_key');
```
