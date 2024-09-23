<div class="container">
    <div class="row justify-content-end align-items-center">
        <div class="col-md-3"></div>
        <div class="col-md-6 text-center">
            <h1>Changelog</h1>
            <h3>test</h3>
        </div>
        <div class="col-md-3">
            <div class="text-md-end text-center">
                <a href="home" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Go back"><i class="fa-solid fa-arrow-left"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <?php
            // Load the changelog from the JSON file
            $changelog = json_decode(file_get_contents('changelog.json'), true);

            // Loop through each version in the changelog and display it
            foreach ($changelog as $version): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Version <?= htmlspecialchars($version['version']) ?>
                            <small class="text-muted">- <?= htmlspecialchars($version['date']) ?></small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul>
                            <?php foreach ($version['changes'] as $change): ?>
                                <li><?= htmlspecialchars($change) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>
