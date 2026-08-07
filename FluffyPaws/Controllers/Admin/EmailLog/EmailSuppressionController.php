<?php

namespace FluffyPaws\Controllers\Admin\EmailLog;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Context\DbContext;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Services\Auth\AuthorizationService;
use FluffyPaws\Data\Entities\Emails\EmailSuppressionEntity;
use FluffyPaws\Data\Repositories\EmailSuppressionRepository;
use FluffyPaws\Security\PawsCapability;
use FluffyPaws\Services\Emails\EmailSuppressionService;
use SharedPaws\Models\Emails\EmailSuppressionModel;
use SharedPaws\Models\Emails\EmailSuppressionReason;
use SharedPaws\Models\Emails\EmailSuppressionSource;

use function Fluffy\Data\Query\c;
use function Fluffy\Data\Query\from;
use function Fluffy\Data\Query\x;

/**
 * Admin view of the suppression list — who we have stopped sending to and why.
 * Rows normally arrive from the SES webhook; Create covers a manual block and
 * Delete covers the release ("customer fixed their mailbox, let it through").
 *
 * Reuses the ManageEmailTemplates capability, like EmailLogController — email
 * admin is treated as one area.
 */
class EmailSuppressionController extends BaseController
{
    function __construct(
        protected IMapper $mapper,
        protected DbContext $db,
        protected EmailSuppressionRepository $suppressions,
        protected EmailSuppressionService $service,
        protected AuthorizationService $auth
    ) {}

    /** GET /api/admin/email-suppression — paginated list, newest first. */
    public function List(int $page = 1, int $size = 10, ?string $search = null)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageEmailTemplates)) {
            return $this->Forbidden();
        }

        $query = from(EmailSuppressionEntity::class);

        $search = strtolower(trim($search ?? ''));
        if ($search !== '') {
            $expression = null;
            foreach (['Email', 'Reason', 'Source', 'Detail'] as $col) {
                if ($expression) {
                    $expression->or(c($col), 'LIKE', "%$search%");
                } else {
                    $expression = x(c($col), 'LIKE', "%$search%");
                }
            }
            $query->where($expression);
        }

        $query->orderByDescending('Id')
            ->page($page)
            ->take($size);

        $entities = $this->db->execute($query);
        $models = array_map(
            fn($entity) => $this->mapper->map(EmailSuppressionModel::class, $entity),
            $entities['list']
        );
        return ['list' => $models, 'total' => $entities['total']];
    }

    /** POST /api/admin/email-suppression — block an address by hand. */
    public function Create(EmailSuppressionModel $suppression)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageEmailTemplates)) {
            return $this->Forbidden();
        }
        $email = EmailSuppressionService::normalize($suppression->Email);
        if ($email === '' || strpos($email, '@') === false) {
            return $this->BadRequest(['A valid email address is required.']);
        }
        if (!$this->service->suppress(
            $email,
            EmailSuppressionReason::Manual,
            EmailSuppressionSource::Admin,
            $suppression->Detail
        )) {
            return $this->ServerError(['Could not add the address to the suppression list.']);
        }
        $entity = $this->suppressions->findByEmail($email);
        return $entity ? $this->mapper->map(EmailSuppressionModel::class, $entity) : $this->NotFound();
    }

    /** DELETE /api/admin/email-suppression/{id} — release an address. */
    public function Delete(int $id)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageEmailTemplates)) {
            return $this->Forbidden();
        }
        /** @var ?EmailSuppressionEntity $entity */
        $entity = $this->suppressions->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        return $this->suppressions->delete($entity);
    }
}
