<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link       http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Authentication\Validator;

use Traversable;
use Zend\Authentication\Adapter\ValidatableAdapterInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result;
use Zend\Authentication\Exception;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\AbstractValidator;

/**
 * Authentication Validator
 */
class Authentication extends AbstractValidator
{
    /**
     * Error codes
     * @const string
     */
    const IDENTITY_NOT_FOUND = 'identityNotFound';
    const IDENTITY_AMBIGUOUS = 'identityAmbiguous';
    const CREDENTIAL_INVALID = 'credentialInvalid';
    const UNCATEGORIZED      = 'uncategorized';
    const GENERAL            = 'general';

    /**
     * Error Messages
     * @var array
     */
    protected $messageTemplates = [
        self::IDENTITY_NOT_FOUND => 'Invalid identity',
        self::IDENTITY_AMBIGUOUS => 'Identity is ambiguous',
        self::CREDENTIAL_INVALID => 'Invalid password',
        self::UNCATEGORIZED      => 'Authentication failed',
        self::GENERAL            => 'Authentication failed',
    ];

    /**
     * Authentication Adapter
     * @var ValidatableAdapterInterface
     */
    protected $adapter;

    /**
     * Identity (or field)
     * @var string
     */
    protected $identity;

    /**
     * Credential (or field)
     * @var string
     */
    protected $credential;

    /**
     * Authentication Service
     * @var AuthenticationService
     */
    protected $service;

    /**
     * Authentication\Result codes mapping
     * @var array
     */
    protected static $codeMap = [
        Result::FAILURE_IDENTITY_NOT_FOUND => self::IDENTITY_NOT_FOUND,
        Result::FAILURE_CREDENTIAL_INVALID => self::CREDENTIAL_INVALID,
        Result::FAILURE_IDENTITY_AMBIGUOUS => self::IDENTITY_AMBIGUOUS,
        Result::FAILURE_UNCATEGORIZED      => self::UNCATEGORIZED,
    ];

    /**
     * Sets validator options
     *
     * @param mixed $options
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (is_array($options)) {
            if (array_key_exists('adapter', $options)) {
                $this->setAdapter($options['adapter']);
            }
            if (array_key_exists('identity', $options)) {
                $this->setIdentity($options['identity']);
            }
            if (array_key_exists('credential', $options)) {
                $this->setCredential($options['credential']);
            }
            if (array_key_exists('service', $options)) {
                $this->setService($options['service']);
            }
        }
        parent::__construct($options);
    }

    /**
     * Get Adapter
     *
     * @return ValidatableAdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Set Adapter
     *
     * @param  ValidatableAdapterInterface $adapter
     * @return Authentication
     */
    public function setAdapter(ValidatableAdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Get Identity
     *
     * @return mixed
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * Set Identity
     *
     * @param  mixed          $identity
     * @return Authentication
     */
    public function setIdentity($identity)
    {
        $this->identity = $identity;

        return $this;
    }

    /**
     * Get Credential
     *
     * @return mixed
     */
    public function getCredential()
    {
        return $this->credential;
    }

    /**
     * Set Credential
     *
     * @param  mixed          $credential
     * @return Authentication
     */
    public function setCredential($credential)
    {
        $this->credential = $credential;

        return $this;
    }

    /**
     * Get Service
     *
     * @return AuthenticationService
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set Service
     *
     * @param  AuthenticationService $service
     * @return Authentication
     */
    public function setService(AuthenticationService $service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Returns true if and only if authentication result is valid
     *
     * If authentication result fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value   OPTIONAL Credential (or field)
     * @param  array $context OPTIONAL Authentication data (identity and/or credential)
     * @return bool
     * @throws Exception\RuntimeException
     */
    public function isValid($value = null, $context = null)
    {
        if ($value !== null) {
            $this->setCredential($value);
        }

        if ($this->identity === null) {
            throw new Exception\RuntimeException('Identity must be set prior to validation');
        }
        if (($context !== null) && array_key_exists($this->identity, $context)) {
            $identity = $context[$this->identity];
        } else {
            $identity = $this->identity;
        }

        if ($this->credential === null) {
            throw new Exception\RuntimeException('Credential must be set prior to validation');
        }
        if (($context !== null) && array_key_exists($this->credential, $context)) {
            $credential = $context[$this->credential];
        } else {
            $credential = $this->credential;
        }

        if (!$this->service) {
            throw new Exception\RuntimeException('AuthenticationService must be set prior to validation');
        }

        if (!$this->adapter) {
            $adapter = $this->service->getAdapter();
            if (!$adapter) {
                throw new Exception\RuntimeException('Adapter must be set prior to validation');
            }
            if (!$adapter instanceof ValidatableAdapterInterface) {
                throw new Exception\RuntimeException(sprintf(
                    'Adapter must be an instance of ValidatableAdapterInterface, %s given',
                    (is_object($adapter) ? get_class($adapter) : gettype($adapter))
                ));
            }
        } else {
            $adapter = $this->adapter;
        }

        $adapter->setIdentity($identity);
        $adapter->setCredential($credential);

        $result = $this->service->authenticate($this->adapter);

        if (!$result->isValid()) {
            $code = self::GENERAL;
            if (array_key_exists($result->getCode(), static::$codeMap)) {
                $code = static::$codeMap[$result->getCode()];
            }
            $this->error($code);

            return false;
        }

        return true;
    }
}
