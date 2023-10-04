<?php

namespace Nadi\Laravel\Metric;

use Nadi\Metric\Base;

class Framework extends Base
{
    public function metrics(): array
    {
        return [
            'framework.name' => 'Laravel',
            'framework.version' => app()->version(),
        ];
    }
}
