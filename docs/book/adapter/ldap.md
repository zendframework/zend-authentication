# LDAP Authentication

`Zend\Authentication\Adapter\Ldap` supports web application authentication with
LDAP services. Its features include username and domain name canonicalization,
multi-domain authentication, and failover capabilities. It has been tested to
work with [Microsoft Active Directory](http://www.microsoft.com/windowsserver2003/technologies/directory/activedirectory/)
and [OpenLDAP](http://www.openldap.org/), but it should also work with other
LDAP service providers.

This documentation includes a guide on using `Zend\Authentication\Adapter\Ldap`,
an exploration of its API, an outline of the various available options,
diagnostic information for troubleshooting authentication problems, and example
options for both Active Directory and OpenLDAP servers.

## Usage

The following example demonstrates creating and configuring the `Ldap`
authentication adapter, and also illustrates how to work with the
authentication messages returned in the authentication result.

```php
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Adapter\Ldap as LdapAdapter;

// Retrieve the username and pasword from the request somehow.
$username = /* ... */;
$password = /* ... */;

$auth = new AuthenticationService();

$config = [
    'server1' => [
        'host'                   => 's0.foo.net',
        'accountDomainName'      => 'foo.net',
        'accountDomainNameShort' => 'FOO',
        'accountCanonicalForm'   => 3,
        'username'               => 'CN=user1,DC=foo,DC=net',
        'password'               => 'pass1',
        'baseDn'                 => 'OU=Sales,DC=foo,DC=net',
        'bindRequiresDn'         => true,
    ],
    'server2' => [
        'host'                   => 'dc1.w.net',
        'useStartTls'            => true,
        'accountDomainName'      => 'w.net',
        'accountDomainNameShort' => 'W',
        'accountCanonicalForm'   => 3,
        'baseDn'                 => 'CN=Users,DC=w,DC=net',
    ],
];

$adapter = new LdapAdapter($config, $username, $password);

$result = $auth->authenticate($adapter);

// Messages from position 2 and up are informational messages from the LDAP
// server:
foreach ($result->getMessages() as $i => $message) {
    if ($i < 2) {
        continue;
    }

    // Potentially log the $message
}
```

As noted in the above example, the returned authentication result contains
messages even on success.  LDAP has some notoriety for difficulty in debugging,
and the `Ldap` adapter attempts to assist you with this by providing
informational messages for every authentication attempt. A later section in
this document provides more detail on the messages returned.

You will note that the configuration contains information for multiple servers.
When multiple server configuration is provided, the adapter will loop through
each until one provides successful authentication.  The above configuration
will instruct the adapter to attempt authenticattion against the OpenLDAP
server `s0.foo.net` first, falling back to the Active Directory server
`dc1.w.net`on failure.

With servers in different domains, this configuration illustrates multi-domain
authentication. You can also have multiple servers in the same domain to
provide redundancy.

Note that in this case, even though OpenLDAP has no need for the short NetBIOS
style domain name used by Windows, we provide it here for name canonicalization
purposes (described in the [Username Canonicalization](#username-canonicalization)
section below).

A later section on [server options](#server-options) details all available options)

## The API

The `Zend\Authentication\Adapter\Ldap` constructor accepts three parameters.

The `$options` parameter is required and must be an array containing one or
more sets of options.  Note that it is **an array of arrays**, with each
sub-array providing [zend-ldap](http://framework.zend.com/manual/current/en/modules/zend.ldap.introduction.html)
options. Even if you will be using only one LDAP server, the options must still
be within a sub-array.

Referring back to the example in the previous section, the information provided
in each set of options is different mainly because Active Directory does not
require a username be in DN form when binding (see the `bindRequiresDn` option
in the [Server Options](#server-options) section below), which means we can
omit a number of options associated with retrieving the DN for a username being
authenticated.

> ### What is a Distinguished Name?
>
> A DN or "distinguished name" is a string that represents the path to an
> object within the LDAP directory. Each comma-separated component is an
> attribute and value representing a node. The components are evaluated in
> reverse. For example, the user account `CN=Bob Carter,CN=Users,DC=w,DC=net`
> is located directly within the `CN=Users,DC=w,DC=net container`.  This
> structure is best explored with an LDAP browser like the ADSI Edit MMC
> snap-in for Active Directory or phpLDAPadmin.

The names of servers (e.g. 'server1' and 'server2' shown above) are largely
arbitrary and only used for informational purposes by the adapter. We recommend
using string identifiers that omit any HTML/XML entity references to ensure
they do not break any logging displays.

With multiple sets of server options, the adapter can authenticate users in
multiple domains and provide failover so that if one server is not available,
another will be queried.

> ### The Gory Details: What Happens in the Authenticate Method?
>
> When the `authenticate()` method is called, the adapter iterates over each
> set of server options, passes them to the internal `Zend\Ldap\Ldap` instance,
> and calls the `Zend\Ldap\Ldap::bind()` method with the username and password
> being authenticated.
>
> `Zend\Ldap\Ldap` checks to see if the username is qualified with a domain
> (e.g., has a domain component like `alice@foo.net` or `FOO\alice`). If a
> domain is present, but does not match either of the server's domain names
> (e.g., `foo.net` or `FOO`), a special exception is thrown and caught by
> `Zend\Authentication\Adapter\Ldap` that causes that server to be ignored, and
> progression to the next server configured.
>
> If a domain matches, or if the user did not supply a qualified username,
> `Zend\Ldap\Ldap` proceeds to try to bind with the supplied credentials. if
> the bind is unsuccessful, `Zend\Ldap\Ldap` throws a
> `Zend\Ldap\Exception\LdapException` which is caught by
> `Zend\Authentication\Adapter\Ldap` and, again, the adapter progresses to the
> next configured server.
>
> If the bind is successful, server iteration stops, and the adapter's
> `authenticate()` method returns a successful result.
>
> If all configured servers fail to authenticate, `authenticate()` returns a
> failure result with error messages from the last server consulted.

The username and password parameters of the `Zend\Authentication\Adapter\Ldap`
constructor represent the credentials being authenticated (i.e., the
credentials supplied by the user through your HTML login form). Alternatively,
they may also be set with the `setUsername()` and `setPassword()` methods.

## Server Options

Each set of server options in the context of `Zend\Authentication\Adapter\Ldap`
consists of the following options, which are passed, largely unmodified, to
`Zend\Ldap\Ldap::setOptions()`:

Name                     | Description
------------------------ | -----------
`host`                   | The hostname of LDAP server that these options represent. This option is required.
`port`                   | The port on which the LDAP server is listening. If `useSsl` is `TRUE`, the default port value is 636. If `useSsl` is `FALSE`, the default port value is 389.
`useStartTls`            | Whether or not the LDAP client should use TLS (aka SSLv2) encrypted transport. A value of `TRUE` is strongly favored in production environments to prevent passwords from be transmitted in clear text. The default value is `FALSE`, as servers frequently require that a certificate be installed separately after installation. The `useSsl` and `useStartTls` options are mutually exclusive. The `useStartTls` option should be favored over `useSsl`, but not all servers support this newer mechanism.
`useSsl`                 | Whether or not the LDAP client should use SSL encrypted transport. The `useSsl` and `useStartTls` options are mutually exclusive, but `useStartTls` should be favored if the server and LDAP client library support it. This value also changes the default port value (see port description above).
`username`               | The DN of the account used to perform account DN lookups. LDAP servers that require the username to be in DN form when performing the “bind” require this option. Meaning, if `bindRequiresDn` is `TRUE`, this option is required. This account does not need to be a privileged account; an account with read-only access to objects under the `baseDn` is all that is necessary (and preferred based on the Principle of Least Privilege).
`password`               | The password of the account used to perform account DN lookups. If this option is not supplied, the LDAP client will attempt an “anonymous bind” when performing account DN lookups.
`bindRequiresDn`         | Some LDAP servers require that the username used to bind be in DN form like `CN=Alice Baker,OU=Sales,DC=foo,DC=net` (basically all servers except Active Directory). If this option is `TRUE`, this instructs `Zend\Ldap\Ldap` to automatically retrieve the DN corresponding to the username being authenticated, if it is not already in DN form, and then re-bind with the proper DN. The default value is `FALSE`. Currently only Microsoft Active Directory Server (ADS) is known not to require usernames to be in DN form when binding, and therefore this option may be `FALSE` with AD (and it should be, as retrieving the DN requires an extra round trip to the server). Otherwise, this option must be set to `TRUE` (e.g. for OpenLDAP). This option also controls the default `accountFilterFormat` used when searching for accounts. See the `accountFilterFormat` option.
`baseDn`                 | The DN under which all accounts being authenticated are located. This option is required. if you are uncertain about the correct baseDn value, it should be sufficient to derive it from the user’s DNS domain using `DC=` components. For example, if the user’s principal name is `alice@foo.net`, a `baseDn` of `DC=foo,DC=net` should work. A more precise location (e.g., `OU=Sales,DC=foo,DC=net`) will be more efficient, however.
`accountCanonicalForm`   | A value of 2, 3, or 4 indicating the form to which account names should be canonicalized after successful authentication. Values are as follows: 2 for traditional username style names (e.g., `alice`), 3 for backslash-style names (e.g., `FOO\alice`) or 4 for principal style usernames (e.g., `alice@foo.net`). The default value is 4 (e.g., `alice@foo.net`). For example, with a value of 3, the identity returned by `Zend\Authentication\Result::getIdentity()` (and `Zend\Authentication\AuthenticationService::getIdentity()`, if `Zend\Authentication\AuthenticationService` was used) will always be `FOO\alice`, regardless of what form Alice supplied, whether it be `alice`, `alice@foo.net`, `FOO\alice`, `FoO\aLicE`, `foo.net\alice`, etc. See the []Account Name Canonicalization](http://framework.zend.com/manual/current/en/modules/zend.ldap.introduction.html#account-name-canonicalization) section in the zend-ldap documentation for details. Note that when using multiple sets of server options it is recommended, but not required, that the same `accountCanonicalForm` be used with all server options so that the resulting usernames are always canonicalized to the same form (e.g., if you canonicalize to `EXAMPLE\username` with an AD server but to `username@example.com` with an OpenLDAP server, that may be awkward for the application’s high-level logic).
`accountDomainName`      | The FQDN domain name for which the target LDAP server is an authority (e.g., `example.com`). This option is used to canonicalize names so that the username supplied by the user can be converted as necessary for binding. It is also used to determine if the server is an authority for the supplied username (e.g., if `accountDomainName` is `foo.net` and the user supplies `bob@bar.net`, the server will not be queried, and a failure will result). This option is not required, but if it is not supplied, usernames in principal name form (e.g., `alice@foo.net`) are not supported. It is strongly recommended that you supply this option, as there are many use-cases that require generating the principal name form.
`accountDomainNameShort` | The ‘short’ domain for which the target LDAP server is an authority (e.g., `FOO`). Note that there is a 1:1 mapping between the `accountDomainName` and `accountDomainNameShort`. This option should be used to specify the NetBIOS domain name for Windows networks, but may also be used by non-AD servers (e.g., for consistency when multiple sets of server options with the backslash style `accountCanonicalForm`). This option is not required but if it is not supplied, usernames in backslash form (e.g., `FOO\alice`) are not supported.
`accountFilterFormat`    | The LDAP search filter used to search for accounts. This string is a `printf()`-style expression that must contain one `%s` to accommodate the username. The default value is `(&(objectClass=user)(sAMAccountName=%s))`, unless `bindRequiresDn` is set to `TRUE`, in which case the default is `(&(objectClass=posixAccount)(uid=%s))`. For example, if for some reason you wanted to use `bindRequiresDn = true` with AD you would need to set `accountFilterFormat = '(&(objectClass=user)(sAMAccountName=%s))'`.
`optReferrals`           | If set to `TRUE`, this option indicates to the LDAP client that referrals should be followed. The default value is `FALSE`.

> ### TLS and SSL
>
> If you enable `useStartTls = TRUE` or `useSsl = TRUE` you may find that the
> LDAP client generates an error claiming that it cannot validate the server's
> certificate. Assuming the PHP LDAP extension is ultimately linked to the
> OpenLDAP client libraries, to resolve this issue you can set `TLS_REQCERT
> never` in the OpenLDAP client `ldap.conf` (and restart the web server) to
> indicate to the OpenLDAP client library that you trust the server.
> Alternately, if you are concerned that the server could be spoofed, you can
> export the LDAP server's root certificate and put it on the web server so
> that the OpenLDAP client can validate the server's identity.

## Collecting Debugging Messages

`Zend\Authentication\Adapter\Ldap` collects debugging information within its
`authenticate()` method. This information is stored in the
`Zend\Authentication\Result` object as messages. The array returned by
`Zend\Authentication\Result::getMessages()` is described as follows:

Messages Array Index | Description
-------------------- | -----------
Index 0              | A generic, user-friendly message that is suitable for displaying to users (e.g., "Invalid credentials"). If the authentication is successful, this string is empty.
Index 1              | A more detailed error message that is not suitable to be displayed to users but should be logged for the benefit of server operators. If the authentication is successful, this string is empty.
Indexes 2 and higher | All log messages in order starting at index 2.

In practice, index 0 should be displayed to the user (e.g., using the
[FlashMessenger helper](https://docs.zendframework.com/zend-mvc-plugin-flashmessenger)),
index 1 should be logged and, if debugging information is being collected,
indexes 2 and higher could be logged as well (although the final message always
includes the string from index 1).

## Common Options for Specific Servers

### Options for Active Directory

For AD*, the following options are noteworthy:

Name                     | Additional Notes
------------------------ | ----------------
`host`                   | As with all servers, this option is required.
`useStartTls`            | For the sake of security, this should be `TRUE` if the server has the necessary certificate installed.
`useSsl`                 | Possibly used as an alternative to `useStartTls` (see above).
`baseDn`                 | As with all servers, this option is required. By default, AD places all user accounts under the Users container (e.g., `CN=Users,DC=foo,DC=net`), but the default is not common in larger organizations. Ask your AD administrator what the best DN for accounts for your application would be.
`accountCanonicalForm`   | You almost certainly want this to be 3 for backslash style names (e.g., `FOO\alice`), which are most familiar to Windows users. You should not use the unqualified form 2 (e.g., `alice`), as this may grant access to your application to users with the same username in other trusted domains (e.g., `BAR\alice` and `FOO\alice` will be treated as the same user). (See also note below.)
`accountDomainName`      | This is required with AD unless `accountCanonicalForm` 2 is used, which, again, is discouraged.
`accountDomainNameShort` | The NetBIOS name of the domain that users are in and for which the AD server is an authority. This is required if the backslash style `accountCanonicalForm` is used.

> #### Use qualified account names
>
> Technically there should be no danger of accidental cross-domain
> authentication with the current `Zend\Authentication\Adapter\Ldap`
> implementation, since server domains are explicitly checked, but this may not
> be true of a future implementation that discovers the domain at runtime, or
> if an alternative adapter is used (e.g., Kerberos). In general, account name
> ambiguity is known to be the source of security issues, so always try to use
> qualified account names.

### Options for OpenLDAP

For OpenLDAP or a generic LDAP server using a typical posixAccount style
schema, the following options are noteworthy:

Name                   | Additional Notes
---------------------- | ----------------
host                   | As with all servers, this option is required.
useStartTls            | For the sake of security, this should be `TRUE` if the server has the necessary certificate installed.
useSsl                 | Possibly used as an alternative to `useStartTls` (see above).
username               | Required and must be a DN, as OpenLDAP requires that usernames be in DN form when performing a bind. Try to use an unprivileged account.
password               | The password corresponding to the username above, but this may be omitted if the LDAP server permits an anonymous binding to query user accounts.
bindRequiresDn         | Required and must be TRUE, as OpenLDAP requires that usernames be in DN form when performing a bind.
baseDn                 | As with all servers, this option is required and indicates the DN under which all accounts being authenticated are located.
accountCanonicalForm   | Optional, but the default value is 4 (principal style names like `alice@foo.net`), which may not be ideal if your users are used to backslash style names (e.g., `FOO\alice`). For backslash style names, use value 3.
accountDomainName      | Required unless you're using `accountCanonicalForm` 2, which is not recommended.
accountDomainNameShort | If AD is not also being used, this value is not required. Otherwise, if `accountCanonicalForm` 3 is used, this option is required and should be a short name that corresponds adequately to the `accountDomainName` (e.g., if your `accountDomainName` is `foo.net`, a good `accountDomainNameShort` value might be `FOO`).
