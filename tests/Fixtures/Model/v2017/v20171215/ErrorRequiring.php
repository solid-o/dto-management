<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Fixtures\Model\v2017\v20171215;

use Solido\DtoManagement\Finder\ServiceLocator;

class ErrorRequiring
{
    public function __construct(ServiceLocator $locator)
    {
        $locator->get('non-existent-service');
    }
}
