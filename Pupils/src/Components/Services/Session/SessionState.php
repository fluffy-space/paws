<?php

namespace Pupils\Components\Services\Session;

use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Interceptor\IHttpInterceptor;
use Viewi\Components\Http\Message\Request;
use Viewi\Components\Http\Interceptor\IRequestHandler;
use Viewi\Components\Http\Message\Response;
use Viewi\Components\Http\Interceptor\IResponseHandler;
use Viewi\DI\Singleton;

#[Singleton]
class SessionState implements IHttpInterceptor
{
    private bool $initiated = false;
    private ?string $CSRFToken = null;
    private array $resolveQueue = [];

    public function __construct(private HttpClient $http)
    {
    }

    public function request(Request $request, IRequestHandler $handler)
    {
        if ($this->CSRFToken !== null) {
            $newRequest = $request->withHeader('X-CSRF-TOKEN', $this->CSRFToken);
            $handler->next($newRequest);
        } else {
            $this->putInQueue($request, $handler);
            if (!$this->initiated) {
                $this->initiated = true;
                // get the CSRF token
                $this->http->post('/api/authorization/session')->then(function ($response) {
                    $this->CSRFToken = $response['CSRFToken'];
                    $this->resolveCallbacks();
                }, function ($error) {
                    $this->initiated = false;
                    $this->resolveCallbacks($error);
                });
            } else {
                $this->resolveCallbacks();
            }
        }
    }

    public function response(Response $response, IResponseHandler $handler)
    {
        $handler->next($response);
    }

    public function putInQueue(Request $request, IRequestHandler $handler)
    {
        $this->resolveQueue[] = function (?string $csrfToken, $error) use ($request, $handler) {
            if ($error) {
                $handler->reject($request);
                return;
            }
            $newRequest = $request->withHeader('X-CSRF-TOKEN', $csrfToken);
            $handler->next($newRequest);
        };
    }

    public function resolveCallbacks($error = null)
    {
        foreach ($this->resolveQueue as $callBack) {
            $callBack($this->CSRFToken, $error);
        }
        $this->resolveQueue = [];
    }
}
