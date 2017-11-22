# Introduction

zend-authentication provides an API for authentication and includes concrete
authentication adapters for common use case scenarios.

zend-authentication is concerned only with **authentication** and not with
**authorization**.  Authentication is loosely defined as determining whether an
entity actually is what it purports to be (i.e., identification), based on some
set of credentials. Authorization, the process of deciding whether to allow an
entity access to, or to perform operations upon, other entities is outside the
scope of `Zend\Authentication`. For more information about authorization and
access control with Zend Framework, please see the
[zend-permissions-acl](https://docs.zendframework.com/zend-permissions-acl/) or
[zend-permissions-rbac](https://github.com/zendframework/zend-permissions-rbac)
components.

> ### AuthenticationService
>
> There is no `Zend\Authentication\Authentication` class; instead the class
> `Zend\Authentication\AuthenticationService` is provided. This class uses the
> composed authentication adapter and persistent storage backend.

## Usage

There are two approaches to using zend-authentication adapters:

- indirectly, through `Zend\Authentication\AuthenticationService::authenticate()`
- directly, through the adapter's `authenticate()` method

The following example illustrates how to use an adapter indirectly, through the
use of the `Zend\Authentication\AuthenticationService` class:

```php
use My\Auth\Adapter;
use Zend\Authentication\AuthenticationService;

// Instantiate the authentication service:
$auth = new AuthenticationService();

// Instantiate the authentication adapter:
$authAdapter = new Adapter($username, $password);

// Attempt authentication, saving the result:
$result = $auth->authenticate($authAdapter);

if (! $result->isValid()) {
    // Authentication failed; print the reasons why:
    foreach ($result->getMessages() as $message) {
        echo "$message\n";
    }
} else {
    // Authentication succeeded; the identity ($username) is stored
    // in the session:
    // $result->getIdentity() === $auth->getIdentity()
    // $result->getIdentity() === $username
}
```

After a successful authentication attempt, subsequent requests can query the
authentication service to determine if an identity is present, and, if so,
retrieve it:

```php
if ($auth->hasIdentity()) {
    // Identity exists; get it
    $identity = $auth->getIdentity();
}
```

To remove the identity from persistent storage, use the `clearIdentity()`
method. This typically would be used for implementing an application "logout"
operation:

```php
$auth->clearIdentity();
```

When the automatic use of persistent storage is inappropriate for a particular
use case, a developer may bypass the use of the
`Zend\Authentication\AuthenticationService` class, using an adapter class
directly. Direct use of an adapter class involves configuring and preparing an
adapter object and then calling its `authenticate()` method. Adapter-specific
details are discussed in the documentation for each adapter. The following
example directly utilizes the fictional `My\Auth\Adapter` from the above
examples:

```php
use My\Auth\Adapter;

// Set up the authentication adapter:
$authAdapter = new Adapter($username, $password);

// Attempt authentication, saving the result:
$result = $authAdapter->authenticate();

if (! $result->isValid()) {
    // Authentication failed; print the reasons why
    foreach ($result->getMessages() as $message) {
        echo "$message\n";
    }
} else {
    // Authentication succeeded
    // $result->getIdentity() === $username
}
```
