<?php

namespace Pupils\Components\Views\Shared\ListPage;

use SharedPaws\Models\BaseModel;
use SharedPaws\Validation\IValidationRules;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Routing\ClientRoute;
use Viewi\DI\Inject;
use Viewi\DI\Scope;
use Viewi\UI\Components\Alerts\AlertService;
use Viewi\UI\Components\Forms\FormContext;
use Viewi\UI\Components\Modals\ModalService;
use Viewi\UI\Components\Tables\DataTableContext;
use Viewi\UI\Components\Tables\TableFilter;

class ListPage extends BaseComponent
{
    public ?string $title = null;
    public string $urlSegment = 'not-provided';
    /** API base prefix the CRUD calls are issued against; override for non-admin (e.g. member) areas. */
    public string $apiBase = '/api/admin/';
    /** Client-route base the create/edit navigation targets; override for non-admin (e.g. member) areas. */
    public string $routeBase = '/admin/';
    public ?string $apiUrl = null;
    public string $name = 'Entity';
    public array $items = [];
    public TableFilter $filter;
    public array $columns = [];
    public $deleteMessage = null;
    public bool $embedded = false;
    public bool $editInline = false;
    public bool $add = true;
    /** Show the per-row edit and delete actions. Set false for a read-only list. */
    public bool $edit = true;
    public bool $remove = true;
    public ?string $addLink = null;
    public ?string $addText = null;
    /**
     * 
     * @var callable($item): IValidationRules
     */
    public $validationFactory = null;
    /**
     * 
     * @var callable(): BaseModel
     */
    public $newFactory = null;
    public array $query = [];
    /**
     * Keep the list's own state in the address bar — `?search=…&page=…` — so a list can be shared,
     * survives a refresh, and Back from an edit page lands where the person left. Other query
     * parameters (a page's own filters, e.g. `folder`) are preserved untouched. Opt-in: a list
     * embedded in another page must not start rewriting that page's URL.
     *
     * With it on, the edit and create links carry `?return=<this list's URL>`, which EditHeader's
     * Back link then honours.
     */
    public bool $syncUrl = false;

    public function __construct(
        private HttpClient $http,
        private ModalService $modal,
        private AlertService $messages,
        private ClientRoute $route,
        #[Inject(Scope::COMPONENT)]
        private DataTableContext $tableContext,
        #[Inject(Scope::COMPONENT)]
        private FormContext $form,
    ) {
        $this->filter = new TableFilter();
    }

    public function mounted()
    {
        if ($this->apiUrl === null) {
            $this->apiUrl = $this->urlSegment;
        }
        if ($this->syncUrl) {
            $this->readUrl();
        }
        $this->getData();
        $this->tableContext->passProps([
            'items' => $this->items,
            'columns' => $this->columns,
            'filter' => $this->filter,
            'editInline' => $this->editInline,
            'search' => 1,
            'add' => $this->add,
            'addLink' => $this->addLink,
            'addText' => $this->addText ?? "Add {$this->name}",
            'edit' => $this->edit,
            'remove' => $this->remove,
            'paging' => 1,
            // So a search restored from the URL is also visible in the box.
            'searchValue' => $this->filter->searchText,
        ]);
        $this->tableContext->on('search', fn($event) => $this->onSearch($event));
        $this->tableContext->on('page', fn($event) => $this->onPageChange($event));
        $this->tableContext->on('create', fn($event) => $this->onCreate($event));
        $this->tableContext->on('edit', fn($event) => $this->onEdit($event));
        $this->tableContext->on('delete', fn($event) => $this->onDelete($event));
        $this->tableContext->on('save', fn($event) => $this->onSave($event));
        $this->tableContext->on('cancel', fn() => $this->getData());
        // Re-fetch when a parent changes the extra filter params (e.g. a status filter).
        // Reset to the first page so a filter change never leaves you on an empty page.
        $this->watch('query', function () {
            $this->filter->paging->page = 1;
            $this->getData();
        });
    }

    private function getData()
    {
        $searchEncoded = urlencode($this->filter->searchText);
        $query = '';
        foreach ($this->query as $name => $value) {
            $query .= "&{$name}={$value}";
        }
        $this->http->get("{$this->apiBase}{$this->apiUrl}?page={$this->filter->paging->page}&size={$this->filter->paging->size}&search={$searchEncoded}{$query}")
            ->then(function ($items) {
                $total = $items['total'];
                $size = $this->filter->paging->size;
                if (count($items['list']) === 0 && $total > 0 && $this->filter->paging->page > 1) {
                    // The rows this page showed are gone (moved away, deleted): show the new last
                    // page rather than an empty one that says "no matches".
                    $this->filter->paging->page = (int) ceil($total / $size);
                    $this->getData();
                    return;
                }
                $this->items = $items['list'];
                $this->cancelEdit();
                $this->tableContext->passProps(['items' => $this->items]);
                $this->filter->paging->setTotal($items['total']);
                $this->writeUrl();
            }, function () {
                // error
            });
    }

