import {ProductionTable} from "./Table/ProductionTable";
import {ImportExport} from "./Table/Utils/ImportExport";
import {PowerTable} from "./Table/PowerTable";
import {ImportsTable} from "./Table/ImportsTable";

let productionTable = new ProductionTable('recipes', true);

productionTable.consoleLog();
productionTable.renderTable();

document.addEventListener('DOMContentLoaded', () => {
    const importButton = document.getElementById('importButton');
    const exportButton = document.getElementById('exportButton');

    if (importButton) {
        importButton.addEventListener('click', () => ImportExport.importData());
    }

    if (exportButton) {
        exportButton.addEventListener('click', () => ImportExport.exportData());
    }
});

// let powerTable = new PowerTable('power', true);
//
// powerTable.consoleLog();
// powerTable.renderTable();
//
// let importsTable = new ImportsTable('imports', true);
//
// importsTable.consoleLog();
// importsTable.renderTable();
