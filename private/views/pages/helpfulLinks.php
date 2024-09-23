<?php
$helpfulLinks = HelpfulLinks::getApprovedHelpfulLinks();
$unapprovedHelpfulLinks = [];
$success = '';
$error = '';

if (isset($_SESSION['admin'])) {
    if ($_SESSION['admin'] != null) {
        $unapprovedHelpfulLinks = HelpfulLinks::getUnapprovedHelpfulLinks();
    }
}

if (isset($_GET['success'])) {
    if ($_GET['success'] == 'true') {
        $success = 'Link suggestion submitted successfully';
    } else {
        $error = 'Error submitting link suggestion';
    }
}

// Handling approval and disapproval
if (isset($_POST['action']) && isset($_POST['link_id'])) {
    $linkId = $_POST['link_id'];
    if ($_POST['action'] === 'approve') {
        $result = HelpfulLinks::approveLink($linkId);
        header('Location: /helpfulLinks');
        exit();

    } elseif ($_POST['action'] === 'disapprove') {
        $result = HelpfulLinks::disapproveLink($linkId);
        header('Location: /helpfulLinks');
        exit();
    }
}

?>

<div class="container d-flex flex-column" style="min-height: 65vh;">
    <div class="row">
        <div class="col text-center">
            <h1>Helpful Links</h1>
            <p>Here are some helpful links that you may find useful.</p>
        </div>
        <?php if ($success) : ?>
            <div class="alert alert-success" role="alert">
                <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if ($error) : ?>
            <div class="alert alert-danger" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="flex-grow-1">
        <div class="row">
            <?php foreach ($helpfulLinks as $link) : ?>
                <div class="col-md-6 col-lg-4 mb-4 d-flex">
                    <div class="card shadow-sm h-100 w-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold m-0 text-center">
                                <a href="<?= $link->url ?>" target="_blank" class="text-decoration-none">
                                    <?= $link->name ?>
                                    <i class="fas fa-external-link-alt ms-2"></i>
                                </a>
                            </h5>
                            <?php if (!empty($link->description)) : ?>
                                <hr>
                                <p class="card-text text-muted"><?= $link->description ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Moved Unapproved Links Section Here -->


    <div class="row mt-4">
        <div class="col d-flex justify-content-center align-items-center">
            <!-- Suggestion Button -->
            <div <?= !empty($unapprovedHelpfulLinks) ? 'class="me-3"' : '' ?>>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#suggestionModal">
                    Suggest a New Link
                </button>
            </div>

            <!-- Unapproved Links Toggle Button -->
            <?php if (!empty($unapprovedHelpfulLinks)) : ?>
                <div class="ms-3">
                    <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#unapprovedLinks" aria-expanded="false" aria-controls="unapprovedLinks">
                        <i class="fas fa-chevron-down"></i> Unapproved Links
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Unapproved Links Collapse Section -->
    <?php if (!empty($unapprovedHelpfulLinks)) : ?>
        <div class="collapse mt-3" id="unapprovedLinks">
            <div class="row">
                <?php foreach ($unapprovedHelpfulLinks as $link) : ?>
                    <div class="col-md-6 col-lg-4 mb-4 d-flex">
                        <div class="card shadow-sm h-100 w-100">
                            <div class="card-body p-0 d-flex flex-column">
<!--                                list of all the data-->
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">
                                        <span class="fw-bold">Name:</span> <?= $link->name ?>
                                    </li>
                                    <li class="list-group-item">
                                        <span class="fw-bold">URL:</span> <a href="<?= $link->url ?>" target="_blank"><?= $link->url ?></a>
                                    </li>
                                    <li class="list-group-item">
                                        <span class="fw-bold">Description:</span> <?= $link->description ?>
                                    </li>
                                </ul>

                                <div class="card-footer d-flex justify-content-center">
                                    <form method="POST" class="text-center">
                                        <input type="hidden" name="link_id" value="<?= $link->id ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                                        <button type="submit" name="action" value="disapprove" class="btn btn-danger">Disapprove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../private/views/Popups/linkSuggestion.php'; ?>

<script>
    const url = window.location.protocol + "//" + window.location.host + window.location.pathname;
    window.history.replaceState({path: url}, "", url);
</script>

<?php if ($error != '' || $success != '') : ?>
    <script>
        setTimeout(function () {
            window.location.href = '/helpfulLinks';
        }, 3000);
    </script>
<?php endif; ?>
