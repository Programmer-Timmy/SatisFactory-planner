<?php
$savegameId = null;
global $require;

if (preg_match('#^/game_save/(\d+)(/.*)?$#', $_SERVER['REQUEST_URI'], $matches)) {
    $savegameId = (int)$matches[1];
}



$urlStart = '/game_save/' . $savegameId;

if ($savegameId) :
    $dedicatedServer = DedicatedServer::getBySaveGameId($savegameId);
    if ($dedicatedServer) :
        ?>

        <nav class="navbar navbar-expand-lg navbar-dark bg-dark sub-navbar mb-5 pt-0">
            <div class="container-fluid">
                <div class="collapse navbar-collapse" id="subNavbarSupportedContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link py-0 my-0 <?= $require === $urlStart ? 'active' : '' ?>" href="/game_save/<?php echo $savegameId; ?>">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-0 my-0 <?= $require === $urlStart . '/dedicated_server' ? 'active' : '' ?>" href="/game_save/<?php echo $savegameId; ?>/dedicated_server">Dedicated
                                Server</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php
    endif;
endif;
// End of file