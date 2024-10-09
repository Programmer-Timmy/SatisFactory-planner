import {ProductionTableRow} from "../Data/ProductionTableRow";
import {ImportsTableRow} from "../Data/ImportsTableRow";
import {Resource} from "../Types/Resource";
import {HtmlGeneration} from "./HtmlGeneration";


export class ImportsTableFunctions {
    /**
     * Calculates the imports required based on production table rows.
     *
     * @param productionTableRows - An array of production table rows.
     * @returns A tuple containing the imports table rows and updated indexes.
     */
    public static calculateImports(productionTableRows: ProductionTableRow[]): {
        importsTableRows: ImportsTableRow[],
        indexes: number[]
    } {
        let importsTableRows: ImportsTableRow[] = [];

        // Reset the usage property for all production table rows
        let updatedIndexes: number[] = this.resetUsage(productionTableRows);

        // Loop through each production row to calculate imports
        for (let i = 0; i + 1 < productionTableRows.length; i++) {
            const row: ProductionTableRow = productionTableRows[i];
            const requiredItems: Resource[] | undefined = row.recipe?.resources;

            // Calculate production rate based on export_amount_per_min
            const productionRate: number = this.calculateProductionRate(row);

            if (requiredItems) {
                this.processRequiredItems(requiredItems, productionRate, row, productionTableRows, importsTableRows, updatedIndexes);
            }
        }

        const html = HtmlGeneration.generateImportsTableRows(importsTableRows);
        $('#imports tbody').html(html);

        importsTableRows.push(new ImportsTableRow());

        return {importsTableRows, indexes: updatedIndexes};
    }

    /**
     * Resets the usage property for all production table rows.
     *
     * @param productionTableRows - An array of production table rows.
     */
    private static resetUsage(productionTableRows: ProductionTableRow[]): number[] {
        const changedRows: number[] = [];
        for (const row of productionTableRows) {
            const rowUsage = row.Usage;
            if (row.Usage > 0) {
                changedRows.push(productionTableRows.indexOf(row));
                row.Usage = 0;
                row.exportPerMin = row.quantity;
            }

            if (row.extraCells !== null) {
                if (row.extraCells.Usage > 0) {
                    let rowIndex = productionTableRows.indexOf(row);
                    if (rowIndex !== -1 && !changedRows.includes(rowIndex)) {
                        changedRows.push(rowIndex);
                    }
                    row.extraCells.Usage = 0;
                    row.extraCells.ExportPerMin = row.extraCells.Quantity;
                }
            }
        }

        return changedRows;
    }

    /**
     * Calculates the production rate based on the provided production row.
     *
     * @param row - The production table row to calculate the production rate for.
     * @returns The calculated production rate.
     */
    private static calculateProductionRate(row: ProductionTableRow): number {
        return row.recipe?.export_amount_per_min ? row.quantity / row.recipe.export_amount_per_min : 0;
    }

    /**
     * Processes required items for imports based on the production table rows.
     *
     * @param requiredItems - An array of resources required for production.
     * @param productionRate - The production rate of the current row.
     * @param row - The current production table row being processed.
     * @param productionTableRows - The complete array of production table rows.
     * @param importsTableRows - The array of imports table rows to update.
     * @param updatedIndexes - The array to track updated production row indexes.
     */
    private static processRequiredItems(
        requiredItems: Resource[],
        productionRate: number,
        row: ProductionTableRow,
        productionTableRows: ProductionTableRow[],
        importsTableRows: ImportsTableRow[],
        updatedIndexes: number[]
    ): void {
        for (const requiredItem of requiredItems) {
            let amountNeeded = requiredItem.importAmount * productionRate;

            // Get produced rows that match the required item
            const producedRows = productionTableRows.filter(r => r.product === requiredItem.name);

            // get double export rows
            const doubleExportRow = productionTableRows.filter(r => r.extraCells !== null && r.extraCells.Product === requiredItem.name);

            let totalAvailable = 0; // Track total available quantity from produced rows
            let totalUsed = 0; // Track total usage from produced rows

            // Check each produced row
            const {
                totalUsed: used,
                totalAvailable: available
            } = this.processProducedRows(producedRows, amountNeeded, totalUsed, totalAvailable, productionTableRows, updatedIndexes);
            totalUsed = used;
            totalAvailable = available;

            // Check each double export row
            const {
                totalUsed: used2,
                totalAvailable: available2
            } = this.processDoubleExportRows(doubleExportRow, amountNeeded, totalUsed, totalAvailable, productionTableRows, updatedIndexes);
            totalUsed = used2;
            totalAvailable = available2;


            // If there is still a need for imports after using available production
            const amountToImport = amountNeeded - totalUsed;
            if (amountToImport > 0) {
                this.addToImportsTable(importsTableRows, requiredItem.itemId, amountToImport);
            }
        }
    }