    /**
     * Fetch the current page again, after the page changed rows behind the list's back (moved them
     * to another folder, say). Stays on the page it is on; steps back when that page is now empty.
     */
    public function reload()
    {
        $this->getData();
    }

    public function onSearch()
    {
        $this->getData();
    }

    /** Restore search and page from the address bar (syncUrl). */
    private function readUrl()
    {
        $params = $this->route->getQueryParams();
        $search = $params['search'] ?? '';
        $page = (int) ($params['page'] ?? 1);
        $this->filter->searchText = '' . $search;
        $this->filter->paging->page = $page > 0 ? $page : 1;
    }

    /**
     * This list's URL as it should read now: every parameter the page already had, with search and
     * page rewritten (and left out when they are the default, so a plain list keeps a plain URL).
     */
    private function currentListUrl(): string
    {
        $params = $this->route->getQueryParams();
        $query = '';
        $glue = '?';
        foreach ($params as $name => $value) {
            if ($name === 'search' || $name === 'page' || $name === 'return') {
                continue;
            }
            $query .= $glue . $name . '=' . self::queryValue('' . $value);
            $glue = '&';
        }
        if ($this->filter->searchText !== '') {
            $query .= $glue . 'search=' . self::queryValue($this->filter->searchText);
            $glue = '&';
        }
        if ($this->filter->paging->page > 1) {
            $query .= $glue . 'page=' . $this->filter->paging->page;
        }
        return $this->route->getUrlPath() . $query;
    }

    /**
     * Encoded for a query string, but with "/" left as it is: it is legal there, and it keeps an
     * address that names a path readable — ?folder=Campaigns/Q4 rather than Campaigns%2FQ4.
     */
    private static function queryValue(string $value): string
    {
        return str_replace('%2F', '/', urlencode($value));
    }

    /** Replace, not push: typing a search should not leave one history entry per keystroke. */
    private function writeUrl()
    {
        if ($this->syncUrl) {
            $this->route->replaceUrl($this->currentListUrl());
        }
    }

    /** `?return=` for the edit and create links, so Back comes home to this exact list. */
    private function returnParam(): string
    {
        return $this->syncUrl ? '?return=' . urlencode($this->currentListUrl()) : '';
    }

    public function onPageChange()
    {
        $this->getData();
    }

    private function deleteItem($item)
    {
        $this->http->delete("{$this->apiBase}{$this->apiUrl}/{$item->Id}")->then(function () {
            $this->messages->success("{$this->name} has been successfully deleted", 5000);
            $this->getData();
        }, function ($error) {
            // error
            echo $error;
            $this->messages->error("{$this->name} deletion has failed", 5000);
        });
    }

    public function onDelete($item)
    {
        $this->modal->confirm($this->deleteMessage ? ($this->deleteMessage)($item) : "Are you sure you want to delete item with Id {$item->Id}?", function () use ($item) {
            $this->deleteItem($item);
        });
    }

    public function onEdit($item)
    {
        if ($this->editInline) {
        } else {
            $this->route->navigate("{$this->routeBase}{$this->urlSegment}/{$item->Id}" . $this->returnParam());
        }
    }

    public function onCreate()
    {
        if ($this->addLink) {
            $this->route->navigate($this->addLink);
        } elseif ($this->editInline) {
            if ($this->newFactory !== null) {
                $newItem = ($this->newFactory)();
                array_unshift($this->items, $newItem);
                $this->items = [...$this->items];
                $this->tableContext->passProps([
                    'items' => $this->items,
                    'editItem' => $newItem,
                    'changeMode' => true
                ]);
            }
        } else {
            $this->route->navigate("{$this->routeBase}{$this->urlSegment}/create" . $this->returnParam());
        }
    }

    public function onSave(BaseModel $item)
    {
        if ($this->validationFactory !== null) {
            if (!$this->form->validate(($this->validationFactory)($item)->getValidationRules())) {
                return;
            }
        }
        $createMode = $item->Id === 0;
        $this->http->request(
            $createMode ? 'post' : 'put',
            $createMode ? "{$this->apiBase}{$this->apiUrl}" : "{$this->apiBase}{$this->apiUrl}/{$item->Id}",
            $item
        )
            ->then(function (?BaseModel $model) use ($createMode) {
                if ($model !== null) {
                    $text = $createMode ? 'created' : 'saved';
                    $this->messages->success("{$this->name} was successfully $text.", 5000);
                }
                $this->cancelEdit();
                $this->getData();
            }, function ($response) {
                // Reject arg is a Response object; handleResponse reads errors off the BODY.
                $this->handleResponse(true, $response->body);
            });
    }

    public function cancelEdit()
    {
        $this->tableContext->passProps(['editItem' => null, 'changeMode' => false]);
    }

    public function handleResponse(bool $hasError, $response = null)
    {
        if ($hasError) {
            if ($response['errors']) {
                $this->messages->error($response['errors'][0], 5000);
            } else if ($response['message']) {
                $this->messages->error($response['message'], 5000);
            } else {
                $this->messages->error('Saving has failed', 5000);
            }
        }
    }
}
