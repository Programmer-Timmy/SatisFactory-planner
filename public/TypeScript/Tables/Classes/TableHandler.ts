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
        let lengthReduction = 0;

        if (id === 'power') {
            lengthReduction = 1;
        }

        for (let i = 0; i < table.length - lengthReduction; i++) {
            const row = table[i];
            const values = $(row).find('input, select');
            const rowValues: any[] = [];

            values.each((_, value) => {
                const type = $(value).attr('type');
                if (type === 'number') {
                    rowValues.push(Number($(value).val()));
                } else {
                    rowValues.push($(value).val());
                }
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

    private addSpecificEventListener(tableId: string) {
        const inputsAndSelects = $(`#${tableId} tbody`).find('input, select');

        inputsAndSelects.each((_, element) => {
            $(element).on('change', (event) => {
                this.handleInputChange(event, tableId);
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
        const amountExtra = target.closest('tr').prevAll('.extra-output').length;
        const columnIndex = target.closest('td').index();
        const value = target.val();

        // If the last row is selected, add a new row
        if (this.checkIfLastRow(target, tableId) && this.checkIfSelect(target)) {
            this.addNewRow(tableId);
        }


        const row = this.getRowByTableIdAndIndex(tableId, rowIndex - amountExtra);

        if (row && columnIndex >= 0) {
            this.updateRowData(row, columnIndex, value);

            switch (tableId) {
                case 'imports':
                    // Custom logic for imports table
                    break;
                case 'recipes':
                    await this.HandleProductionTable(row, rowIndex, value, tableId, target);
                    break;
                case 'power':
                    await this.HandlePowerTable(row, rowIndex, value, tableId, target);
                    break;
                default:
                    break;
            }
        }

        console.log(this.productionTableRows);

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
        let rowToUpdate = $(table[rowIndex]);

        rowToUpdate.find('input, select').each((index, element) => {
            const key = Object.keys(row)[index];
            let value: any;

            if (row.recipe && row.recipe.hasOwnProperty(key)) {
                value = row.recipe[key];  // Use value from recipe
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
        let lastRow
        if (tableId === 'power') {
            lastRow = $(`#${tableId} tbody tr:nth-last-child(2)`);
        } else {
            lastRow = $(`#${tableId} tbody tr:last`);
        }

        const newRow = lastRow.clone();
        newRow.find('input[type="number"]').val(0);
        newRow.find('input[type="text"]').val('');
        newRow.find('input[name="power_clock_speed[]"]').val(100);
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
     * @param {string} tableId - The ID of the table.
     * @returns {boolean} True if the element is in the last row, false otherwise.
     */
    private checkIfLastRow(target: JQuery, tableId: String): boolean {
        if (tableId === 'power') {
            return target.closest('tr').is(':nth-last-child(2)');
        }
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
            const target = $(`#recipes tbody tr:eq(${index})`);
            const amountExtra = target.closest('tr').prevAll('.extra-output').length;
            const row = this.productionTableRows[index];
            this.updateRowInTable('recipes', index + amountExtra, row);


        }
    }

    /**
     * Handles the change event for the power table.
     * @param row - The row object to update.
     * @param rowIndex - The index of the row in the table.
     * @param value - The new value to set in the row.
     * @param tableId - The ID of the table.
     * @param target - The target element that triggered the event.
     * @constructor
     * @private
     */
    private async HandlePowerTable(row: PowerTableRow, rowIndex: number, value: any, tableId: string, target: JQuery) {
        if (this.checkIfSelect(target)) {
            await PowerTableFunctions.updateBuilding(row, value);
        } else {
            row.Consumption = PowerTableFunctions.calculateSingleConsumption(row);
        }
        this.updateRowInTable(tableId, rowIndex, row);
        PowerTableFunctions.updateTotalConsumption(this.powerTableRows);
    }

    /**
     * Handles the change event for the production table.
     * @param row - The row object to update.
     * @param rowIndex - The index of the row in the table.
     * @param value - The new value to set in the row.
     * @param tableId - The ID of the table.
     * @param target - The target element that triggered the event.
     * @constructor
     * @private
     */

    private async HandleProductionTable(row: ProductionTableRow, rowIndex: number, value: any, tableId: string, target: JQuery) {
        await ProductionLineFunctions.calculateProductionExport(row);

        if (this.checkIfSelect(target)) {
            await ProductionLineFunctions.updateRecipe(row, value);
        }

        this.updateRowInTable(tableId, rowIndex, row);

        this.powerTableRows = PowerTableFunctions.calculateBuildings(this.productionTableRows, this.powerTableRows);
        this.addSpecificEventListener('power');

        const data: {
            importsTableRows: ImportsTableRow[],
            indexes: number[]
        } = ImportsTableFunctions.calculateImports(this.productionTableRows);
        this.importsTableRows = data.importsTableRows;
        this.UpdateOnIndex(data.indexes);
    }
}
