import {Table} from './TableBase';
import {TableHeader} from "./Utils/TableHeader";
import {Options} from "./Utils/TableHeader";
import {PowerTable} from "./PowerTable";
import {ImportsTable} from "./ImportsTable";
import {Settings} from "./Utils/Settings";

export class ProductionTable extends Table {

    public Settings: Settings = new Settings();

    constructor(tableId: string, disableOnChange: boolean = false) {
        super(tableId, disableOnChange);
        let options: Options;
        const select = $('#recipes tbody tr:last td select');
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
            new TableHeader('Recipe', 'select', false, options, 'production_recipe_id[]'),
            new TableHeader('Quantity Per/min', 'number', false, {}, 'production_quantity[]', '1', 0),
            new TableHeader('Product', 'text', true),
            new TableHeader('Usage Per/min', 'number', true, {}, 'production_usage[]', '0', 0),
            new TableHeader('Export Per/min', 'number', true, {}, 'production_export[]', '0', 0),
        ]

        this.ReadRows();
    }

    override async handleChange(event: Event) {
        // reading the rows
        await this.ReadRows();
        this.Settings.applyChanges();

        const powerTable = new PowerTable('power', this, true);
        const $target = $(event.target as HTMLInputElement);
        const $row = $($target).closest('tr');

        // check if the last row is. If it is and the select is selected add a new row
        if (this.checkIfLastRow($row) && this.checkIfSelect($($target))) {
            await this.addRow();
            this.renderTable();
        }

        // removing the last row
        this.deleteRow(-1)

        // preparing promises
        let promises = [await this.calculateExport()]
        this.Settings.autoPowerMachine ? promises.push(await powerTable.calculatePowerUsage()) : null;

        // calculating
        await Promise.all(promises)

        // adding row and rendering the table
        await this.addRow();
        this.renderTable();

        if (this.Settings.autoImportExport) {
            await new ImportsTable('imports', this, true, true).calculateImport();
        }
    }

    public async calculateExport() {
        // Fetch all recipes in parallel for performance improvement
        const recipesPromises = this.tableRows.map(row => this.getRecipe(+row.cells[0]));
        const recipes = await Promise.all(recipesPromises);

        for (let i = 0; i < this.tableRows.length; i++) {
            let row = this.tableRows[i];
            let quantityPerMin = +row.cells[1];
            let usagePerMin = +row.cells[3];
            let exportPerMin = quantityPerMin - usagePerMin;

            if (quantityPerMin < usagePerMin) {
                row.cells[3] = quantityPerMin.toString();
                continue;
            }

            // Validate input numbers and correct if needed
            if (!this.checkIfNumbersAreValid(quantityPerMin, usagePerMin, exportPerMin)) {
                this.correctInvalidNumbers(row, quantityPerMin, usagePerMin, exportPerMin);
                continue;
            }

            const recipe = recipes[i] as Record<string, any>; // Assume that the recipe data is an object with string keys
            row.cells[2] = recipe?.itemName || '';

            // Handle double export logic
            if (recipe.export_amount_per_min2 != null) {
                this.handleDoubleExport(row, recipe, quantityPerMin);
            } else if (row.doubleExport) {
                row.extraCells = [];
                row.doubleExport = false;
            }

            row.cells[4] = exportPerMin.toString();
        }
    }

    private correctInvalidNumbers(row: any, quantityPerMin: number, usagePerMin: number, exportPerMin: number) {
        if (quantityPerMin < 0) row.cells[1] = '0';
        if (usagePerMin < 0) row.cells[3] = '0';
        if (exportPerMin < 0) row.cells[4] = '0';
    }

    public handleDoubleExport(row: any, recipe: Record<string, any>, quantityPerMin: number) {
        const firstExportPerMin = +recipe.export_amount_per_min;
        const secondExportPerMin = +recipe.export_amount_per_min2;
        const secondExportPerMinMultiplier = secondExportPerMin / firstExportPerMin;

        if (row.doubleExport) {
            const originalSecondExportPerMin = quantityPerMin * secondExportPerMinMultiplier;
            const secondExportPerMinValue = quantityPerMin * secondExportPerMinMultiplier - +row.extraCells[1];
            row.extraCells[0] = recipe.secondItemName;
            this.updateDoubleExport(row, quantityPerMin, row.extraCells[1], secondExportPerMinValue);

            if (secondExportPerMinValue < +row.extraCells[1]) {
                row.extraCells[1] = originalSecondExportPerMin.toString();
            }
        } else {
            const secondExportPerMinValue = quantityPerMin * secondExportPerMinMultiplier;
            row.extraCells = [recipe.secondItemName, '0', secondExportPerMinValue.toString()];
            row.doubleExport = true;
        }

    }

    private updateDoubleExport(row: any, quantityPerMin: number, extraUsagePerMin: number, secondExportPerMin: number) {
        if (!this.checkIfNumbersAreValid(quantityPerMin, +extraUsagePerMin, secondExportPerMin)) {
            if (quantityPerMin < 0) row.cells[1] = '0';
            if (+extraUsagePerMin < 0) row.extraCells[1] = '0';
            if (secondExportPerMin < 0) row.extraCells[2] = '0';
        } else {
            row.extraCells[2] = secondExportPerMin.toString();
        }
    }

    private checkIfNumbersAreValid(quantityPerMin: number, usagePerMin: number, exportPerMin: number): boolean {
        if (quantityPerMin < 0 || usagePerMin < 0 || exportPerMin < 0) {
            return false;
        }
        if (quantityPerMin < usagePerMin || usagePerMin > quantityPerMin) {
            return false;
        }
        return true
    }
}