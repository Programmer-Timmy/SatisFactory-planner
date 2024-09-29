<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Test Page</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center">API Test Page</h1>

    <button id="healthCheckBtn" class="btn btn-primary mb-3">Health Check</button>
    <button id="queryServerStateBtn" class="btn btn-secondary mb-3">Query Server State</button>

    <div id="apiResponse" class="mt-3">
        <!-- API response will be displayed here -->
    </div>
</div>

<script>
    $(document).ready(function () {
        // HealthCheck API Call
        $('#healthCheckBtn').click(function () {
            $.ajax({
                url: 'dedicatedServerAPI/healthCheck', // Replace with your actual PHP file path
                type: 'POST',
                data: {action: 'HealthCheck'},
                success: function (response) {
                    displayResponse(response);
                },
                error: function (xhr, status, error) {
                    $('#apiResponse').html(`<div class="alert alert-danger">Error: ${error}</div>`);
                }
            });
        });

        // QueryServerState API Call
        $('#queryServerStateBtn').click(function () {
            $.ajax({
                url: 'dedicatedServerAPI/queryServerState', // Replace with your actual PHP file path
                type: 'POST',
                data: {action: 'QueryServerState'},
                success: function (response) {
                    displayResponse(response);
                },
                error: function (xhr, status, error) {
                    $('#apiResponse').html(`<div class="alert alert-danger">Error: ${error}</div>`);
                }
            });
        });

        // Function to display API response
        function displayResponse(response) {
            try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    $('#apiResponse').html(`<div class="alert alert-success">${data.data}</div>`);
                } else {
                    $('#apiResponse').html(`<div class="alert alert-danger">${data.message}</div>`);
                }
            } catch (e) {
                $('#apiResponse').html(`<div class="alert alert-danger">Invalid response format</div>`);
            }
        }
    });
</script>

</body>
</html>

<?php
function generateRandomToken($length = 64)
{
    return bin2hex(random_bytes($length / 2));
}

// Example usage
$token = generateRandomToken();
echo $token; // Outputs a secure random token