    /**
     * Processes the produced rows to determine how much can be used for imports.
     * @param producedRows - The array of produced rows to process.
     * @param amountNeeded - The total amount needed for imports.
     * @param totalUsed - The total amount used from produced rows.
     * @param totalAvailable - The total amount available from produced rows.
     * @param productionTableRows - The complete array of production table rows.
     * @param updatedIndexes - The array to track updated production row indexes.
     * @private
     */
    private static processProducedRows(
        producedRows: ProductionTableRow[],
        amountNeeded: number,
        totalUsed: number,
        totalAvailable: number,
        productionTableRows: ProductionTableRow[],
        updatedIndexes: number[]
    ): { totalUsed: number; totalAvailable: number } {

        for (const producedRow of producedRows) {
            const availableAmount = producedRow.quantity - producedRow.Usage;

            // Calculate how much we can use from this row
            const canUse = Math.min(availableAmount, amountNeeded - totalUsed);

            if (canUse <= 0) {
                continue;
            }

            // Update usage for this produced row
            producedRow.Usage += +canUse.toFixed(2);
            producedRow.exportPerMin = +(producedRow.quantity - producedRow.Usage).toFixed(2);

            // Update the total used amount
            totalUsed += canUse;
            totalAvailable += availableAmount; // Count how much is available from this row

            const index = productionTableRows.indexOf(producedRow);
            if (index !== -1 && !updatedIndexes.includes(index)) {
                updatedIndexes.push(index);
            }
        }

        return {totalUsed, totalAvailable};
    }

    private static processDoubleExportRows(
        doubleExportRows: ProductionTableRow[],
        amountNeeded: number,
        totalUsed: number,
        totalAvailable: number,
        productionTableRows: ProductionTableRow[],
        updatedIndexes: number[]
    ): { totalUsed: number; totalAvailable: number } {

        for (const doubleExport of doubleExportRows) {
            if (doubleExport.extraCells === null) {
                continue;
            }

            const availableAmount = doubleExport.extraCells.ExportPerMin - doubleExport.extraCells.Usage;

            // Calculate how much we can use from this row
            const canUse = Math.min(availableAmount, amountNeeded - totalUsed);

            if (canUse <= 0) {
                continue;
            }

            // Update usage for this extra row
            doubleExport.extraCells.Usage += +canUse.toFixed(2);
            doubleExport.extraCells.ExportPerMin = +(doubleExport.extraCells.ExportPerMin - doubleExport.extraCells.Usage).toFixed(2);

            // Update the total used amount
            totalUsed += canUse;
            totalAvailable += availableAmount; // Count how much is available from this row

            const index = productionTableRows.indexOf(doubleExport);
            if (index !== -1 && !updatedIndexes.includes(index)) {
                updatedIndexes.push(index);
            }
        }

        return {totalUsed, totalAvailable};
    }



    /**
     * Adds the required amount to the imports table rows.
     *
     * @param importsTableRows - The imports table rows to update.
     * @param itemId - The ID of the item to import.
     * @param amountToImport - The amount of the item to import.
     */
    private static addToImportsTable(importsTableRows: ImportsTableRow[], itemId: number, amountToImport: number): void {
        const existingImportRow = importsTableRows.find(r => r.itemId === itemId);

        if (existingImportRow) {
            existingImportRow.quantity += amountToImport;
        } else {
            importsTableRows.push(new ImportsTableRow(itemId, amountToImport));
        }
    }


}
