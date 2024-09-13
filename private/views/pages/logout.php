<?php
$_SESSION['userId'] = null;
$_SESSION['redirect'] = 'home';
header('Location:/login?logout=true');