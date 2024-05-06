<?php

class Outputs
{
    public static function getAllOutputs()
    {
        $outputs = Database::getAll("output", ['output.*', 'items.name as item'], ['items' => 'items.id = output.items_id where output.ammount > 0'], [], 'items.name ASC');
        $imports = Database::getAll("input", ['*'], [], [], 'items_id ASC');

        // Combine amounts of the same item ID
        $outputArray = [];
        foreach ($outputs as $output) {
            if (isset($outputArray[$output->items_id])) {
                $outputArray[$output->items_id]->ammount += $output->ammount;
            } else {
                $outputArray[$output->items_id] = $output;
            }
        }

        $importArray = [];
        foreach ($imports as $import) {
            if (isset($importArray[$import->items_id])) {
                $importArray[$import->items_id]->ammount += $import->ammount;
            } else {
                $importArray[$import->items_id] = $import;
            }
        }


        foreach ($outputArray as $output) {
            foreach ($importArray as $import) {
                if ($output->items_id == $import->items_id) {
                    $output->ammount -= $import->ammount;

                    if ($output->ammount <= 0) {
                        unset($outputArray[$output->items_id]);
                    }
                }
            }
        }

        //sort the array by item name


        return $outputArray;


    }
}