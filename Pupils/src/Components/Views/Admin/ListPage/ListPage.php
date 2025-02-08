<?php

namespace Pupils\Components\Views\Admin\ListPage;

use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Routing\ClientRoute;
use Viewi\DI\Inject;
use Viewi\DI\Scope;
use Viewi\UI\Components\Alerts\AlertService;
use Viewi\UI\Components\Modals\ModalService;
use Viewi\UI\Components\Tables\DataTableContext;
use Viewi\UI\Components\Tables\TableFilter;

class ListPage extends BaseComponent
{
    public ?string $title = null;
    public string $urlSegment = 'not-provided';
    public string $name = 'Entity';
    public array $items = [];
    public TableFilter $filter;
    public array $columns = [];
    public $deleteMessage = null;

    public function __construct(
        private HttpClient $http,
        private ModalService $modal,
        private AlertService $messages,
        private ClientRoute $route,
        #[Inject(Scope::COMPONENT)]
        private DataTableContext $tableContext
    ) {
        $this->filter = new TableFilter();
    }

    public function mounted()
    {
        $this->getData();
        $this->tableContext->passProps([
            'items' => $this->items,
            'columns' => $this->columns,
            'filter' => $this->filter,
            'addText' => "Add {$this->name}",
        ]);
        $this->tableContext->on('search', fn($event) => $this->onSearch($event));
        $this->tableContext->on('page', fn($event) => $this->onPageChange($event));
        $this->tableContext->on('create', fn($event) => $this->onCreate($event));
        $this->tableContext->on('edit', fn($event) => $this->onEdit($event));
        $this->tableContext->on('delete', fn($event) => $this->onDelete($event));
    }

    private function getData()
    {
        $searchEncoded = urlencode($this->filter->searchText);
        $this->http->get("/api/admin/{$this->urlSegment}?page={$this->filter->paging->page}&size={$this->filter->paging->size}&search={$searchEncoded}")
            ->then(function ($items) {
                $this->items = $items['list'];
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
        $this->http->delete("/api/admin/{$this->urlSegment}/{$item->Id}")->then(function () {
            $this->messages->success("{$this->name} has been successfully deleted", null, 5000);
            $this->getData();
        }, function ($error) {
            // error
            echo $error;
            $this->messages->error("{$this->name} deletion has failed", null, 5000);
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
        $this->route->navigate("/admin/{$this->urlSegment}/{$item->Id}");
    }

    public function onCreate()
    {
        $this->route->navigate("/admin/{$this->urlSegment}/create");
    }
}
