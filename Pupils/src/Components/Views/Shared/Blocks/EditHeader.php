<?php

namespace Pupils\Components\Views\Shared\Blocks;

use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Routing\ClientRoute;
use Viewi\UI\Components\Modals\ModalService;

class EditHeader extends BaseComponent
{
    public bool $createMode = false;
    public int $state = 0;
    public ?string $backUrl = null;
    public ?string $title = null;
    /** Where Back actually goes: the list the person came from when it said so, else backUrl. */
    public ?string $backHref = null;
    /**
     * Offer Delete beside Save (never while creating). The header asks the question; on "yes" it
     * emits `delete` with where Back leads, so the page can delete and land on the same list state
     * the person came from (EditPage::onDelete does both).
     */
    public bool $removable = false;
    public string $deleteMessage = 'Delete this item? This cannot be undone.';

    public function __construct(private ClientRoute $route, private ModalService $modal)
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

    public function confirmDelete()
    {
        $this->modal->confirm($this->deleteMessage, function () {
            $this->emitEvent('delete', $this->backHref ?? $this->backUrl ?? '');
        });
    }

    public function onSave(DomEvent $event)
    {
        $this->emitEvent('click', $event);
    }
}
