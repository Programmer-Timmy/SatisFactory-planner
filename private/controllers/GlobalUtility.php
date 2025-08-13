<?php

class GlobalUtility {
    /**
     * @param array $data
     * @param array $shownTables ['*'] for all columns or ['name', 'date', 'removed'] for specific columns
     * @param array $customButtons [['class' => '{Style Classes}', 'action'=> '{page url whit get}={id wil always be at the end}', 'label' => '{The button text}'], [...]]
     * @param array $excludeBools ['column_name'] for columns that are bools and should not be shown as true/false
     * @param bool $bootstrap
     * @param bool $enableBool
     * @param string|null $customId
     * @return string
     */
    public static function createTable(array $data, array $shownTables = ['*'], array $customButtons = [], array $excludeBools = [], bool $bootstrap = true, bool $enableBool = true, ?string $customId = null): string {
        $tableClass = $bootstrap ? 'table table-striped table-hover table-responsive' : '';
        $customId = $customId ? "id='$customId'" : '';


        $table = "<div $customId class=' " . ($bootstrap ? 'table-responsive' : '') . "'>";
        $table .= '<table class="' . $tableClass . '">';
        $table .= '<thead class="' . ($bootstrap ? 'table-dark' : '') . '"><tr>';

        if (!empty($data)) {
            foreach (get_object_vars($data[0]) as $column => $value) {
                $oldColumn = $column;
                $column = str_replace('_', ' ', $column);
                if ($shownTables[0] == '*') {
                    $table .= '<th>' . $column . '</th>';
                } else {
                    if (in_array($oldColumn, $shownTables)) {
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
                    if (($value == 1 || $value == 0) && $enableBool && !in_array($column, $excludeBools)) {
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
            $table .= '<div class="alert alert-info text-center" role="alert">
                        No data available.
                    </div>';
        }

        $table .= '</table>';
        $table .= '</div>';

        return $table;


    }

    public static function formatUpdatedTime($updated_at): string {
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
            return $interval->format('%i minutes ago');
        } else {
            return 'Just now';
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
     * @param string $title The title of the card.
     * @param string|null $content The main content of the card as HTML (e.g., charts, tables).
     * @param array $controls An array of controls, like dropdowns or buttons, to add to the card header.
     * @param string|null $footer Optional footer content as HTML.
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

    public static function generateRecipeSelect(array $recipes, int | null $selectedRecipeId = null): string {
        ob_start();
        $recipeName = '';
        if ($selectedRecipeId === null) {
            $selectedRecipeId = '';
        } else {
            // get recipe from array
            foreach ($recipes as $recipe) {
                if ($recipe->id == $selectedRecipeId) {
                    $recipeName = $recipe->name;
                    break;
                }
            }

        }
        ?>
        <div class="bg-white recipe-select position-relative">
            <input type="text" data-sp-skip="true" class="form-control rounded-0 search-input" name="recipeSearch"
                   placeholder="Search by product or recipe" value="<?= htmlspecialchars($recipeName) ?>" autocomplete="off">
            <input type="hidden" name="recipeId" class="recipe-id" value="<?= $selectedRecipeId ?>" autocomplete="off">
            <div class="select-items collapse position-absolute child bg-white overflow-y-auto z-2" style="max-height: 300px; min-width: 300px;">
                <?php foreach ($recipes as $recipe): ?>

                    <div class="p-1 select-item" data-recipe-id="<?= $recipe->id ?>"
                         data-recipe-name="<?= htmlspecialchars($recipe->name) ?>">
                        <h6 class="m-0 text-center small recipe-name"><?= $recipe->name ?></h6>

                        <div class="d-flex justify-content-center align-items-center mt-1 flex-wrap" style="gap:4px;">

                            <?php if ($recipe->ingredients): ?>
                                <?php foreach ($recipe->ingredients as $ingredient): ?>
                                    <div class="d-flex align-items-center" style="gap:2px;">
                                        <img src="/image/items/<?= strtolower(str_replace('_', '-', $ingredient->class_name)) ?>_256.png"
                                             title="<?= $ingredient->name ?>"
                                             class="img-fluid" style="width: 26px; height: 26px;">
                                        <small class="text-muted"><?= round($ingredient->quantity, 5) ?></small>
                                    </div>
                                <?php endforeach; ?>

                                <i class="fa-solid fa-arrow-right" style="font-size:12px;"></i>

                            <?php endif; ?>

                            <?php if ($recipe->building): ?>
                                <img src="/image/items/<?= strtolower(str_ireplace('build', 'desc', str_replace('_', '-', $recipe->building[0]->class_name))) ?>_256.png"
                                     title="<?= $recipe->building[0]->name ?>"
                                     class="img-fluid" style="width: 26px; height: 26px;">
                            <?php endif; ?>

                            <?php if ($recipe->products): ?>
                                <i class="fa-solid fa-arrow-right" style="font-size:12px;"></i>
                                <?php foreach ($recipe->products as $product): ?>
                                    <div class="d-flex align-items-center recipe-product" style="gap:2px;" data-product-id="<?= $product->id ?>" data-product-name="<?= htmlspecialchars($product->name) ?>">
                                        <img src="/image/items/<?= strtolower(str_replace('_', '-', $product->class_name)) ?>_256.png"
                                             title="<?= $product->name ?>"
                                             class="img-fluid" style="width: 26px; height: 26px;">
                                        <small class="text-muted"><?= round($product->quantity, 5) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

