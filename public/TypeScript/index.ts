import {ProductionTable} from "./Table/ProductionTable";
import {ImportExport} from "./Table/Utils/ImportExport";
import {PowerTable} from "./Table/PowerTable";
import {ImportsTable} from "./Table/ImportsTable";

let productionTable = new ProductionTable('recipes', true);

productionTable.renderTable();

let powerTable = new PowerTable('power', productionTable, true);

powerTable.renderTable();

let importsTable = new ImportsTable('imports', productionTable, true);

importsTable.renderTable();


document.addEventListener('DOMContentLoaded', () => {
    const importButton = document.getElementById('importButton');
    const exportButton = document.getElementById('exportButton');

    if (importButton) {
        importButton.addEventListener('click', () => ImportExport.importData());
    }

    if (exportButton) {
        exportButton.addEventListener('click', () => ImportExport.exportData());
    }

    const saveButton = document.getElementById('save_button') as HTMLButtonElement;

    saveButton.addEventListener('click', (event: MouseEvent) => {
        event.preventDefault();

        if (event.shiftKey) {
            ImportExport.saveProductionLine();

        }
    });
});
