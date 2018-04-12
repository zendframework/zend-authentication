# Identity Persistence

Authenticating a request that includes authentication credentials is useful, but
it is also often useful to persist the authenticated identity between requests, so
the user does not need to provide credentials with each request.

HTTP is a stateless protocol; however, techniques such as cookies and sessions
have been developed in order to facilitate maintaining state across multiple
requests in server-side web applications.

## Default Persistence in the PHP Session

By default, zend-authentication provides persistent storage of the identity from a successful
authentication attempt using PHP session facilities. Upon a successful authentication attempt,
`Zend\Authentication\AuthenticationService::authenticate()` stores the identity from the
authentication result into persistent storage. Unless specified otherwise,
`Zend\Authentication\AuthenticationService` uses a storage class named
`Zend\Authentication\Storage\Session`, which depends on
[zend-session](https://github.com/zendframework/zend-session).

You may also implement `Zend\Authentication\Storage\StorageInterface`, and
provide your implementation to `Zend\Authentication\AuthenticationService::setStorage()`.

> ### Bypass the AuthenticationService
>
> If automatic persistent storage of the identity is not appropriate for your
> use case, you can skip usage of `Zend\Authentication\AuthenticationService`
> altogether, and instead use an adapter directly.

## Modifying the Session Namespace

`Zend\Authentication\Storage\Session` uses the session namespace `Zend_Auth`.
This namespace may be overridden by passing a different value to the constructor
of `Zend\Authentication\Storage\Session`, and this value is internally passed
along to the constructor of [Zend\\Session\\Container](https://github.com/zendframework/zend-session).
This should occur before authentication is attempted, since
`Zend\Authentication\AuthenticationService::authenticate()` injects the
authenticated identity into the configured storage.

```php
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;

$auth = new AuthenticationService();

// Use 'someNamespace' instead of 'Zend_Auth'
$auth->setStorage(new SessionStorage('someNamespace'));

// Set up the auth adapter, $authAdapter
$authAdapter = /* ... */;

// Authenticate, saving the result, and persisting the identity on success:
$result = $auth->authenticate($authAdapter);
```

## Chain Storage

A website might use multiple storage strategies for identity persistence; the
`Chain` Storage can be used to glue these together.

For example, the `Chain` can be configured to first use `Session` storage and
then use an `OAuth` storage adapter. One could configure this in the following
way:

```php
$storage = new Chain;
$storage->add(new Session);
$storage->add(new OAuth);   // Note: imaginary storage, not part of zend-authentication
```

When the `Chain` Storage is used, its underlying storage adapters will be
consulted in the order in which they were added to the chain. Using our scenario
above, the `Session` storage adapter will be consulted first. When that happens:

- If the `Session` storage is non-empty, the `Chain` will use and return its
  contents.
- If the `Session` storage is empty, the `Chain` will move on to the `OAuth`
  storage adapter.
- If the `OAuth` storage is empty, the `Chain` will return an empty result.
- If the `OAuth` storage is non-empty, the `Chain` will use and return its
  contents. However, it will *also* populate all storage adapters with higher
  priority with the contents; in our example, the `Session` storage will be
  populated, but if we'd added any adapters after the `OAuth` adapter, they
  would not.

The priority of storage adapters in the Chain can be made explicit via the
`Chain::add` method, which accepts a second argument indicating the priority.
(Per standard priority queue usage, higher values have higher priority, and
lower or negative values have lower priority.)

```php
$chain->add(new A, 2);
$chain->add(new B, 10); // B will be used first
```

## Implementing Custom Storage

Sometimes developers may need to use a different identity storage mechanism than
that provided by `Zend\Authentication\Storage\Session`. To do so, implement
`Zend\Authentication\Storage\StorageInterface` and supply an instance of your
implementation to `Zend\Authentication\AuthenticationService::setStorage()`.

The following examples demonstrate the process.

First, implement `Zend\Authentication\Storage\StorageInterface`:

```php
<?php
namespace My;

use Zend\Authentication\Storage\StorageInterface;

class Storage implements StorageInterface
{
    /**
     * Returns true if and only if storage is empty.
     *
     * @return boolean
     * @throws \Zend\Authentication\Exception\ExceptionInterface If it is
     *     impossible to determine whether storage is empty.
     */
    public function isEmpty()
    {
        /**
         * @todo implementation
         */
    }

    /**
     * Returns the contents of storage.
     *
     * Behavior is undefined when storage is empty.
     *
     * @return mixed
     * @throws \Zend\Authentication\Exception\ExceptionInterface If reading
     *     contents from storage is impossible
     */

    public function read()
    {
        /**
         * @todo implementation
         */
    }

    /**
     * Writes $contents to storage.
     *
     * @param  mixed $contents
     * @return void
     * @throws \Zend\Authentication\Exception\ExceptionInterface If writing
     *     $contents to storage is impossible
     */

    public function write($contents)
    {
        /**
         * @todo implementation
         */
    }

    /**
     * Clears contents from storage.
     *
     * @return void
     * @throws \Zend\Authentication\Exception\ExceptionInterface If clearing
     *     contents from storage is impossible.
     */

    public function clear()
    {
        /**
         * @todo implementation
         */
    }
}
```

In order to use this custom storage class, `Zend\Authentication\AuthenticationService::setStorage()`
is invoked before an authentication query is attempted:

```php
use My\Storage;
use Zend\Authentication\AuthenticationService;

// Create the authentication service instance:
$auth = new AuthenticationService();

// Instruct the authentication service to use the custom storage class:
$auth->setStorage(new Storage());

// Create the authentication adapter:
$adapter = /* ... */;

// Authenticate, saving the result, and persisting the identity on success:
$result = $auth->authenticate($adapter);
```
