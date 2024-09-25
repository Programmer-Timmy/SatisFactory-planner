import {PowerTableRow} from "./Data/PowerTableRow";
import {ProductionTableRow} from "./Data/ProductionTableRow";
import {buildingOptions} from "./Data/BuildingOptions";

export class PowerTableFunctions {

    public static deleteNonUserRows(table: PowerTableRow[]) {
        const userRows = table.filter(row => row.userRow);
        const nonUserRows = table.filter(row => !row.userRow);
        const tableId = 'power';
        const tableBody = $(`#${tableId} tbody`);
        for (let i = 0; i < nonUserRows.length; i++) {
            const row = nonUserRows[i];
            const index = table.indexOf(row);
            table.splice(index, 1);
            tableBody.find(`tr:eq(${index})`).remove();
            i--;
        }

        return userRows;
    }

    public static calculateBuildings(productionTableRows: ProductionTableRow[]) {
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
                    powerTableRows.push(new PowerTableRow(building.id, amountOfBuilding, 100, consumption, false));

                }

                if (exes > 0) {
                    const clockSpeed = exes * 100;
                    const consumption = +PowerTableFunctions.calculateConsumption(1, clockSpeed, building.power_used);

                    // if existing row add it under the existing row
                    if (existingRow) {
                        const index = powerTableRows.indexOf(existingRow);
                        powerTableRows.splice(index + 1, 0, new PowerTableRow(building.id, 1, +clockSpeed.toFixed(1), consumption, false));
                    } else {
                        powerTableRows.push(new PowerTableRow(building.id, amountOfBuilding, +clockSpeed.toFixed(1), consumption, false));
                    }
                }
            }
        }

        const html = PowerTableFunctions.generatePowerTable(powerTableRows, buildingOptions, PowerTableFunctions.calculateTotalConsumption(powerTableRows));
        $('#power tbody').html(html);

    }

    private static calculateConsumption(amount: number, ClockSpeed: number, Consumption: number) {
        // tot de maght van 1,321928
        const clockSpeed = Math.pow(ClockSpeed / 100, 1.321928);
        // to 1 decimal
        return (amount * Consumption * clockSpeed).toFixed(1);
    }

    private static calculateTotalConsumption(table: PowerTableRow[]): number {
        const totalConsumption = table.reduce((acc, row) => acc + row.Consumption, 0);
        return parseFloat(totalConsumption.toFixed(3)); // Limit to 3 decimal places
    }

    private static generatePowerTable(powerRows: PowerTableRow[], buildingOptions: string, totalConsumption: number): string {
        const rowsHtml = powerRows.map((row, index) => {
            return `
      <tr>
        <td class="m-0 p-0 w-50">
          <select class="form-control rounded-0" name="power_building_id[]" min="0">
            ${buildingOptions.replace(`<option value="${row.buildingId}">`, `<option value="${row.buildingId}" selected>`)}
          </select>
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="${row.quantity}" class="form-control rounded-0" name="power_amount[]" min="0" step="any" data-index="${index}" onchange="updateConsumption()">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="${row.clockSpeed}" class="form-control rounded-0" name="power_clock_speed[]" min="1" max="250" step="any" data-index="${index}" onchange="updateConsumption()">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="${row.Consumption}" class="form-control rounded-0" disabled name="power_Consumption[]" min="0" step="any">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="hidden" value="${row.userRow ? 1 : 0}" class="form-control rounded-0" readonly name="user[]" min="0">
        </td>
      </tr>
    `;
        }).join('');

        // Add an empty row for new entries
        const emptyRowHtml = `
      <tr>
        <td class="m-0 p-0 w-50">
          <select class="form-control rounded-0" name="power_building_id[]" min="0">
            ${buildingOptions.replace(/<option /, '<option selected ')} <!-- Selects the first option -->
          </select>
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="" class="form-control rounded-0" name="power_amount[]" min="0" step="any" onchange="updateConsumption()">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="" class="form-control rounded-0" name="power_clock_speed[]" min="1" max="250" step="any" onchange="updateConsumption()">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="0" class="form-control rounded-0" readonly name="power_Consumption[]" min="0" step="any">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="hidden" value="0" class="form-control rounded-0" readonly name="user[]" min="0">
        </td>
      </tr>
    `;

        // Total row
        const totalRowHtml = `
      <tr>
        <td colspan="1" class="table-dark">
          Total:
        </td>
        <td colspan="2"></td>
        <td class="w-25 m-0 p-0">
          <input type="number" name="total_consumption" readonly class="form-control rounded-0" id="totalConsumption" value="${totalConsumption}">
        </td>
      </tr>
    `;

        return rowsHtml + emptyRowHtml + totalRowHtml;
    }


}