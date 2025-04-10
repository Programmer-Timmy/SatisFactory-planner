<?php
$users = Users::getAllUsers();
?>

<div class="container">
    <div class="row align-items-center mb-3">
        <div class="col-lg-4"></div>
        <div class="col-lg-4">
            <h1 class="text-center">Users</h1>
        </div>
        <div class="col-lg-4 text-lg-end text-center">
            <a href="/admin" class="btn btn-primary">Return to admin page</a>
        </div>
    </div>
    <?= GlobalUtility::createTable(
        $users,
        ['username', 'email', 'updates', 'admin', 'verified', 'saves', 'shared_saves'],
        [
            ['class' => 'btn btn-primary', 'action' => '/admin/users/edit?id=', 'label' => 'Edit'],
            ['class' => 'btn btn-danger', 'action' => '/admin/users/delete?id=', 'label' => 'Delete'],
            ['class' => 'btn btn-success', 'action' => '/admin/users/game-saves?id=', 'label' => 'Save Games']
        ],
        [
            'shared_saves',
            'saves',
        ]
    )
    ?>
</div>
