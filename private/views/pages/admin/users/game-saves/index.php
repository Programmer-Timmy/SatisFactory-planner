<?php

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    header('Location: /admin/users');
    exit;
}

$users = Users::getUserById($id);

if (empty($users)) {
    header('Location: /admin/users');
    exit;
}

$saveGames = GameSaves::getSaveGamesByUser($id);
?>
<div class="container">
    <div class="row align-items-center mb-3">
        <div class="col-lg-2"></div>
        <div class="col-lg-8">
            <h1 class="text-center">Save Games for <b><?= htmlspecialchars($users->username) ?></b></h1>
        </div>
        <div class="col-lg-2 text-lg-end text-center">
            <a href="/admin/users" class="btn btn-primary">Return to users</a>
        </div>
    </div>
    <?= GlobalUtility::createTable(
        $saveGames,
        ['title', 'created_at', 'owner', 'production_lines']
        ,
        [
            ['class' => 'btn btn-success', 'action' => '/admin/users/game-saves/production-lines?user=' . $id . '&id=', 'label' => 'Production Lines'],
        ],
        [
            'owner',
            'created_at',
            'title'
        ]
    );
    ?>
    <p class="text-muted text-center">You can't edit, delete or add game saves here. Only the user can do that.</p>

</div>


