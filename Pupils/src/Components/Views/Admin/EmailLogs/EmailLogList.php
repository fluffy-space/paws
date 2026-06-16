<?php

namespace Pupils\Components\Views\Admin\EmailLogs;

use Pupils\Components\Guards\HasCapability;
use SharedPaws\Models\Emails\EmailLogModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Attributes\Middleware;
use Viewi\UI\Components\Tables\TableColumn;

#[Middleware([[HasCapability::class, 'ManageEmailTemplates']])]
class EmailLogList extends BaseComponent
{
    public array $columns = [];

    public function __construct() {}

    public function init()
    {
        $this->setUpColumns();
    }

    public function setUpColumns()
    {
        $this->columns = [
            new TableColumn('Id'),
            new TableColumn('CreatedOn', 'Sent', 'DateColumn'),
            new TableColumn('Type'),
            new TableColumn('Recipient'),
            new TableColumn('Subject'),
            new TableColumn('Status'),
        ];
    }

    public function deleteMessage()
    {
        return fn(EmailLogModel $item) => "Delete the email log #{$item->Id} to '{$item->Recipient}'?";
    }
}
