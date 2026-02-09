<?php

namespace Modules\AppAITools\Livewire\Tools;

use Livewire\Component;

class MoreTools extends Component
{
    public function render()
    {
        return view('appaitools::livewire.tools.more-tools', [
            'subTools' => config('appaitools.sub_tools'),
        ]);
    }
}
