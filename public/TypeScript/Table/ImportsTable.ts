import {Table} from "./TableBase";
import {Options, TableHeader} from "./Utils/TableHeader";
import {ProductionTable} from "./ProductionTable";
import {TableRow} from "./Utils/TableRow";


export class ImportsTable extends Table {

    private productionTable: ProductionTable;

    constructor(tableId: string, productionTable: ProductionTable, disableOnChange: boolean = false, skipReading: boolean = false) {

        super(tableId, disableOnChange);

        this.productionTable = productionTable;

        let options: Options;
        const select = $('#imports tbody tr:last td select');
        if (select.length > 0) {
            // @ts-ignore
            options = select.find('option').map((index, option) => {
                // check if it is disabled
                const disabled = option.disabled ? true : false;
                return {
                    value: option.value,
                    display: option.text,
                    disabled: disabled
                }
            }).get();
        } else {
            options = {};
        }

        this.tableHeaders = [
            new TableHeader('Item', 'select', false, options, 'imports_item_id[]'),
            new TableHeader('Amount', 'number', false, {}, 'imports_ammount[]'),
        ]

        if (!skipReading) {
            this.ReadRows();
        }
    }

    override async handleChange(event: Event) {

        // Get new changes
        await this.ReadRows();

        const $target = $(event.target as HTMLInputElement);
        const $row = $($target).closest('tr');

        if (this.checkIfLastRow($row) && this.checkIfSelect($($target))) {
            await this.addRow();
        }

        this.productionTable.handleChange(event);

    }

    public async calculateImport() {
        await this.processImports();

        await this.processTableRows();

        this.consoleLog();
        this.productionTable.consoleLog();

        this.addRow();
        this.renderTable();

        this.productionTable.addRow();
        this.productionTable.renderTable();
    }

    private async processImports() {
        const productionRows = this.productionTable.tableRows;

        this.deleteAllRows();

        for (const row of productionRows) {
            const recipeId = +row.cells[0];
            const quantity = +row.cells[1];

            // Fetching recipe imports and recipe data asynchronously
            const recipeImportsPromise: Promise<{ [key: string]: any }> = this.getRecipeImports(recipeId);
            const recipeDataPromise: Promise<{ [key: string]: any }> = this.getRecipe(recipeId);

            // Skip iteration if either promise is falsy
            if (!recipeImportsPromise || !recipeDataPromise) {
                continue;
            }

            // Wait for both promises to resolve
            const [imports, recipeData] = await Promise.all([recipeImportsPromise, recipeDataPromise]);

            const productionRate = quantity / recipeData['export_amount_per_min'];

            // Iterate through each import item
            imports.forEach((importItem: { [key: string]: any }) => {
                const importAmount = importItem['importAmount'] * productionRate;
                const existingImportIndex = this.checkIfImportAlreadyExists(importItem['itemId']);

                if (existingImportIndex !== false) {
                    // Update existing import amount
                    this.tableRows[existingImportIndex].cells[1] = Math.round(
                        +this.tableRows[existingImportIndex].cells[1] + importAmount
                    ).toString();
                } else if (importAmount > 0) {
                    // Add new row if import amount is positive and not already existing
                    this.addRow();
                    this.tableRows[this.tableRows.length - 1].cells = [importItem['itemId'], Math.round(importAmount)];
                }
            });

        }
    }

    private async processTableRows() {
        for (let i = 0; i < this.tableRows.length; i++) {
            const itemId = this.tableRows[i].cells[0];
            const alreadyProduced = await this.checkIfAlreadyProduced(itemId);

            if (alreadyProduced !== false) {
                let remainingAmount = +this.tableRows[i].cells[1];
                for (const {index, double} of alreadyProduced) {

                    remainingAmount = this.updateProductionRow(index, remainingAmount, double);

                    if (remainingAmount <= 0) {
                        this.deleteRow(i);
                        i--;
                        break;
                    }

                    this.tableRows[i].cells[1] = remainingAmount.toString();
                }
            }
        }
    }

    private checkIfImportAlreadyExists(itemId: string): number | false {
        for (let i = 0; i < this.tableRows.length; i++) {
            if (this.tableRows[i].cells[0] == itemId) {
                return i;
            }
        }
        return false;
    }

