# HTTP Authentication Adapter

`Zend\Authentication\Adapter\Http` provides a mostly-compliant implementation of
[RFC-2617](http://tools.ietf.org/html/rfc2617),
[Basic](http://en.wikipedia.org/wiki/Basic_authentication_scheme) and
[Digest](http://en.wikipedia.org/wiki/Digest_access_authentication) HTTP
Authentication. Digest authentication is a method of HTTP authentication that
improves upon Basic authentication by providing a way to authenticate without
having to transmit the password in clear text across the network.

## Major Features

- Supports both Basic and Digest authentication.
- Issues challenges in all supported schemes, so client can respond with any
  scheme it supports.
- Supports proxy authentication.
- Includes support for authenticating against text files and provides an
  interface for authenticating against other sources, such as databases.

There are a few notable features of RFC-2617 that are not implemented yet:

- Nonce tracking, which would allow for "stale" support, and increased replay
  attack protection.
- Authentication with integrity checking, or "auth-int".
- Authentication-Info HTTP header.

## Requirements

The HTTP authentication adapter requires [zend-http](https://github.com/zendframework/zend-http)
in order to do its work:

```bash
$ composer require zendframework/zend-http
```

## Design Overview

This adapter consists of two sub-components, the HTTP authentication class
itself, and its "Resolvers." The HTTP authentication class encapsulates the
logic for carrying out both Basic and Digest authentication. It consumes
Resolvers to look up a client's identity in some data store (text file by
default), and retrieve the credentials from the data store. The "resolved"
credentials are then compared to the values submitted by the client to
determine whether authentication is successful.

## Configuration Options

`Zend\Authentication\Adapter\Http` requires a configuration array passed to its
constructor. There are several configuration options available, and some are
required:

Option Name      | Required                                   | Description
---------------- | ------------------------------------------ | -----------
`accept_schemes` | Yes                                        | Determines which authentication schemes the adapter will accept from the client. Must be a space-separated list containing `basic` and/or `digest`.
`realm`          | Yes                                        | Sets the authentication realm; usernames should be unique within a given realm.
`digest_domains` | Yes, when `accept_schemes` contains digest | Space-separated list of URIs for which the same authentication information is valid. The URIs need not all point to the same server.
`nonce_timeout`  | Yes, when `accept_schemes` contains digest | Sets the number of seconds for which the nonce is valid. See notes below.
`use_opaque`     | No                                         | Specifies whether to send the opaque value in the header. True by default.
`algorithm`      | No                                         | Specified the algorithm. Defaults to MD5, the only supported option (for now).
`proxy_auth`     | No                                         | Disabled by default. Enable to perform Proxy authentication, instead of normal origin server authentication.

> ### nonce timeout
>
> The current implementation of the `nonce_timeout` has some interesting side
> effects. This setting is supposed to determine the valid lifetime of a given
> nonce, or effectively how long a client's authentication information is
> accepted. As an example, if it's set to 3600, it will cause the adapter to
> prompt the client for new credentials every hour, on the hour.  This will be
> resolved in a future release, once nonce tracking and stale support are
> implemented.

## Resolvers

A resolver's job is to take a username and realm, and return some kind of
credential value. Basic authentication expects to receive the base64-encoded
version of the user's password. Digest authentication expects to receive a hash
of the user's username, the realm, and their password (each separated by
colons). Currently, the only supported hash algorithm is MD5.

`Zend\Authentication\Adapter\Http` relies on objects implementing
`Zend\Authentication\Adapter\Http\ResolverInterface`. The component includes
resolvers for plain text files and Apache `htpasswd`-generated files; you can
also provide your own implementations.

### File Resolver

The file resolver is a very simple class. It has a single property specifying a
filename, which can also be passed to the constructor. Its `resolve()` method
walks through the text file, searching for a line with a matching username and
realm. The text file format is similar to Apache `htpasswd` files:

```text
<username>:<realm>:<credentials>
```

Each line consists of three fields &mdash; username, realm, and credentials
&mdash; each separated by a colon.  The credentials field is opaque to the file
resolver; it simply returns that value as-is to the caller. Therefore, this
same file format serves both Basic and Digest authentication. In Basic
authentication, the credentials field should be written in clear text. In
Digest authentication, it should be the MD5 hash described above.

There are two ways to create a file resolver:

```php
use Zend\Authentication\Adapter\Http\FileResolver;

$path     = 'data/passwd.txt';
$resolver = new FileResolver($path);
```

or

```php
$path     = 'data/passwd.txt';
$resolver = new FileResolver();
$resolver->setFile($path);
```

If the given path is empty or not readable, an exception is thrown.

### Apache Resolver

`Zend\Authentication\Adapter\Http\ApacheResolver` operates similarly to the
`FileResolver`, but is capable of reading files generated by Apache's `htpasswd`
facility, as described in the [Apache documentation](http://httpd.apache.org/docs/current/misc/password_encryptions.html).

In order to do so, you will need to also install [zend-crypt](http://docs.zendframework.com/zend-crypt/):

```bash
$ composer require zendframework/zend-crypt
```

In all other ways, it behaves like the `FileResolver`, meaning you instantiate
it with a path to the `htpasswd`-generated file, or inject the path after
instantiation:

```php
use Zend\Authentication\Adapter\Http\ApacheResolver;

$path = 'data/htpasswd';

// Inject at instantiation:
$resolver = new ApacheResolver($path);

// Or afterwards:
$resolver = new ApacheResolver();
$resolver->setFile($path);
```

## Basic Usage

First, set up an array with the required configuration values:

```php
$config = [
    'accept_schemes' => 'basic digest',
    'realm'          => 'My Web Site',
    'digest_domains' => '/members_only /my_account',
    'nonce_timeout'  => 3600,
];
```

This array will cause the adapter to accept either Basic or Digest
authentication, and will require authenticated access to all the areas of the
site under `/members_only` and `/my_account`. The realm value is usually
displayed by the browser in the password dialog box. The `nonce_timeout`
behaves as described above.

Next, create the `Zend\Authentication\Adapter\Http` object:

```php
use Zend\Authentication\Adapter\Http;

$adapter = new Http($config);
```

Since we're supporting both Basic and Digest authentication, we need two
different resolver objects.

```php
use Zend\Authentication\Adapter\Http\FileResolver;

$basicResolver  = new FileResolver('data/basic-passwd.txt');
$digestResolver = new FileResolver('data/digest-passwd.txt');

$adapter->setBasicResolver($basicResolver);
$adapter->setDigestResolver($digestResolver);
```

Finally, we perform authentication. The adapter requires zend-http request and
response instances in order to lookup credentials and provide challenge responses:

```php
use Zend\Http\Request;
use Zend\Http\Response;

// $request is an instance of Request
// $response is an instance of Response
$adapter->setRequest($request);
$adapter->setResponse($response);

$result = $adapter->authenticate();
if (! $result->isValid()) {
    // Bad username/password, or canceled password prompt
}
```
