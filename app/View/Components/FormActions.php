<?php

namespace App\View\Components;

use Illuminate\View\Component;

class FormActions extends Component
{
    public $mode;
    public $submitText;
    public $cancelRoute;
    public $cancelText;

    public function __construct(
        $mode = 'create', // 'create' or 'edit'
        $submitText = null,
        $cancelRoute = null,
        $cancelText = 'Annuler'
    ) {
        $this->mode = $mode;
        $this->submitText = $submitText ?: ($mode === 'edit' ? 'Modifier' : 'Valider');
        $this->cancelRoute = $cancelRoute;
        $this->cancelText = $cancelText;
    }

    public function render()
    {
        return view('components.form-actions');
    }
}