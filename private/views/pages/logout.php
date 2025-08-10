<?php
unset($_SESSION['userId']);
unset($_SESSION['admin']);
unset($_SESSION['lastVisitedSaveGame']);
unset($_SESSION['csrf_token']);

$_SESSION['redirect'] = 'game_saves';

header('Location:/login?logout=true');