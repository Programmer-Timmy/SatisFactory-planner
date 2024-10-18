import {TableHandler} from "./TableHandler";
import {ImportNodes} from "./Data/Visualization/ImportNodes";
import {ProductionNodes} from "./Data/Visualization/ProductionNodes";
import {ExportNodes} from "./Data/Visualization/ExportNodes";
import {Connection} from "./Data/Visualization/Connection";

/**
 * Visualization class
 *
 * This class is responsible for creating the visualization of the production line
 * @class
 * @property {TableHandler} TableHandler - The table handler object
 * @property {ImportNodes[]} importNodes - The import nodes array
 * @property {ProductionNodes[]} productionNodes - The production nodes array
 * @property {ExportNodes[]} exportNodes - The export nodes array
 *
 * @property {Connection[]} importConnections - The import connections array
 * @property {Connection[]} productionConnections - The production connections array
 * @property {Connection[]} exportConnections - The export connections array
 */
export class Visualization {

    public TableHandler: TableHandler;
    public importNodes: ImportNodes[] = [];
    public productionNodes: ProductionNodes[] = [];
    public exportNodes: ExportNodes[] = [];

    public importConnections: Connection[] = [];
    public productionConnections: Connection[] = [];
    public exportConnections: Connection[] = [];

    /**
     * Constructor for the Visualization class
     * @constructor
     * @param {TableHandler} tableHandler - The table handler object
     */
    constructor(tableHandler: TableHandler) {
        this.TableHandler = tableHandler;
        this.getImportNodes();
        this.getProduction();
        this.getExportNodes();

        this.getImportConnection();

        console.log(this);
        console.log(this.TableHandler);
    }

    /**
     * Get all import nodes from the import table and add them to the import nodes array
     * @private
     */
    private getImportNodes(): void {
        for (let i = 0; i < this.TableHandler.importsTableRows.length; i++) {
            const row = this.TableHandler.importsTableRows[i];
            this.importNodes.push(new ImportNodes(i, row.product, row.quantity));
        }
    }

    /**
     * Get all import connections from the production table and add them to the import connections array
     * @private
     */
    private getImportConnection(): void {
        let index = 0;
        for (let i = 0; i < this.TableHandler.productionTableRows.length; i++) {
            const row = this.TableHandler.productionTableRows[i];

            for (let j = 0; j < row.imports.length; j++) {
                const importRow = row.imports[j];
                this.importConnections.push(new Connection(index, importRow.index, i, importRow.amount, importRow.product));
                index++;
            }
        }
    }

    /**
     * Get all production nodes and connections from the production table
     * @private
     */
    private getProduction(): void {
        for (let i = 0; i < this.TableHandler.productionTableRows.length; i++) {
            const row = this.TableHandler.productionTableRows[i];

            const building = row.recipe?.building;

            if (building) {
                this.productionNodes.push(new ProductionNodes(i, row.product, row.quantity, building.name, building.id));
            }

            for (let j = 0; j < row.productionImports.length; j++) {
                const importRow = row.productionImports[j];
                this.productionConnections.push(new Connection(i, importRow.index, i, importRow.amount, importRow.product));
            }
        }
    }

    /**
     * Get all export nodes from the production table and add them to the export nodes array and connections array
     * @private
     */
    private getExportNodes(): void {
        let index = 0;
        for (let i = 0; i < this.TableHandler.productionTableRows.length; i++) {
            const row = this.TableHandler.productionTableRows[i];

            if (row.exportPerMin > 0) {
                this.exportNodes.push(new ExportNodes(index, row.product, row.exportPerMin));
                this.exportConnections.push(new Connection(index, i, this.exportNodes.length - 1, row.exportPerMin, row.product));
                index++;
            }

            // @ts-ignore
            if (row.extraCells?.ExportPerMin > 0) {
                // @ts-ignore
                this.exportNodes.push(new ExportNodes(index, row.product, row.extraCells?.ExportPerMin));
                // @ts-ignore
                this.exportConnections.push(new Connection(index, i, this.exportNodes.length - 1, row.extraCells?.ExportPerMin, row.product));
                index++;
            }

        }
    }
}