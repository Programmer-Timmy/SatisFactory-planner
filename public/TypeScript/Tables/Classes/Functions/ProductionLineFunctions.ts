import {Ajax} from "./Ajax";
import {ProductionTableRow} from "../Data/ProductionTableRow";
import {ExtraProductionRow} from "../Data/ExtraProductionRow";

export class ProductionLineFunctions {

    /**
     * Calculate the export production based on the quantity and usage of the row.
     * If the row has a double export, it calculates and updates the second export value.
     *
     * @param {any} row - The row object containing the production data.
     */
    public static async calculateProductionExport(row: any): Promise<void> {
        // Calculate the primary export based on quantity and usage
        row.exportPerMin = row.quantity - row.Usage;

        if (row.doubleExport && row.recipe !== null) {
            const secondExportPerMin = this.calculateSecondExportPerMin(row);
            if (secondExportPerMin !== undefined) {
                row.extraCells.Quantity = secondExportPerMin;
                row.extraCells.ExportPerMin = secondExportPerMin;
            }
        }
    }

    /**
     * Updates the recipe for the given row and recalculates related data, such as double export.
     *
     * @param {ProductionTableRow} row - The row object to update.
     * @param {string} value - The new recipe ID or value.
     * @returns {Promise<void>}
     */
    public static async updateRecipe(row: ProductionTableRow, value: string): Promise<void> {
        await this.saveRecipe(value, row);

        const recipe = row.recipe;
        if (recipe === null) return;

        row.recipeId = recipe.id;
        row.product = recipe.itemName;
        row.doubleExport = recipe.secondItemName !== null;

        if (row.doubleExport) {
            const exportPerMin = +recipe.export_amount_per_min;
            // @ts-ignore
            const secondExportPerMin = +recipe.export_amount_per_min2;

            const secondExportPerMinMultiplier = secondExportPerMin / exportPerMin;
            const quantityPerMin = row.quantity;

            // Update the extra cells for the second export
            row.extraCells = new ExtraProductionRow(
                // @ts-ignore
                recipe.secondItemName,
                0, // Assuming no usage for the second product
                quantityPerMin * secondExportPerMinMultiplier
            );
        }
    }

    /**
     * Saves the recipe data for a given recipe ID and row.
     *
     * @param {string} recipeId - The recipe ID to fetch.
     * @param {ProductionTableRow} row - The row object to store the recipe in.
     * @returns {Promise<void>}
     */
    public static async saveRecipe(recipeId: string, row: ProductionTableRow): Promise<void> {
        row.recipe = await Ajax.getRecipe(+recipeId);
    }

    /**
     * Handles the display and layout changes when a row has a double export,
     * creating a second row for the extra export if necessary.
     *
     * @param {ProductionTableRow} row - The row object to update.
     * @param {JQuery<HTMLElement>} rowToUpdate - The corresponding table row element.
     */
    public static handleDoubleExport(row: ProductionTableRow, rowToUpdate: JQuery<HTMLElement>): void {
        if (row.doubleExport && row.extraCells !== null) {
            if (!rowToUpdate.next('.extra-output').length) {
                // Modify first two columns to span 2 rows, adjust select/input height
                rowToUpdate.find('td:nth-child(2)').attr('rowspan', 2);
                rowToUpdate.find('td:nth-child(3)').attr('rowspan', 2);
                rowToUpdate.find('td:nth-child(2) .search-input').css('height', '78px');
                rowToUpdate.find('td:nth-child(3) input').css('height', '78px');

                // Add extra row for double export values
                const extraRow = $(`
                    <tr class="extra-output">
                        <td class="m-0 p-0">
                            <input type="text" name="product" value="${row.extraCells.Product}" class="form-control rounded-0" readonly>
                        </td>
                        <td class="m-0 p-0">
                            <input type="number" name="production_usage2[]" value="${row.extraCells.Usage}" class="form-control rounded-0" readonly step="any">
                        </td>
                        <td class="m-0 p-0">
                            <input type="number" name="production_export2[]" value="${row.extraCells.ExportPerMin}" class="form-control rounded-0" readonly step="any">
                        </td>
                    </tr>
                `);
                extraRow.insertAfter(rowToUpdate);
            } else {
                // Update values of the existing extra row
                const extraRow = rowToUpdate.next('.extra-output');
                const usage = extraRow.find('input[name="production_usage2[]"]');
                const exportPerMin = extraRow.find('input[name="production_export2[]"]');
                usage.val(row.extraCells.Usage);
                exportPerMin.val(row.extraCells.ExportPerMin);

            }
        } else if (rowToUpdate.next('.extra-output').length) {
            // Remove the extra row if double export is no longer active
            rowToUpdate.next('.extra-output').remove();

            // Reset the rowspan for the first two columns
            rowToUpdate.find('td:nth-child(2)').removeAttr('rowspan');
            rowToUpdate.find('td:nth-child(3)').removeAttr('rowspan');

            // Reset the input/select height
            rowToUpdate.find('td:nth-child(2) .search-input').css('height', '');
            rowToUpdate.find('td:nth-child(3) input').css('height', '');
        }
    }

    /**
     * Calculates the second export per minute for double export rows.
     *
     * @param {ProductionTableRow} row - The row object to calculate for.
     * @returns {number | undefined} - The calculated second export per minute or undefined if not applicable.
     */
    public static calculateSecondExportPerMin(row: ProductionTableRow): number | undefined {
        if (row.recipe === null) return;

        const secondExportPerMin = row.recipe.export_amount_per_min2;
        const exportPerMin = row.recipe.export_amount_per_min;

        if (secondExportPerMin === null || exportPerMin === null) return;

        const secondExportPerMinMultiplier = secondExportPerMin / exportPerMin;
        return row.quantity * secondExportPerMinMultiplier;
    }
}
