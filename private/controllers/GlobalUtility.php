<?php

class GlobalUtility
{
    /**
     * @param array $data
     * @param array $shownTables ['*'] for all columns or ['name', 'date', 'removed'] for specific columns
     * @param array $customButtons [['class' => '{Style Classes}', 'action'=> '{page url whit get}={id wil always be at the end}', 'label' => '{The button text}'], [...]]
     * @param bool $bootstrap
     * @return string
     */
    public static function createTable(array $data, array $shownTables = ['*'], array $customButtons = [], bool $bootstrap = true): string
    {
        $tableClass = $bootstrap ? 'table table-striped table-hover table-responsive' : '';

        $table = '<div class="' . ($bootstrap ? 'table-responsive' : '') . '">';
        $table .= '<table class="' . $tableClass . '">';
        $table .= '<thead><tr>';

        if (!empty($data)) {
            foreach (get_object_vars($data[0]) as $column => $value) {
                if ($shownTables[0] == '*') {
                    $table .= '<th>' . $column . '</th>';
                } else {
                    if (in_array($column, $shownTables)) {
                        $table .= '<th>' . ucfirst($column) . '</th>';
                    }
                }
            }
            if (!empty($customButtons)) {
                foreach ($customButtons as $button) {
                    $table .= '<th></th>';
                }
            }

            $table .= '</tr></thead><tbody>';

            foreach ($data as $row) {
                $table .= '<tr>';
                foreach (get_object_vars($row) as $column => $value) {
                    if ($value == 1 || $value == 0) {
                        $value = $value ? 'True' : 'False';
                    }
                    if ($shownTables[0] == '*') {
                        $table .= '<td>' . $value . '</td>';
                    } else {
                        if (in_array($column, $shownTables)) {
                            $table .= '<td>' . $value . '</td>';
                        }
                    }

                }

                if (!empty($customButtons)) {
                    foreach ($customButtons as $button) {
                        $table .= '<td><a class="' . $button['class'] . '" href="' . $button['action'] . $row->id . '">' . $button['label'] . '</a></td>';
                    }
                }

                $table .= '</tr>';
            }

            $table .= '</tbody>';
        } else {
            $table .= '<th>No data</th>';
        }

        $table .= '</table>';
        $table .= '</div>';

        return $table;


    }

}