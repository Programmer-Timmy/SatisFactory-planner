import {ImportsTable} from "../ImportsTable";
import {ProductionTable} from "../ProductionTable";
import {PowerTable} from "../PowerTable";

export class ImportExport {
    public static async importData() {
        if (!this.checkIfFileIsSelected() || !this.checkIfFileIsJson()) {
            return;
        }

        const productionTableData = new ProductionTable('recipes', true);
        const powerTableData = new PowerTable('power', productionTableData, true);
        const importTableData = new ImportsTable('imports', productionTableData, true);

        const jsonFile = $('#importFile').prop('files')[0];
        const reader = new FileReader();

        const thisClass = this;

        reader.readAsText(jsonFile);
        reader.onload = async function () {
            const data: Record<string, any> = JSON.parse(reader.result as string);

            Promise.all([
                productionTableData.importData(data.productionTable),
                powerTableData.importData(data.powerTable),
                importTableData.importData(data.importTable)
            ]).catch((error) => {
                console.error(error);
                thisClass.showErrorMessage('An error occurred while importing the data. Please try again.');
                return;
            });

            // Reset the file input
            const fileInput = document.getElementById('importFile') as HTMLInputElement;
            fileInput.value = '';

            const url = new URL(window.location.href);
            const id = parseInt(url.searchParams.get('id') as string);

            const returnedData: Record<string, any> = await thisClass.saveData(data, id);

            if (returnedData['success']) {
                thisClass.showSuccessMessage('Data successfully imported.');
                return;
            }

            thisClass.showErrorMessage('An error occurred while importing the data. Please try again.');

        }
    }

    public static exportData() {
        const productionTableData = new ProductionTable('recipes', true);
        const powerTableData = new PowerTable('power', productionTableData, true);
        const importTableData = new ImportsTable('imports', productionTableData, true);

        const dataToExport = {
            productionTable: productionTableData,
            powerTable: powerTableData,
            importTable: importTableData
        }

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

    private static showSuccessMessage(message: string) {
        this.showMessage('successAlert', message);
    }

    private static showErrorMessage(message: string) {
        this.showMessage('errorAlert', message);
    }

    private static generateFileName(): string {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const dateTimestamp = new Date().toLocaleString('en-GB', {timeZone: timezone}).replace(/[\/\s,:]/g, '_');
        const productionLineName = $('#productionLineName').text().replace(/\s/g, '_');

        return `${productionLineName}_data_${dateTimestamp}.json`;
    }

    private static async saveData(data: Record<string, any>, id: number) : Promise<Record<string, any>> {
        // ajax call to save data
        return new Promise((resolve, reject) => {
            $.ajax({
                type: 'POST',
                url: 'saveProductionLine',
                data: {
                    data: JSON.stringify(data),
                    id: id
                },
                success: function (response) {
                    resolve(JSON.parse(response));
                },
                error: function (xhr, status, error) {
                    reject(error);
                }
            });
        });
    }


}