import {ProductionTableRow} from "../Data/ProductionTableRow";
import {PowerTableRow} from "../Data/PowerTableRow";
import {ImportsTableRow} from "../Data/ImportsTableRow";
import {Ajax} from "./Ajax";
import {Checklist} from "../Checklist";
import {TableHandler} from "../TableHandler";

interface newAndOldIds {
    new: number,
    old: string
}
export class SaveFunctions {


    public static prepareSaveData(productionTableRows: ProductionTableRow[], powerTableRows: PowerTableRow[], importsTableRows: ImportsTableRow[], checklist: Checklist|null): Record<string, any> {
        return {
            productionTableRows: productionTableRows,
            powerTableRows: powerTableRows,
            importsTableRows: importsTableRows,
            checklist: checklist?.getChecklist()
        };
    }

    public static saveProductionLine(jsonData: Record<string, any>, tableHandler: TableHandler, isQuickSave: boolean = true): Promise<boolean> {
        try {
            const url = new URL(window.location.href);
            const id = parseInt(url.searchParams.get('id') as string);

            return Ajax.saveData(jsonData, id).then((response) => {
                if (response['success']) {
                    if (!isQuickSave) {
                        return true
                    }

                    this.showSuccessMessage('Data successfully saved.');
                    const newAndOldIds: newAndOldIds[] = response.data.newAndOldIds;

                    if (newAndOldIds.length > 0) {
                        this.updateProductionIds(newAndOldIds, tableHandler);
                    }
                    return true;
                }
                this.showErrorMessage('An error occurred while saving the data. Please try again.');
                return false;
            }).catch((error) => {
                this.showErrorMessage('An error occurred while saving the data. Please try again.');
                return false;
            });
        } catch (error) {
            this.showErrorMessage('An error occurred while saving the data. Please try again.');
            return Promise.resolve(false);
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

    private static updateProductionIds(newAndOldIds: newAndOldIds[], tableHandler: TableHandler) {
        newAndOldIds.forEach( (newAndOldId) => {
            const newId = newAndOldId.new;
            const oldId = newAndOldId.old;
            $(`#recipes input[type="hidden"][name="production_id[]"][value="${oldId}"]`).val(newId);

            const productionRow = tableHandler.productionTableRows.find( (row) =>  row.row_id === oldId)

            if (productionRow) {
                productionRow.row_id = newId;
            }
        });
    }
}