    /**
     * Check if the item is already produced
     *
     * @param itemId
     * @param productionTable
     * @private
     */

    private async checkIfAlreadyProduced(itemId: string): Promise<[{ double: boolean, index: number }] | false> {
        const itemName = await this.getItemName(+itemId);
        let returnData: [{ double: boolean, index: number }] = [{double: false, index: 0}];
        for (let i = 0; i < this.productionTable.tableRows.length; i++) {
            const row = this.productionTable.tableRows[i];

            if (row.cells[2] == itemName) {
                row.cells[3] = '0';
                row.cells[4] = row.cells[1];
                returnData.push({double: false, index: i});
            }

            if (row.doubleExport && row.extraCells[0] == itemName) {
                row.extraCells[1] = '0';
                const recipe = await this.getRecipe(+row.cells[0]);
                this.productionTable.handleDoubleExport(row, recipe, +row.cells[1]);
                returnData.push({double: true, index: i});
            }
        }
        if (returnData.length == 1) {
            return false;
        }
        returnData.shift();

        return returnData;
    }

    /**
     * Updates a production row based on the given index, remaining amount, and whether the row is "double".
     * @param index - The index of the production row.
     * @param remainingAmount - The remaining amount to be processed.
     * @param isDouble - Whether the row is a "double" row.
     * @returns The remaining amount after processing.
     */
    private updateProductionRow(index: number, remainingAmount: number, isDouble: boolean): number {
        if (isDouble) {
            return this.updateDoubleRow(index, remainingAmount);
        } else {
            return this.updateSingleRow(index, remainingAmount);
        }
    }

    /**
     * Updates a "double" production row.
     * @param index - The index of the production row.
     * @param remainingAmount - The remaining amount to be processed.
     * @returns The remaining amount after processing.
     */
    private updateDoubleRow(index: number, remainingAmount: number): number {
        const productionRow = this.productionTable.tableRows[index];
        const usedAmount = Math.min(remainingAmount, +productionRow.extraCells[2]);
        const excessAmount = remainingAmount - +productionRow.extraCells[2];

        productionRow.extraCells[1] = usedAmount.toString();
        productionRow.extraCells[2] = (+productionRow.extraCells[2] - usedAmount).toString();

        return excessAmount > 0 ? excessAmount : 0;
    }

    /**
     * Updates a single production row.
     * @param index - The index of the production row.
     * @param remainingAmount - The remaining amount to be processed.
     * @returns The remaining amount after processing.
     */
    private updateSingleRow(index: number, remainingAmount: number): number {
        const productionRow = this.productionTable.tableRows[index];
        const tableProductionAmount = +productionRow.cells[1];
        const usedAmount = Math.min(remainingAmount, tableProductionAmount);
        const excessAmount = remainingAmount - tableProductionAmount;

        // Update cells
        productionRow.cells[3] = usedAmount.toString();
        productionRow.cells[4] = (tableProductionAmount - usedAmount).toString();

        // Ensure any potential issue with consoleLog or similar methods
        return excessAmount > 0 ? excessAmount : 0;
    }


    /**
     * Get the recipe imports from the database
     * @param recipeId
     * @private
     */
    private async getRecipeImports(recipeId: number): Promise<object> {
        return new Promise(function (resolve, reject) {
            $.ajax({
                type: 'GET',
                url: 'getRecipeResources',
                data: {
                    id: recipeId
                },
                success: function (response) {
                    try {
                        // Parse the JSON response
                        resolve(JSON.parse(response));
                    } catch (error) {
                        reject(error);
                    }
                },
                error: function (xhr, status, error) {
                    reject(error);
                }
            });
        });
    }

    /**
     * Get the recipe from the database
     * @param itemId
     * @private
     */
    private async getItemName(itemId: number): Promise<string> {
        return new Promise(function (resolve, reject) {
            $.ajax({
                type: 'GET',
                url: 'getItemName',
                data: {
                    id: itemId
                },
                success: function (response) {
                    try {
                        // Parse the JSON response
                        resolve(response);
                    } catch (error) {
                        reject(error);
                    }
                },
                error: function (xhr, status, error) {
                    reject(error);
                }
            });
        });
    }
}