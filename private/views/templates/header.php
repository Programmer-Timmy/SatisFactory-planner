<?php
// A function to generate a responsive title based on the URL
function getPageTitle() {
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <title><?php echo getPageTitle(); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <!-- ajax -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@5.0.4/css/bootstrap5-toggle.min.css" rel="stylesheet">
    <!-- font awasome -->
    <script src="https://kit.fontawesome.com/65416f0144.js" crossorigin="anonymous"></script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>
<body>


