<?php

class Outputs
{
    public static function getAllOutputs($id)
    {
        $productionLines = Database::getAll("production_lines", ['id', 'title'], [], ['game_saves_id' => $id, 'active' => 1]);
        $outputArray = [];

        foreach ($productionLines as $productionLine) {
            $outputs = Database::query(
                "SELECT output.*, items.name as item
                FROM output
                JOIN items ON items.id = output.items_id
                WHERE output.ammount > 0 AND output.production_lines_id = ?
                ORDER BY items.name ASC",
                [$productionLine->id]
            );

            foreach ($outputs as $output) {
                if (isset($outputArray[$productionLine->title][$output->items_id])) {
                    $outputArray[$productionLine->title][$output->items_id]->ammount += $output->ammount;
                } else {
                    $outputArray[$productionLine->title][$output->items_id] = $output;
                }
            }
        }

        $sourceUsageRows = Database::query(
            "SELECT sources.exporting_production_lines_id, sources.items_id, SUM(sources.assigned_amount) as assigned_amount
            FROM production_line_import_sources sources
            JOIN production_lines exporting_lines ON exporting_lines.id = sources.exporting_production_lines_id
            JOIN production_lines importing_lines ON importing_lines.id = sources.importing_production_lines_id
            WHERE exporting_lines.game_saves_id = ?
              AND importing_lines.game_saves_id = ?
              AND exporting_lines.active = 1
              AND importing_lines.active = 1
            GROUP BY sources.exporting_production_lines_id, sources.items_id",
            [$id, $id]
        ) ?: [];

        $sourceUsageByLine = [];
        foreach ($sourceUsageRows as $usage) {
            $sourceUsageByLine[(int)$usage->exporting_production_lines_id][(int)$usage->items_id] = (float)$usage->assigned_amount;
        }

        foreach ($productionLines as $productionLine) {
            $lineTitle = $productionLine->title;
            if (!isset($outputArray[$lineTitle])) {
                continue;
            }

            foreach ($outputArray[$lineTitle] as $itemId => $outputItem) {
                $assignedAmount = $sourceUsageByLine[(int)$productionLine->id][(int)$itemId] ?? 0;
                $outputItem->ammount -= $assignedAmount;
            }

            $outputArray[$lineTitle] = array_filter($outputArray[$lineTitle], function ($outputItem) {
                return $outputItem->ammount > 0;
            });

            usort($outputArray[$lineTitle], function ($a, $b) {
                return strcmp($a->item, $b->item);
            });
        }

        return $outputArray;
    }
}
