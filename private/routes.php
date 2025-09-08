<?php
// routes.php

Router::get('game_save/{id}', function ($id) {
    $_GET['id'] = $id;
    $_GET['game_save_id'] = $id;
    require_once __DIR__ . '/views/pages/game_save/index.php';
});

Router::get('game_save/{id}/dedicated_server', function ($id) {
    $_GET['id'] = $id;
    $_GET['game_save_id'] = $id;
    require_once __DIR__ . '/views/pages/game_save/dedicated_server.php';
});

Router::get('game_save/{id}/production_line/{lineId}', function ($id, $lineId) {
    $_GET['game_save_id'] = $id;
    $_GET['id'] = $lineId;
    require_once __DIR__ . '/views/pages/game_save/production_line.php';
});
