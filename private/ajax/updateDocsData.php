<?php
header('Content-Type: application/json');

class InvalidJsonDataException extends RuntimeException {
    private string $details;
    private ?int $jsonLine;
    private ?int $jsonColumn;
    private ?string $excerpt;

    public function __construct(string $details, ?int $line = null, ?int $column = null, ?string $excerpt = null) {
        parent::__construct('Invalid JSON file. The uploaded file could not be parsed as JSON.');
        $this->details = $details;
        $this->jsonLine = $line;
        $this->jsonColumn = $column;
        $this->excerpt = $excerpt;
    }

    public function getDetails(): string {
        return $this->details;
    }

    public function getJsonLineNumber(): ?int {
        return $this->jsonLine;
    }

    public function getJsonColumnNumber(): ?int {
        return $this->jsonColumn;
    }

    public function getExcerpt(): ?string {
        return $this->excerpt;
    }
}

if (!isset($_SESSION['admin']) || !$_SESSION['admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission to access this page']);
    exit(1);
}

if (empty($_POST) && empty($_FILES)) {
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $postMaxSize = parseIniSize(ini_get('post_max_size'));

    http_response_code(400);

    if ($contentLength > 0 && $postMaxSize > 0 && $contentLength > $postMaxSize) {
        echo json_encode([
            'error' => 'The uploaded request is larger than the server post limit.',
            'code' => UPLOAD_ERR_INI_SIZE,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ]);
        exit(1);
    }

    echo json_encode([
        'error' => 'No data sent',
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
    ]);
    exit(1);
}

function parseIniSize(string $value): int {
    $value = trim($value);
    $unit = strtolower(substr($value, -1));
    $size = (float) $value;

    return match ($unit) {
        'g' => (int) ($size * 1024 * 1024 * 1024),
        'm' => (int) ($size * 1024 * 1024),
        'k' => (int) ($size * 1024),
        default => (int) $size,
    };
}

try {
    $action = $_POST['action'] ?? 'submit';
    $jsonData = loadJsonPayload();
    $itemsNativeClasses = normalizeMultiSelectInput($_POST['ItemsNativeClasses'] ?? null, 'ItemsNativeClasses');
    $buildingNativeClasses = normalizeMultiSelectInput($_POST['BuildingNativeClasses'] ?? null, 'BuildingNativeClasses');
    $data = decodeJsonPayload($jsonData);

    $docsData = new DocsData($data, $itemsNativeClasses, $buildingNativeClasses);

    if ($action === 'preview') {
        $preview = $docsData->getPreviewChanges();
        $html = generatePreviewHtml($preview);

        http_response_code(200);
        echo json_encode([
            'html' => $html,
            'mode' => 'preview',
        ]);
        exit(0);
    }

    if ($action !== 'submit') {
        throw new RuntimeException('Unknown action');
    }

    $docsData->insertItems();
    $docsData->insertBuildings();
    $docsData->insertRecipes();

    SiteSettings::incrementDataVersion();

    $addedHtml = generateSectionHtml('Added', 'info', $docsData->added_stuff);
    $deletedHtml = generateSectionHtml('Deleted', 'danger', $docsData->deleted_stuff);
    $updatedHtml = generateSectionHtml('Updated', 'warning', $docsData->updated_stuff);

    http_response_code(200);
    echo json_encode([
        'html' => $addedHtml . $deletedHtml . $updatedHtml,
        'mode' => 'submit',
    ]);
    exit(0);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit(1);
} catch (InvalidJsonDataException $e) {
    http_response_code(400);
    echo encodeJsonResponse([
        'error' => $e->getMessage(),
        'message' => buildInvalidJsonMessage($e),
        'details' => $e->getDetails(),
        'line' => $e->getJsonLineNumber(),
        'column' => $e->getJsonColumnNumber(),
        'excerpt' => $e->getExcerpt(),
    ]);
    exit(1);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit(1);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit(1);
}

