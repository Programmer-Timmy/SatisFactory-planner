<?php
// routes.php

Router::get('game_save/{id}', function ($id) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/views/pages/game_save/index.php';
});

Router::get('game_save/{id}/dedicated_server', function ($id) {
    $_GET['id'] = $id;
    require_once __DIR__ . '/views/pages/game_save/dedicated_server.php';
});
