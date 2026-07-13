<?php

namespace App\View\Components;

use Illuminate\View\Component;

class FileUpload extends Component
{
    public $name;
    public $label;
    public $oldValue;
    public $accept;
    public $multiple;
    public $preview;
    public $helpText;
    public $required;
    public $existingImage;
    public $deleteRoute;
    public $imageId;
    public $maxSize;
    public $wrapperClass;
    public $showPreview;

    public function __construct(
        $name = 'image',
        $label = 'Image',
        $oldValue = null,
        $accept = 'image/*',
        $multiple = false,
        $preview = true,
        $helpText = null,
        $required = false,
        $existingImage = null,
        $deleteRoute = null,
        $imageId = null,
        $maxSize = '2MB',
        $wrapperClass = 'col-lg-12 col-sm-12 col-12',
        $showPreview = true
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->oldValue = $oldValue;
        $this->accept = $accept;
        $this->multiple = $multiple;
        $this->preview = $preview;
        $this->helpText = $helpText;
        $this->required = $required;
        $this->existingImage = $existingImage;
        $this->deleteRoute = $deleteRoute;
        $this->imageId = $imageId;
        $this->maxSize = $maxSize;
        $this->wrapperClass = $wrapperClass;
        $this->showPreview = $showPreview;
    }

    public function render()
    {
        return view('components.file-upload');
    }
}