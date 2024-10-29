<?php
$users = Users::getAllUsers();
?>

<div class="container">
    <h1 class="text-center mb-3">Users</h1>
    <a href="/admin" class="btn btn-primary w-100 mb-3">Return to admin page</a>
    <?= GlobalUtility::createTable(
        $users,
        ['username', 'email', 'updates', 'admin', 'verified'],
        [
            ['class' => 'btn btn-primary', 'action' => '/admin/users/edit?id=', 'label' => 'Edit'],
            ['class' => 'btn btn-danger', 'action' => '/admin/users/delete?id=', 'label' => 'Delete']
        ]
    )
    ?>
</div>
