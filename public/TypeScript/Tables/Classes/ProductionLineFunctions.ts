import {Ajax} from "./Ajax";
import {ExtraProductionRow} from "./Data/ExtraProductionRow";
import {ProductionTableRow} from "./Data/ProductionTableRow";

export class ProductionLineFunctions {

    public static calculateProductionExport(row: any): void {
        // Calculate the export based on the production quantity and usage
        row.exportPerMin = row.quantity - row.Usage;

        if (row.doubleExport) {
            if (row.recipe === null) {
                return;
            }

            const secondExportPerMin = this.calculateSecondExportPerMin(row);

            if (secondExportPerMin === undefined) {
                return;
            }
            row.extraCells.exportPerMin = secondExportPerMin;
        }
    }

    public static async updateRecipe(row: ProductionTableRow, value: string,): Promise<void> {
        await this.saveRecipe(value, row);
        const recipe: { [p: string]: any } | null = row.recipe;

        if (recipe === null) {
            return;
        }

        row.recipeId = recipe.id;
        row.product = recipe.itemName;
        row.doubleExport = recipe.secondItemName !== null;

        if (row.doubleExport) {
            const exportPerMin = +recipe.export_amount_per_min;
            const secondExportPerMin = +recipe.export_amount_per_min2;

            const secondExportPerMinMultiplier = secondExportPerMin / exportPerMin;
            const quantityPerMin = row.quantity;

            row.extraCells = new ExtraProductionRow(
                recipe.secondItemName,
                0,
                quantityPerMin * secondExportPerMinMultiplier
            );
        }
    }

    public static async saveRecipe(recipeId: string, row: ProductionTableRow) {
        row.recipe = await Ajax.getRecipe(+recipeId);
    }

    private static calculateSecondExportPerMin(row: ProductionTableRow): number | undefined {
        if (row.recipe === null) {
            return;
        }

        const secondExportPerMin = row.recipe.export_amount_per_min2;
        const exportPerMin = row.recipe.export_amount_per_min;

        if (secondExportPerMin === null || exportPerMin === null) {
            return;
        }

        const secondExportPerMinMultiplier = secondExportPerMin / exportPerMin;

        return row.quantity * secondExportPerMinMultiplier;
    }
}