# Adapters Introduction

zend-authentication adapters are used to authenticate against a particular type
of authentication service, such as LDAP, RDBMS, or file-based storage. Different
adapters are likely to have vastly different options and behaviors, but some
basic things are common among authentication adapters. For example, accepting
authentication credentials (including a purported identity), performing queries
against the authentication service, and returning results are common to
zend-authentication adapters.

## AdapterInterface

Each adapter implements `Zend\Authentication\Adapter\AdapterInterface`.  This
interface defines one method, `authenticate()`, which provides the
implementation for performing an authentication query. Each adapter class must
be prepared prior to calling `authenticate()`; such adapter preparation might
include setting up credentials from user input (e.g., username and password), or
defining values for adapter-specific configuration options, such as database
connection settings for a database table adapter.

The following is an example authentication adapter that requires a username and
password to be set for authentication. Other details, such as how the
authentication service is queried, have been omitted for brevity:

```php
<?php
namespace My\Auth;

use Zend\Authentication\Adapter\AdapterInterface;

class Adapter implements AdapterInterface
{
    /**
     * Sets username and password for authentication
     *
     * @return void
     */
    public function __construct($username, $password)
    {
        // ...
    }

    /**
     * Performs an authentication attempt
     *
     * @return \Zend\Authentication\Result
     * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface
     *     If authentication cannot be performed
     */
    public function authenticate()
    {
        // ...
    }
}
```

As indicated in its docblock, `authenticate()` must return an instance of
`Zend\Authentication\Result` (or of a class derived from
`Zend\Authentication\Result`). If performing an authentication query is
impossible, `authenticate()` should throw an exception that derives from
`Zend\Authentication\Adapter\Exception\ExceptionInterface`.

## Results

Authentication adapters return an instance of `Zend\Authentication\Result` from
`authenticate()` in order to represent the results of an authentication attempt.
Adapters populate the `Zend\Authentication\Result` object upon construction:

```php
namespace Zend\Authentication;

class Result
{
    /**
     * @param int $code
     * @param mixed $identity
     * @param array $messages
     */
    public function __construct($code, $identity, array $messages = []);
}
```

where:

- `$code` is an integer indicating the result status. Typically you will use one
  of the constants defined in the `Result` class to provide this; a table
  follows detailing those.
- `$identity` is the value representing the authenticated identity. This may be
  any PHP type; typically you will see a string username or token, or an object
  type specific to the application or login module you utilize. When the result
  represents a failure to authenticate, this will often be null; some systems
  will provide a default identity in such cases.
- `$messages` is an array of authentication failure messages.

The following result codes are available:

```php
namespace Zend\Authentication;

class Result
{
    const SUCCESS = 1;
    const FAILURE = 0;
    const FAILURE_IDENTITY_NOT_FOUND = -1;
    const FAILURE_IDENTITY_AMBIGUOUS = -2;
    const FAILURE_CREDENTIAL_INVALID = -3;
    const FAILURE_UNCATEGORIZED = -4;
}
```

Note that success is a truthy value, while failure of any sort is a falsy value.

Results provide the following four user-facing operations:

- `isValid()` returns `TRUE` if and only if the result represents a successful
  authentication attempt.
- `getCode()` returns the `Zend\Authentication\Result` constant identifier
  associated with the specific result. This may be used in situations where the
  developer wishes to distinguish among several authentication result types.
  This allows developers to maintain detailed authentication result statistics,
  for example. Another use of this feature is to provide specific, customized
  messages to users for usability reasons, though developers are encouraged to
  consider the risks of providing such detailed reasons to users, instead of a
  general authentication failure message. For more information, see the notes
  below.
- `getIdentity()` returns the identity of the authentication attempt.
- `getMessages()` returns an array of messages regarding a failed authentication
  attempt.

A developer may wish to branch based on the type of authentication result in
order to perform more specific operations. Some operations developers might find
useful are locking accounts after too many unsuccessful password attempts,
flagging an IP address after too many nonexistent identities are attempted, and
providing specific, customized authentication result messages to the user.

The following example illustrates how a developer may branch on the result code:

```php
$result = $authenticationService->authenticate($adapter);

switch ($result->getCode()) {

    case Result::FAILURE_IDENTITY_NOT_FOUND:
        /** do stuff for nonexistent identity **/
        break;

    case Result::FAILURE_CREDENTIAL_INVALID:
        /** do stuff for invalid credential **/
        break;

    case Result::SUCCESS:
        /** do stuff for successful authentication **/
        break;

    default:
        /** do stuff for other failure **/
        break;
}
```
