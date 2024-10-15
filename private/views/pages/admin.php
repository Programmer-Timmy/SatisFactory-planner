<?php
// Get all the files from the admin folder and store file names in an array
$files = scandir(__DIR__ . '/admin');
$pages = [];
foreach ($files as $file) {
    if (is_file(__DIR__ . '/admin/' . $file)) {
        $fileName = pathinfo($file, PATHINFO_FILENAME);
        $fileName = preg_replace('/(?<!^)([A-Z])/', ' $1', $fileName);
        $fileName = str_replace(['_', '-'], ' ', $fileName);
        $fileName = ucwords($fileName);
        $pages[] = (object)['name' => $fileName, 'file' => $file];
    }
}
?>

<div class="container">
    <h1 class="text-center mb-4">Admin Panel</h1>
    <div class="row">
        <?php foreach ($pages as $page) : ?>
            <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm border-0 rounded">
                    <div class="card-body text-center">
                        <h5 class="card-title"><?= $page->name ?></h5>
                        <p class="card-text text-muted">Access the <?= strtolower($page->name) ?> management page.</p>
                        <a href="/admin/<?= pathinfo($page->file, PATHINFO_FILENAME) ?>"
                           class="btn btn-primary btn-block rounded-pill">
                            <i class="fas fa-cog"></i> Go to <?= $page->name ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .card {
        transition: transform 0.2s;
    }

    .card:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: bold;
    }

    .card-text {
        font-size: 0.9rem; /* Smaller text for description */
        margin-bottom: 1.5rem; /* Space between description and button */
    }
</style>
