<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['userId'];

    $database = new NewDatabase();
    $database->beginTransaction();
    try {

        $database->delete(table: "login_attempts", where: ["users_id" => $userId]);
        $database->delete(table: "error_404_logs", where: ["users_id" => $userId]);
        $database->delete(table: "error_403_logs", where: ["users_id" => $userId]);

        $database->commit();
    } catch (Exception $e) {
        $database->rollBack();
        $_SESSION['error'] = "An error occurred while deleting your personal data. Please try again later.";
        header('Location: /account/delete-data');
    }

    $_SESSION['success'] = "Your personal data has been successfully deleted!";
    header('Location: /account');
    exit;
}
?>

<div class="container">
    <h1 class="text-center text-danger mb-4">Delete Personal Data</h1>

    <?php GlobalUtility::displayFlashMessages(); ?>

    <p class="text-center fs-5">
        Are you sure you want to delete your personal data? This action cannot be undone. Note that this will only delete non-essential data.
    </p>

    <div class="d-flex justify-content-center mt-4">
        <form method="post">
            <button type="submit" value="delete-personal-data" name="delete-personal-data" class="btn btn-danger me-3 px-4">Delete Data
            </button>
        </form>
        <a href="/account" class="btn btn-primary me-3 px-4">Cancel</a>
    </div>
</div>
