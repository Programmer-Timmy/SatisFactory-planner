import {ImportsTableRow} from "./Data/ImportsTableRow";
import {ProductionTableRow} from "./Data/ProductionTableRow";
import {PowerTableRow} from "./Data/PowerTableRow";
import {ProductionLineFunctions} from "./ProductionLineFunctions";
import {ExtraProductionRow} from "./Data/ExtraProductionRow";
import {Ajax} from "./Ajax";
import {PowerTableFunctions} from "./PowerTableFunctions";
import {ImportsTableFunctions} from "./ImportsTableFunctions";

/**
 * Class responsible for handling the manipulation and event handling of tables.
 */
export class TableHandler {

    public importsTableRows: ImportsTableRow[];
    public productionTableRows: ProductionTableRow[];
    public powerTableRows: PowerTableRow[];

    constructor() {
        this.importsTableRows = this.readTable<ImportsTableRow>('imports', ImportsTableRow);
        this.productionTableRows = this.readTable<ProductionTableRow>('recipes', ProductionTableRow);
        this.powerTableRows = this.readTable<PowerTableRow>('power', PowerTableRow);

        this.addEventListeners();
    }

    /**
     * Generic method to read tables and convert rows into class instances.
     * @param {string} id - The ID of the table.
     * @param {new(...args: any[]) => T} rowClass - The class constructor for the table rows.
     * @returns {T[]} An array of instances of the specified row class.
     */
    private readTable<T>(id: string, rowClass: { new(...args: any[]): T }): T[] {
        const table = $(`#${id} tbody tr`);
        const rows: T[] = [];

        for (let i = 0; i < table.length; i++) {
            const row = table[i];
            const values = $(row).find('input, select');
            const rowValues: any[] = [];

            values.each((_, value) => {
                rowValues.push($(value).val());
            });

            // Handle double export in recipes table
            if (id === 'recipes' && table[i + 1]?.classList.contains('extra-output')) {
                const extraRow = table[i + 1];
                const extraRowValues = $(extraRow).find('input, select').map((_, el) => $(el).val()).get();

                // Create instance of ExtraProductionRow
                const extraRowInstance = new ExtraProductionRow(
                    extraRowValues[0] as string,          // Product
                    Number(extraRowValues[1]),            // Usage
                    Number(extraRowValues[2])             // ExportPerMin
                );

                // Append extraRowInstance to rowValues
                rowValues.push(true, extraRowInstance);

                // Skip the extra row in the next iteration
                i++;
            }

            rows.push(new rowClass(...rowValues));
        }
        return rows;
    }

    /**
     * Adds event listeners for change events on all inputs and selects within tables.
     */
    private addEventListeners() {
        const tables = ['imports', 'recipes', 'power'];

        tables.forEach((tableId) => {
            const inputsAndSelects = $(`#${tableId} tbody`).find('input, select');

            inputsAndSelects.each((_, element) => {
                $(element).on('change', (event) => {
                    this.handleInputChange(event, tableId);
                });
            });
        });
    }

