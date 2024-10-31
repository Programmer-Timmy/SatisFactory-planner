<?php
// A function to generate a responsive title based on the URL

function getPageTitle($skipCheck = false) {
    global $titles;
    $url = $_SERVER['REQUEST_URI'];

    $pageTitle = ucfirst($titles['default']);

    // Find the corresponding title based on URL
    foreach ($titles as $urlPattern => $title) {
        if (strpos($url, $urlPattern) !== false) {
            $pageTitle = $title;
            break;
        }
    }
    return $pageTitle;
}

$changelog = ['version' => '0.0.0'];

$changelogJson = json_decode(file_get_contents('changelog.json'), true);
if ($changelogJson) {
    $changelog = $changelogJson[0];
}

$theme = 'styles-light';
if (isset($_COOKIE['theme'])) {
    $theme = $_COOKIE['theme'] === 'dark' ? 'styles-dark' : 'styles-light';
}

function getDescription() {
    global $description;
    $url = $_SERVER['REQUEST_URI'];

    $pageDescription = $description['default'];

    // Find the corresponding title based on URL
    foreach ($description as $urlPattern => $title) {
        if (strpos($url, $urlPattern) !== false) {
            $pageDescription = $title;
            break;
        }
    }
    return $pageDescription;
}

function getKeywords() {
    global $keywords;
    $url = $_SERVER['REQUEST_URI'];


    $pageKeywords = $keywords['default'];

    // Find the corresponding title based on URL
    foreach ($keywords as $urlPattern => $title) {
        if (strpos($url, $urlPattern) !== false) {
            $pageKeywords = $title;
            break;
        }
    }
    return $pageKeywords;
}

$url = $_SERVER['REQUEST_URI'];
global $site;
global $allowedIPs;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="Satisfactory Planner">
    <?php if (!$site['maintenance']) : ?>

        <meta name="description" content="<?= getDescription() ?>">
        <meta name="keywords" content="<?= getKeywords() ?>">
        <meta name="theme-color" content="#343a40">
        <link rel="canonical" href="https://satisfactoryplanner.timmygamer.nl<?= $url ?>">
        <!-- no index -->
        <meta name="robots" content="noindex, nofollow">


        <!-- og tags -->
        <meta property="og:title" content="<?= getPageTitle() ?>">
        <meta property="og:description" content="<?= getDescription() ?>">
        <meta property="og:image" content="image/favicons/android-chrome-192x192.png">
        <meta property="og:url" content="https://satisfactoryplanner.timmygamer.nl<?= $url ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Satisfactory Planner">
    <meta property="og:locale" content="en_US">
    <meta property="og:image:width" content="192">
    <meta property="og:image:height" content="192">

    <!-- icon -->
    <link rel="apple-touch-icon" sizes="57x57" href="image/favicons/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="image/favicons/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="image/favicons/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="image/favicons/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="image/favicons/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="image/favicons/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="image/favicons/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="image/favicons/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="image/favicons/apple-touch-icon-180x180.png">
    <link rel="icon" type="image/png" href="image/favicons/favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="image/favicons/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="image/favicons/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/png" href="image/favicons/android-chrome-192x192.png" sizes="192x192">
    <link rel="manifest" href="image/favicons/manifest.json">
    <link rel="shortcut icon" href="image/favicons/favicon.ico">

    <!-- title -->
    <title><?php echo getPageTitle(true); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@5.0.4/css/bootstrap5-toggle.min.css" rel="stylesheet">
    <!--    make sure its loaded and eceutued bevore page load-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@5.0.4/js/bootstrap5-toggle.ecmas.min.js" defer></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/<?= $theme ?>.css?v=<?= $changelog['version'] ?>" id="theme">
    <!-- ajax -->
    <!-- font awasome -->
    <script src="https://kit.fontawesome.com/65416f0144.js" crossorigin="anonymous"></script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/qtip2/3.0.3/jquery.qtip.min.css"/>

</head>
<body>


