<?php
/**
 * @see       https://github.com/zendframework/zend-authentication for the canonical source repository
 * @copyright Copyright (c) 2013-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-authentication/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Authentication\Validator;

use Traversable;
use Zend\Authentication\Adapter\ValidatableAdapterInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Result;
use Zend\Authentication\Exception;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\AbstractValidator;

use function is_array;
use function is_string;

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
     * Authentication\Result codes mapping
     * @const array
     */
    const CODE_MAP = [
        Result::FAILURE_IDENTITY_NOT_FOUND => self::IDENTITY_NOT_FOUND,
        Result::FAILURE_CREDENTIAL_INVALID => self::CREDENTIAL_INVALID,
        Result::FAILURE_IDENTITY_AMBIGUOUS => self::IDENTITY_AMBIGUOUS,
        Result::FAILURE_UNCATEGORIZED      => self::UNCATEGORIZED,
    ];

    /**
     * Authentication\Result codes mapping configurable overrides
     * @var string[]
     */
    protected $codeMap = [];

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
     * Sets validator options
     *
     * @param array|Traversable $options
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (is_array($options)) {
            if (isset($options['adapter'])) {
                $this->setAdapter($options['adapter']);
            }
            if (isset($options['identity'])) {
                $this->setIdentity($options['identity']);
            }
            if (isset($options['credential'])) {
                $this->setCredential($options['credential']);
            }
            if (isset($options['service'])) {
                $this->setService($options['service']);
            }
            if (isset($options['code_map'])) {
                foreach ($options['code_map'] as $code => $template) {
                    if (empty($template) || ! is_string($template)) {
                        throw new Exception\InvalidArgumentException(
                            'Message key in code_map option must be a non-empty string'
                        );
                    }
                    if (! isset($this->messageTemplates[$template])) {
                        $this->messageTemplates[$template] = $this->messageTemplates[static::GENERAL];
                    }
                    $this->codeMap[(int) $code] = $template;
                }
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
     * @param ValidatableAdapterInterface $adapter
     * @return self Provides a fluent interface
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
     * @param mixed $identity
     * @return self Provides a fluent interface
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
     * @param mixed $credential
     * @return self Provides a fluent interface
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
     * @param AuthenticationService $service
     * @return self Provides a fluent interface
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
     * @param null|mixed $value OPTIONAL Credential (or field)
     * @param null|array $context OPTIONAL Authentication data (identity and/or credential)
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

        $identity = ($context !== null) && array_key_exists($this->identity, $context)
            ? $context[$this->identity]
            : $this->identity;

        if ($this->credential === null) {
            throw new Exception\RuntimeException('Credential must be set prior to validation');
        }

        $credential = ($context !== null) && array_key_exists($this->credential, $context)
            ? $context[$this->credential]
            : $this->credential;

        if (! $this->service) {
            throw new Exception\RuntimeException('AuthenticationService must be set prior to validation');
        }

        $adapter = $this->adapter ?: $this->getAdapterFromAuthenticationService();
        $adapter->setIdentity($identity);
        $adapter->setCredential($credential);

        $result = $this->service->authenticate($adapter);

        if ($result->isValid()) {
            return true;
        }

        $messageKey = $this->mapResultCodeToMessageKey($result->getCode());
        $this->error($messageKey);

        return false;
    }

    /**
     * @param int $code Authentication result code
     * @return string Message key that should be used for the code
     */
    protected function mapResultCodeToMessageKey($code)
    {
        if (isset($this->codeMap[$code])) {
            return $this->codeMap[$code];
        }
        if (array_key_exists($code, static::CODE_MAP)) {
            return static::CODE_MAP[$code];
        }
        return self::GENERAL;
    }

    /**
     * @return ValidatableAdapterInterface
     * @throws Exception\RuntimeException if no adapter present in
     *     authentication service
     * @throws Exception\RuntimeException if adapter present in authentication
     *     service is not a ValidatableAdapterInterface instance
     */
    private function getAdapterFromAuthenticationService()
    {
        $adapter = $this->service->getAdapter();
        if (! $adapter) {
            throw new Exception\RuntimeException('Adapter must be set prior to validation');
        }

        if (! $adapter instanceof ValidatableAdapterInterface) {
            throw new Exception\RuntimeException(sprintf(
                'Adapter must be an instance of %s; %s given',
                ValidatableAdapterInterface::class,
                is_object($adapter) ? get_class($adapter) : gettype($adapter)
            ));
        }

        return $adapter;
    }
}
