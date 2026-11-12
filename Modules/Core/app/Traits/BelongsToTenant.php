<?php

declare(strict_types=1);

namespace Modules\Core\Traits;

use App\Models\Concerns\BelongsToTenant as BaseBelongsToTenant;

/**
 * Trait modular de aislamiento Multi-Tenant para modelos operativos de Remsis.
 */
trait BelongsToTenant
{
    use BaseBelongsToTenant;
}

