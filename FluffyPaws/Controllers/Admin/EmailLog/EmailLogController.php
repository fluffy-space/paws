<?php

namespace FluffyPaws\Controllers\Admin\EmailLog;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Context\DbContext;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Services\Auth\AuthorizationService;
use FluffyPaws\Data\Entities\Emails\EmailLogEntity;
use FluffyPaws\Data\Repositories\EmailLogRepository;
use FluffyPaws\Security\PawsCapability;
use SharedPaws\Models\Emails\EmailLogModel;

use function Fluffy\Data\Query\c;
use function Fluffy\Data\Query\from;
use function Fluffy\Data\Query\x;

/**
 * Read-only (+ delete) admin view of the EmailLog table. Reuses the
 * ManageEmailTemplates capability — "email admin" is treated as one area.
 * Rows are normally pruned by EmailLogCleanupTask; Delete allows manual purge.
 */
class EmailLogController extends BaseController
{
    function __construct(
        protected IMapper $mapper,
        protected DbContext $db,
        protected EmailLogRepository $emailLogs,
        protected AuthorizationService $auth
    ) {}

    /** GET /api/admin/email-log — paginated list (Body omitted from the payload). */
    public function List(int $page = 1, int $size = 10, ?string $search = null)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageEmailTemplates)) {
            return $this->Forbidden();
        }

        $query = from(EmailLogEntity::class);

        $search = trim($search ?? '');
        if ($search) {
            $search = strtolower($search);
            $parts = explode(' ', $search);
            $searchExpression = null;
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                foreach (['Recipient', 'Subject', 'Type', 'Status'] as $col) {
                    if ($searchExpression) {
                        $searchExpression->or(c($col), 'LIKE', "%$part%");
                    } else {
                        $searchExpression = x(c($col), 'LIKE', "%$part%");
                    }
                }
            }
            if ($searchExpression) {
                $query->where($searchExpression);
            }
        }

        $query->orderByDescending('Id')
            ->page($page)
            ->take($size);

        $entities = $this->db->execute($query);

        $models = array_map(function ($entity) {
            /** @var EmailLogModel $model */
            $model = $this->mapper->map(EmailLogModel::class, $entity);
            // Keep the list payload lean — full body is only fetched on the detail view.
            $model->Body = null;
            return $model;
        }, $entities['list']);
        return ['list' => $models, 'total' => $entities['total']];
    }

    /** GET /api/admin/email-log/{id} — single row including the stored body (dev/local). */
    public function Get(int $id)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageEmailTemplates)) {
            return $this->Forbidden();
        }
        $entity = $this->emailLogs->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        return $this->mapper->map(EmailLogModel::class, $entity);
    }

    /** GET /api/admin/email-log/{id}/body — raw stored HTML for the preview iframe. */
    public function GetBody(int $id)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageEmailTemplates)) {
            return $this->Forbidden();
        }
        $entity = $this->emailLogs->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        return $entity->Body ?? '<p style="font-family:sans-serif;color:#888;padding:1rem">No body stored for this email (bodies are only kept in dev/local).</p>';
    }

    /** DELETE /api/admin/email-log/{id} — manual purge of a single log row. */
    public function Delete(int $id)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageEmailTemplates)) {
            return $this->Forbidden();
        }
        $entity = $this->emailLogs->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        return $this->emailLogs->delete($entity);
    }
}
