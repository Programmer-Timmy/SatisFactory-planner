<?php

// Usage
try {
    $client = new APIClient('192.168.2.11', 7777, 'YOUR_API_TOKEN_HERE');
    $response = $client->post('HealthCheck', ['ClientCustomData' => '']);

    // Assuming 'HealthCheck' has some specific response format
    $output = '';
    foreach ($response['data'] as $key => $value) {
        $output .= "<div class='row'><div class='col-6'>$key</div><div class='col-6'>$value</div></div>";
    }

    echo json_encode(['status' => 'success', 'data' => $output]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