function loadJsonPayload(): string {
    if (isset($_POST['chunkUpload']) && $_POST['chunkUpload'] === '1') {
        return loadChunkedJsonPayload();
    }

    $uploadedFile = getUploadedJsonFile();

    if ($uploadedFile !== null) {
        validateUploadedJsonFile($uploadedFile);
        $jsonData = readUploadedJsonFile($uploadedFile);

        return normalizeJsonEncoding($jsonData);
    }

    if (!isset($_POST['jsonData'])) {
        throw new RuntimeException('No JSON file uploaded');
    }

    return normalizeJsonEncoding((string) $_POST['jsonData']);
}

function getUploadedJsonFile(): ?array {
    if (isset($_FILES['jsonFile'])) {
        return $_FILES['jsonFile'];
    }

    if (isset($_FILES['docsFile'])) {
        return $_FILES['docsFile'];
    }

    return null;
}

function validateUploadedJsonFile(array $uploadedFile): void {
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        throwUploadError($uploadedFile['error']);
    }

    if (!isset($uploadedFile['tmp_name']) || $uploadedFile['tmp_name'] === '') {
        throw new RuntimeException('No JSON file uploaded');
    }
}

function throwUploadError(int $errorCode): void {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file is larger than the server upload limit.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file is larger than the form limit.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write uploaded file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
    ];

    http_response_code(400);
    echo json_encode([
        'error' => $errorMessages[$errorCode] ?? 'Unknown upload error',
        'code' => $errorCode,
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
    ]);
    exit(1);
}

function readUploadedJsonFile(array $uploadedFile): string {
    $jsonData = file_get_contents($uploadedFile['tmp_name']);

    if ($jsonData === false || $jsonData === '') {
        throw new RuntimeException('Uploaded file is empty');
    }

    if (isset($_POST['compressed']) && $_POST['compressed'] === 'gzip') {
        $jsonData = gzdecode($jsonData);

        if ($jsonData === false) {
            throw new RuntimeException('Invalid gzip file');
        }
    }

    return $jsonData;
}

function loadChunkedJsonPayload(): string {
    $uploadedFile = getUploadedJsonFile();

    if ($uploadedFile === null) {
        throw new RuntimeException('No JSON file uploaded');
    }

    validateUploadedJsonFile($uploadedFile);

    $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_POST['uploadId'] ?? ''));
    $index = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
    $totalChunks = filter_var($_POST['totalChunks'] ?? null, FILTER_VALIDATE_INT);

    if ($uploadId === '' || $index === false || $totalChunks === false || $index < 0 || $totalChunks < 1 || $index >= $totalChunks) {
        throw new RuntimeException('Invalid chunk upload metadata');
    }

    $chunkDir = getChunkUploadDir($uploadId);
    $chunkPath = $chunkDir . DIRECTORY_SEPARATOR . $index . '.part';

    if (!move_uploaded_file($uploadedFile['tmp_name'], $chunkPath)) {
        throw new RuntimeException('Failed to store upload chunk');
    }

    for ($i = 0; $i < $totalChunks; $i++) {
        if (!file_exists($chunkDir . DIRECTORY_SEPARATOR . $i . '.part')) {
            http_response_code(200);
            echo json_encode([
                'mode' => 'chunk',
                'received' => $index,
                'complete' => false,
            ]);
            exit(0);
        }
    }

    $jsonData = '';
    for ($i = 0; $i < $totalChunks; $i++) {
        $chunkData = file_get_contents($chunkDir . DIRECTORY_SEPARATOR . $i . '.part');

        if ($chunkData === false) {
            cleanupChunkUpload($chunkDir);
            throw new RuntimeException('Failed to read upload chunk');
        }

        $jsonData .= $chunkData;
    }

    cleanupChunkUpload($chunkDir);

    if (isset($_POST['compressed']) && $_POST['compressed'] === 'gzip') {
        $jsonData = gzdecode($jsonData);

        if ($jsonData === false) {
            throw new RuntimeException('Invalid gzip file');
        }
    }

    return normalizeJsonEncoding($jsonData);
}

function getChunkUploadDir(string $uploadId): string {
    $baseDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'satisfactory_docs_uploads';
    $chunkDir = $baseDir . DIRECTORY_SEPARATOR . $uploadId;

    if (!is_dir($chunkDir) && !mkdir($chunkDir, 0777, true) && !is_dir($chunkDir)) {
        throw new RuntimeException('Failed to create temporary upload folder');
    }

    return $chunkDir;
}

