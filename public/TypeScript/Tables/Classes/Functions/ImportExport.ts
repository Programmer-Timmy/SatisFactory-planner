import {TableHandler} from "../TableHandler";

export class ImportExport {
    public static async importData(tableHandler: TableHandler) {
        if (!this.checkIfFileIsSelected() || !this.checkIfFileIsJson()) {
            return;
        }

        const jsonFile = $('#importFile').prop('files')[0];
        const reader = new FileReader();
        reader.readAsText(jsonFile);

        reader.onload = (event) => {
            const jsonData = JSON.parse(event.target?.result as string);

            if (!jsonData.productionTable || !jsonData.powerTable || !jsonData.importTable) {
                this.showErrorMessage('The selected file does not contain the required data.');
                return;
            }

            tableHandler.saveData(jsonData.productionTable, jsonData.powerTable, jsonData.importTable);
            this.showSuccessMessage('Data successfully imported.');
        }
    }

    public static exportData(tableHandler: TableHandler) {
        const productionTableData = tableHandler.productionTableRows;
        const importTableData = tableHandler.importsTableRows;
        const powerTableData = tableHandler.powerTableRows;


        const dataToExport = {
            productionTable: productionTableData,
            powerTable: powerTableData,
            importTable: importTableData
        }

        console.log(tableHandler);

        const bytes = new TextEncoder().encode(JSON.stringify(dataToExport));
        const blob = new Blob([bytes], {type: 'application/json'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = this.generateFileName();
        a.click();
        URL.revokeObjectURL(url);

        this.showSuccessMessage('Data successfully exported.');
    }

    private static checkIfFileIsSelected(): boolean {
        const fileInput = document.getElementById('importFile') as HTMLInputElement;

        if (!fileInput.files?.length) {
            // Add Bootstrap 'is-invalid' class
            fileInput.classList.add('is-invalid');
            // Show the validation feedback message
            const feedback = fileInput.nextElementSibling as HTMLElement;
            if (feedback) {
                feedback.classList.add('d-block');
            }
            return false;
        }

        // If a file is selected, ensure 'is-invalid' class is removed
        fileInput.classList.remove('is-invalid');
        const feedback = fileInput.nextElementSibling as HTMLElement;
        if (feedback) {
            feedback.classList.remove('d-block');
        }

        return true;
    }

    private static checkIfFileIsJson(): boolean {
        const fileInput = document.getElementById('importFile') as HTMLInputElement;
        const file = fileInput.files?.[0];

        // Check if the file type is JSON
        if (file && file.type !== 'application/json') {
            fileInput.classList.add('is-invalid');
            const feedback = fileInput.nextElementSibling as HTMLElement;
            if (feedback) {
                feedback.textContent = 'Please select a valid JSON file.';
                feedback.classList.add('d-block');
            }
            return false;
        }

        return true;
    }

    private static showMessage(elementId: string, message: string) {
        const alertElement = document.getElementById(elementId) as HTMLDivElement;
        if (alertElement) {
            alertElement.textContent = message;

            // Ensure the alert is hidden first
            alertElement.classList.add('d-none');
            alertElement.classList.remove('show');

            // Trigger reflow to allow for the transition
            void alertElement.offsetWidth;

            // Fade in the alert
            alertElement.classList.remove('d-none');
            alertElement.classList.add('show');

            // Automatically fade out after 5 seconds
            setTimeout(() => {
                alertElement.classList.remove('show');
                setTimeout(() => {
                    alertElement.classList.add('d-none');
                }, 150); // Time to wait for the fade-out to complete
            }, 5000); // Display duration before fading out
        }
    }

    private static showSuccessMessage(message: string, type: 1 | 2 = 1) {
        if (type === 1) {
            this.showMessage('successAlert', message);
            return;
        }
    }

    private static showErrorMessage(message: string, type: 1 | 2 = 1) {
        if (type === 1) {
            this.showMessage('errorAlert', message);
            return;
        }
    }

    private static generateFileName(): string {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const dateTimestamp = new Date().toLocaleString('en-GB', {timeZone: timezone}).replace(/[\/\s,:]/g, '_');
        let productionLineName = $('#productionLineName').text().replace('Production Line - ', '');
        productionLineName = productionLineName.replace(/\s/g, '_');

        return `${productionLineName}_data_${dateTimestamp}.json`;
    }
}