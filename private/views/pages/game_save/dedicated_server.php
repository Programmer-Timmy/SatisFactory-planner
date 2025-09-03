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

    $sessions = $client->post('EnumerateSessions')['data']['sessions'] ?? [];

    var_dump($sessions[0]['saveHeaders']);;

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
            <!-- Left column: Single big card -->
            <div class="col-sm-12 col-md-4 col-lg-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <!-- Status -->
                        <div class="text-center mb-4">
                            <div class="server-status-item d-flex justify-content-center mb-3">
                                <div class="status-indicator <?= $healthy ? 'online' : 'offline' ?>"
                                     style="width: 130px; height: 130px;">
                                    <div class="status-blink <?= $healthy ? 'blink-online' : 'blink-offline' ?>"></div>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0"><?= $healthy ? 'Server Online' : 'Server Offline' ?></h4>
                        </div>

                        <hr>

                        <!-- Server Info -->
                        <h5 class="text-primary mb-3"><i class="fa-solid fa-server me-2"></i>Server Information</h5>
                        <ul class="list-unstyled mb-4">
                            <li><strong>IP Address:</strong> <?= htmlspecialchars($dedicatedServer->server_ip) ?></li>
                            <li><strong>Port:</strong> <?= htmlspecialchars($dedicatedServer->server_port) ?></li>
                            <li>
                                <strong>Health:</strong> <?= $healthy ? '<span class="text-success">Healthy <i class="fa-solid fa-check fa-lg"></i></span>' : '<span class="text-danger">Unhealthy <i class="fa-solid fa-xmark fa-lg"></i></span>' ?>
                            </li>
                            <li><strong>Last Checked:</strong>
                                <?php if ($response && isset($response['data']['timestamp'])): ?>
                                    <?= date('Y-m-d H:i:s', strtotime($response['data']['timestamp'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </li>
                        </ul>

                        <?php if ($serverState): ?>
                            <?php $state = $serverState['serverGameState']; ?>

                            <!-- Game Session Info -->
                            <h5 class="text-primary mb-3"><i class="fa-solid fa-gamepad me-2"></i>Game Session Info</h5>
                            <ul class="list-unstyled mb-4">
                                <li><strong>Session:</strong> <?= htmlspecialchars($state['activeSessionName']) ?></li>
                                <li><strong>Players:</strong> <?= $state['numConnectedPlayers'] ?>
                                    /<?= $state['playerLimit'] ?></li>
                                <li><strong>Tech Tier:</strong> <?= $state['techTier'] ?></li>
                                <li><strong>Phase:</strong> <?= cleanGamePhase($state['gamePhase']) ?></li>
                            </ul>

                            <!-- Game Status -->
                            <h5 class="text-primary mb-3"><i class="fa-solid fa-heart-pulse me-2"></i>Game Status</h5>
                            <ul class="list-unstyled mb-4">
                                <li>
                                    <strong>Running:</strong> <?= $state['isGameRunning'] ? '<i class="fa-solid fa-check text-success fa-lg"></i> Yes' : '<i class="fa-solid fa-xmark text-danger fa-lg"></i> No' ?>
                                </li>
                                <li>
                                    <strong>Paused:</strong> <?= $state['isGamePaused'] ? '<i class="fa-solid fa-pause text-danger fa-lg"></i> Yes' : '<i class="fa-solid fa-play text-info fa-lg"></i> No' ?>
                                </li>
                                <li><strong>Tick Rate:</strong> <?= number_format($state['averageTickRate'], 2) ?> TPS
                                </li>
                            </ul>

                            <!-- Misc Info -->
                            <h5 class="text-primary mb-3"><i class="fa-solid fa-info-circle me-2"></i>Misc</h5>
                            <ul class="list-unstyled mb-0">
                                <?php
                                $totalSeconds = $state['totalGameDuration'];

                                $totalHours = floor($totalSeconds / 3600);
                                $minutes = floor(($totalSeconds % 3600) / 60);
                                $seconds = $totalSeconds % 60;

                                $formattedDuration = sprintf('%d:%02d:%02d', $totalHours, $minutes, $seconds);
                                ?>
                                <li><strong>Total Duration:</strong> <?= $formattedDuration ?></li>
                                <li><strong>Auto-load
                                        Session:</strong> <?= htmlspecialchars($state['autoLoadSessionName']) ?></li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-8 col-lg-9">
                <!--                actions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="text-primary mb-3"><i class="fa-solid fa-rocket me-2"></i>Server Actions</h5>
                        <div class="d-flex flex-column flex-md-row gap-3">
                            <!--                      actions:       stop server, download save game, upload save game, update server settings, update advanged game settings -->
                            <button id="stopServerBtn"
                                    class="btn btn-danger flex-fill" <?= !$healthy ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-stop me-2"></i> Stop Server
                            </button>
                            <button id="downloadSaveBtn" class="btn btn-secondary flex-fill" data-bs-toggle="modal"
                                    data-bs-target="#downloadSaveModal" <?= !$healthy ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-download me-2"></i> Download Save Game
                            </button>
                            <button id="uploadSaveBtn" class="btn btn-secondary flex-fill">
                                <i class="fa-solid fa-upload me-2"></i> Upload Save Game
                            </button>
                            <button id="updateSettingsBtn" class="btn btn-primary flex-fill">
                                <i class="fa-solid fa-gears me-2"></i> Update Server Settings
                            </button>
                            <button id="updateGameSettingsBtn" class="btn btn-primary flex-fill">
                                <i class="fa-solid fa-gamepad me-2"></i> Update Game Settings
                            </button>
                        </div>
                        <small class="form-text text-muted mt-2">Note: Actions may take a few moments to
                            complete.</small>
                    </div>
                </div>
            </div>

            <!-- Right column (empty/future) -->
            <div class="col-sm-12 col-md-6 col-lg-7"></div>
        <?php else: ?>
            <div class="alert alert-warning">No dedicated server found for this save.</div>
        <?php endif; ?>
    </div>

    <!--    modal of downloading-->
    <div class="modal fade" id="downloadSaveModal" tabindex="-1" aria-labelledby="downloadSaveModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="downloadSaveModalLabel">Download Save Game</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="saveGameDownloadSelect" class="form-label">Select a session</label>
                    <select id="saveGameDownloadSelect" class="form-select">
                        <option disabled selected>Select a session</option>
                        <?php if ($sessions && count($sessions) > 0): ?>
                            <?php foreach ($sessions as $session): ?>
                                <option value="<?= htmlspecialchars($session['sessionName']) ?>">
                                    <?= htmlspecialchars($session['sessionName']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option disabled>No sessions available</option>
                        <?php endif; ?>
                    </select>
                    <div class="form-text text-muted mt-2">
                        Choose the session to view available save files for download.
                    </div>

                    <label for="saveFileDownloadSelect" class="form-label mt-3">Select a save file</label>
                    <select id="saveFileDownloadSelect" class="form-select">
                        <option disabled>Select a session first</option>
                    </select>
                    <div class="form-text text-muted mt-2">
                        Choose the save file to download from the selected session.
                        The saves are ordered from most recent to oldest.
                    </div>
                </div>
                <div class="modal-body d-none" id="downloadSaveModalLoading">
                    <div class="d-flex justify-content-center align-items-center">
                        <div class="spinner-border text-primary me-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Preparing download...</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" id="downloadSaveLink" class="btn btn-primary disabled"
                            aria-disabled="true">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <i class="fa-solid fa-download me-2"></i> Download Save
                    </button>
                </div>

                <script>
                    // Embed PHP data into JS
                    const sessions = <?= json_encode($sessions) ?>;

                    const sessionSelect = document.getElementById('saveGameDownloadSelect');
                    const saveSelect = document.getElementById('saveFileDownloadSelect');
                    const downloadButton = document.getElementById('downloadSaveLink');

                    function updateSaveOptions() {
                        const selectedSession = sessionSelect.value;
                        saveSelect.innerHTML = ''; // Clear existing options

                        const session = sessions.find(s => s.sessionName === selectedSession);

                        if (session && session.saveHeaders.length > 0) {
                            const defaultOption = document.createElement('option');
                            defaultOption.disabled = true;
                            defaultOption.selected = true;
                            defaultOption.textContent = 'Select a save file';
                            saveSelect.appendChild(defaultOption);
                            session.saveHeaders.forEach(save => {
                                const option = document.createElement('option');
                                option.value = save.saveName;
                                option.textContent = save.saveName;
                                saveSelect.appendChild(option);
                            });
                        } else {
                            const option = document.createElement('option');
                            option.disabled = true;
                            option.textContent = 'No saves available';
                            option.selected = true;
                            saveSelect.appendChild(option);
                        }
                    }

                    sessionSelect.addEventListener('change', updateSaveOptions);
                    saveSelect.addEventListener('change', () => {
                        const selectedSession = sessionSelect.value;
                        const selectedSave = saveSelect.value;

                        console.log(selectedSession, selectedSave);

                        if (selectedSession && selectedSave) {
                            downloadButton.classList.remove('disabled');
                            downloadButton.removeAttribute('aria-disabled');
                        } else {
                            downloadButton.classList.add('disabled');
                            downloadButton.setAttribute('aria-disabled', 'true');
                        }
                    });

                    downloadButton.addEventListener('click', () => {
                        showLoading(true);

                        $.ajax({
                            url: '/dedicatedServerAPI/downloadSave',
                            type: 'POST',
                            data: {
                                gameSaveId: <?= $saveGameId ?>,
                                sessionName: $(sessionSelect).val(),
                                saveName: $(saveSelect).val()
                            },
                            headers: {'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?>'},
                            success: function (response) {
                                //application/octet-stream
                                const blob = new Blob([response], {type: 'application/octet-stream'});
                                // add .sav extension if not present
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                a.download = $(saveSelect).val().endsWith('.sav') ? $(saveSelect).val() : $(saveSelect).val() + '.sav';
                                document.body.appendChild(a);
                                a.click();
                                a.remove();
                                window.URL.revokeObjectURL(url);
                                showLoading(false);
                            },
                            error: function (xhr, status, error) {
                                alert('Error downloading save: ' + (xhr.responseJSON?.error || error));
                                showLoading(false);
                            }
                        });
                    })

                    function resetModal() {
                        sessionSelect.selectedIndex = 0;
                        saveSelect.innerHTML = '<option disabled selected>Select a session first</option>';
                        downloadButton.classList.add('disabled');
                        downloadButton.setAttribute('aria-disabled', 'true');
                    }

                    function showLoading(show) {
                        const loadingDiv = document.getElementById('downloadSaveModalLoading');
                        const contentDiv = loadingDiv.previousElementSibling; // The modal body
                        const button = document.getElementById('downloadSaveLink');
                        if (show) {
                            loadingDiv.classList.remove('d-none');
                            contentDiv.classList.add('d-none');
                            button.classList.add('disabled');
                            button.setAttribute('aria-disabled', 'true');
                            button.querySelector('span').classList.remove('d-none');

                        } else {
                            loadingDiv.classList.add('d-none');
                            contentDiv.classList.remove('d-none');
                            button.querySelector('span').classList.add('d-none');
                            button.classList.remove('disabled');
                            button.setAttribute('aria-disabled', 'false');
                            button.querySelector('span').classList.add('d-none');
                        }
                    }

                    // Initialize options for the first session
                    if (sessions.length > 0) {
                        updateSaveOptions();
                    }
                </script>
            </div>
        </div>
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
        0% {
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