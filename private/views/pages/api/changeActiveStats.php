<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $requestData = json_decode(file_get_contents('php://input'), true);

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
            $html .= '<div class="overflow-auto" style="max-height: 40vh;">
                <table class="table table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th scope="col">Item</th>
                        <th scope="col">Amount</th>
                    </tr>
                    </thead>
                    <tbody id="output_table">';
            foreach ($outputs as $output) {
                $html .= '<tr>
                            <td>' . htmlspecialchars($output->item) . '</td>
                            <td>' . htmlspecialchars($output->ammount) . '</td>
                        </tr>';
            }
            $html .= '</tbody>
                </table>
            </div>';
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
