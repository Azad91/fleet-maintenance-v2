<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class StatCard extends Component
{
    public $count;

    public $label;

    public $icon;

    public $color;

    public function __construct($count, $label, $icon, $color = 'primary')
    {
        $this->count = $count;
        $this->label = $label;
        $this->icon = $icon;
        $this->color = $color;
    }

    public function render(): View|Closure|string
    {
        return view('components.stat-card');
    }
}
