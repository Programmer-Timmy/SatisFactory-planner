import {ProductionTableRow} from "../Data/ProductionTableRow";
import {PowerTableRow} from "../Data/PowerTableRow";
import {buildingOptions} from "../Data/BuildingOptions";
import {HtmlGeneration} from "./HtmlGeneration";
import {Ajax} from "./Ajax";

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
                const amount = row.quantity;
                const existingRow = powerTableRows.find(row => row.buildingId === building.id);

                let amountOfBuilding = amount / recipe.export_amount_per_min;
                let exes = 0;

                const consumption = +PowerTableFunctions.calculateConsumption(amountOfBuilding, 100, building.power_used);

                if (amountOfBuilding % 1 !== 0) {
                    exes = amountOfBuilding % 1;
                    amountOfBuilding = Math.floor(amountOfBuilding);
                }

                if (existingRow) {
                    existingRow.quantity += amountOfBuilding;
                    existingRow.Consumption = +PowerTableFunctions.calculateConsumption(existingRow.quantity, 100, building.power_used);
                } else {
                    powerTableRows.push(new PowerTableRow(building.id, amountOfBuilding, 100, consumption, false, building));

                }

                if (exes > 0) {
                    const clockSpeed = exes * 100;
                    const consumption = +PowerTableFunctions.calculateConsumption(1, clockSpeed, building.power_used);
                    powerTableRows.push(new PowerTableRow(building.id, 1, +clockSpeed.toFixed(1), consumption, false, building));
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

    private static calculateConsumption(amount: number, ClockSpeed: number, Consumption: number) {
        // tot de maght van 1,321928
        const clockSpeed = Math.pow(ClockSpeed / 100, 1.321928);
        // to 1 decimal
        return (amount * Consumption * clockSpeed).toFixed(1);
    }

    static calculateTotalConsumption(table: PowerTableRow[]): number {
        const filteredTable = table.filter(row => row.Consumption !== 0);
        const totalConsumption = filteredTable.reduce((acc, row) => {
            const consumption = Number(row.Consumption) || 0; // Ensure it's a number
            return acc + consumption;
        }, 0);

        return parseFloat(totalConsumption.toFixed(3)); // Limit to 3 decimal places
    }

    public static calculateSingleConsumption(row: PowerTableRow): number {
        if (row.building === null) return 0;

        const clockSpeed = Math.pow(row.clockSpeed / 100, 1.321928);
        return +(row.quantity * row.building.power_used * clockSpeed).toFixed(1);
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