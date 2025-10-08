import {Session} from "../Interfaces/Session";

export class SaveDownloadHandler {
    private csrfToken: string;
    private saveGameId: number;
    private sessions: Session[];

    private sessionSelect: HTMLSelectElement;
    private saveSelect: HTMLSelectElement;
    private downloadButton: HTMLButtonElement;

    constructor(csrfToken: string, saveGameId: number, sessions: Session[]) {
        this.csrfToken = csrfToken;
        this.saveGameId = saveGameId;
        this.sessions = sessions;

        this.sessionSelect = document.getElementById('saveGameDownloadSelect') as HTMLSelectElement;
        this.saveSelect = document.getElementById('saveFileDownloadSelect') as HTMLSelectElement;
        this.downloadButton = document.getElementById('downloadSaveLink') as HTMLButtonElement;
    }

    public init(): void {
        if (!this.sessionSelect || !this.saveSelect || !this.downloadButton) return;

        this.sessionSelect.addEventListener('change', () => this.updateSaveOptions());
        this.saveSelect.addEventListener('change', () => this.handleSaveSelection());
        this.downloadButton.addEventListener('click', () => this.downloadSave());

        if (this.sessions.length > 0) {
            this.updateSaveOptions();
        }
    }

    // ---------- UI Handling ----------

    private updateSaveOptions(): void {
        const selectedSession = this.sessionSelect.value;
        this.saveSelect.innerHTML = '';

        const session = this.sessions.find(s => s.sessionName === selectedSession);
        if (session && session.saveHeaders.length > 0) {
            const defaultOption = new Option('Select a save file', '', true, true);
            defaultOption.disabled = true;
            this.saveSelect.appendChild(defaultOption);

            session.saveHeaders.forEach(save => {
                const option = new Option(save.saveName, save.saveName);
                this.saveSelect.appendChild(option);
            });
        } else {
            const option = new Option('No saves available', '', true, true);
            option.disabled = true;
            this.saveSelect.appendChild(option);
        }

        this.toggleDownloadButton(false);
    }

    private handleSaveSelection(): void {
        const hasSelection:boolean = !!(this.sessionSelect.value && this.saveSelect.value);
        this.toggleDownloadButton(hasSelection);
    }

    private toggleDownloadButton(enable: boolean): void {
        if (enable) {
            this.downloadButton.classList.remove('disabled');
            this.downloadButton.removeAttribute('aria-disabled');
        } else {
            this.downloadButton.classList.add('disabled');
            this.downloadButton.setAttribute('aria-disabled', 'true');
        }
    }

    // ---------- Modal States ----------

    private showLoading(show: boolean): void {
        const loadingDiv = document.getElementById('downloadSaveModalLoading');
        const completedDiv = document.getElementById('downloadCompleted');
        const contentDiv = loadingDiv?.previousElementSibling as HTMLElement;
        const spinner = this.downloadButton.querySelector('span');

        if (!loadingDiv || !completedDiv || !contentDiv || !spinner) return;

        if (show) {
            loadingDiv.classList.remove('d-none');
            completedDiv.classList.add('d-none');
            contentDiv.classList.add('d-none');
            spinner.classList.remove('d-none');
            this.toggleDownloadButton(false);
        } else {
            loadingDiv.classList.add('d-none');
            completedDiv.classList.add('d-none');
            contentDiv.classList.remove('d-none');
            spinner.classList.add('d-none');
            this.toggleDownloadButton(true);
        }
    }

    private showDownloadCompleted(show: boolean): void {
        const completedDiv = document.getElementById('downloadCompleted');
        const loadingDiv = document.getElementById('downloadSaveModalLoading');
        const contentDiv = loadingDiv?.previousElementSibling as HTMLElement;
        const spinner = this.downloadButton.querySelector('span');

        if (!completedDiv || !loadingDiv || !contentDiv || !spinner) return;

        if (show) {
            completedDiv.classList.remove('d-none');
            loadingDiv.classList.add('d-none');
            contentDiv.classList.add('d-none');
            spinner.classList.add('d-none');
            this.toggleDownloadButton(false);
        } else {
            completedDiv.classList.add('d-none');
            loadingDiv.classList.add('d-none');
            contentDiv.classList.remove('d-none');
            spinner.classList.add('d-none');
            this.toggleDownloadButton(true);
        }
    }

    private resetModal(): void {
        this.sessionSelect.selectedIndex = 0;
        this.saveSelect.innerHTML = '<option disabled selected>Select a session first</option>';
        this.toggleDownloadButton(false);
    }

    // ---------- AJAX ----------

    private downloadSave(): void {
        this.showLoading(true);

        $.ajax({
            url: '/dedicatedServerAPI/downloadSave',
            type: 'POST',
            data: {
                gameSaveId: this.saveGameId,
                sessionName: $(this.sessionSelect).val(),
                saveName: $(this.saveSelect).val(),
            },
            headers: { 'X-CSRF-Token': this.csrfToken },
            xhrFields: { responseType: 'blob' }, // Important to receive binary data
            success: (response: Blob) => {
                const saveName = $(this.saveSelect).val() as string;
                const blob = new Blob([response], { type: 'application/octet-stream' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = saveName.endsWith('.sav') ? saveName : `${saveName}.sav`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);

                this.showDownloadCompleted(true);
                setTimeout(() => {
                    this.resetModal();
                    this.showDownloadCompleted(false);
                    // @ts-ignore
                    $('#downloadSaveModal').modal('hide');
                }, 3000);
            },
            error: (xhr, status, error) => {
                alert('Error downloading save: ' + (xhr.responseJSON?.error || error));
                this.showLoading(false);
            },
        });
    }
}
