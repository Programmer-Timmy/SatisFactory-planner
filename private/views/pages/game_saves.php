<?php
require_once '../private/types/role.php';
ob_start();
$gameSaves = GameSaves::getSaveGamesByUser($_SESSION['userId']);
$error = '';
$success = '';
if ($_POST && isset($_POST['UpdatedSaveGameName'])) {
    if (!GameSaves::checkAccessOwner($_POST['id'])) {
        header('Location:/game_saves');
        exit();
    }

    $updatedSaveGameName = $_POST['UpdatedSaveGameName'];


    if (strlen($updatedSaveGameName) > 45) {
        $error = 'Save Game Name is too lengthy. Please use up to 45 characters.';
    } elseif ($updatedSaveGameName !== strip_tags($updatedSaveGameName)) {
        $error = 'Security Alert: Unauthorized characters detected in Save Game Name. Nice try, but FICSIT Security has blocked that!';
    } elseif (isset($_FILES['saveGameImage']['tmp_name']) && $_FILES['saveGameImage']['tmp_name'] &&
        !in_array(mime_content_type($_FILES['saveGameImage']['tmp_name']), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
        $error = 'Invalid image format. Please upload an image in JPEG, PNG, GIF, or WebP format.';
    } elseif (isset($_FILES['saveGameImage']['tmp_name']) && $_FILES['saveGameImage']['tmp_name'] && $_FILES['saveGameImage']['size'] > 2097152) {
        $error = 'Image size is too large. Please upload an image under 2MB.';
    } elseif (isset($_FILES['saveGameImage']['name']) && $_FILES['saveGameImage']['name'] !== strip_tags($_FILES['saveGameImage']['name'])) {
        $error = 'Security Alert: Unauthorized characters detected in image name. Nice try, but FICSIT Security has blocked that!';
    }

    if (!$error) {
        $gameSave_id = $_POST['id'];

        if (!isset($_POST['AllowedUsers'])) {
            $_POST['AllowedUsers'] = [];
        }

        try {
            $gameSaveId = GameSaves::updateSaveGame($gameSave_id, $_SESSION['userId'], $updatedSaveGameName, $_FILES['UpdatedSaveGameImage']);
        } catch (Exception $e) {
            $error = 'Error updating save game. Please try again or contact support.';
        }


        if ($_POST['dedicatedServerIp'] && $_POST['dedicatedServerPort']) {
            if (!filter_var($_POST['dedicatedServerIp'], FILTER_VALIDATE_IP)) {
                if (!preg_match('/^(?!:\/\/)([a-zA-Z0-9-_]{1,63}\.)+[a-zA-Z]{2,6}$/', $_POST['dedicatedServerIp'])) {
                    $error = 'Invalid IP address or domain name';
                }
            } elseif (!is_numeric($_POST['dedicatedServerPort'])) {
                $error = 'Invalid port number';
            }

            if (!$error) {
                $data = DedicatedServer::saveServer($gameSave_id, $_POST['dedicatedServerIp'], $_POST['dedicatedServerPort'], $_POST['dedicatedServerPassword']);
                if ($data) {
                    if ($data['status'] === 'error') {
                        $error = $data['message'];
                    } else {
                        $success = $data['message'];
                    }
                }
            }
        }

        header('Location:/game_saves');
        exit();
    }
}

if ($_GET && isset($_GET['delete'])) {
    $gameSaveId = $_GET['delete'];
    if (!GameSaves::checkAccessOwner($gameSaveId)) {
        $_SESSION['error'] = 'You do not have permission to delete this save game.';
        header('Location:/game_saves');
        exit();
    }
    $result = GameSaves::deleteSaveGame($gameSaveId);
    if (!$result->success) {
        $_SESSION['error'] = $result->message;
        header('Location:/game_saves');
        exit();
    }
    $_SESSION['success'] = 'Game save deleted successfully.';
    header('Location:/game_saves');
    exit();
}

if ($_GET && isset($_GET['request'])) {
    $requestId = $_GET['request'];
    if (isset($_GET['decline'])) {
        GameSaves::declineRequest($requestId);
    } else {
        GameSaves::acceptRequest($requestId);
    }
    header('Location:/game_saves');
    exit();
}

$Invites = GameSaves::getRequests($_SESSION['userId']);

$class = 'col-md-6 col-lg-4';
if (count($gameSaves) <= 2) {
    $class = 'col-md-6';
}


?>
<div class="container">
        <?php GlobalUtility::displayFlashMessages() ?>
    <div class="row align-items-center">
        <div class="d-none d-md-block col-3 "></div>
        <div class="col-9 col-md-6 text-md-center text-start">
            <h1>Game Saves</h1>
        </div>
        <div class="col-3">
            <div class="d-flex justify-content-end">
                <div class="dropdown mega-dropdown me-2" data-bs-auto-close="outside">
                    <div class="position-relative" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if ($Invites) : ?>
                            <p class="pinned position-absolute translate-middle p-2 bg-danger border border-light rounded-circle"
                               aria-hidden="true" id="request-count"><?= count($Invites) ?></p>
                        <?php endif; ?>
                        <button id="Invites" class="btn btn-secondary" data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                data-bs-title="Invites">
                            <i class="fa-solid fa-envelope"></i>
                        </button>

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
                                                <a href="?request=<?= $request->id ?>"
                                                   class="btn btn-success btn-sm"
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

                <button id="add_game_save" class="btn btn-primary" data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        data-bs-title="Add Game Save"><i class="fa-solid fa-plus"></i>
                </button>

            </div>
        </div>
    </div>
    <?php if ($error) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" id="error">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <script>
                setTimeout(() => {
                    $('#error').remove();
                }, 5000);
            </script>
        </div>

    <?php endif; ?>
    <?php if ($success) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" id="success">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            <script>
                setTimeout(() => {
                    // fade out after 5 seconds
                    $('#success').remove();
                }, 5000);
            </script>
        </div>
    <?php endif; ?>
    <?php if (empty($gameSaves)) : ?>
        <div class="row">
            <div class="col-12 text-center">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-warning"></i>
                    Oops! You don't have any game saves yet. You can add a game save by clicking the button below, or a
                    friend can invite you to a game save.

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#popupModal">Add Game Save
                </button>
            </div>
        </div>
    <?php else : ?>
        <!--    show cards-->
        <div class="row <?= count($gameSaves) == 1 ? "justify-content-center" : "" ?>">
            <?php foreach ($gameSaves as $gameSave) :
                $gameSave->image = (file_exists('image/' . $gameSave->image) && !empty($gameSave->image)) ? $gameSave->image : 'default_img.png';
                ?>
                <div class="d-flex align-items-stretch <?= $class ?> mt-3">
                    <div class="card h-100 w-100">
                        <div class="position-relative">
                            <a href="game_save?id=<?= $gameSave->game_saves_id ?>"
                               class="card-link text-black text-decoration-none">
                                <img src="image/<?= $gameSave->image ?>" class="card-img-top object-fit-cover" style="max-height: 400px" alt="...">
                            </a>
                            <?php if ($gameSave->role === Role::OWNER->value) : ?>
                                <a class="btn btn-danger position-absolute top-0 end-0"
                                   href="game_saves?delete=<?= $gameSave->game_saves_id ?>"
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
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <small class="text-muted">Created At: <?= $gameSave->created_at ?></small>
                            <a href="game_save?id=<?= $gameSave->game_saves_id ?>" class="btn btn-outline-primary btn-sm">
                                Open
                            </a>
                        </div>
                    </div>
                </div>
                <?php if ($gameSave->role === Role::OWNER->value) require '../private/views/Popups/saveGame/updateSaveGame.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../private/views/Popups/saveGame/addSaveGame.php'; ?>

<script>
    document.getElementById('InvitesDropdown').addEventListener('click', function (event) {
        event.stopPropagation();
    });
</script>
<script>
    $('.dedicatedServerButton').on('click', function () {
        this.classList.toggle('rounded-bottom-0');
    });
</script>
<script>
    const togglePassword = $('.togglePassword');
    togglePassword.on('click', function () {
        const passwordInput = $(this).prev();
        const eyeIcon = $(this).find('i');

        const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
        passwordInput.attr('type', type);
        eyeIcon.toggleClass('fa-eye-slash');
        eyeIcon.toggleClass('fa-eye');
    });
</script>
