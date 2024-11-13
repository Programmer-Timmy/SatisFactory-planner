<?php
header('Content-Type: application/json');

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission to access this page']);
    exit(1);
}

if (!$_POST) {
    http_response_code(400);
    echo json_encode(['error' => 'No data sent']);
    exit(1);
}

if (!isset($_POST['jsonData'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No jsonData sent']);
    exit(1);
}

$jsonData = $_POST['jsonData'];

if (!isset($_POST['ItemsNativeClasses'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No ItemsNativeClasses sent']);
    exit(1);
}

if (!isset($_POST['BuildingNativeClasses'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No BuildingNativeClasses sent']);
    exit(1);
}

$ItemsNativeClasses = $_POST['ItemsNativeClasses'];
$BuildingNativeClasses = $_POST['BuildingNativeClasses'];

try {
    $data = json_decode($jsonData, true);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit(1);
}

try {
    $docsData = new DocsData($data, $ItemsNativeClasses, $BuildingNativeClasses);

    $docsData->insertItems();
    $docsData->insertBuildings();
    $docsData->insertRecipes();

    SiteSettings::incrementDataVersion();

    $added_stuff = $docsData->added_stuff;
    $deleted_stuff = $docsData->deleted_stuff;
    $updated_stuff = $docsData->updated_stuff;

    $addedHtml = generateSectionHtml('Added', 'info', $added_stuff);
    $deletedHtml = generateSectionHtml('Deleted', 'danger', $deleted_stuff);
    $updatedHtml = generateSectionHtml('Updated', 'warning', $updated_stuff);

    $html = $addedHtml . $deletedHtml . $updatedHtml;

    http_response_code(200);
    echo json_encode(['html' => $html]);
    exit(0);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit(1);
}

function generateSectionHtml($title, $color, $stuff) {
    if (empty($stuff['items']) && empty($stuff['buildings']) && empty($stuff['recipes'])) {
        return '';
    }

    $sectionHtml = "<div class='card mb-4'>";
    $sectionHtml .= "<div class='card-header bg-$color text-black '>$title</div>";

    foreach ($stuff as $typeName => $type) {
        if (empty($type)) continue;

        $sectionHtml .= "<div class='card-body row'>";
        $sectionHtml .= "<div class='col-lg-12'>";
        $sectionHtml .= "<div class='card mb-3'>";
        $sectionHtml .= "<div class='card-header'>" . htmlspecialchars($typeName) . "</div>";
        $sectionHtml .= "<div class='card-body row'>";

        foreach ($type as $item) {
            $sectionHtml .= "<div class='col-lg-4'>";
            $sectionHtml .= "<div class='card mb-3'>";
            $sectionHtml .= "<div class='card-header'>" . htmlspecialchars($item['name']) . "</div>";
            $sectionHtml .= "</div></div>";
        }

        $sectionHtml .= "</div></div></div></div>";
    }

    $sectionHtml .= "</div>";
    return $sectionHtml;
}

