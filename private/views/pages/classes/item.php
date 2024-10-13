<?php

class item
{
    public $id;
    public $name;
    public $form;
    public $class_name;

    // Constructor to initialize properties
    public function __construct($id, $name, $form, $class_name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->form = $form;
        $this->class_name = $class_name;
    }
}
