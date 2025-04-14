import {ProductionTableRow} from "../Data/ProductionTableRow";
import {PowerTableRow} from "../Data/PowerTableRow";
import {buildingOptions} from "../Data/BuildingOptions";
import {HtmlGeneration} from "./HtmlGeneration";
import {Ajax} from "./Ajax";
import {Recipe} from "../Types/Recipe";

export class PowerTableFunctions {

    public static calculateBuildings(productionTableRows: ProductionTableRow[], oldPowerTableRows: PowerTableRow[]): PowerTableRow[] {
        // get the user rows
        const userRows = oldPowerTableRows.filter(row => row.userRow == true);

        // remove the old add row
        userRows.pop();

        let powerTableRows: PowerTableRow[] = [];
        for (let i = 0; i + 1 < productionTableRows.length; i++) {
            const row = productionTableRows[i];
            const recipe = row.recipe;
            if (recipe !== null) {
                const building = recipe.building;
                const existingRow = powerTableRows.find(row => row.buildingId === building.id);

                const maxClockSpeed = row.recipeSetting?.clockSpeed || 100;
                const useSomersloop = row.recipeSetting?.useSomersloop || false; // dubbels the output

                let amountOfBuilding = PowerTableFunctions.calculateBuildingAmount(recipe, row);
                let exes = 0;

                const consumption = +PowerTableFunctions.calculateConsumption(amountOfBuilding, maxClockSpeed, building.power_used, useSomersloop);

                if (amountOfBuilding % 1 !== 0) {
                    exes = amountOfBuilding % 1;
                    amountOfBuilding = Math.floor(amountOfBuilding);
                }

                if (existingRow) {
                    existingRow.quantity += amountOfBuilding;
                    existingRow.Consumption = +PowerTableFunctions.calculateConsumption(existingRow.quantity, maxClockSpeed, building.power_used, useSomersloop);
                    existingRow.clockSpeed = maxClockSpeed;
                } else {
                    powerTableRows.push(new PowerTableRow(building.id, amountOfBuilding, maxClockSpeed, consumption, false, building));

                }

                if (exes > 0) {
                    const clockSpeed = exes * 100;
                    if (clockSpeed < 1) {
                        continue;
                    }
                    const consumption = +PowerTableFunctions.calculateConsumption(1, clockSpeed, building.power_used, useSomersloop);
                    powerTableRows.push(new PowerTableRow(building.id, 1, +clockSpeed.toFixed(5), consumption, false, building));
                }
            }
        }

        // Add user rows to the power table
        powerTableRows = powerTableRows.concat(userRows);

        const html = HtmlGeneration.generatePowerTable(powerTableRows, buildingOptions, PowerTableFunctions.calculateTotalConsumption(powerTableRows));
        $('#power tbody').html(html);

        powerTableRows.push(new PowerTableRow());

        return powerTableRows

    }

    public static calculateBuildingAmount(recipe: Recipe, row: ProductionTableRow): number {
        const amount = row.quantity;

        const maxClockSpeed = row.recipeSetting?.clockSpeed || 100;
        const useSomersloop = row.recipeSetting?.useSomersloop || false; // dubbels the output

        return amount / (recipe.export_amount_per_min * (maxClockSpeed / 100)) / (useSomersloop ? 2 : 1);
    }

    private static calculateConsumption(amount: number, ClockSpeed: number, Consumption: number, useSomersloop: boolean): number {
        // (1 + filledSlots / totalSlots) ^ 2
        const powerMultiplier = Math.pow((1 + (useSomersloop ? 4 : 0) / 4), 2);
        const clockSpeed = Math.pow(ClockSpeed / 100, 1.321928);
        return +(amount * Consumption * powerMultiplier * clockSpeed).toFixed(5);
    }

    static calculateTotalConsumption(table: PowerTableRow[]): number {
        const filteredTable = table.filter(row => row.Consumption !== 0);
        const totalConsumption = filteredTable.reduce((acc, row) => {
            const consumption = Number(row.Consumption) || 0; // Ensure it's a number
            return acc + consumption;
        }, 0);

        return parseFloat(totalConsumption.toFixed(5)); // Limit to 3 decimal places
    }

    public static calculateSingleConsumption(row: PowerTableRow): number {
        if (row.building === null) return 0;

        const clockSpeed = Math.pow(row.clockSpeed / 100, 1.321928);
        return +(row.quantity * row.building.power_used * clockSpeed).toFixed(5);
    }

    public static async updateBuilding(row: PowerTableRow, buildingId: number): Promise<void> {
        row.building = await Ajax.getBuilding(buildingId);
        row.buildingId = buildingId;
        row.Consumption = +PowerTableFunctions.calculateSingleConsumption(row);
    }

    public static updateTotalConsumption(powerTableRows: PowerTableRow[]): void {
        const totalConsumption = PowerTableFunctions.calculateTotalConsumption(powerTableRows);
        $('#totalConsumption').val(totalConsumption);
    }
}