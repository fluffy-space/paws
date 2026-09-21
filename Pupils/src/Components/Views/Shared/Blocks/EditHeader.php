<?php

namespace Pupils\Components\Views\Shared\Blocks;

use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Routing\ClientRoute;

class EditHeader extends BaseComponent
{
    public bool $createMode = false;
    public int $state = 0;
    public ?string $backUrl = null;
    public ?string $title = null;
    /** Where Back actually goes: the list the person came from when it said so, else backUrl. */
    public ?string $backHref = null;

    public function __construct(private ClientRoute $route)
    {
    }

    public function mounted()
    {
        $this->backHref = self::safeReturn($this->route->getQueryParams()['return'] ?? '') ?? $this->backUrl;
    }

    /**
     * A ListPage with syncUrl sends `?return=/app/team/short-url?folder=3&page=2`. Honoured only when
     * it is a path on this site — never a scheme or a protocol-relative "//host" — so the parameter
     * cannot be used to send someone elsewhere.
     */
    public static function safeReturn(string $value): ?string
    {
        if ($value === '' || strpos($value, '/') !== 0 || strpos($value, '//') === 0 || strpos($value, '/\\') === 0) {
            return null;
        }
        return $value;
    }

    public function onSave(DomEvent $event)
    {
        $this->emitEvent('click', $event);
    }
}
