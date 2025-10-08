import { StateResponse } from "../Interfaces/StateResponse";
import { HealthResponse } from "../Interfaces/HealthResponse";
import {SaveDownloadHandler} from "./SaveDownloadHandler";
import {Session} from "../Interfaces/Session";

export class ServerStatusUpdater {
    private csrfToken: string;
    private saveGameId: number;
    private refreshInterval: number;
    private saveDownloadHandler?: SaveDownloadHandler;
    private sessions: Session[];

    constructor(csrfToken: string, saveGameId: number, refreshIntervalMs: number = 60_000, sessions: Session[] = []) {
        this.csrfToken = csrfToken;
        this.saveGameId = saveGameId;
        this.refreshInterval = refreshIntervalMs;
        this.sessions = sessions;
    }

    public init(): void {
        document.addEventListener('DOMContentLoaded', () => {
            this.updateLastChecked();
            setInterval(() => this.checkHealth(), this.refreshInterval);
        });

        this.bindStopServerButton();
        this.saveDownloadHandler = new SaveDownloadHandler(this.csrfToken, this.saveGameId, this.sessions);
        this.saveDownloadHandler.init();
    }

    // ---------- Private Helpers ----------

    private updateListItem(label: string, html: string): void {
        const elements = Array.from(document.querySelectorAll('li strong'));
        const target = elements.find(el => el.textContent?.includes(label));
        if (target?.parentElement) {
            target.parentElement.innerHTML = html;
        }
    }

    private cleanGamePhase(phaseString: string): string {
        const match = phaseString.match(/GP_([^\.]+)/);
        return match ? match[1].replace(/_/g, ' ') : phaseString;
    }

    private formatHealth(healthy: boolean): string {
        return healthy
            ? '<span class="text-success">Healthy <i class="fa-solid fa-check fa-lg"></i></span>'
            : '<span class="text-danger">Unhealthy <i class="fa-solid fa-xmark fa-lg"></i></span>';
    }

    private formatRunning(isRunning: boolean): string {
        return isRunning
            ? '<i class="fa-solid fa-check text-success fa-lg"></i> Yes'
            : '<i class="fa-solid fa-xmark text-danger fa-lg"></i> No';
    }

    private formatPaused(isPaused: boolean): string {
        return isPaused
            ? '<i class="fa-solid fa-pause text-danger fa-lg"></i> Yes'
            : '<i class="fa-solid fa-play text-info fa-lg"></i> No';
    }

    private updateLastChecked(): void {
        const now = new Date();
        const timeString = now.toLocaleTimeString([], { hour12: false });
        this.updateListItem('Last Checked:', `<strong>Last Checked:</strong> ${timeString}`);
    }

    // ---------- Core Logic (AJAX) ----------

    private checkHealth(): void {
        $.ajax({
            url: '/dedicatedServerAPI/healthCheck',
            type: 'POST',
            data: { gameSaveId: this.saveGameId },
            headers: { 'X-CSRF-Token': this.csrfToken },
            success: (response: HealthResponse) => {
                if (response?.data?.health === 'healthy') {
                    this.updateServerState();
                } else {
                    console.warn('Health check failed:', response.message);
                }
            },
            error: (xhr, status, error) => {
                console.error('Health check error:', xhr.responseJSON?.error || error);
            }
        });
    }

    private updateServerState(): void {
        $.ajax({
            url: '/dedicatedServerAPI/queryServerState',
            type: 'POST',
            data: { gameSaveId: this.saveGameId },
            headers: { 'X-CSRF-Token': this.csrfToken },
            success: (response: StateResponse) => {
                const data = response?.data?.serverGameState;
                if (!data) return;

                const statusIndicator = document.querySelector('.status-indicator');
                const statusBlink = document.querySelector('.status-indicator .status-blink');
                const statusTitle = document.querySelector('h4.fw-bold');

                statusIndicator?.classList.remove('offline');
                statusIndicator?.classList.add('online');
                statusBlink?.classList.remove('blink-offline');
                statusBlink?.classList.add('blink-online');
                if (statusTitle) statusTitle.textContent = 'Server Online';

                this.updateListItem('Health:', `<strong>Health:</strong> ${this.formatHealth(true)}`);
                this.updateLastChecked();

                this.updateListItem('Session:', `<strong>Session:</strong> ${data.activeSessionName}`);
                this.updateListItem('Players:', `<strong>Players:</strong> ${data.numConnectedPlayers}/${data.playerLimit}`);
                this.updateListItem('Tech Tier:', `<strong>Tech Tier:</strong> ${data.techTier}`);
                this.updateListItem('Phase:', `<strong>Phase:</strong> ${this.cleanGamePhase(data.gamePhase)}`);
                this.updateListItem('Running:', `<strong>Running:</strong> ${this.formatRunning(data.isGameRunning)}`);
                this.updateListItem('Paused:', `<strong>Paused:</strong> ${this.formatPaused(data.isGamePaused)}`);
                this.updateListItem('Tick Rate:', `<strong>Tick Rate:</strong> ${data.averageTickRate.toFixed(2)} TPS`);
                this.updateListItem('Total Duration:', `<strong>Total Duration:</strong> ${data.totalGameDuration}`);
                this.updateListItem('Auto-load Session:', `<strong>Auto-load Session:</strong> ${data.autoLoadSessionName}`);
            },
            error: (xhr, status, error) => {
                console.error('State update error:', xhr.responseJSON?.error || error);
            }
        });
    }

    // ---------- Stop Server Logic ----------

    private bindStopServerButton(): void {
        const stopBtn = document.getElementById('confirmStopServerBtn');
        if (!stopBtn) return;

        stopBtn.addEventListener('click', () => this.stopServer(stopBtn));
    }

    private stopServer(button: HTMLElement): void {
        const alertDiv = document.getElementById('stopServerAlert') as HTMLElement;
        const modalBody = button.parentElement as HTMLElement;
        const shutdownDiv = document.getElementById('shutdownConfirmation') as HTMLElement;

        alertDiv.classList.add('d-none');
        alertDiv.classList.remove('alert-success', 'alert-danger');
        alertDiv.textContent = '';

        button.setAttribute('disabled', 'true');
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Stopping...';

        $.ajax({
            url: '/dedicatedServerAPI/shutdown',
            type: 'POST',
            data: { gameSaveId: this.saveGameId },
            headers: { 'X-CSRF-Token': this.csrfToken },
            success: () => {
                modalBody.classList.add('d-none');
                shutdownDiv.classList.remove('d-none');

                setTimeout(() => {
                    // @ts-ignore (Bootstrap modal jQuery integration)
                    $('#stopServerModal').modal('hide');
                    location.reload();
                }, 3000);
            },
            error: (xhr, status, error) => {
                alertDiv.classList.remove('d-none');
                alertDiv.classList.add('alert-danger');
                alertDiv.textContent =
                    'Error stopping server: ' + (xhr.responseJSON?.error || error);
            },
            complete: () => {
                button.removeAttribute('disabled');
                button.innerHTML = 'Yes, Stop Server';
            },
        });
    }

}
