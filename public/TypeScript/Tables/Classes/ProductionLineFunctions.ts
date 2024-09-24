import {Ajax} from "./Ajax";
import {ExtraProductionRow} from "./Data/ExtraProductionRow";

export class ProductionLineFunctions {

    public static calculateProductionExport(row: any) {
        // Calculate the export based on the production quantity and usage
        row.exportPerMin = row.quantity - row.Usage;
    }

    public static async updateRecipe(row: any, tableId: string, rowIndex: number, value: string) {
        const recipeId = value;
        const recipe = Ajax.getRecipe(+recipeId);

        // Update the row with the recipe data
        await recipe.then((data) => {
            console.log(data);
            const recipeData = data as Record<string, any>;
            row.product = recipeData.itemName;
            row.doubleExport = recipeData.secondItemName !== null;

            if (row.doubleExport) {
                const exportPerMin = +recipeData.export_amount_per_min;
                const secondExportPerMin = +recipeData.export_amount_per_min2;

                const secondExportPerMinMultiplier = secondExportPerMin / exportPerMin;
                const quantityPerMin = row.quantity;

                const extraRow = new ExtraProductionRow(
                    recipeData.secondItemName,
                    0,
                    quantityPerMin * secondExportPerMinMultiplier
                );

                row.extraCells = extraRow;
            }
        });

        return row;
    }
}