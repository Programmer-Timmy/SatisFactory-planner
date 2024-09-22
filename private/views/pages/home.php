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

if ($_GET && isset($_GET['request'])) {
    $requestId = $_GET['request'];
    if (isset($_GET['decline'])) {
        GameSaves::declineRequest($requestId);
    } else {
        GameSaves::acceptRequest($requestId);
    }
    header('Location:/home');
    exit();
}

$Invites = GameSaves::getRequests($_SESSION['userId']);

$class = 'col-md-6 col-lg-4';
if (count($gameSaves) == 2) {
    $class = 'col-md-6';
} elseif (count($gameSaves) == 1) {
    $class = 'col-md-8 offset-md-2';

}


?>
<div class="container">
    <div class="row align-items-center">
        <div class="d-none d-md-block col-3 "></div>
        <div class="col-9 col-md-6 text-md-center text-start">
            <h2>Game Saves</h2>
        </div>
        <div class="col-3">
            <div class="d-flex justify-content-end">
                <div class="dropdown mega-dropdown me-2" data-bs-auto-close="outside">
                    <div class="position-relative" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if ($Invites) : ?>
                            <p class="pinned position-absolute translate-middle p-2 bg-danger border border-light rounded-circle"
                               aria-hidden="true" id="request-count"><?= count($Invites) ?></p>
                        <?php endif; ?>
                        <button id="Invites" class="btn btn-secondary"><i
                                    class="fa-solid fa-envelope"></i></button>
                    </div>
                    <div id="InvitesDropdown" class="dropdown-menu p-2 mt-2 fade" aria-labelledby="Invites"
                         style="width: 300px;">
                        <h5 class="dropdown-header">Invites</h5>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <div id="InvitesList">
                            <?php if ($Invites) : ?>

                                <div class="d-flex justify-content-between p-1">
                                    <p class="m-0">Username</p>
                                    <p class="m-0">Game Save</p>
                                    <p class="m-0">Action</p>
                                </div>
                                <?php foreach ($Invites as $request) : ?>
                                    <div class="card">
                                        <div class="card-body d-flex justify-content-between p-2 align-items-center">
                                            <p class="m-0"><?= $request->username ?></p>
                                            <p class="m-0"><?= $request->title ?></p>
                                            <div>
                                                <a href="?request=<?= $request->id ?>" class="btn btn-success btn-sm"
                                                   style="width: 30px;"
                                                   data-bs-toggle="tooltip" data-bs-placement="top"
                                                   data-bs-title="Accept Request"><i class="fa-solid fa-check"></i>
                                                </a>
                                                <a href="?request=<?= $request->id ?>&decline=true"
                                                   class="btn btn-danger btn-sm" style="width: 30px;"
                                                   data-bs-toggle="tooltip" data-bs-placement="top"
                                                   data-bs-title="Decline Request"><i class="fa-solid fa-times"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <h6 class="text-center">No Invites found</h6>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <button id="add_product_line" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-title="Add Game Save"><i class="fa-solid fa-plus"></i></button>
            </div>
        </div>
    </div>

    <?php if (empty($gameSaves)) : ?>
        <h1>No Game Saves Found</h1>
    <?php else : ?>
        <!--    show cards-->
        <div class="row">
            <?php foreach ($gameSaves as $gameSave) :
                $gameSave->image = (file_exists('image/' . $gameSave->image) && !empty($gameSave->image)) ? $gameSave->image : 'default_img.png';
                ?>
                <div class="d-flex align-items-stretch <?= $class ?> mt-3">
                    <a href="game_save?id=<?= $gameSave->game_saves_id ?>"
                       class="card-link text-black text-decoration-none">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="image/<?= $gameSave->image ?>" class="card-img-top" alt="...">
                                <?php if ($gameSave->owner_id == $_SESSION['userId']) : ?>
                                    <a class="btn btn-danger position-absolute top-0 end-0"
                                       href="home?delete=<?= $gameSave->game_saves_id ?>"
                                       onclick="return confirm('Delete this game save?')"
                                       data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"><i
                                                class="fa-solid fa-trash"></i></a>
                                    <button class="btn btn-primary position-absolute top-0 start-0"
                                            id="update_save_game_line_<?= $gameSave->id ?>"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><i
                                                class="fa-solid fa-pencil"></i></button>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?= $gameSave->title ?></h5>
                                <p class="card-text">Owner: <?= $gameSave->Owner ?></p>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted">Created At: <?= $gameSave->created_at ?></small>
                            </div>
                        </div>
                    </a>
                </div>
                <?php if ($gameSave->owner_id == $_SESSION['userId']) require '../private/views/Popups/updateSaveGame.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once '../private/views/Popups/addSaveGame.php'; ?>

<script>
    document.getElementById('InvitesDropdown').addEventListener('click', function (event) {
        event.stopPropagation();
    });
</script>