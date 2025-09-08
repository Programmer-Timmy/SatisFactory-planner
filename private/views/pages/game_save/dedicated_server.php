<?php
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /game_saves');
    exit();
}

$saveGameId = (int)htmlspecialchars($_GET['id']);

$dedicatedServer = DedicatedServer::getBySaveGameId($saveGameId);

try {
    $client = new APIClient($dedicatedServer->server_ip, $dedicatedServer->server_port, $dedicatedServer->server_token);
    $response = $client->post('HealthCheck', ['ClientCustomData' => ''], timeout: 3);

    $healthy = $response['response_code'] === 200 && $response['data']['health'] === 'healthy';

    $queryState = $client->post('QueryServerState');

    $sessions = $client->post('EnumerateSessions')['data']['sessions'] ?? [];

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
            <div class="col-sm-12 col-md-4 col-xl-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <!-- Status -->
                        <div class="text-center mt-4 mb-2">
                            <div class="server-status-item d-flex justify-content-center mb-4">
                                <div class="status-indicator <?= $healthy ? 'online' : 'offline' ?>"
                                     style="width: 130px; height: 130px;">
                                    <div class="status-blink <?= $healthy ? 'blink-online' : 'blink-offline' ?>"></div>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-0 mt-3"><?= $healthy ? 'Server Online' : 'Server Offline' ?></h4>
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
                                <?= date('H:i:s') ?>
                            </li>
                        </ul>

                        <?php $state = $serverState['serverGameState'] ?? null; ?>

                        <!-- Game Session Info -->
                        <h5 class="text-primary mb-3"><i class="fa-solid fa-gamepad me-2"></i>Game Session Info</h5>
                        <ul class="list-unstyled mb-4">
                            <li><strong>Session:</strong> <?= htmlspecialchars($state['activeSessionName'] ?? 'N/A') ?>
                            </li>
                            <li><strong>Players:</strong>
                                <?= isset($state['numConnectedPlayers']) ? $state['numConnectedPlayers'] : 'N/A' ?> /
                                <?= isset($state['playerLimit']) ? $state['playerLimit'] : 'N/A' ?>
                            </li>
                            <li><strong>Tech Tier:</strong> <?= $state['techTier'] ?? 'N/A' ?></li>
                            <li><strong>Phase:</strong> <?= cleanGamePhase($state['gamePhase'] ?? 'N/A') ?></li>
                        </ul>

                        <!-- Game Status -->
                        <h5 class="text-primary mb-3"><i class="fa-solid fa-heart-pulse me-2"></i>Game Status</h5>
                        <ul class="list-unstyled mb-4">
                            <li>
                                <strong>Running:</strong>
                                <?= isset($state['isGameRunning'])
                                    ? ($state['isGameRunning']
                                        ? '<i class="fa-solid fa-check text-success fa-lg"></i> Yes'
                                        : '<i class="fa-solid fa-xmark text-danger fa-lg"></i> No')
                                    : 'N/A' ?>
                            </li>
                            <li>
                                <strong>Paused:</strong>
                                <?= isset($state['isGamePaused'])
                                    ? ($state['isGamePaused']
                                        ? '<i class="fa-solid fa-pause text-danger fa-lg"></i> Yes'
                                        : '<i class="fa-solid fa-play text-info fa-lg"></i> No')
                                    : 'N/A' ?>
                            </li>
                            <li>
                                <strong>Tick Rate:</strong>
                                <?= isset($state['averageTickRate'])
                                    ? number_format($state['averageTickRate'], 2) . ' TPS'
                                    : 'N/A' ?>
                            </li>
                        </ul>

                        <!-- Misc Info -->
                        <h5 class="text-primary mb-3"><i class="fa-solid fa-info-circle me-2"></i>Misc</h5>
                        <ul class="list-unstyled mb-0">
                            <?php
                            if (isset($state['totalGameDuration'])) {
                                $totalSeconds = $state['totalGameDuration'];
                                $totalHours = floor($totalSeconds / 3600);
                                $minutes = floor(($totalSeconds % 3600) / 60);
                                $seconds = $totalSeconds % 60;
                                $formattedDuration = sprintf('%d:%02d:%02d', $totalHours, $minutes, $seconds);
                            } else {
                                $formattedDuration = 'N/A';
                            }
                            ?>
                            <li><strong>Total Duration:</strong> <?= $formattedDuration ?></li>
                            <li><strong>Auto-load Session:</strong>
                                <?= htmlspecialchars($state['autoLoadSessionName'] ?? 'N/A') ?>
                            </li>
                        </ul>

                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-8 col-xl-9">
                <!--                actions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="text-primary mb-3"><i class="fa-solid fa-rocket me-2"></i>Server Actions</h5>
                        <div class="d-flex flex-column flex-md-row gap-3 flex-wrap">
                            <!--                      actions:       stop server, download save game, upload save game, update server settings, update advanged game settings -->
                            <button id="stopServerBtn"
                                    class="btn btn-danger flex-fill" <?= !$healthy ? 'disabled' : '' ?>
                                    data-bs-toggle="modal"
                                    data-bs-target="#stopServerModal">
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
                    <div class="form-text text-muted mt-2 text-center">
                        Depending on the save size, this may take a few moments. You can close this dialog while the
                        download is being prepared.
                    </div>
                </div>
                <div class="modal-body d-none" id="downloadCompleted">
                    <div class="d-flex justify-content-center align-items-center flex-column">
                        <i class="fa-solid fa-check-circle text-success fa-2x mb-2 text-center" id="downloadSuccessIcon"
                           style="font-size: 100px"></i>
                        <span id="downloadSuccessMessage" class="text-center">Download completed successfully!</span>
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
                    const sessions = <?= json_encode($sessions ?? []) ?>

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
                                showDownloadCompleted(true);
                                setTimeout(() => {
                                    resetModal();
                                    showDownloadCompleted(false);
                                    $('#downloadSaveModal').modal('hide');
                                }, 3000); // hide after
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
                        const completedDiv = document.getElementById('downloadCompleted');
                        const contentDiv = loadingDiv.previousElementSibling; // The modal body
                        const button = document.getElementById('downloadSaveLink');
                        if (show) {
                            loadingDiv.classList.remove('d-none');
                            contentDiv.classList.add('d-none');
                            completedDiv.classList.add('d-none');
                            button.classList.add('disabled');
                            button.setAttribute('aria-disabled', 'true');
                            button.querySelector('span').classList.remove('d-none');

                        } else {
                            loadingDiv.classList.add('d-none');
                            contentDiv.classList.remove('d-none');
                            completedDiv.classList.add('d-none');
                            button.querySelector('span').classList.add('d-none');
                            button.classList.remove('disabled');
                            button.setAttribute('aria-disabled', 'false');
                            button.querySelector('span').classList.add('d-none');
                        }
                    }

                    function showDownloadCompleted(show) {
                        const completedDiv = document.getElementById('downloadCompleted');
                        const loadingDiv = document.getElementById('downloadSaveModalLoading');
                        const contentDiv = loadingDiv.previousElementSibling; // The modal body
                        const button = document.getElementById('downloadSaveLink');
                        if (show) {
                            completedDiv.classList.remove('d-none');
                            loadingDiv.classList.add('d-none');
                            contentDiv.classList.add('d-none');
                            button.classList.add('disabled');
                            button.setAttribute('aria-disabled', 'true');
                            button.querySelector('span').classList.add('d-none');

                        } else {
                            completedDiv.classList.add('d-none');
                            loadingDiv.classList.add('d-none');
                            contentDiv.classList.remove('d-none');
                            button.querySelector('span').classList.add('d-none');
                            button.classList.remove('disabled');
                            button.setAttribute('aria-disabled', 'false');
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
    <!--    modal of shutting down-->
    <div class="modal fade" id="stopServerModal" tabindex="-1" aria-labelledby="stopServerModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stopServerModalLabel">Stop Dedicated Server</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fa-solid fa-triangle-exclamation text-warning fa-3x mb-3"></i>
                    <p class="mb-3">Are you sure you want to stop the dedicated server? This will disconnect all
                        players and may result in loss of unsaved progress.</p>
                    <div id="stopServerAlert" class="alert d-none" role="alert"></div>
                    <button id="confirmStopServerBtn" class="btn btn-danger">Yes, Stop Server</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
                <div class="modal-body text-center d-none" id="shutdownConfirmation">
                    <i class="fa-solid fa-check-circle text-success fa-3x mb-3" style="font-size: 100px"></i>
                    <p class="mb-0">The server has been successfully stopped.</p>
                </div>
            </div>
        </div>
        <script>
            document.getElementById('confirmStopServerBtn').addEventListener('click', function () {
                const alertDiv = document.getElementById('stopServerAlert');
                const modalBody = this.parentElement;
                const shutdownDiv = document.getElementById('shutdownConfirmation');
                alertDiv.classList.add('d-none');
                alertDiv.classList.remove('alert-success', 'alert-danger');
                alertDiv.textContent = '';

                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Stopping...';

                $.ajax({
                    url: '/dedicatedServerAPI/shutdown',
                    type: 'POST',
                    data: {
                        gameSaveId: <?= $saveGameId ?>
                    },
                    headers: {'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?>'},
                    success: function (response) {
                        modalBody.classList.add('d-none');
                        shutdownDiv.classList.remove('d-none');

                        setTimeout(() => {
                            $('#stopServerModal').modal('hide');
                            location.reload(); // Refresh the page to update status
                        }, 3000); // hide after 3 seconds
                    },
                    error: function (xhr, status, error) {
                        alertDiv.classList.remove('d-none');
                        alertDiv.classList.add('alert-danger');
                        alertDiv.textContent = 'Error stopping server: ' + (xhr.responseJSON?.error || error);
                    },
                    complete: function () {
                        const btn = document.getElementById('confirmStopServerBtn');
                        btn.disabled = false;
                        btn.innerHTML = 'Yes, Stop Server';
                    }
                });
            });
        </script>
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

<script>
    // set last checked to current time
    document.addEventListener('DOMContentLoaded', function () {
        const now = new Date();
        const timeString = now.toLocaleTimeString([], {hour12: false});
        $('li strong:contains("Last Checked:")').parent().html('<strong>Last Checked:</strong> ' + timeString);
    });

    // Helper to mimic PHP cleanGamePhase
    function cleanGamePhase(phaseString) {
        const match = phaseString.match(/GP_([^\.]+)/);
        if (match) {
            return match[1].replace(/_/g, ' ');
        }
        return phaseString;
    }

    // Helper for health icon + text
    function formatHealth(healthy) {
        return healthy
            ? '<span class="text-success">Healthy <i class="fa-solid fa-check fa-lg"></i></span>'
            : '<span class="text-danger">Unhealthy <i class="fa-solid fa-xmark fa-lg"></i></span>';
    }

    // Helper for running/paused
    function formatRunning(isRunning) {
        return isRunning
            ? '<i class="fa-solid fa-check text-success fa-lg"></i> Yes'
            : '<i class="fa-solid fa-xmark text-danger fa-lg"></i> No';
    }

    function formatPaused(isPaused) {
        return isPaused
            ? '<i class="fa-solid fa-pause text-danger fa-lg"></i> Yes'
            : '<i class="fa-solid fa-play text-info fa-lg"></i> No';
    }

    // elke minuut de health status verversen
    setInterval(() => {
        $.ajax({
            url: '/dedicatedServerAPI/healthCheck',
            type: 'POST',
            dataType: 'json',
            data: {saveGameId: <?= $saveGameId ?>},
            headers: {'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?>'},
            success: function (response) {
                if (response && response.data && response.data.health === 'healthy') {
                    $.ajax({
                        url: '/dedicatedServerAPI/queryServerState',
                        type: 'POST',
                        dataType: 'json',
                        data: {saveGameId: <?= $saveGameId ?>},
                        headers: {'X-CSRF-Token': '<?= $_SESSION['csrf_token'] ?>'},
                        success: function (stateResponse) {
                            if (!stateResponse || !stateResponse.data) return;
                            const data = stateResponse.data.serverGameState;

                            // Update status indicator
                            $('.status-indicator').removeClass('offline').addClass('online');
                            $('.status-indicator .status-blink').removeClass('blink-offline').addClass('blink-online');
                            $('h4.fw-bold').text('Server Online');

                            // Update health + timestamp
                            $('li strong:contains("Health:")').parent().html('<strong>Health:</strong> ' + formatHealth(true));
                            // only time
                            $('li strong:contains("Last Checked:")').parent().html('<strong>Last Checked:</strong> ' + new Date().toLocaleTimeString([], {hour12: false}));

                            // Update session info
                            $('li strong:contains("Session:")').parent().html('<strong>Session:</strong> ' + data.activeSessionName);
                            $('li strong:contains("Players:")').parent().html('<strong>Players:</strong> ' + data.numConnectedPlayers + '/' + data.playerLimit);
                            $('li strong:contains("Tech Tier:")').parent().html('<strong>Tech Tier:</strong> ' + data.techTier);
                            $('li strong:contains("Phase:")').parent().html('<strong>Phase:</strong> ' + cleanGamePhase(data.gamePhase));

                            // Update game status
                            $('li strong:contains("Running:")').parent().html('<strong>Running:</strong> ' + formatRunning(data.isGameRunning));
                            $('li strong:contains("Paused:")').parent().html('<strong>Paused:</strong> ' + formatPaused(data.isGamePaused));
                            $('li strong:contains("Tick Rate:")').parent().html('<strong>Tick Rate:</strong> ' + parseFloat(data.averageTickRate).toFixed(2) + ' TPS');

                            // Update misc info
                            $('li strong:contains("Total Duration:")').parent().html('<strong>Total Duration:</strong> ' + data.totalGameDuration);
                            $('li strong:contains("Auto-load Session:")').parent().html('<strong>Auto-load Session:</strong> ' + data.autoLoadSessionName);
                        },
                        error: function (xhr, status, error) {
                            console.error(xhr.responseJSON?.error || error);
                        }
                    });
                } else {
                    console.warn('Health check failed:', response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseJSON?.error || error);
            }
        });
    }, 60000); // every minute
</script>

<!--todo: move to TS-->

