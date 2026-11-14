<?php

declare(strict_types=1);

namespace Rooberthh\Sdk\Tests\Fixtures\Acme\Enums;

enum ProjectStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
}
