<?php

/*
 * Regiondo Bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) 2018, derhaeuptling
 * @author     Codefog <https://codefog.pl>
 * @license    MIT
 */

namespace Derhaeuptling\RegiondoBundle;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ClientFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $secureKey;

    /**
     * @var bool
     */
    private $sandbox = false;

    /**
     * ClientFactory constructor.
     *
     * @param string $publicKey
     * @param string $secureKey
     * @param bool   $sandbox
     */
    public function __construct(string $publicKey, string $secureKey, bool $sandbox = false)
    {
        $this->publicKey = $publicKey;
        $this->secureKey = $secureKey;
        $this->sandbox = $sandbox;
    }

    /**
     * Set the cache provider.
     *
     * @param CacheProvider $cacheProvider
     */
    public function setCacheProvider(CacheProvider $cacheProvider): void
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * Create the client.
     *
     * @param int   $cacheTtl
     * @param array $config
     *
     * @return Client
     */
    public function create(int $cacheTtl = 0, array $config = []): Client
    {
        $stack = HandlerStack::create();
        $stack->push($this->getHeadersMiddleware());

        // Add the cache middleware
        if (null !== $this->cacheProvider && $cacheTtl > 0) {
            $stack->push(new CacheMiddleware(new GreedyCacheStrategy(new DoctrineCacheStorage($this->cacheProvider), $cacheTtl)), 'cache');
        }

        $guzzle = new GuzzleClient(\array_merge([
            'base_uri' => $this->sandbox ? 'https://sandbox-api.regiondo.com/v1/' : 'https://api.regiondo.com/v1/',
            'handler' => $stack,
        ], $config));

        return new Client($guzzle, $this->logger);
    }

    /**
     * Flush the cache.
     */
    public function flushCache(): void
    {
        if (null !== $this->cacheProvider) {
            $this->cacheProvider->flushAll();
        }
    }

    /**
     * Get the middleware that adds the necessary headers on each request.
     *
     * @return callable
     */
    private function getHeadersMiddleware(): callable
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $time = \time();
                $message = $time.$this->publicKey.$request->getUri()->getQuery();

                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $request = $request
                    ->withHeader('Accept', 'application/json')
                    ->withHeader('X-API-ID', $this->publicKey)
                    ->withHeader('X-API-TIME', $time)
                    ->withHeader('X-API-HASH', \hash_hmac('sha256', $message, $this->secureKey))
                ;

                return $handler($request, $options);
            };
        };
    }
}
