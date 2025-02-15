<?php

namespace SharedPaws\Models\MenuItem;

use SharedPaws\Models\BaseModel;

class MenuItemModel extends BaseModel
{
    public string $Title = '';
    public ?string $Link = null;
    public bool $NewTab = false;
    public bool $Published = false;
    public ?string $LinkClass = null;
    public ?string $Icon = null;
    public int $Order = 0;
    public int $Location = 0; // 0 - header, 0 footer
    public int $Column = 0; // valid for footer
    public int $Row = 0; // valid for footer
}
