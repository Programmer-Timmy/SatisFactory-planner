import {HealthCheck} from "../Data/HealthCheck";
import {ToastElement} from "../Data/ToastElement";
import type { Toast } from "bootstrap";

class FakeToast {
    constructor(_element: HTMLElement) {}
    show() {}
    hide() {}
    dispose() {}
}

// Use FakeToast as a fallback when Bootstrap's Toast is not available
const ToastConstructor: typeof Toast = (window.bootstrap?.Toast || FakeToast) as typeof Toast;

export class DedicatedServer {

    public show: boolean = false;
    public toast: Toast;
    public toastMessage: string = "";
    public closed: boolean = false;
    public gameSaveId: number = NaN;
    public fillColor: string = '#57e389';


    constructor(gameSaveId: number) {
        this.gameSaveId = gameSaveId;
        // insert the toast element
        document.body.appendChild(ToastElement[0]);

        this.toast = new ToastConstructor(document.getElementById('DedicatedServerStatus')!);

        if (document.cookie.includes('toastClosed=true')) {
            this.closed = true;
        } else {
            this.closed = false;
        }

        if (!this.closed) {
            this.HealthCheck();
            this.applyeventListeners();
            const intervalId = setInterval(() => {
                if (!this.closed) {
                    this.HealthCheck();
                } else {
                    clearInterval(intervalId);
                }
            }, 60000);

        }
    }

    public ShowToast(): void {
        $('#DedicatedServerStatus .toast-header svg rect').attr('fill', this.fillColor);
        $('#DedicatedServerStatus .toast-body').html(this.toastMessage);

        if (!$('#DedicatedServerStatus').hasClass('show')) {
            this.toast.show();
        }
    }

    public HealthCheck(): void {
        this.AjaxHealthCheck().then((response) => {
            if (response.data.health === 'healthy') {
                this.fillColor = '#57e389';
                this.AjaxQueryServer().then((response) => {
                    let message = '';
                    message += `<div class="text-success mb-2 fw-bold">Status: Healthy</div>`;
                    message += `<div><strong>Session Name:</strong> ${response.data.serverGameState.activeSessionName}</div>`;
                    message += `<div><strong>Game Paused:</strong> ${response.data.serverGameState.isGamePaused ? '<span class="text-danger">Yes</span>' : '<span class="text-success">No</span>'}</div>`;
                    message += `<div><strong>Connected Players:</strong> ${response.data.serverGameState.numConnectedPlayers}</div>`;
                    message += `<div><strong>Total Game Time:</strong> ${response.data.serverGameState.totalGameDuration} minutes</div>`;

                    this.toastMessage = message;
                    this.ShowToast();
                }).catch((error) => {
                    this.fillColor = '#ff6b6b';
                    this.toastMessage = `<div class="text-danger fw-bold">The dedicated server is down.</div>`;
                    this.ShowToast();
                });
            }
        }).catch((error) => {
            this.fillColor = '#ff6b6b';
            this.toastMessage = `<div class="text-danger fw-bold">The dedicated server is down.</div>`;
            this.ShowToast();
        });
    }


    public AjaxHealthCheck(gameSaveId: number = this.gameSaveId): Promise<HealthCheck> {
        const token = this._getCsrfToken();
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: '/dedicatedServerAPI/healthCheck', // Replace with your actual PHP file path
                type: 'POST',
                data: {
                    saveGameId: gameSaveId
                },
                headers: {'X-CSRF-Token': token},
                dataType: 'json',
                success: function (response) {
                    try {
                        if (response.status === 'success') {
                            resolve(response);
                        } else {
                            reject(response);
                        }
                    } catch (error) {
                        reject(error);
                    }
                },
                error: function (xhr, status, error) {
                    $('#apiResponse').html(`<div class="alert alert-danger">Error: ${error}</div>`);
                }
            });
        });
    }

    public AjaxQueryServer(gameSaveId: number = this.gameSaveId): Promise<any> {
        const token = this._getCsrfToken();
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: '/dedicatedServerAPI/queryServerState', // Replace with your actual PHP file path
                type: 'POST',
                data: {
                    saveGameId: gameSaveId
                },
                headers: {'X-CSRF-Token': token},
                dataType: 'json',
                success: function (response) {
                    try {
                        if (response.status === 'success') {
                            resolve(response);
                        } else {
                            reject(response);
                        }
                    } catch (error) {
                        reject(error);
                    }
                },
                error: function (xhr, status, error) {
                    $('#apiResponse').html(`<div class="alert alert-danger">Error: ${error}</div>`);
                }
            });
        });
    }

    public applyeventListeners(): void {
        $('#DedicatedServerStatus').on('hidden.bs.toast', () => {
            // set cookie to not show the toast
            this.closed = true;
            document.cookie = 'toastClosed=true';
        });
    }

    private _getCsrfToken(): string {
        const meta = $('meta[name="csrf-token"]');
        if (meta.length === 0) {
            throw new Error('CSRF token not found');
        }
        return <string>meta.attr('content');
    }
}