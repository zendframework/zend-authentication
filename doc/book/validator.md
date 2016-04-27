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
