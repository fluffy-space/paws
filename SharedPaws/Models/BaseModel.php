<?php

namespace SharedPaws\Models;

class BaseModel
{
    public int $Id = 0;

    public int $CreatedOn = 0;
    public ?string $CreatedBy = null;

    public int $UpdatedOn = 0;
    public ?string $UpdatedBy = null;
}
