<?php

class Outputs
{
    public static function getAllOutputs($id)
    {
        $productionLines = Database::getAll("production_lines", ['id'], [], ['game_saves_id' => $id]);
        $outputArray = [];
        $importArray = [];
        foreach ($productionLines as $productionLine) {
            $outputs = Database::getAll("output", ['output.*', 'items.name as item'], ['items' => 'items.id = output.items_id where output.ammount > 0 and production_lines_id = ' . $productionLine->id], [], 'items.name ASC');
            $imports = Database::getAll("input", ['input.*', 'items.name as item'], ['items' => 'items.id = input.items_id where input.ammount > 0 and production_lines_id = ' . $productionLine->id], [], 'items.name ASC');
            foreach ($outputs as $output) {
                if (isset($outputArray[$output->items_id])) {
                    $outputArray[$output->items_id]->ammount += $output->ammount;
                } else {
                    $outputArray[$output->items_id] = $output;
                }
            }

            foreach ($imports as $import) {
                if (isset($importArray[$import->items_id])) {
                    $importArray[$import->items_id]->ammount += $import->ammount;
                } else {
                    $importArray[$import->items_id] = $import;
                }
            }
        }

        // Combine amounts of the same item ID
        $newOutputArray = [];
        foreach ($outputArray as $output) {
            if (isset($newOutputArray[$output->items_id])) {
                $newOutputArray[$output->items_id]->ammount += $output->ammount;
            } else {
                $newOutputArray[$output->items_id] = $output;
            }
        }

        $newImportArray = [];
        foreach ($importArray as $import) {
            if (isset($newImportArray[$import->items_id])) {
                $newImportArray[$import->items_id]->ammount += $import->ammount;
            } else {
                $newImportArray[$import->items_id] = $import;
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