import {TableHandler} from "./Classes/TableHandler";
import {SaveFunctions} from "./Classes/Functions/SaveFunctions";

const tableHandler = new TableHandler();


const saveButton = $("#save_button");
saveButton.on("click", (event: JQuery.ClickEvent) => {
    if (event.shiftKey) {
        event.preventDefault();
        SaveFunctions.saveProductionLine(SaveFunctions.prepareSaveData(tableHandler.productionTableRows, tableHandler.powerTableRows, tableHandler.importsTableRows));

        saveButton.tooltip('hide');
        saveButton.blur();
    }
})