    /**
     * Handles the change event for table inputs/selects.
     * @param {JQuery.ChangeEvent} event - The change event object.
     * @param {string} tableId - The ID of the table where the event occurred.
     */
    private async handleInputChange(event: JQuery.ChangeEvent, tableId: string) {
        const target = $(event.target);
        const rowIndex = target.closest('tr').index();
        const columnIndex = target.closest('td').index();
        const value = target.val();

        // If the last row is selected, add a new row
        if (this.checkIfLastRow(target) && this.checkIfSelect(target)) {
            this.addNewRow(tableId);
        }

        const row = this.getRowByTableIdAndIndex(tableId, rowIndex);

        if (row && columnIndex >= 0) {
            this.updateRowData(row, columnIndex, value);

            switch (tableId) {
                case 'imports':
                    // Custom logic for imports table
                    break;
                case 'recipes':
                    ProductionLineFunctions.calculateProductionExport(row);

                    if (this.checkIfSelect(target)) {
                        await ProductionLineFunctions.updateRecipe(row, value);
                    }

                    this.updateRowInTable(tableId, rowIndex, row);

                    this.powerTableRows = PowerTableFunctions.calculateBuildings(this.productionTableRows);

                    const data: [ImportsTableRow[], number[]] = ImportsTableFunctions.calculateImports(this.productionTableRows);
                    this.importsTableRows = data[0];

                    this.UpdateOnIndex(data[1]);

                case 'power':
                    // Custom logic for power table
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Retrieves the row from the corresponding table by tableId and rowIndex.
     * @param {string} tableId - The ID of the table.
     * @param {number} rowIndex - The index of the row to retrieve.
     * @returns {any} The row object.
     */
    private getRowByTableIdAndIndex(tableId: string, rowIndex: number): any {
        switch (tableId) {
            case 'imports':
                return this.importsTableRows[rowIndex];
            case 'recipes':
                return this.productionTableRows[rowIndex];
            case 'power':
                return this.powerTableRows[rowIndex];
            default:
                return null;
        }
    }

    /**
     * Updates the row object with new data based on the column index.
     * @param {any} row - The row object to update.
     * @param {number} columnIndex - The index of the column.
     * @param {any} value - The new value to set in the row.
     */
    private updateRowData(row: any, columnIndex: number, value: any) {
        const rowKeys = Object.keys(row);
        const key = rowKeys[columnIndex];
        row[key] = value;
    }

    /**
     * Updates the visual representation of the row in the table.
     * @param {string} tableId - The ID of the table.
     * @param {number} rowIndex - The index of the row to update.
     * @param {any} row - The updated row object.
     */
    private updateRowInTable(tableId: string, rowIndex: number, row: any) {
        const table = $(`#${tableId} tbody tr`);
        const rowToUpdate = $(table[rowIndex]);

        rowToUpdate.find('input, select').each((index, element) => {
            const key = Object.keys(row)[index];
            let value: any;

            if (row.recipe && row.recipe.hasOwnProperty(key)) {
                // If row has a recipe, use recipe values
            } else {
                value = row[key];  // Use value from row
            }

            $(element).val(value);
        });

        if (tableId === 'recipes') {
            ProductionLineFunctions.handleDoubleExport(row, rowToUpdate);
        }
    }

    /**
     * Adds a new row to the table when the last row is modified.
     * @param {string} tableId - The ID of the table.
     */
    private addNewRow(tableId: string) {
        const lastRow = $(`#${tableId} tbody tr:last`);
        const newRow = lastRow.clone();
        newRow.find('input').val('');
        newRow.find('select').prop('selectedIndex', 0);
        newRow.insertAfter(lastRow);

        newRow.find('input, select').each((_, element) => {
            $(element).on('change', (event) => {
                this.handleInputChange(event, tableId);
            });
        });

        switch (tableId) {
            case 'imports':
                this.importsTableRows.push(new ImportsTableRow());
                break;
            case 'recipes':
                this.productionTableRows.push(new ProductionTableRow());
                break;
            case 'power':
                this.powerTableRows.push(new PowerTableRow());
                break;
            default:
                break;
        }
    }

    /**
     * Checks if the selected element is in the last row.
     * @param {JQuery} target - The target element.
     * @returns {boolean} True if the element is in the last row, false otherwise.
     */
    private checkIfLastRow(target: JQuery): boolean {
        return target.closest('tr').is(':last-child');
    }

    /**
     * Checks if the selected element is a <select> element.
     * @param {JQuery} target - The target element.
     * @returns {boolean} True if the element is a <select>, false otherwise.
     */
    private checkIfSelect(target: JQuery): boolean {
        return target.is('select');
    }

    private UpdateOnIndex(indexes: number[]) {
        for (let i = 0; i < indexes.length; i++) {
            const index = indexes[i];
            const row = this.productionTableRows[index];
            this.updateRowInTable('recipes', index, row);
        }
    }

}