function cleanupChunkUpload(string $chunkDir): void {
    if (!is_dir($chunkDir)) {
        return;
    }

    foreach (glob($chunkDir . DIRECTORY_SEPARATOR . '*.part') ?: [] as $chunkPath) {
        unlink($chunkPath);
    }

    rmdir($chunkDir);
}

function normalizeJsonEncoding(string $jsonData): string {
    if (str_starts_with($jsonData, "\xEF\xBB\xBF")) {
        return substr($jsonData, 3);
    }

    if (str_starts_with($jsonData, "\xFF\xFE")) {
        return convertJsonEncoding(substr($jsonData, 2), 'UTF-16LE');
    }

    if (str_starts_with($jsonData, "\xFE\xFF")) {
        return convertJsonEncoding(substr($jsonData, 2), 'UTF-16BE');
    }

    $utf16Encoding = detectUtf16Encoding($jsonData);
    if ($utf16Encoding !== null) {
        return convertJsonEncoding($jsonData, $utf16Encoding);
    }

    if (!mb_check_encoding($jsonData, 'UTF-8')) {
        throw new InvalidJsonDataException(
            'Unexpected file encoding. The Docs file must be valid UTF-8 or UTF-16 text.',
            null,
            null,
            ''
        );
    }

    return $jsonData;
}

function convertJsonEncoding(string $jsonData, string $fromEncoding): string {
    $converted = mb_convert_encoding($jsonData, 'UTF-8', $fromEncoding);

    if ($converted === false || $converted === '') {
        throw new InvalidJsonDataException(
            'Failed to convert the Docs file from ' . $fromEncoding . ' to UTF-8.',
            null,
            null,
            ''
        );
    }

    return $converted;
}

function detectUtf16Encoding(string $data): ?string {
    $sample = substr($data, 0, 200);
    $length = strlen($sample);

    if ($length < 4) {
        return null;
    }

    $evenNulls = 0;
    $oddNulls = 0;

    for ($i = 0; $i < $length; $i++) {
        if ($sample[$i] !== "\x00") {
            continue;
        }

        if ($i % 2 === 0) {
            $evenNulls++;
        } else {
            $oddNulls++;
        }
    }

    if ($oddNulls > 5 && $oddNulls > $evenNulls * 2) {
        return 'UTF-16LE';
    }

    if ($evenNulls > 5 && $evenNulls > $oddNulls * 2) {
        return 'UTF-16BE';
    }

    return null;
}

function decodeJsonPayload(string $jsonData): array {
    $data = json_decode($jsonData, true);

    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
        throw createInvalidJsonException($jsonData);
    }

    return $data;
}

function createInvalidJsonException(string $jsonData): InvalidJsonDataException {
    $details = json_last_error_msg();
    $position = findJsonErrorPosition($jsonData);

    return new InvalidJsonDataException(
        $details,
        $position['line'],
        $position['column'],
        $position['excerpt']
    );
}

function encodeJsonResponse(array $payload): string {
    $json = json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);

    if ($json !== false) {
        return $json;
    }

    return '{"error":"Failed to encode JSON response"}';
}

function findJsonErrorPosition(string $jsonData): array {
    $errorOffset = null;

    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $jsonData, $matches, PREG_OFFSET_CAPTURE)) {
        $errorOffset = $matches[0][1];
    } else {
        $trimmed = trim($jsonData);
        $firstCharacter = $trimmed[0] ?? '';
        $lastCharacter = $trimmed !== '' ? $trimmed[strlen($trimmed) - 1] : '';

        if ($firstCharacter !== '{' && $firstCharacter !== '[') {
            $errorOffset = strspn($jsonData, " \t\r\n");
        } elseif (
            ($firstCharacter === '{' && $lastCharacter !== '}')
            || ($firstCharacter === '[' && $lastCharacter !== ']')
        ) {
            $errorOffset = max(0, strlen($jsonData) - 1);
        }
    }

    if ($errorOffset === null) {
        return [
            'line' => null,
            'column' => null,
            'excerpt' => trimForJsonErrorExcerpt(substr($jsonData, 0, 160), 1),
        ];
    }

    $beforeError = substr($jsonData, 0, $errorOffset);
    $line = substr_count($beforeError, "\n") + 1;
    $lastNewline = strrpos($beforeError, "\n");
    $column = $lastNewline === false ? $errorOffset + 1 : $errorOffset - $lastNewline;
    $lineText = getJsonLine($jsonData, $line);

    return [
        'line' => $line,
        'column' => $column,
        'excerpt' => trimForJsonErrorExcerpt($lineText, $column),
    ];
}

