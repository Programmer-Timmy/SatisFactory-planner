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

        await this.calculateImport();
    }

    public async calculateImport() {
        const productionRows = this.productionTable.tableRows;

        this.deleteAllRows();

        for (const row of productionRows) {
            const recipe = row.cells[0];
            const quantity = row.cells[1];
            const recipeImports : Promise<{[key: string]: any}> = this.getRecipeImports(+recipe);
            const recipeData: Promise<{[key: string]: any}> = this.getRecipe(recipe);

            if (!recipeImports || !recipeData) {
                continue;
            }

            await Promise.all([recipeImports, recipeData]).then((values) => {
                const imports :{[key: string]: any} = values[0];
                const recipeData :{[key: string]: any} = values[1];

                const perMin = +quantity / recipeData['export_amount_per_min']

                imports.forEach((key: {[key: string]: any}) => {
                    let amount = key['importAmount'] * perMin;
                    const existingImport = this.checkIfImportAlreadyExists(key['itemId']);

                    if (existingImport !== false) {
                        this.tableRows[existingImport].cells[1] = Math.round((+this.tableRows[existingImport].cells[1] + amount)* 1).toString();
                        return;
                    }

                    if (amount > 0) {
                        this.addRow();
                        this.tableRows[this.tableRows.length - 1].cells = [key['itemId'],  Math.round(amount * 1)];
                    }
                });
            });
        }

        for (let i = 0; i < this.tableRows.length -2 ; i++) {
            const itemId = this.tableRows[i].cells[0];
            const alreadyProdused = await this.checkIfAlreadyProdused(itemId, this.productionTable);
            let amount = +this.tableRows[i].cells[1];

            if (alreadyProdused !== false) {
                const index = alreadyProdused.index;

                if (alreadyProdused.double) {
                    let exesAmount = amount - +this.productionTable.tableRows[index].extraCells[2];
                    let usageAmount = amount;

                    if (exesAmount > 0) {
                        usageAmount = +this.productionTable.tableRows[index].extraCells[1];
                    }

                    this.productionTable.tableRows[index].extraCells[1] = usageAmount.toString();
                    this.productionTable.tableRows[index].extraCells[2] = (+this.productionTable.tableRows[index].extraCells[2] - +this.productionTable.tableRows[index].extraCells[1]).toString();

                    if (exesAmount <= 0) {
                        this.deleteRow(i);
                        i--;
                        continue;
                    }

                    amount = exesAmount;

                    this.tableRows[i].cells[1] = amount.toString();
                } else {

                    let usageAmount = amount;
                    let exesAmount = amount - +this.productionTable.tableRows[index].cells[3];

                    if (exesAmount > 0) {
                        usageAmount = +this.productionTable.tableRows[index].cells[1];
                    }

                    this.productionTable.tableRows[index].cells[3] = usageAmount.toString();
                    this.productionTable.tableRows[index].cells[4] = (+this.productionTable.tableRows[index].cells[1] - +this.productionTable.tableRows[index].cells[3]).toString();

                    if (exesAmount <= 0) {
                        this.deleteRow(i);
                        i--;
                        continue;
                    }

                    amount = exesAmount;

                    this.tableRows[i].cells[1] = amount.toString();
                }
            }
        }
        this.addRow();
        this.renderTable();

        this.productionTable.addRow();
        this.productionTable.renderTable();
    }

    private checkIfImportAlreadyExists(itemId: string): number | false {
        for (let i = 0; i < this.tableRows.length; i++) {
            if (this.tableRows[i].cells[0] == itemId) {
                return i;
            }
        }
        return false;
    }

    private async checkIfAlreadyProdused(itemId: string, productionTable: ProductionTable): Promise<{double: boolean, index: number} | false>{
        const itemName = await this.getItemName(+itemId);
        for (let i = 0; i < productionTable.tableRows.length; i++) {
            if (productionTable.tableRows[i].cells[2] == itemName) {
                return {double: false, index: i};
            }
            if (productionTable.tableRows[i].doubleExport && productionTable.tableRows[i].extraCells[0] == itemName) {
                return {double: true, index: i};
            }
        }
        return false
    }

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