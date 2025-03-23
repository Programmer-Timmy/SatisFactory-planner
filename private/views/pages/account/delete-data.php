<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];

    // Verwijder alleen niet-essentiële data (geen account)
    Database::delete(table: "login_attempts", where: ["user_id" => $userId]);
    Database::delete(table: "error_404_logs", where: ["users_id" => $userId]);
    Database::delete(table: "error_403_logs", where: ["users_id" => $userId]);

    $_SESSION['success'] = "Your personal data has been successfully deleted!";
    exit;
}
?>

<div class="col-md-3"></div>
<div class="col-md-6">
    <h3 class="card-title text-center">Delete Personal Data</h3>
</div>
<div class="col-md-3"></div>
<div class="card-text">
    <div class="alert alert-danger text-center" role="alert">
        <strong>Warning!</strong> This action cannot be undone. All your personal data will be deleted.
    </div>
    <form method="post">
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-danger">Delete Data</button>
        </div>
    </form>
</div>

