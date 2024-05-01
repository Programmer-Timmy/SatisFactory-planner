<?php
$gameSaves = GameSaves::getSaveGamesByUser($_SESSION['userId']);

?>
<div class="container mt-5">
    <div class="row justify-content-end align-items-center">
        <div class="col-md-7 text-md-end text-sm-center">
            <h2>Game Saves</h2>
        </div>
        <div class="col-md-5">
            <div class="text-md-end text-sm-center">
                <button id="add_product_line" class="btn btn-primary"><i class="fa-solid fa-plus"></i></button>
            </div>
        </div>
    </div>

    <?php if (empty($gameSaves)) : ?>
        <h1>No Game Saves Found</h1>

    <?php else :?>
<!--    show cards-->
        <div class="row">
            <?php foreach ($gameSaves as $gameSave) : ?>
                <div class="col-md-4">
                    <a href="game_save?id=<?= $gameSave->game_saves_id ?>" class="card-link text-black text-decoration-none">
                        <div class="card mt-3">
                            <img src="image/default_img.png" class="card-img-top" style="height: 250px; object-fit: cover" alt="...">
                            <div class="card-body">
                                <h5 class="card-title"><?= $gameSave->title ?></h5>
                                <p class="card-text">Owner: <?= $gameSave->Owner ?></p>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted text-right">Created At: <?= $gameSave->created_at ?></small>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


</div>

<?php require_once '../private/views/Popups/addSaveGame.php'; ?>
