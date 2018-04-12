<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Authentication\Adapter\Http;

use PHPUnit\Framework\TestCase;
use Zend\Authentication\Adapter\Http;
use Zend\Authentication\Adapter;
use Zend\Authentication;
use Zend\Http\Headers;
use Zend\Http\Request;
use Zend\Http\Response;

/**
 * @group      Zend_Auth
 */
class ObjectTest extends TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * Path to test files
     *
     * @var string
     */
    protected $_filesPath;

    /**
     * HTTP Basic configuration
     *
     * @var array
     */
    protected $_basicConfig;

    /**
     * HTTP Digest configuration
     *
     * @var array
     */
    protected $_digestConfig;

    /**
     * HTTP Basic Digest configuration
     *
     * @var array
     */
    protected $_bothConfig;

    /**
     * File resolver setup against with HTTP Basic auth file
     *
     * @var Http\FileResolver
     */
    protected $_basicResolver;

    /**
     * File resolver setup against with HTTP Digest auth file
     *
     * @var Http\FileResolver
     */
    protected $_digestResolver;
    // @codingStandardsIgnoreEnd

    /**
     * Sets up test configuration
     *
     * @return void
     */
    public function setUp()
    {
        $this->_filesPath      = __DIR__ . '/TestAsset';
        $this->_basicResolver  = new Http\FileResolver("$this->_filesPath/htbasic.1");
        $this->_digestResolver = new Http\FileResolver("$this->_filesPath/htdigest.3");
        $this->_basicConfig    = [
            'accept_schemes' => 'basic',
            'realm'          => 'Test Realm'
        ];
        $this->_digestConfig   = [
            'accept_schemes' => 'digest',
            'realm'          => 'Test Realm',
            'digest_domains' => '/ http://localhost/',
            'nonce_timeout'  => 300
        ];
        $this->_bothConfig     = [
            'accept_schemes' => 'basic digest',
            'realm'          => 'Test Realm',
            'digest_domains' => '/ http://localhost/',
            'nonce_timeout'  => 300
        ];
    }

    public function testValidConfigs()
    {
        $configs = [
            $this->_basicConfig,
            $this->_digestConfig,
            $this->_bothConfig,
        ];
        foreach ($configs as $config) {
            new Adapter\Http($config);
        }
        $this->addToAssertionCount(1);
    }

    /**
     * @return array
     */
    public function invalidConfigs()
    {
        return [
            'bad1' => [
                [
                    'auth_type' => 'bogus',
                    'realm'     => 'Test Realm',
                ],
            ],
            'bad2' => [
                [
                    'auth_type'      => 'digest',
                    'realm'          => 'Bad: "Chars"'."\n",
                    'digest_domains' => '/ /admin',
                    'nonce_timeout'  => 300,
                ],
            ],
            'bad3' => [
                [
                    'auth_type'      => 'digest',
                    'realm'          => 'Test Realm',
                    'digest_domains' => 'no"quotes'."\tor tabs",
                    'nonce_timeout'  => 300,
                ],
            ],
            'bad4' => [
                [
                    'auth_type'      => 'digest',
                    'realm'          => 'Test Realm',
                    'digest_domains' => '/ /admin',
                    'nonce_timeout'  => 'junk',
                ],
            ]
        ];
    }

    /**
     * @dataProvider invalidConfigs
     */
    public function testInvalidConfigs($cfg)
    {
        $this->expectException(Adapter\Exception\ExceptionInterface::class);
        new Adapter\Http($cfg);
    }

    public function testAuthenticateArgs()
    {
        $a = new Adapter\Http($this->_basicConfig);

        try {
            $a->authenticate();
            $this->fail('Attempted authentication without request/response objects');
        } catch (Adapter\Exception\ExceptionInterface $e) {
            // Good, it threw an exception
        }

        $request  = new Request;
        $response = new Response;

        // If this throws an exception, it fails
        $response = $a->setRequest($request)
          ->setResponse($response)
          ->authenticate();

        $this->assertInstanceOf(Authentication\Result::class, $response);
    }

    public function noResolvers()
    {
        return [
            'basic' => [
                'Basic',
                '_basicConfig',
            ],
            'digest' => [
                'Digest',
                '_digestConfig',
            ],
        ];
    }

    /**
     * @dataProvider noResolvers
     */
    public function testNoResolvers($authHeader, $cfgProperty)
    {
        // Stub request for Basic auth
        $headers  = new Headers;
        $headers->addHeaderLine(
            'Authorization',
            sprintf(
                '%s <followed by a space character',
                $authHeader
            )
        );

        $request  = new Request;
        $request->setHeaders($headers);
        $response = new Response;

        $a = new Adapter\Http($this->$cfgProperty);
        $a->setRequest($request)
          ->setResponse($response);

        $this->expectException(Adapter\Exception\ExceptionInterface::class);
        $a->authenticate();
    }

    public function testWrongResolverUsed()
    {
        $response = new Response();
        $headers  = new Headers();
        $request  = new Request();

        $headers->addHeaderLine('Authorization', 'Basic <followed by a space character');
        $request->setHeaders($headers);

        // Test a Digest auth process while the request is containing a Basic auth header
        $adapter = new Adapter\Http($this->_digestConfig);
        $adapter->setDigestResolver($this->_digestResolver)
                ->setRequest($request)
                ->setResponse($response);
        $result = $adapter->authenticate();

        $this->assertEquals($result->getCode(), Authentication\Result::FAILURE_CREDENTIAL_INVALID);
    }

    public function testUnsupportedScheme()
    {
        $response = new Response();
        $headers  = new Headers();
        $request  = new Request();

        $headers->addHeaderLine('Authorization', 'NotSupportedScheme <followed by a space character');
        $request->setHeaders($headers);

        $a = new Adapter\Http($this->_digestConfig);
        $a->setDigestResolver($this->_digestResolver)
          ->setRequest($request)
          ->setResponse($response);
        $result = $a->authenticate();
        $this->assertEquals($result->getCode(), Authentication\Result::FAILURE_UNCATEGORIZED);
    }
}
