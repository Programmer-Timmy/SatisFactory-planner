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
    public static function createTable(array $data, array $shownTables = ['*'], array $customButtons = [], bool $bootstrap = true, bool $enableBool = true, ?string $customId = null): string
    {
        $tableClass = $bootstrap ? 'table table-striped table-hover table-responsive' : '';
        $customId = $customId ? "id='$customId'" : '';


        $table = "<div $customId class=' " . ($bootstrap ? 'table-responsive' : '') . "'>";
        $table .= '<table class="' . $tableClass . '">';
        $table .= '<thead class="' .($bootstrap ? 'table-dark' : '') . '"><tr>';

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
                    if (($value == 1 || $value == 0) && $enableBool) {
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

    public static function formatUpdatedTime($updated_at): string
    {
        // Convert updated_at to a DateTime object
        $updatedTime = new DateTime($updated_at);

        // Get the current time
        $currentTime = new DateTime('now');

        // Calculate the difference between the current time and updated time
        $interval = $currentTime->diff($updatedTime);

        // Format the time difference
        if ($interval->days > 0) {
             return $interval->format('%a days ago');
        } elseif ($interval->h > 0) {
            return $interval->format('%h hours ago');
        } elseif ($interval->i > 0) {
            return  $interval->format('%i minutes ago');
        } else {
            return  'Just now';
        }
    }

    public static function dateTimeToLocal($time): string {
        $date = new DateTime($time);
        $date->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        return $date->format('d-m-Y H:i T');
    }

    /**
     * Renders a dynamic, reusable card layout with customizable title, content, controls, and footer.
     *
     * @param string $title           The title of the card.
     * @param string|null $content     The main content of the card as HTML (e.g., charts, tables).
     * @param array $controls          An array of controls, like dropdowns or buttons, to add to the card header.
     * @param string|null $footer      Optional footer content as HTML.
     * @param bool $foreBottomControls Whether to render the controls at the bottom of the card.
     */
    public static function renderCard(string $title, ?string $content = null, array $controls = [], ?string $footer = null, bool $foreBottomControls = false
    ): void {


        ?>
        <div class="card h-100">
            <div class="card-body h-100">
                <div class="row align-items-center mb-3">
                    <div class="col-md-3"></div>
                    <div class="col-md-6">
                        <h3 class="card-title text-center"><?= htmlspecialchars($title) ?></h3>
                    </div>
                    <?php if ($foreBottomControls): ?>
                        <div class="col-md-12 d-flex justify-content-center flex-wrap">
                            <?php foreach ($controls as $control): ?>
                                <?= $control ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="col-md-3 d-flex justify-content-lg-end justify-content-center">
                            <?php foreach ($controls as $control): ?>
                                <?= $control ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-text">
                    <?php if (empty($content)): ?>
                        <div class="alert alert-info text-center" role="alert">
                            No content available.
                        </div>
                    <?php else: ?>
                        <?= $content ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($footer): ?>
                <div class="card-footer text-center">
                    <?= $footer ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function displayFlashMessages(): void {
        if (isset($_SESSION['error'])) {
            ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['success'])) {
            ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php
            unset($_SESSION['success']);
        }
    }
}

