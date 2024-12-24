<?php

namespace App\View\Components;

use Illuminate\View\Component;

class SchemaMarkup extends Component
{
    public $schema;

    public function __construct($schema)
    {
        $this->schema = $schema;
    }

    public function render()
    {
        return view('components.schema-markup');
    }
}