<?php
/**
 * @see       https://github.com/zendframework/zend-authentication for the canonical source repository
 * @copyright Copyright (c) 2013-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-authentication/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Authentication\Validator;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zend\Authentication\Adapter\ValidatableAdapterInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Exception;
use Zend\Authentication\Result as AuthenticationResult;
use Zend\Authentication\Validator\Authentication as AuthenticationValidator;
use ZendTest\Authentication as AuthTest;

class AuthenticationTest extends TestCase
{
    /**
     * @var AuthenticationValidator
     */
    protected $validator;

    /**
     * @var AuthenticationService
     */
    protected $authService;

    /**
     * @var ValidatableAdapterInterface
     */
    protected $authAdapter;

    public function setUp()
    {
        $this->validator = new AuthenticationValidator();
        $this->authService = new AuthenticationService();
        $this->authAdapter = new AuthTest\TestAsset\ValidatableAdapter();
    }

    public function testOptions()
    {
        $auth = new AuthenticationValidator([
            'adapter' => $this->authAdapter,
            'service' => $this->authService,
            'identity' => 'username',
            'credential' => 'password',
        ]);
        $this->assertSame($auth->getAdapter(), $this->authAdapter);
        $this->assertSame($auth->getService(), $this->authService);
        $this->assertSame($auth->getIdentity(), 'username');
        $this->assertSame($auth->getCredential(), 'password');
    }

    public function testSetters()
    {
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->setCredential('credential');
        $this->assertSame($this->validator->getAdapter(), $this->authAdapter);
        $this->assertSame($this->validator->getService(), $this->authService);
        $this->assertSame($this->validator->getIdentity(), 'username');
        $this->assertSame($this->validator->getCredential(), 'credential');
    }

    public function testNoIdentityThrowsRuntimeException()
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Identity must be set prior to validation');
        $this->validator->isValid('password');
    }

    public function testNoAdapterThrowsRuntimeException()
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('Adapter must be set prior to validation');
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');
    }

    public function testNoServiceThrowsRuntimeException()
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('AuthenticationService must be set prior to validation');
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');
    }

    public function testEqualsMessageTemplates()
    {
        $validator = $this->validator;
        $this->assertAttributeEquals(
            $validator->getOption('messageTemplates'),
            'messageTemplates',
            $validator
        );
    }

    public function testWithoutContext()
    {
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->setCredential('credential');

        $this->assertEquals('username', $this->validator->getIdentity());
        $this->assertEquals('credential', $this->validator->getCredential());
        $this->assertTrue($this->validator->isValid());
    }

    public function testWithContext()
    {
        $this->validator->setAdapter($this->authAdapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password', [
            'username' => 'myusername',
            'password' => 'mypassword',
        ]);
        $adapter = $this->validator->getAdapter();
        $this->assertEquals('myusername', $adapter->getIdentity());
        $this->assertEquals('mypassword', $adapter->getCredential());
    }

    public function errorMessagesProvider()
    {
        return [
            'failure' => [
                AuthenticationResult::FAILURE,
                false,
                [AuthenticationValidator::GENERAL => 'Authentication failed'],
            ],
            'identity-not-found' => [
                AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND,
                false,
                [AuthenticationValidator::IDENTITY_NOT_FOUND => 'Invalid identity'],
            ],
            'identity-ambiguous' => [
                AuthenticationResult::FAILURE_IDENTITY_AMBIGUOUS,
                false,
                [AuthenticationValidator::IDENTITY_AMBIGUOUS => 'Identity is ambiguous'],
            ],
            'credential-invalid' => [
                AuthenticationResult::FAILURE_CREDENTIAL_INVALID,
                false,
                [AuthenticationValidator::CREDENTIAL_INVALID => 'Invalid password'],
            ],
            'uncategorized' => [
                AuthenticationResult::FAILURE_UNCATEGORIZED,
                false,
                [AuthenticationValidator::UNCATEGORIZED => 'Authentication failed'],
            ],
            'success' => [
                AuthenticationResult::SUCCESS,
                true,
                [],
            ],
        ];
    }

    /**
     * @dataProvider errorMessagesProvider
     * @param int   $code
     * @param bool  $valid
     * @param array $messages
     */
    public function testErrorMessages($code, $valid, $messages)
    {
        $adapter = new AuthTest\TestAsset\ValidatableAdapter($code);

        $this->validator->setAdapter($adapter);
        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->setCredential('credential');

        $this->assertEquals($valid, $this->validator->isValid());
        $this->assertEquals($messages, $this->validator->getMessages());
    }

    /**
     * Test using Authentication Service's adapter
     */
    public function testUsingAdapterFromService()
    {
        $this->authService->setAdapter($this->authAdapter);

        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');

        $this->assertEquals('username', $this->validator->getIdentity());
        $this->assertEquals('password', $this->validator->getCredential());
        $this->assertEquals('username', $this->authAdapter->getIdentity());
        $this->assertEquals('password', $this->authAdapter->getCredential());
        $this->assertNull($this->validator->getAdapter());
        $this->assertTrue($this->validator->isValid());
    }

    /**
     * Ensures that isValid() throws an exception when Authentication Service's
     * adapter is not an instance of ValidatableAdapterInterface
     */
    public function testUsingNonValidatableAdapterFromServiceThrowsRuntimeException()
    {
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            '%s; %s given',
            ValidatableAdapterInterface::class,
            AuthTest\TestAsset\SuccessAdapter::class
        ));

        $adapter = new AuthTest\TestAsset\SuccessAdapter();
        $this->authService->setAdapter($adapter);

        $this->validator->setService($this->authService);
        $this->validator->setIdentity('username');
        $this->validator->isValid('password');
    }
}
