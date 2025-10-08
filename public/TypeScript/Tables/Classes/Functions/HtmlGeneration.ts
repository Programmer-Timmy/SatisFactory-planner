import {PowerTableRow} from "../Data/PowerTableRow";
import {ImportsTableRow} from "../Data/ImportsTableRow";
import {ItemOptions} from "../Data/ItemOptions";
import {RecipeOptions} from "../Data/RecipeOptions";
import {ProductionTableRow} from "../Data/ProductionTableRow";

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
                row.quantity.toFixed(5);

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

        return rowsHTML; // Combine the existing rows with the empty row
    }

    /**
     * Generates the HTML for the production table rows.
     *
     * @param productionTableRows - The array of production table rows to generate HTML for.
     * @returns The generated HTML string for the production table rows.
     */
    public static generateProductionTableRows(productionTableRows: ProductionTableRow[]): string {
        const rowsHTML = productionTableRows.map(row => {
            let style = '';
            let doubleExport = '';
            let rowSpan = '';
            if (row.doubleExport) {
                style = 'style="height: 78px"';
                rowSpan = 'rowspan="2"';
                doubleExport = `
            <tr class="extra-output">
                <td class="m-0 p-0">
                    <input type="text" readonly class="form-control rounded-0 product-name" value="${row.extraCells?.Product}">
                </td>
                <td class="m-0 p-0">
                    <input min="0" type="number" step="any" name="production_usage2[]" value="${row.extraCells?.Usage}" required readonly class="form-control rounded-0 usage-amount">
                </td>
                <td class="m-0 p-0">
                    <input min="0" type="number" step="any" name="production_export2[]" value="${row.extraCells?.Quantity}" required readonly class="form-control rounded-0 export-amount">
                </td>
            </tr>
            `;
            }
            return ` <tr>
          <td class="hidden">
            <input type="hidden" name="production_id[]" value="${row.row_id}">
          </td>
          <td class="m-0 p-0" ${rowSpan}>
            <select name="production_recipe_id[]" class="form-control rounded-0 item-recipe-id recipe" ${style}>
              ${RecipeOptions.replace(`value="${row.recipeId}"`, `value="${row.recipeId}" selected`)}
            </select>
          </td>
          <td class="m-0 p-0" ${rowSpan}>
            <input min="0" type="number" step="any" name="production_quantity[]" value="${row.quantity}" required class="form-control rounded-0 production-quantity" ${style}>
          </td>
          <td class="m-0 p-0">
            <input type="text" readonly class="form-control rounded-0 product-name" value="${row.product}">
          </td>
          <td class="m-0 p-0">
            <input min="0" type="number" step="any" name="production_usage[]" value="${row.Usage}" required readonly class="form-control rounded-0 usage-amount">
          </td>
          <td class="m-0 p-0">
            <input min="0" type="number" step="any" name="production_export[]" value="${row.exportPerMin}" required readonly class="form-control rounded-0 export-amount">
          </td>
        </tr>
        ${doubleExport}
        `}).join('');


        const emptyRowHTML = `
        <tr>
            <td class="m-0 p-0">
                <select name="production_recipe_id[]" class="form-control rounded-0 item-recipe-id recipe">
                    ${RecipeOptions.replace(/<option /, '<option selected ')}
                </select>
            </td>
            <td class="m-0 p-0">
                <input min="0" type="number" value="0" step="any" name="production_quantity[]" required class="form-control rounded-0 production-quantity">
            </td>
            <td class="m-0 p-0">
                <input type="text" readonly class="form-control rounded-0 product-name">
            </td>
            <td class="m-0 p-0">
                <input min="0" type="number" value="0" step="any" name="production_usage[]" required readonly class="form-control rounded-0 usage-amount">
            </td>
            <td class="m-0 p-0">
                <input min="0" type="number" value="0" step="any" name="production_export[]" required readonly class="form-control rounded-0 export-amount">
            </td>
        </tr>
    `;

        return rowsHTML + emptyRowHTML; // Combine the existing rows with the empty row
    }

    public static createCard(index:number, recipeName: string, productionAmount: number, buildingAmount: number, beenBuild: boolean, beenTested: boolean, building?: string) {
        return `
        <div class="card mb-2" id="check-${index}">
            <div class="card-body p-3">
                <h5 class="card-title recipeName">${recipeName}</h5>
                <p class="card-text"><span class="productionAmount">${productionAmount}</span> per min - <span class="buildingAmount">${+buildingAmount.toFixed(5)}</span> <span class="buildingName">${building}</span></p>
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <input type="checkbox" data-toggle="toggle" data-onstyle="success" data-offstyle="dark" for="build" class="beenBuild"
                               data-onlabel="<i class='fa-solid fa-check'></i>" data-offlabel="<i class='fa-solid fa-times'></i>"
                               data-size="sm" data-style="ios" data-theme="dark" ${beenBuild ? "checked" : ""}/>
                        <label for="build">Build</label>
                    </div>
                    <div>
                        <!--                        same checkbox as above-->
                        <input type="checkbox" data-toggle="toggle" data-onstyle="success" data-offstyle="dark" for="tested" class="beenTested"
                               data-onlabel="<i class='fa-solid fa-check'></i>" data-offlabel="<i class='fa-solid fa-times'></i>"
                               data-size="sm" data-style="ios" data-theme="dark" ${beenTested ? "checked" : ""}/>
                        <label for="tested">Tested</label>
                    </div>
                </div>
            </div>
        </div>
        `;
    }
}