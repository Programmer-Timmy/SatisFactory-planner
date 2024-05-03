<?php
ob_start();
$gameSaves = GameSaves::getSaveGamesByUser($_SESSION['userId']);

if ($_POST && isset($_POST['UpdatedSaveGameName'])) {
    // Assuming you've included or defined the Database class somewhere

    $UpdatedSaveGameName = $_POST['UpdatedSaveGameName'];

    $gameSave_id = $_POST['id'];

    if ($_POST['AllowedUsers'] == null) {
        $_POST['AllowedUsers'] = [];
    }

    // Assuming Database::insert() is a function that inserts data into the database
    $gameSaveId = GameSaves::updateSaveGame($gameSave_id, $_SESSION['userId'], $UpdatedSaveGameName, $_FILES['UpdatedSaveGameImage'], $_POST['AllowedUsers']);
    if ($gameSaveId) {
        header('Location:/home');
        exit();
    }

}

if ($_GET && isset($_GET['delete'])) {
    $gameSaveId = $_GET['delete'];
    GameSaves::deleteSaveGame($gameSaveId);
    header('Location:/home');
    exit();

}

?>
<div class="container">
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
                <?php
                    if (file_exists('image/' . $gameSave->image) && $gameSave->image != '' && $gameSave->image != null) {
                        $gameSave->image = $gameSave->image;
                    } else {
                        $gameSave->image = 'default_img.png';
                    }
                ?>
                <div class="col-md-4 d-flex align-items-stretch">
                    <a href="game_save?id=<?= $gameSave->game_saves_id ?>" class="card-link text-black text-decoration-none">
                        <div class="card mt-3">
                            <div class="position-relative">
                                <img src="image/<?= $gameSave->image ?>" class="card-img-top" alt="...">
                                <?php if ($gameSave->owner_id == $_SESSION['userId']) : ?>
                                    <a class="btn btn-danger position-absolute top-0 end-0" href="home?delete=<?= $gameSave->game_saves_id ?>" onclick="return confirm('Are you sure you want to delete this game save?')"><i class="fa-solid fa-trash"></i></a>
                                    <button id="update_product_line_<?= $gameSave->id ?>" class="btn btn-primary position-absolute top-0 start-0"><i class="fa-solid fa-pencil"></i></button>
                                <?php endif; ?>
                            </div>
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
