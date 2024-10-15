<?php

class building
{
    public $id;
    public $name;
    public $class_name;
    public $power_used;
    public $power_produced;

    // Constructor to initialize properties
    public function __construct($id, $name, $class_name, $power_used, $power_produced)
    {
        $this->id = $id;
        $this->name = $name;
        $this->class_name = $class_name;
        $this->power_used = $power_used;
        $this->power_produced = $power_produced;
    }
}