function getJsonLine(string $jsonData, int $lineNumber): string {
    $lines = preg_split('/\R/', $jsonData);

    return $lines[$lineNumber - 1] ?? '';
}

function trimForJsonErrorExcerpt(string $lineText, int $column): string {
    $start = max(0, $column - 80);
    $excerpt = substr($lineText, $start, 160);

    if ($start > 0) {
        $excerpt = '...' . $excerpt;
    }

    if ($start + 160 < strlen($lineText)) {
        $excerpt .= '...';
    }

    return $excerpt;
}

function buildInvalidJsonMessage(InvalidJsonDataException $exception): string {
    $message = 'The selected Docs file is not valid JSON.';

    if ($exception->getJsonLineNumber() !== null && $exception->getJsonColumnNumber() !== null) {
        $message .= ' Check line ' . $exception->getJsonLineNumber() . ', column ' . $exception->getJsonColumnNumber() . '.';
    }

    $message .= ' Parser message: ' . $exception->getDetails() . '.';

    return $message;
}

function normalizeMultiSelectInput(mixed $input, string $fieldName): array {
    if ($input === null) {
        throw new InvalidArgumentException("No {$fieldName} sent");
    }

    if (is_array($input)) {
        return array_values(array_filter($input, static fn($value) => $value !== '' && $value !== null));
    }

    if ($input === '') {
        return [];
    }

    return [$input];
}

function generatePreviewHtml(array $preview): string {
    $summary = countChanges($preview);
    $html = "<div class='alert alert-info mb-4'>";
    $html .= "<strong>Preview ready.</strong> ";
    $html .= htmlspecialchars($summary) . ". Review the changes below before applying them.";
    $html .= "</div>";

    $html .= generateSectionHtml('Added', 'info', $preview['added_stuff']);
    $html .= generateSectionHtml('Deleted', 'danger', $preview['deleted_stuff']);
    $html .= generateSectionHtml('Updated', 'warning', $preview['updated_stuff']);

    return $html;
}

function countChanges(array $preview): string {
    $counts = [
        'added' => count($preview['added_stuff']['items']) + count($preview['added_stuff']['buildings']) + count($preview['added_stuff']['recipes']),
        'deleted' => count($preview['deleted_stuff']['items']) + count($preview['deleted_stuff']['buildings']) + count($preview['deleted_stuff']['recipes']),
        'updated' => count($preview['updated_stuff']['items']) + count($preview['updated_stuff']['buildings']) + count($preview['updated_stuff']['recipes']),
    ];

    return sprintf('%d added, %d updated, %d deleted', $counts['added'], $counts['updated'], $counts['deleted']);
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
            if (!empty($item['changes'])) {
                $sectionHtml .= "<div class='card-body p-0'>";
                $sectionHtml .= "<table class='table table-sm table-striped mb-0'>";
                $sectionHtml .= "<thead><tr><th>Field</th><th>Current</th><th>Uploaded</th></tr></thead><tbody>";

                foreach ($item['changes'] as $change) {
                    $sectionHtml .= "<tr>";
                    $sectionHtml .= "<td>" . htmlspecialchars((string) $change['field']) . "</td>";
                    $sectionHtml .= "<td>" . htmlspecialchars((string) $change['old']) . "</td>";
                    $sectionHtml .= "<td>" . htmlspecialchars((string) $change['new']) . "</td>";
                    $sectionHtml .= "</tr>";
                }

                $sectionHtml .= "</tbody></table>";
                $sectionHtml .= "</div>";
            }
            $sectionHtml .= "</div></div>";
        }

        $sectionHtml .= "</div></div></div></div>";
    }

    $sectionHtml .= "</div>";
    return $sectionHtml;
}
