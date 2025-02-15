<?php

namespace Pupils\Components\Views\Admin\EditPage;

use SharedPaws\Models\BaseModel;
use SharedPaws\Models\Media\PictureModel;
use SharedPaws\Validation\IValidationRules;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Environment\ClientTimer;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Routing\ClientRoute;
use Viewi\UI\Components\Alerts\AlertService;
use Viewi\UI\Components\Buttons\ActionButton;
use Viewi\UI\Components\Forms\ActionForm;
use Viewi\UI\Components\Validation\ValidationMessage;

abstract class EditPage extends BaseComponent
{
    public $item = null;
    public string $name = "Item";
    public int $state = ActionButton::STATE_PENDING;
    public bool $createMode = false;
    public $validation = null;
    private ?ActionForm $actionForm = null;
    public ?ValidationMessage $generalMessages = null;

    public string $segment = 'not-defined';
    public ?string $apiUrl = null;

    public function __construct(
        public int $id,
        private HttpClient $http,
        private AlertService $messages,
        private ClientRoute $route
    ) {}

    public abstract function getValidation(BaseModel $item): ?IValidationRules;
    public abstract function getNewItem(): BaseModel;

    public function init()
    {
        if ($this->apiUrl === null) {
            $this->apiUrl = $this->segment;
        }
        if ($this->id > 0) {
            //edit
            $this->http->get("/api/admin/{$this->apiUrl}/{$this->id}")
                ->then(function ($item) {
                    $this->item = $item;
                    $this->validation = $this->getValidation($item);
                }, function () {
                    // error
                });
        } else {
            // create
            $this->createMode = true;
            $this->item = $this->getNewItem();
            $this->validation = $this->getValidation($this->item);
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
            $this->createMode ? "/api/admin/{$this->apiUrl}" : "/api/admin/{$this->apiUrl}/{$this->id}",
            $this->item
        )
            ->then(function ($post) {
                $this->stopLoading(ActionButton::STATE_SUCCESS);
                if ($post !== null) {
                    $text = $this->createMode ? 'created' : 'saved';
                    $this->messages->success("{$this->name} was successfully $text.", null, 5000);
                    if ($this->createMode) {
                        $this->route->navigate("/admin/{$this->segment}/{$post->Id}");
                    } else {
                        $this->item = $post;
                        $this->validation = $this->getValidation($this->item);
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
        $this->item->PictureId = null;
        $this->item->Picture = null;
    }

    public function imageUploaded(PictureModel $picture)
    {
        $this->item->PictureId = $picture->Id;
        $this->item->Picture = $picture;
    }
}
