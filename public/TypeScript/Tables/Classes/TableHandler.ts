import {ImportsTableRow} from "./Data/ImportsTableRow";
import {ProductionTableRow} from "./Data/ProductionTableRow";
import {PowerTableRow} from "./Data/PowerTableRow";
import {ProductionLineFunctions} from "./ProductionLineFunctions";
import {ExtraProductionRow} from "./Data/ExtraProductionRow";
import {Ajax} from "./Ajax";

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

    // Generic method to read tables
    private readTable<T>(id: string, rowClass: { new(...args: any[]): T }): T[] {
        // Retrieve table rows from the DOM
        const table = $(`#${id} tbody tr`);
        const rows: T[] = [];

        for (let i = 0; i < table.length; i++) {
            const row = table[i];
            const values = $(row).find('input, select');
            const rowValues: any[] = [];

            for (let j = 0; j < values.length; j++) {
                const value = values[j];
                rowValues.push($(value).val());
            }

            // Handle dubble export
            if (id === 'recipes' && table[i + 1]?.classList.contains('extra-output')) {
                const extraRow = table[i + 1];
                const extraRowValues = $(extraRow).find('input, select').map((_, el) => $(el).val()).get();

                // Create instance of ExtraProductionRow
                const extraRowInstance = new ExtraProductionRow(
                    extraRowValues[0] as string,          // Product
                    Number(extraRowValues[1]),            // Usage (number)
                    Number(extraRowValues[2])             // ExportPerMin (number)
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

    private addEventListeners() {
        // Iterate through each table row
        const tables = ['imports', 'recipes', 'power'];

        tables.forEach((tableId) => {
            // Select all input and select elements for the given table
            const inputsAndSelects = $(`#${tableId} tbody`).find('input, select');

            // Attach event listeners to each input/select
            inputsAndSelects.each((index, element) => {
                $(element).on('change', (event) => {
                    console.log('Event triggered:', event);
                    this.handleInputChange(event, tableId);
                });
            });
        });
    }

    private async handleInputChange(event: JQuery.ChangeEvent, tableId: string) {
        const target = $(event.target);
        const rowIndex = target.closest('tr').index();
        const columnIndex = target.closest('td').index();
        const value = target.val();

        // if last row and select is selected add a new row
        if (this.checkIfLastRow(target) && this.checkIfSelect(target)) {
            this.addNewRow(tableId);
        }

        // Get the corresponding row based on the tableId and row index
        const row = this.getRowByTableIdAndIndex(tableId, rowIndex);

        console.log('Row:', row);

        if (row && columnIndex >= 0) {
            // Dynamically update the property on the row object
            this.updateRowData(row, columnIndex, value);

            switch (tableId) {
                case 'imports':
                    // Do something with the imports table
                    break;
                case 'recipes':
                    ProductionLineFunctions.calculateProductionExport(row);

                    // if changed element is select
                    if (this.checkIfSelect(target)) {
                        await ProductionLineFunctions.updateRecipe(row, tableId, rowIndex, value);
                    }

                    this.updateRowInTable(tableId, rowIndex, row);

                    break;
                case 'power':
                    // Do something with the power table
                    break;
                default:
                    break;
            }

        }
    }

// Helper function to get the correct row from the corresponding table
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

// Function to update specific data in the row object based on column name
    private updateRowData(row: any, columnIndex: number, value: any) {
        const rowKeys = Object.keys(row);
        const key = rowKeys[columnIndex];
        row[key] = value;
    }

    private updateRowInTable(tableId: string, rowIndex: number, row: any) {
        const table = $(`#${tableId} tbody tr`);
        const rowToUpdate = $(table[rowIndex]);

        // Update the row in the table
        rowToUpdate.find('input, select').each((index, element) => {
            const key = Object.keys(row)[index];
            $(element).val(row[key]);
        });

        if (row.doubleExport) {
            // change the first and second column to rowspawn 2 and height for the select and input to 78px
            rowToUpdate.find('td:first').attr('rowspan', 2);
            rowToUpdate.find('td:nth-child(2)').attr('rowspan', 2);
            rowToUpdate.find('td:first select').css('height', '78px');
            rowToUpdate.find('td:nth-child(2) input').css('height', '78px');

            // add new row under the current row
            const extraRow = $(`<tr class="extra-output">
                <td class="m-0 p-0"><input type="text" name="product" value="${row.extraCells.Product}" class="form-control rounded-0" readonly></td>
                <td class="m-0 p-0"><input type="number" name="usage" value="${row.extraCells.Usage}" class="form-control rounded-0" readonly step="any"></td>
                <td class="m-0 p-0"><input type="number" name="exportPerMin" value="${row.extraCells.ExportPerMin}" class="form-control rounded-0" readonly step="any"></td>
            </tr>`);

            extraRow.insertAfter(rowToUpdate);
        } else {
            // remove the extra row
            rowToUpdate.next('.extra-output').remove();

            // reset the first and second column to rowspawn 1 and remove height for the select and input
            rowToUpdate.find('td:first').attr('rowspan', 1);
            rowToUpdate.find('td:nth-child(2)').attr('rowspan', 1);
            rowToUpdate.find('td:first select').css('height', '');
            rowToUpdate.find('td:nth-child(2) input').css('height', '');

        }
    }

    private addNewRow(tableId: string) {
        const lastRow = $(`#${tableId} tbody tr:last`);
        const newRow = lastRow.clone();
        newRow.find('input').val('');
        newRow.find('select').prop('selectedIndex', 0);

        newRow.insertAfter(lastRow);

        // Attach event listeners to the new row
        newRow.find('input, select').each((index, element) => {
            $(element).on('change', (event) => {
                console.log('Event triggered:', event);
                this.handleInputChange(event, tableId);
            });
        });

        // Add new row to the corresponding tableRows array
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

    private checkIfLastRow(target: JQuery) {
        return target.closest('tr').is(':last-child');
    }

    private checkIfSelect(target: JQuery) {
        return target.is('select');
    }


}
