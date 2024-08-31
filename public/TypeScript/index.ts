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
    const saveButton = document.getElementById('save_button');

    if (importButton) {
        importButton.addEventListener('click', () => ImportExport.importData());
    }

    if (exportButton) {
        exportButton.addEventListener('click', () => ImportExport.exportData());
    }

    if (saveButton) {
        saveButton.addEventListener('click', (event: MouseEvent) => {
            if (event.shiftKey) {
                event.preventDefault();
                ImportExport.saveProductionLine();
            }
        });
    }
});
