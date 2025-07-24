<?php

namespace OutboundIQ\Laravel\Http;

use GuzzleHttp\Client as BaseGuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use OutboundIQ\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OutboundIQGuzzleClient extends BaseGuzzleClient
{
    private Client $outboundClient;

    public function __construct(Client $outboundClient, array $config = [])
    {
        $this->outboundClient = $outboundClient;

        if (!isset($config['handler'])) {
            $stack = HandlerStack::create(new CurlHandler());
            
            // Add our middleware
            $stack->push(function ($handler) {
                return function (RequestInterface $request, array $options) use ($handler) {
                    $startTime = microtime(true);
                    
                    return $handler($request, $options)->then(
                        function (ResponseInterface $response) use ($request, $startTime) {
                            $duration = (microtime(true) - $startTime) * 1000;
                            
                            // Get request/response bodies
                            $requestBody = null;
                            if ($request->getBody()->isSeekable()) {
                                $requestBody = $request->getBody()->getContents();
                                $request->getBody()->rewind();
                            }
                            
                            $responseBody = null;
                            if ($response->getBody()->isSeekable()) {
                                $responseBody = $response->getBody()->getContents();
                                $response->getBody()->rewind();
                            }
                            
                            // Track the API call
                            $this->outboundClient->trackApiCall(
                                url: (string)$request->getUri(),
                                method: $request->getMethod(),
                                duration: $duration,
                                statusCode: $response->getStatusCode(),
                                requestHeaders: $request->getHeaders(),
                                requestBody: $requestBody,
                                responseHeaders: $response->getHeaders(),
                                responseBody: $responseBody,
                                request_type: 'guzzle'
                            );
                            
                            return $response;
                        },
                        function ($reason) use ($request, $startTime) {
                            $duration = (microtime(true) - $startTime) * 1000;
                            
                            // Get request body
                            $requestBody = null;
                            if ($request->getBody()->isSeekable()) {
                                $requestBody = $request->getBody()->getContents();
                                $request->getBody()->rewind();
                            }
                            
                            // Determine error details
                            $statusCode = 0;
                            $errorType = 'unknown_error';
                            $errorMessage = $reason->getMessage();
                            
                            if ($reason instanceof \GuzzleHttp\Exception\ConnectException) {
                                $errorType = 'connection_error';
                                if (strpos($errorMessage, 'timed out') !== false) {
                                    $errorType = 'timeout';
                                } elseif (strpos($errorMessage, 'Could not resolve') !== false) {
                                    $errorType = 'dns_error';
                                }
                            } elseif ($reason instanceof \GuzzleHttp\Exception\RequestException && $reason->hasResponse()) {
                                $statusCode = $reason->getResponse()->getStatusCode();
                                $errorType = 'http_error';
                            }
                            
                            // Track the failed API call
                            $this->outboundClient->trackApiCall(
                                url: (string)$request->getUri(),
                                method: $request->getMethod(),
                                duration: $duration,
                                statusCode: $statusCode,
                                requestHeaders: $request->getHeaders(),
                                requestBody: $requestBody,
                                responseHeaders: [],
                                responseBody: null,
                                request_type: 'guzzle',
                                error_message: $errorMessage,
                                error_type: $errorType
                            );
                            
                            throw $reason;
                        }
                    );
                };
            }, 'outboundiq');
            
            $config['handler'] = $stack;
        }
        
        parent::__construct($config);
    }
} 