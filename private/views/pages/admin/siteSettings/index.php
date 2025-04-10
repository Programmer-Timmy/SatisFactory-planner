<?php
$isOwner = SiteSettings::isOwner();

if (!$isOwner) {
    header('Location: /admin');
    exit;
}

$success = false;
$error = false;


$settings = SiteSettings::getSettings();
$exclude = ['id', 'owner_id', 'data_version'];
$booleans = ['maintenance', 'debug'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    foreach ($settings as $key => $value) {
        if (in_array($key, $exclude)) continue;
        if (in_array($key, $booleans)) {
            $data[$key] = isset($_POST[$key]) ? 1 : 0;
        } else {
            $data[$key] = $_POST[$key];
        }
    }
    try {
        SiteSettings::updateSettings($data);
        $settings = SiteSettings::getSettings();
        $success = true;
    } catch (Exception $e) {
        echo($e);
        $error = true;
    }
}
global $changelog;
?>

<div class="container">
    <div class="row align-items-center mb-3">
        <div class="col-lg-4"></div>
        <div class="col-lg-4">
            <h1 class="text-center">Site Settings</h1>
        </div>
        <div class="col-lg-4 text-lg-end text-center">
            <a href="/admin" class="btn btn-primary">Return to admin page</a>
        </div>
    </div>
    <form method="post">
        <?php if ($success) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Settings updated successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($error) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                An error occurred while updating the settings.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($success || $error) : ?>
            <script>
                setTimeout(() => {
                    $('.alert').alert('close');
                }, 5000);
            </script>
        <?php endif; ?>
        <fieldset>
            <legend>Site Settings</legend>
            <!-- Text Inputs -->
            <?php foreach ($settings as $key => $value) : ?>
                <?php if (in_array($key, $exclude) || in_array($key, $booleans)) continue; ?>
                <div class="mb-4">
                    <label for="<?= $key ?>" class="form-label"><?= ucwords(str_replace('_', ' ', $key)) ?></label>
                    <input type="text" class="form-control" id="<?= $key ?>" name="<?= $key ?>"
                           value="<?= htmlspecialchars($value ?? '') ?>">
                </div>
            <?php endforeach; ?>
        </fieldset>

        <fieldset>
            <legend>Toggle Settings</legend>
            <div class="row align-items-center">
                <!-- Boolean (Checkbox) Inputs -->
                <?php foreach ($settings as $key => $value) : ?>
                    <?php if (in_array($key, $booleans)) : ?>
                        <div class="mb-3 form-check col-sm-6 col-md-5 col-lg-4 col-xl-3">
                            <input type="checkbox" class="form-check-input" id="<?= $key ?>"
                                   name="<?= $key ?>" <?= $value ? 'checked' : '' ?>
                                   data-on="Enabled" data-offlabel="Disabled" data-toggle="toggle" data-style="ios"
                                   data-onstyle="success" data-offstyle="danger">
                            <label class="form-check-label ps-3"
                                   for="<?= $key ?>"><?= ucwords(str_replace('_', ' ', $key)) ?></label>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="mt-4 text-center d-flex justify-content-center">
            <button type="submit" class="btn btn-primary me-3">Save</button>
            <a href="/admin/siteSettings/sendUpdateMail" class="btn btn-primary">Send Update Mail
                (V<?= $changelog['version'] ?>)</a>
        </div>
    </form>

</div>
