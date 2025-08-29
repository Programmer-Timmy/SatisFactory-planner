<?php
global $theme, $changelog;
global $require;

class NavItem {
    public string $url;
    public string $label;
    public bool $active;

    public function __construct(string $url, string $label, bool $active = false) {
        $this->url = $url;
        $this->label = $label;
        $this->active = $active;
    }
}

class DropdownNavItem {
    public string $url;
    public string $label;
    public bool $active;
    public array $items; // Array of navItem objects

    public function __construct(string $url, string $label, array $items, bool $active = false) {
        $this->url = $url;
        $this->label = $label;
        $this->active = $active;
        $this->items = $items;
    }
}

$navItems = [
    new NavItem('/home', 'Home', $require === '/home'),
];

if (isset($_SESSION['userId'])) {
    $saveGamesDropdownItems = [];
    $saveGames = GameSaves::getSaveGamesByUser($_SESSION['userId']);
    if (!$saveGames) {
        $navItems[] = new NavItem('/game_saves', 'Save Games', $require === '/game_saves');
    } else {
        foreach ($saveGames as $saveGame) {
            $saveGamesDropdownItems[] = new NavItem('/game_save?id=' . $saveGame->id, htmlspecialchars($saveGame->title), $require === '/game_save' && $_GET['id'] == $saveGame->id);
        }
        $navItems[] = new DropdownNavItem('/game_saves', 'Save Games', $saveGamesDropdownItems, $require === '/game_saves');
    }
} else {
    $navItems[] = new NavItem('/game_saves', 'Save Games', $require === '/game_saves');
}

$navItems[] = new NavItem('/helpfulLinks', 'Helpful Links', $require === '/helpfulLinks');


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
                <?php foreach ($navItems as $navItem): ?>
                    <?php if (!empty($navItem->items)) : ?>
                        <!-- Dropdown logic -->
                        <li class="nav-item dropdown">
                            <?php if (!empty($navItem->url) && $navItem->url !== '#') : ?>
                                <!-- Split button: main link + dropdown -->
                                <div class="btn-group">
                                    <a href="<?= $navItem->url ?>"
                                       class="nav-link btn btn-link me-2 me-lg-0 <?= $navItem->active ? 'active' : '' ?>">
                                        <?= $navItem->label ?>
                                    </a>
                                    <button type="button"
                                            class="btn btn-link nav-link dropdown-toggle dropdown-toggle-split <?= $navItem->active ? 'active' : '' ?>"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <span class="visually-hidden">Toggle Dropdown</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($navItem->items as $item): ?>
                                            <li>
                                                <a class="dropdown-item <?= $item->active ? 'active' : '' ?>"
                                                   href="<?= $item->url ?>"><?= $item->label ?></a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else : ?>
                                <!-- Normal dropdown toggle -->
                                <a class="nav-link dropdown-toggle <?= $navItem->active ? 'active' : '' ?>"
                                   href="#" id="navbarDropdown" role="button"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <?= $navItem->label ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <?php foreach ($navItem->items as $item): ?>
                                        <li>
                                            <a class="dropdown-item <?= $item->active ? 'active' : '' ?>"
                                               href="<?= $item->url ?>"><?= $item->label ?></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php else: ?>
                        <!-- Regular nav item -->
                        <li class="nav-item me-lg-2 mt-lg-0 mt-2">
                            <a class="nav-link <?= $navItem->active ? 'active' : '' ?>"
                               href="<?= $navItem->url ?>"><?= $navItem->label ?></a>
                        </li>
                    <?php endif; ?>
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
                            <a class="dropdown-item <?= ($require === '/account') ? 'active' : ''; ?>" href="/account">Account</a>
                            <?php if (isset($_SESSION['userId']) && isset($_SESSION['admin']) && $_SESSION['admin']) : ?>
                                <a class="dropdown-item <?= ($require === '/admin') ? 'active' : ''; ?>" href="/admin">Admin
                                    Panel</a>                           <?php endif; ?>

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