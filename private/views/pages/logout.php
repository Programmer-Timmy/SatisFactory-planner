<?php
$_SESSION['userId'] = null;
$_SESSION['redirect'] = 'home';
$_SESSION['lastVisitedSaveGame'] = null;
$_SESSION['admin'] = null;
header('Location:/login?logout=true');