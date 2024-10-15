<?php
ob_start();
global $require;
$lastVisitedSaveGame = '';
if (isset($_SESSION['userId'])){
    if ($_SESSION['userId'] != '' && $_SESSION['userId'] != null){
        $lastVisitedSaveGame = GameSaves::getLastVisitedSaveGame() ?? GameSaves::getSaveGamesByUser($_SESSION['userId'])[0]->id ?? null;
        if ($require === '/game_save') {
            $lastVisitedSaveGame = $_GET['id'];
        }
    }
}

$navItems = [
    '/home' => 'Home',
];

if ($lastVisitedSaveGame != '') {
    $navItems['/game_save?id=' . $lastVisitedSaveGame] = 'Game Save';
}

$navItems['/account'] = 'Account';
$navItems['/helpfulLinks'] = 'Helpful Links';


?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-5">
    <div class="container-fluid">
        <a class="navbar-brand" href="/home">Satisfactroy Planner</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php foreach ($navItems as $url => $label) :?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo ($require === explode('?', $url)[0]
                        ) ? 'active' : ''; ?>" aria-current="page" href="<?= $url ?>">
                            <?= $label ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item me-lg-2">
                        <a class="btn btn-primary" aria-current="page" onclick="
" href="/changelog">Changelog</a>
                    </li>
                    <li class="nav-item me-lg-2 mt-lg-0 mt-2">
                        <a class="btn btn-success" aria-current="page" target="_blank"
                           href="https://forms.gle/fAd5LrGRATYwFHzr7">Leave Feedback</a>
                    </li>
                    <li class="nav-item mt-lg-0 mt-2">
                        <a class="btn btn-danger" aria-current="page" href="/logout"><i
                                    class="fa-solid fa-sign-out"></i></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>