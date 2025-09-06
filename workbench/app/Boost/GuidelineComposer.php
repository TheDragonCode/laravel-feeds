<?php

declare(strict_types=1);

namespace Workbench\App\Boost;

use Laravel\Boost\Install\GuidelineComposer as BaseComposer;

class GuidelineComposer extends BaseComposer
{
    protected string $userGuidelineDir = '/../../../../.ai/guidelines';
}
