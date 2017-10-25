# Database Table Authentication

The adapters under the `Zend\Authentication\Adapter\DbTable` provide the
ability to authenticate against credentials stored in a database table, with
two approaches possible:

- usage of a *credential treatment* function on the RDBMS server with the
  provided credentials.
- execution of a PHP callback on the identity returned by the RDBMS server.

Because each adapter requires an instance of `Zend\Db\Adapter\Adapter` to be
passed to its constructor, each instance is bound to a particular database
connection.  Other configuration options may be set through the constructor and
through instance methods, one for each option.

## `Zend\Authentication\Adapter\DbTable` class is deprecated

The concrete adapter `Zend\Authentication\Adapter\DbTable` has been
deprecated since 2.2.0, and its responsibilities have been split into two,
`Zend\Authentication\Adapter\DbTable\CallbackCheck` and
`Zend\Authentication\Adapter\DbTable\CredentialTreatmentAdapter`.

If you were using `Zend\Authentication\Adapter\DbTable` previously, you can
replace its usage with
`Zend\Authentication\Adapter\DbTable\CredentialTreatmentAdapter`, as the APIs
are the same; `DbTable` extends `CredentialTreatmentAdapter` at this time.
