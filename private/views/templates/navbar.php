<?php
global $theme, $changelog;
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

$navItems['/helpfulLinks'] = 'Helpful Links';


?>
<style>
    .navbar-collapse .toggle-group .btn-dark {
        background-color: var(--bs-btn-active-bg);
        color: white;
    }

    .navbar-collapse .toggle-group .btn-light {
        background-color: white;
        color: black;
    }

    .navbar-collapse .toggle {
        height: 38px !important;
        width: 55px !important;
    }
</style>
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
                    <li class="nav-item me-lg-2 mt-lg-0 mt-2 d-flex align-items-center" style="font-size: 1.5rem;">
                        <input type="checkbox" data-toggle="toggle" data-onstyle="dark" data-offstyle="light"
                               data-on="<i class='fa-solid fa-moon'></i>" data-off="<i class='fa-solid fa-sun'></i>"
                               data-bs-size="sm" data-style="ios" data-theme="dark" id="themeToggle"
                            <?php if ($theme === 'styles-dark') echo 'checked'; ?>>

                    </li>
<!--                    account dropdown-->
                    <li class="nav-item dropdown me-lg-2 mt-lg-0 mt-2">
                        <a class="btn btn-secondary dropdown-toggle" href="#" id="navbarDropdown" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item <?= ($require === '/account') ? 'active' : ''; ?>" href="/account" >Account</a>
                            <?php if (isset($_SESSION['userId']) && isset($_SESSION['admin']) && $_SESSION['admin']) : ?>
                                <a class="dropdown-item <?= ($require === '/admin') ? 'active' : ''; ?>" href="/admin">Admin Panel</a>                           <?php endif; ?>

                            <hr class="dropdown-divider">
                            <?php if (isset($_SESSION['userId'])) : ?>
                                <a class="dropdown-item" href="/logout">Logout</a>
                            <?php else : ?>
                                <a class="dropdown-item" href="/login">Login</a>
                                <a class="dropdown-item" href="/register">Register</a>
                            <?php endif; ?>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>


<script>
    $('#themeToggle').change(function () {
        let theme = $('#theme').attr('href');
        if (theme.includes('light')) {
            $('#theme').attr('href', '/css/styles-dark.css?v=<?= $changelog['version'] ?>');
            document.cookie = 'theme=dark; expires=Fri, 31 Dec 9999 23:59:59 GMT';
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        } else {
            $('#theme').attr('href', '/css/styles-light.css?v=<?= $changelog['version'] ?>');
            document.cookie = 'theme=light; expires=Fri, 31 Dec 9999 23:59:59 GMT';
            document.documentElement.setAttribute('data-bs-theme', 'light');
        }
    });
</script>