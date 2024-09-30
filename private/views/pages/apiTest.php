<?php

$api = new APIClient('play.timmygamer.nl', 7777);

$response = $api->post('PasswordlessLogin', ["MinimumPrivilegeLevel" => 'client']);

if ($response['response_code'] !== 200) {
    die('Failed to login');
}

var_dump($response);

$response = $api->post('PasswordLogin', ['MinimumPrivilegeLevel' => 'client', 'Password' => 'oDqdqRfKSo%CtBZW']);

if ($response['response_code'] !== 200) {
    die('Failed to login');
}

var_dump($response);

