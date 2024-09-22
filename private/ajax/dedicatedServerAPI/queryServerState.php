<?php
function secondsToHMS($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;

    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

// Usage
try {
    $client = new APIClient('192.168.2.11', 7777, 'YOUR_API_TOKEN_HERE');
    $response = $client->post('QueryServerState');

    $output = '';
    foreach ($response['data']['serverGameState'] as $key => $value) {
        if ($key === 'totalGameDuration') {
            $value = secondsToHMS($value);
        }
        $output .= "<div class='row'><div class='col-6'>$key</div><div class='col-6'>$value</div></div>";
    }

    echo json_encode(['status' => 'success', 'data' => $output]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}