<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EmptyState extends Component
{
    public $icon;

    public $message;

    public $link;

    public $linkText;

    public function __construct($icon = 'box', $message = 'Hələ məlumat yoxdur', $link = null, $linkText = 'Yenisini əlavə et')
    {
        $this->icon = $icon;
        $this->message = $message;
        $this->link = $link;
        $this->linkText = $linkText;
    }

    public function render(): View|Closure|string
    {
        return view('components.empty-state');
    }
}
