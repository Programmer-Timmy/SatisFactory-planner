<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $requestData = json_decode(file_get_contents('php://input'), true);

        if (!isset($requestData['gameSaveId'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No game save id provided']);
            exit();
        }

        $gameSaveId = $requestData['gameSaveId'];

        if (!GameSaves::checkAccess($gameSaveId, $_SESSION['userId'], Role::FACTORY_WORKER, negate: true)) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to edit this production line']);
            exit();
        }

        // Extracting data from JSON request
        $id = $requestData['id'];
        $active = $requestData['active'];

        // Call the function to change the active status
        ProductionLines::changeActiveStats($id, $active);

        // Fetch updated outputs after status change
        $outputs = Outputs::getAllOutputs($requestData['gameSaveId']);
        $html = '';
        if (empty($outputs)) {
            $html .= '<h4 class="text-center mt-3">No Outputs Found</h4>';
        } else {
            $html .= '<div class="accordion" id="productionLinesAccordion">';
            foreach ($outputs as $lineTitle => $lineOutputs) :
                $lineTitle = htmlspecialchars($lineTitle);
                $lineId = preg_replace('/\s+/', '_', $lineTitle);
                $html .= '
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse-' . $lineId . '"
                                            aria-expanded="false"
                                            aria-controls="collapse-' . $lineId . '"> ' .
                    htmlspecialchars($lineTitle) . '
                                    </button>
                                </h2>
                                <div id="collapse-' . $lineId . '"
                                     class="accordion-collapse collapse"
                                     data-bs-parent="#productionLinesAccordion">
                                    <div class="accordion-body p-0"> ';
                if (empty($lineOutputs)) {
                    $html .= '<p>No Outputs for this line.</p>';
                } else {
                    $html .= '<table class="table table-striped m-0">
                                                <thead class="table-dark">
                                                <tr>
                                                    <th scope="col">Item</th>
                                                    <th scope="col">Amount</th>
                                                </tr>
                                                </thead>
                                                <tbody>';
                    foreach ($lineOutputs as $output) {
                        $html .= '<tr>
                                                        <td>' . htmlspecialchars($output->item) . '</td>
                                                        <td>' . htmlspecialchars($output->ammount) . '</td>
                                                    </tr>';
                    }
                    $html .= '</tbody>
                                            </table>';
                }
                $html .= '
                                    </div>
                                </div>
                            </div>';
            endforeach;
            $html .= '</div>';

        }

        // Prepare success response with HTML content
        $response = [
            'success' => true,
            'message' => "Active status changed for ID: $id to $active",
            'html' => $html // Include HTML for the frontend to use
        ];

        // Set response headers
        header('Content-Type: application/json');
        echo json_encode($response);

    } catch (Exception $e) {
        // Prepare error response
        $response = [
            'success' => false,
            'error' => $e->getMessage()
        ];

        // Set response headers
        header('Content-Type: application/json');
        echo json_encode($response);
    }
} else {
    // Handle invalid request method
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method']);
}
