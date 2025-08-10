<?php

class Checklist {

    public static function saveChecklist(array $checklist, int $productionLineId) {
        $database = new NewDatabase();
        $database->beginTransaction();
        try {
            $database->delete('checklist', ['production_lines_id' => $productionLineId]);
            foreach ($checklist as $item) {
                    $database->insert(
                    'checklist',
                    [
                        'production_lines_id',
                        'production_id',
                        "been_build",
                        "been_tested",
                    ],
                    [
                        $productionLineId,
                        $item->productionRowId,
                        $item->beenBuild === true ? 1 : 0,
                        $item->beenTested === true ? 1 : 0,
                    ]
                );
            }
            $database->commit();
            return true;
        } catch (Exception $e) {
            $database->rollBack();
            error_log('Failed to save checklist for production line ID: ' . $productionLineId . ' - ' . $e->getMessage());
            return false;
        }
    }

    public static function getChecklist(int $productionLineId) {
        $database = new NewDatabase();
        return $database->getAll('checklist', where: ['production_lines_id' => $productionLineId]);

    }

}