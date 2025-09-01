<?php
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /game_saves');
    exit();
}

$saveGameId = (int)htmlspecialchars($_GET['id']);

$dedicatedServer = DedicatedServer::getBySaveGameId($saveGameId);

try {
    $client = new APIClient($dedicatedServer->server_ip, $dedicatedServer->server_port, $dedicatedServer->server_token);
    $response = $client->post('HealthCheck', ['ClientCustomData' => '']);

    $healthy = $response['response_code'] === 200 && $response['data']['health'] === 'healthy';

    $queryState = $client->post('QueryServerState');

    $serverState = $queryState['data'] ?? null;
} catch (Exception $e) {
    $healthy = false;
    $serverState = null;
}

function cleanGamePhase($phaseString) {
    // Extract only the text after "GP_"
    if (preg_match('/GP_([^\.]+)/', $phaseString, $matches)) {
        $clean = $matches[1]; // "Project_Assembly_Phase_4"
        $clean = str_replace('_', ' ', $clean); // "Project Assembly Phase 4"
        return $clean;
    }
    return $phaseString; // Fallback if no match
}

?>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h1 class="mb-4">Dedicated Server Status</h1>
            <a href="/game_save?id=<?= $saveGameId ?>" class="btn btn-primary mb-4">
                <i class="fa-solid fa-arrow-left"></i> Back to Game Save
            </a>
        </div>
    </div>

    <div class="row">
        <?php if ($dedicatedServer): ?>
            <!-- Server Info Card -->
            <div class="col-sm-12 col-md-4 col-lg-3">
                <div class="card mb-4 shadow-sm h-100">
                    <div class="card-body text-center">
                        <!-- Status indicator -->
                        <div class="server-status-item d-flex justify-content-center mt-3 mb-4 pb-2">
                            <div class="status-indicator <?= $healthy ? 'online' : 'offline' ?>" style="width: 130px; height: 130px;">
                                <div class="status-blink <?= $healthy ? 'blink-online' : 'blink-offline' ?>"></div>
                            </div>
                        </div>

                        <h5 class="card-title mb-4">Server Information</h5>

                        <div class="d-flex flex-column gap-2 text-start">
                            <div class="p-2  rounded d-flex align-items-center">
                                <i class="fa-solid fa-server fa-lg me-3 text-primary"></i>
                                <div>
                                    <strong class="text-primary">IP Address:</strong><br>
                                    <?= htmlspecialchars($dedicatedServer->server_ip) ?>
                                </div>
                            </div>

                            <div class="p-2  rounded d-flex align-items-center">
                                <i class="fa-solid fa-plug fa-lg me-3 text-primary"></i>
                                <div>
                                    <strong class="text-primary">Port:</strong><br>
                                    <?= htmlspecialchars($dedicatedServer->server_port) ?>
                                </div>
                            </div>

                            <div class="p-2  rounded d-flex align-items-center">
                                <i class="fa-solid fa-heart-pulse fa-lg me-3 <?= $healthy ? 'text-success' : 'text-danger' ?>"></i>
                                <div>
                                    <strong class="text-primary">Health Status:</strong><br>
                                    <?= $healthy ? '<span class="text-success">Healthy ✅</span>' : '<span class="text-danger">Unhealthy ❌</span>' ?>
                                </div>
                            </div>

                            <div class="p-2  rounded d-flex align-items-center">
                                <i class="fa-solid fa-clock fa-lg me-3 text-primary"></i>
                                <div>
                                    <strong class="text-primary">Last Checked:</strong><br>
                                    <?php if ($response && isset($response['data']['timestamp'])): ?>
                                        <?= date('Y-m-d H:i:s', strtotime($response['data']['timestamp'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-sm-12 col-md-8 col-lg-9">
                <?php if ($serverState): ?>
                    <?php $state = $serverState['serverGameState']; ?>

                    <!-- Server State Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Game Session Info</h5>
                            <div class="row text-center">
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded bg-light">
                                        <h6 class="text-muted">Session</h6>
                                        <h5><?= htmlspecialchars($state['activeSessionName']) ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded bg-light">
                                        <h6 class="text-muted">Players</h6>
                                        <h5><?= $state['numConnectedPlayers'] ?>/<?= $state['playerLimit'] ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded bg-light">
                                        <h6 class="text-muted">Tech Tier</h6>
                                        <h5><?= $state['techTier'] ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 border rounded bg-light">
                                        <h6 class="text-muted">Phase</h6>
                                        <h5><?= cleanGamePhase($state['gamePhase']) ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Game Status Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Game Status</h5>
                            <div class="row text-center">
                                <div class="col-md-4 mb-3">
                                    <div class="p-3 border rounded bg-light">
                                        <h6 class="text-muted">Running</h6>
                                        <h5><?= $state['isGameRunning'] ? '✅ Yes' : '❌ No' ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="p-3 border rounded bg-light">
                                        <h6 class="text-muted">Paused</h6>
                                        <h5><?= $state['isGamePaused'] ? '⏸️ Paused' : '▶️ Active' ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="p-3 border rounded bg-light">
                                        <h6 class="text-muted">Tick Rate</h6>
                                        <h5><?= number_format($state['averageTickRate'], 2) ?> TPS</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Misc Info -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Misc Information</h5>
                            <p><strong>Total Game Duration:</strong> <?= gmdate("H:i:s", $state['totalGameDuration']) ?>
                                (<?= $state['totalGameDuration'] ?> seconds)</p>
                            <p><strong>Auto-load
                                    Session:</strong> <?= htmlspecialchars($state['autoLoadSessionName']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">No dedicated server found for this save.</div>
        <?php endif; ?>
    </div>

</div>

<style>
    .status-indicator {
        position: relative;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        overflow: visible; /* important to allow the glow outside */
        z-index: 10;
    }

    .status-indicator.online {
        background-color: #3BD671; /* Green for online */
    }

    .status-indicator.offline {
        background-color: rgb(223, 72, 74)
    }

    /* The blinking glow element */
    .status-blink {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        pointer-events: none;
        opacity: 0;
        animation-duration: 3s;
        animation-iteration-count: infinite;
        animation-fill-mode: forwards;
        animation-timing-function: linear;
        z-index: 5;
    }

    .blink-online {
        background-color: #3BD671;
        animation-name: glowFade;
    }

    .blink-offline {
        background-color: rgb(223, 72, 74);
        animation-name: glowFade
    }

    @keyframes glowFade {
        0%{
            opacity: 1;

        }
        20% {
            scale: 1.25;
            opacity: 0.5;
        }
        40% {
            opacity: 0;
            scale: 1.5;
        }
        100% {
            opacity: 0;
            scale: 1;
        }
    }
</style>