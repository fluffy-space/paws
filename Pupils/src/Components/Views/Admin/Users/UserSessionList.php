<?php

namespace Pupils\Components\Views\Admin\Users;

use SharedPaws\Models\User\UserSessionModel;
use Viewi\Components\BaseComponent;
use Viewi\UI\Components\Tables\TableColumn;

/**
 * Embedded list of a single user's login sessions (AUTH tokens) — rendered inside
 * the user edit page "Sessions" tab. Scoped to `$user` (the user id), which filters
 * the list (query userId). Read-only: sessions are not created or edited here, only
 * terminated (the row delete action deletes the UserToken = signs that device out).
 */
class UserSessionList extends BaseComponent
{
    public array $columns = [];
    /** The owning user id (passed from UserEdit). */
    public int $user = 0;

    public function init()
    {
        $this->columns = [
            new TableColumn('CreatedOn', 'Signed in', 'DateColumn'),
            new TableColumn('LastVisit', 'Last seen', 'DateColumn'),
            new TableColumn('Expire', 'Expires'),
            new TableColumn('TokenHash', 'Token'),
        ];
    }

    /** Expire is unix seconds UTC (not microseconds), so it can't use DateColumn. */
    public function expireText(?int $seconds): string
    {
        return $seconds && $seconds > 0 ? gmdate('M j, Y H:i', $seconds) . ' UTC' : 'Never';
    }

    /** Short, non-secret identifier for the session (first chars of the token hash). */
    public function shortHash(?string $hash): string
    {
        return $hash ? substr($hash, 0, 12) : '';
    }

    public function deleteMessage()
    {
        return fn(UserSessionModel $item) => "Terminate this session? The user will be signed out on that device.";
    }
}
