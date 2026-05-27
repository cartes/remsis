<?php

declare(strict_types=1);

namespace Modules\Employees\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Employees\Models\AdministrativeDayRequest;

class AdministrativeDayRequested
{
    use Dispatchable, SerializesModels;

    public AdministrativeDayRequest $request;

    /**
     * Create a new event instance.
     */
    public function __construct(AdministrativeDayRequest $request)
    {
        $this->request = $request;
    }
}
