<?php

class Outputs
{
    public static function getAllOutputs($id)
    {
        $productionLines = Database::getAll("production_lines", ['id', 'title'], [], ['game_saves_id' => $id, 'active' => 1]);
        $outputArray = [];
        $importArray = [];

        // Gather all outputs and imports per production line
        foreach ($productionLines as $productionLine) {
            $outputs = Database::getAll("output", ['output.*', 'items.name as item'], ['items' => 'items.id = output.items_id where output.ammount > 0 and production_lines_id = ' . $productionLine->id], [], 'items.name ASC');
            $imports = Database::getAll("input", ['input.*', 'items.name as item'], ['items' => 'items.id = input.items_id where input.ammount > 0 and production_lines_id = ' . $productionLine->id], [], 'items.name ASC');

            // Accumulate outputs per production line
            foreach ($outputs as $output) {
                if (isset($outputArray[$productionLine->title][$output->items_id])) {
                    $outputArray[$productionLine->title][$output->items_id]->ammount += $output->ammount;
                } else {
                    $outputArray[$productionLine->title][$output->items_id] = $output;
                }
            }

            // Accumulate imports globally
            foreach ($imports as $import) {
                if (isset($importArray[$import->items_id])) {
                    $importArray[$import->items_id]->ammount += $import->ammount;
                } else {
                    $importArray[$import->items_id] = $import;
                }
            }
        }

        // Subtract the global imports from the outputs of each production line
        foreach ($outputArray as $lineTitle => &$outputs) {
            foreach ($outputs as $outputItem) {
                if (isset($importArray[$outputItem->items_id])) {
                    var_dump($outputItem->item);
                    $outputItem->ammount -= $importArray[$outputItem->items_id]->ammount;
                }
            }

            // Filter out items with zero or negative output
            $outputs = array_filter($outputs, function ($outputItem) {
                return $outputItem->ammount > 0;
            });

            // Sort outputs by item name for each line
            usort($outputs, function ($a, $b) {
                return strcmp($a->item, $b->item);
            });
        }

        return $outputArray;
    }
}

