# Callback Verification

Some verification operations cannot be performed well on RDBMS servers. Other
times, you may be unsure which RDBMS system you will be using long-term, and
need to ensure authentication will work consistently. For these situations, you
can use the `Zend\Authentication\Adapter\DbTable\CallbackCheckAdapter` adapter.
Similar to the [CredentialTreatmentAdapter](credential-treatment.md), it
accepts a table name, identity column, and credential column; however, instead
of a credential treatment, it accepts a *credential validation callback* that
is executed when the database returns any matches, and which can be used to
perform additional credential verifications.

## Configuration options

The available configuration options include:

- `tableName`: This is the name of the database table that contains the
  authentication credentials, and against which the database authentication
  query is performed.
- `identityColumn`: This is the name of the database table column used to
  represent the identity.  The identity column must contain unique values, such
  as a username or e-mail address.
- `credentialColumn`: This is the name of the database table column used to
  represent the credential. Under a simple identity and password authentication
  scheme, the credential value corresponds to the password.
- `credentialValidationCallback`: A PHP callable to execute when the database returns matches. The callback will receive:
  - the value of the `credentialColumn` returned from the database
  - the credential that was used by the adapter during authentication

## Basic Usage

Many databases do not provide functions that implement a cryptographically
secure hashing mechanism. Additionally, you may want to ensure that should you
switch database systems, hashing is consistent. This is a perfect use case for
the `CallbackCheckAdapter` adapter; you can implement the password hashing and
verification within PHP instead.

The following code creates an adapter for an in-memory database, creates a
simple table schema, and inserts a row against which we can perform an
authentication query later. This example requires the PDO SQLite extension to
be available:

```php
use Zend\Db\Adapter\Adapter as DbAdapter;

// Create a SQLite database connection
$dbAdapter = new DbAdapter([
    'driver'   => 'Pdo_Sqlite',
    'database' => 'data/sqlite.db',
]);

// Build a simple table creation query
$sqlCreate = 'CREATE TABLE [users] ('
    . '[id] INTEGER  NOT NULL PRIMARY KEY, '
    . '[username] VARCHAR(50) UNIQUE NOT NULL, '
    . '[password] VARCHAR(255) NULL, '
    . '[real_name] VARCHAR(150) NULL)';

// Create the authentication credentials table
$dbAdapter->query($sqlCreate);

// Build a query to insert a row for which authentication may succeed
$sqlInsert = "INSERT INTO users (username, password, real_name) "
    . "VALUES ('my_username', 'my_password', 'My Real Name')";

// Insert the data
$dbAdapter->query($sqlInsert);
```

As you add users, you will need to create a hash of the password provided and
insert that into the database. For users on PHP 5.5+, you can use
[password_hash()](http://php.net/password_hash):

```php
$hash = password_hash($password, PASSWORD_DEFAULT);
```

> ### Password hash length
>
> As of the time of writing, PHP uses a bcrypt algorithm by default for hashing
> passwords with `password_hash()`, and this produces 60 character strings. However,
> the default may change over time, and php.net recommends using 255 character
> fields for storage to allow for larger hash sizes in the future.

To verify a password, we'll create a callback that uses
[password_verify()](http://php.net/manual/en/function.password-verify.php):

```php
$passwordValidation = function ($hash, $password) {
    return password_verify($password, $hash);
};
```

Now that we have the database connection and a password validation function,
we can create our `Zend\Authentication\Adapter\DbTable\CallbackCheckAdapter` adapter
instance, passing the options to the constructor or later via setter methods:

```php
use Zend\Authentication\Adapter\DbTable\CallbackCheckAdapter as AuthAdapter;

// Configure the instance with constructor parameters:
$authAdapter = new AuthAdapter(
    $dbAdapter,
    'users',
    'username',
    'password',
    $passwordValidation
);

// Or configure the instance with setter methods:
$authAdapter = new AuthAdapter($dbAdapter);

$authAdapter
    ->setTableName('users')
    ->setIdentityColumn('username')
    ->setCredentialColumn('password')
    ->setCredentialValidatinCallback($passwordValidation);
```

At this point, the authentication adapter instance is ready to accept
authentication queries. In order to formulate an authentication query, the
input credential values are passed to the adapter prior to calling the
`authenticate()` method:

```php
// Set the input credential values (e.g., from a login form):
$authAdapter
    ->setIdentity('my_username')
    ->setCredential('my_password');

// Perform the authentication query, saving the result
$result = $authAdapter->authenticate();
```

In addition to the availability of the `getIdentity()` method upon the
authentication result object, `Zend\Authentication\Adapter\DbTable\CallbackCheckAdapter`
also supports retrieving the table row upon authentication success:

```php
// Print the identity:
echo $result->getIdentity() . "\n\n";

// Print the result row:
print_r($authAdapter->getResultRowObject());

/* Output:
my_username

Array
(
    [id] => 1
    [username] => my_username
    [password] => my_password
    [real_name] => My Real Name
)
*/
```

Since the table row contains the credential value, it is important to secure
the values against unintended access.

When retrieving the result object, we can either specify what columns to
return, or what columns to omit:

```php
// Specify the columns to return:
$columnsToReturn = [
    'id',
    'username',
    'real_name',
];
print_r($authAdapter->getResultRowObject($columnsToReturn));

/* Output:

Array
(
   [id] => 1
   [username] => my_username
   [real_name] => My Real Name
)
*/

// Or specify the columns to omit; when using this approach,
// pass a null value as the first argument to getResultRowObject():
$columnsToOmit = ['password'];
print_r($authAdapter->getResultRowObject(null, $columnsToOmit);

/* Output:

Array
(
   [id] => 1
   [username] => my_username
   [real_name] => My Real Name
)
*/
```

## Advanced Usage

While the basic use case will fit most scenarios, there may be cases where you have
specialized needs, such as additional criteria that needs to be met for a user to
match.

### Adding criteria to match

Since the validation callback is only provided the hash value from the database
and the credential provided by the user, you cannot do more complex matching
within it. However, you can add criteria to the SQL sent to the server by
retrieving the `Zend\Db\Sql\Select` instance is uses.

As an example, many websites require a user to activate their account before
allowing them to login for the first time. We can add that criteria as follows:

```php
// Create a basic adapter, with only an MD5() credential treatment:
$adapter = new AuthAdapter(
    $db,
    'users',
    'username',
    'password',
    $passwordValidation
);

// Now retrieve the Select instance and modify it:
$select = $adapter->getDbSelect();
$select->where('active = "TRUE"');

// Authenticate; this will include "users.active = TRUE" in the WHERE clause:
$adapter->authenticate();
```
