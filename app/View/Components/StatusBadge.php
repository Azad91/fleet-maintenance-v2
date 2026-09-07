<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatusBadge extends Component
{
    public $status;

    public $type;

    public function __construct($status, $type = 'complaint')
    {
        $this->status = $status;
        $this->type = $type;
    }

    public function render(): View|Closure|string
    {
        return view('components.status-badge');
    }
}
