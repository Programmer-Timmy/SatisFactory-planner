import {ProductionTableRow} from "../Data/ProductionTableRow";
import {PowerTableRow} from "../Data/PowerTableRow";
import {ImportsTableRow} from "../Data/ImportsTableRow";
import {Ajax} from "./Ajax";

export class SaveFunctions {


    public static prepareSaveData(productionTableRows: ProductionTableRow[], powerTableRows: PowerTableRow[], importsTableRows: ImportsTableRow[]): Record<string, any> {
        return {
            productionTableRows: productionTableRows,
            powerTableRows: powerTableRows,
            importsTableRows: importsTableRows
        };
    }

    public static saveProductionLine(jsonData: Record<string, any>) {
        try {
            const url = new URL(window.location.href);
            const id = parseInt(url.searchParams.get('id') as string);


            Ajax.saveData(jsonData, id).then((response) => {
                if (response['success']) {
                    this.showSuccessMessage('Data successfully saved.');
                    return;
                }
                this.showErrorMessage('An error occurred while saving the data. Please try again.');
            }).catch((error) => {
                this.showErrorMessage('An error occurred while saving the data. Please try again.');
            });
        } catch (error) {
            this.showErrorMessage('An error occurred while saving the data. Please try again.');
        }
    }

    private static showSuccessMessage(message: string) {
        this.showMessage('saveSuccessAlert', message);
        return;
    }

    private static showErrorMessage(message: string) {
        this.showMessage('saveErrorAlert', message);
        return;
    }

    private static showMessage(alertId: string, message: string) {
        const alertElement = document.getElementById(alertId) as HTMLDivElement;
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
}