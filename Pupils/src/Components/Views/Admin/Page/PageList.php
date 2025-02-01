<?php

namespace Pupils\Components\Views\Admin\Page;

use Pupils\Components\Guards\AdminGuard;
use SharedPaws\Models\Content\PageModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Attributes\Middleware;
use Viewi\UI\Components\Alerts\AlertService;
use Viewi\UI\Components\Modals\ModalService;
use Viewi\UI\Components\Tables\TableColumn;
use Viewi\UI\Components\Tables\TableFilter;

#[Middleware([AdminGuard::class])]
class PageList extends BaseComponent
{
    /**
     * 
     * @var PageModel[]
     */
    public array $items = [];
    public TableFilter $filter;
    public array $columns = [];

    public function __construct(
        private HttpClient $http,
        private ModalService $modal,
        private AlertService $messages
    ) {
        $this->filter = new TableFilter();
    }

    public function init()
    {
        $this->setUpColumns();
        $this->getData();
    }

    public function setUpColumns()
    {
        $this->columns = [
            new TableColumn('Id'),
            new TableColumn('Title'),
            new TableColumn('Published', TableColumn::TYPE_BOOLEAN),
            new TableColumn('CreatedOn', TableColumn::TYPE_DATETIME, 'Created'),
        ];
    }

    private function getData()
    {
        $searchEncoded = urlencode($this->filter->searchText);
        $this->http->get("/api/admin/content?page={$this->filter->paging->page}&size={$this->filter->paging->size}&search={$searchEncoded}")
            ->then(function ($posts) {
                $this->items = $posts['list'];
                $this->filter->paging->setTotal($posts['total']);
            }, function () {
                // error
            });
    }

    private function deleteItem(PageModel $item)
    {
        $this->http->delete("/api/admin/content/{$item->Id}")->then(function () {
            $this->messages->success('Page has been successfully deleted', null, 5000);
            $this->getData();
        }, function ($error) {
            // error
            echo $error;
            $this->messages->error('Page deletion has failed', null, 5000);
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

    public function onDelete(PageModel $item)
    {
        $this->modal->confirm("Are you sure you want to delete '{$item->Title}' with Id {$item->Id}?", function () use ($item) {
            $this->deleteItem($item);
        });
    }
}
