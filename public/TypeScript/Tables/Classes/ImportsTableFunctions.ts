import {ProductionTableRow} from "./Data/ProductionTableRow";
import {ImportsTableRow} from "./Data/ImportsTableRow";
import {ProductionTable} from "../Table/ProductionTable";
import {Resource} from "./Types/Resource";

export class ImportsTableFunctions {

    public static calculateImports(productionTableRows: ProductionTableRow[]): [ImportsTableRow[], number[]] {
        console.log(productionTableRows);
        let importsTableRows: ImportsTableRow[] = [];
        let updatedIndexes: number[] = []; // Array to track updated indexes

        for (const row of productionTableRows) {
            row.Usage = 0; // Reset usage
        }

        for (let i = 0; i + 1 < productionTableRows.length; i++) {
            const row: ProductionTableRow = productionTableRows[i];
            const requiredItems: Resource[] | undefined = row.recipe?.resources;

            // Calculate production rate based on export_amount_per_min
            const productionRate: number = row.recipe?.export_amount_per_min ? row.quantity / row.recipe.export_amount_per_min : 0;

            if (requiredItems) {
                for (const requiredItem of requiredItems) {
                    let amountNeeded = requiredItem.importAmount * productionRate;
                    console.log(requiredItem.name, amountNeeded);

                    // Get produced rows that match the required item
                    const producedRows = productionTableRows.filter(r => r.product === requiredItem.name);

                    let totalAvailable = 0; // Track total available quantity from produced rows
                    let totalUsed = 0; // Track total usage from produced rows

                    // Check each produced row
                    for (const producedRow of producedRows) {
                        console.log(producedRow);
                        const availableAmount = producedRow.quantity - producedRow.Usage;

                        // Calculate how much we can use from this row
                        const canUse = Math.min(availableAmount, amountNeeded - totalUsed);
                        console.log('Can use:', canUse);

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

                    // If there is still a need for imports after using available production
                    const amountToImport = amountNeeded - totalUsed;
                    console.log('Amount to import:', amountToImport);
                    if (amountToImport > 0) {
                        // Add to importsTableRows
                        importsTableRows.push(new ImportsTableRow(requiredItem.itemId, amountToImport));
                    }
                }
            }
        }

        console.log(importsTableRows);
        console.log(updatedIndexes);

        return [importsTableRows, updatedIndexes];
    }


}