<?php

namespace Pupils\Components\Views\Admin\Page;

use Pupils\Components\Guards\AdminGuard;
use SharedPaws\Models\Content\PageModel;
use SharedPaws\Models\Content\PageValidation;
use SharedPaws\Models\Media\PictureModel;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Environment\ClientTimer;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Routing\ClientRoute;
use Viewi\UI\Components\Alerts\AlertService;
use Viewi\UI\Components\Buttons\ActionButton;
use Viewi\UI\Components\Forms\ActionForm;
use Viewi\UI\Components\Validation\ValidationMessage;

#[Middleware([AdminGuard::class])]
class PageEdit extends BaseComponent
{
    public ?PageModel $page = null;
    public int $state = ActionButton::STATE_PENDING;
    public bool $createMode = false;
    public ?PageValidation $validation = null;
    private ?ActionForm $actionForm = null;
    public ?ValidationMessage $generalMessages = null;
    public bool $rawHtmlEdit = false;

    public function __construct(
        public int $id,
        private HttpClient $http,
        private AlertService $messages,
        private ClientRoute $route
    ) {}

    public function init()
    {
        if ($this->id > 0) {
            //edit
            $this->http->get("/api/admin/content/{$this->id}")
                ->then(function (PageModel $page) {
                    $this->page = $page;
                    $this->validation = new PageValidation($this->page);
                }, function () {
                    // error
                });
        } else {
            // create
            $this->createMode = true;
            $this->page = new PageModel();
            $this->validation = new PageValidation($this->page);
        }
    }

    public function onSave(DomEvent $event)
    {
        $event->preventDefault();
        if (!$this->actionForm->validate()) {
            return;
        }
        $this->state = ActionButton::STATE_PROCESSING;
        $this->http->request(
            $this->createMode ? 'post' : 'put',
            $this->createMode ? '/api/admin/content' : "/api/admin/content/{$this->id}",
            $this->page
        )
            ->then(function (?PageModel $post) {
                $this->stopLoading(ActionButton::STATE_SUCCESS);
                if ($post !== null) {
                    $text = $this->createMode ? 'created' : 'saved';
                    $this->messages->success("Page was successfully $text.", null, 5000);
                    if ($this->createMode) {
                        $this->route->navigate("/admin/content/{$post->Id}");
                    } else {
                        $this->page = $post;
                        $this->validation = new PageValidation($this->page);
                    }
                }
            }, function ($response) {
                $this->stopLoading(ActionButton::STATE_ERROR);
                $this->handleResponse(true, $response);
            });
    }

    public function stopLoading(int $state)
    {
        ClientTimer::setTimeoutStatic(fn() => ($this->state = $state), 500);
        ClientTimer::setTimeoutStatic(fn() => ($this->state = ActionButton::STATE_PENDING), 2500);
    }

    public function handleResponse(bool $hasError, $response = null)
    {
        if ($hasError) {
            if ($response['errors']) {
                $this->generalMessages->messages = $response['errors'];
                $this->messages->error($response['errors'][0], null, 5000);
            } else if ($response['message']) {
                $this->generalMessages->messages = [$response['message']];
                $this->messages->error($response['message'], null, 5000);
            } else {
                $this->generalMessages->messages = ['Saving has failed'];
                $this->messages->error('Saving has failed', null, 5000);
            }
            $this->generalMessages->show = true;
        }
    }

    public function removePicture(DomEvent $event)
    {
        $event->preventDefault();
        $this->page->PictureId = null;
        $this->page->Picture = null;
    }

    public function imageUploaded(PictureModel $picture)
    {
        $this->page->PictureId = $picture->Id;
        $this->page->Picture = $picture;
    }
}
