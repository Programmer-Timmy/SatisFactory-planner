import {ProductionTable} from "./Table/ProductionTable";
import {PowerTable} from "./Table/PowerTable";
import {ImportsTable} from "./Table/ImportsTable";

let productionTable = new ProductionTable('recipes', true);

productionTable.consoleLog();
productionTable.renderTable();

// let powerTable = new PowerTable('power', true);
//
// powerTable.consoleLog();
// powerTable.renderTable();
//
// let importsTable = new ImportsTable('imports', true);
//
// importsTable.consoleLog();
// importsTable.renderTable();
