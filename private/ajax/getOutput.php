<?php

if (!isset($_GET['id'])) {
    header('Location: /');
    exit();
}

$outputs = Outputs::getAllOutputs($_GET['id']);
?>

<?php if (empty($outputs)) : ?>
    <h4 class="text-center mt-3">No Outputs Found</h4>
<?php else: ?>
    <div class="overflow-auto" style="max-height: 40vh;">
        <table class="table table-striped">
            <thead class="table-dark">
            <tr>
                <th scope="col">Item</th>
                <th scope="col">Amount</th>
            </tr>
            </thead>
            <tbody id="output_table">
            <?php foreach ($outputs as $output) : ?>
                <tr>
                    <td><?= $output->item ?></td>
                    <td><?= $output->ammount ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
