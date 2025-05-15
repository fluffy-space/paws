<?php

namespace Pupils\Components\Views\Admin\ListPage;

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
    public ?string $apiUrl = null;
    public string $name = 'Entity';
    public array $items = [];
    public TableFilter $filter;
    public array $columns = [];
    public $deleteMessage = null;
    public bool $embedded = false;
    public bool $editInline = false;
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
        $this->getData();
        $this->tableContext->passProps([
            'items' => $this->items,
            'columns' => $this->columns,
            'filter' => $this->filter,
            'addText' => "Add {$this->name}",
            'editInline' => $this->editInline,
            'search' => 1,
            'add' => 1,
            'edit' => 1,
            'remove' => 1,
            'paging' => 1
        ]);
        $this->tableContext->on('search', fn($event) => $this->onSearch($event));
        $this->tableContext->on('page', fn($event) => $this->onPageChange($event));
        $this->tableContext->on('create', fn($event) => $this->onCreate($event));
        $this->tableContext->on('edit', fn($event) => $this->onEdit($event));
        $this->tableContext->on('delete', fn($event) => $this->onDelete($event));
        $this->tableContext->on('save', fn($event) => $this->onSave($event));
        $this->tableContext->on('cancel', fn() => $this->getData());
    }

    private function getData()
    {
        $searchEncoded = urlencode($this->filter->searchText);
        $query = '';
        foreach ($this->query as $name => $value) {
            $query .= "&{$name}={$value}";
        }
        $this->http->get("/api/admin/{$this->apiUrl}?page={$this->filter->paging->page}&size={$this->filter->paging->size}&search={$searchEncoded}{$query}")
            ->then(function ($items) {
                $this->items = $items['list'];
                $this->cancelEdit();
                $this->tableContext->passProps(['items' => $this->items]);
                $this->filter->paging->setTotal($items['total']);
            }, function () {
                // error
            });
    }

    public function onSearch()
    {
        $this->getData();
    }

    public function onPageChange()
    {
        $this->getData();
    }

    private function deleteItem($item)
    {
        $this->http->delete("/api/admin/{$this->apiUrl}/{$item->Id}")->then(function () {
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
            $this->route->navigate("/admin/{$this->urlSegment}/{$item->Id}");
        }
    }

    public function onCreate()
    {
        if ($this->editInline) {
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
            $this->route->navigate("/admin/{$this->urlSegment}/create");
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
            $createMode ? "/api/admin/{$this->apiUrl}" : "/api/admin/{$this->apiUrl}/{$item->Id}",
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
                $this->handleResponse(true, $response);
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
