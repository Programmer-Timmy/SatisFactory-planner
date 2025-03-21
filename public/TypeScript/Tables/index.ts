import {TableHandler} from "./Classes/TableHandler";
import {SaveFunctions} from "./Classes/Functions/SaveFunctions";
import {ImportExport} from "./Classes/Functions/ImportExport";

const tableHandler = new TableHandler();

const saveButton = $("#save_button");
saveButton.on("click", (event: JQuery.ClickEvent) => {
    if (event.shiftKey) {
        event.preventDefault();
        SaveFunctions.saveProductionLine(
            SaveFunctions.prepareSaveData(
                tableHandler.productionTableRows,
                tableHandler.powerTableRows,
                tableHandler.importsTableRows,
                tableHandler.checklist
            ),
            tableHandler
        );

        saveButton.tooltip('hide');
        saveButton.blur();
    }
})

const exportButton = $("#exportButton");
const importButton = $("#importButton");
exportButton.on("click", () => {
    ImportExport.exportData(tableHandler);
});

importButton.on("click", (event: JQuery.ClickEvent) => {
    event.preventDefault();
    ImportExport.importData(tableHandler);
});
