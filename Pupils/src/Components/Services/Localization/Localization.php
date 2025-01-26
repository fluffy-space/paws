<?php

namespace Pupils\Components\Services\Localization;

use Viewi\Builder\Attributes\GlobalEntry;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\IStartUp\IStartUp;
use Viewi\DI\Singleton;

#[Singleton]
class Localization implements IStartUp
{
    private array $resources = /* @jsobject */ [];

    public function __construct(private HttpClient $http)
    {
    }

    public function setUp()
    {
        $this->http->get("/api/locale-resource/1")
            ->then(function ($resources) {
                $this->resources = $resources;
            }, function () {
                // error
            });
    }

    #[GlobalEntry]
    public function t(string $key, ?array $params = null)
    {
        $text = $this->resources[$key] ?? $key;
        if ($params !== null) {
            foreach ($params as $key => $value) {
                $text = str_replace("{{$key}}", $value, $text);
            }
        }
        return $text;
    }
}
