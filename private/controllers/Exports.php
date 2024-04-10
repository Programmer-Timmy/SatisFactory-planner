<?php

class Exports
{
    public static function getAllExports($production_lines_id)
    {
        return Database::getAll("output", ['*'], [], ['production_lines_id' => $production_lines_id]);
    }

}