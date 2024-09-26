import {PowerTableRow} from "../Data/PowerTableRow";
import {ImportsTableRow} from "../Data/ImportsTableRow";
import {ItemOptions} from "../Data/ItemOptions";

export class HtmlGeneration {

    /**
     * Generates the HTML for the power table.
     * @param powerRows - The array of power table rows to generate HTML for.
     * @param buildingOptions - The HTML string for the building options.
     * @param totalConsumption - The total consumption of the power table.
     *
     * @returns The generated HTML string for the power table.
     */
    public static generatePowerTable(powerRows: PowerTableRow[], buildingOptions: string, totalConsumption: number): string {
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
          <input type="number" value="0" class="form-control rounded-0" name="power_amount[]" min="0" step="any" onchange="updateConsumption()">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="100" class="form-control rounded-0" name="power_clock_speed[]" min="1" max="250" step="any" onchange="updateConsumption()">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="0" class="form-control rounded-0" readonly name="power_Consumption[]" min="0" step="any">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="hidden" value="1" class="form-control rounded-0" readonly name="user[]" min="0">
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

    /**
     * Generates the HTML for the imports table rows.
     *
     * @param importsTableRows - The array of imports table rows to generate HTML for.
     * @returns The generated HTML string for the imports table rows.
     */
    public static generateImportsTableRows(importsTableRows: ImportsTableRow[]): string {
        const rowsHTML = importsTableRows.map(row => {
            const formattedQuantity = Number(row.quantity) % 1 === 0 ?
                row.quantity.toFixed(0) :
                row.quantity.toFixed(1);

            return `
            <tr>
                <td class="m-0 p-0 w-75">
                    <select name="imports_item_id[]" class="form-control rounded-0">
                        ${ItemOptions.replace(`value="${row.itemId}"`, `value="${row.itemId}" selected`)}
                    </select>
                </td>
                <td class="m-0 p-0 w-25">
                    <input min="0" type="number" name="imports_ammount[]" class="form-control rounded-0" value="${formattedQuantity}" readonly>
                </td>
            </tr>
        `;
        }).join('');

        const emptyRowHTML = `
        <tr>
            <td class="m-0 p-0 w-75">
                <select name="imports_item_id[]" class="form-control rounded-0">
                    ${ItemOptions.replace(/<option /, '<option selected ')} <!-- Selects the first option -->
                </select>
            </td>
            <td class="m-0 p-0 w-25">
                <input min="0" type="number" name="imports_ammount[]" class="form-control rounded-0">
            </td>
        </tr>`;

        return rowsHTML + emptyRowHTML; // Combine the existing rows with the empty row
    }
}