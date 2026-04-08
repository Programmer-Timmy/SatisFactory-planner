import {PowerTableRow} from "../Data/PowerTableRow";
import {ImportsTableRow} from "../Data/ImportsTableRow";
import {ItemOptions} from "../Data/ItemOptions";
import {RecipeOptions} from "../Data/RecipeOptions";
import {ProductionTableRow} from "../Data/ProductionTableRow";

export class HtmlGeneration {

    /**
     * Formats a number for display purposes only.
     * - Integers are displayed without decimals
     * - Floats are rounded to 5 decimals, with trailing zeros removed
     * @param value - The value to format
     * @returns Formatted string
     */
    private static formatNumber(value: number | string): string {
        const n = Number(value ?? 0);
        if (Number.isNaN(n)) return String(value ?? '');
        if (n % 1 === 0) return n.toFixed(0);
        // Round to 5 decimals, then remove trailing zeros
        const rounded = Math.round(n * 100000) / 100000;
        return rounded.toFixed(5).replace(/0+$/, '').replace(/\.$/, '');
    }

    /**
     * Escapes a value for safe insertion into HTML.
     * This is used to ensure that any dynamic text cannot break out of
     * attributes or text nodes and be interpreted as HTML or script.
     */
    private static escapeHtml(value: unknown): string {
        const str = String(value ?? '');
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    private static itemClassMap: Record<string, string> | null = null;

    private static getItemClassMap(): Record<string, string> {
        if (this.itemClassMap !== null) return this.itemClassMap;

        try {
            const el = document.getElementById('items-class-map');
            const json = el?.textContent?.trim() || '{}';
            this.itemClassMap = JSON.parse(json);
        } catch {
            this.itemClassMap = {};
        }

        return this.itemClassMap as Record<string, string>;
    }

    private static normalizeItemClassName(className: string): string {
        return className.toLowerCase().replaceAll('_', '-');
    }

    private static getItemIconSrc(itemId: number | null | undefined): string | null {
        if (!itemId) return null;
        const map = this.getItemClassMap();
        const className = map[itemId.toString()];
        if (!className) return null;
        // Allow only safe characters in the class name to prevent HTML/attribute injection
        const safeClassName = className.replace(/[^a-zA-Z0-9_-]/g, '');
        if (!safeClassName) return null;
        return `/image/items/${this.normalizeItemClassName(safeClassName)}_256.png`;
    }

    public static getItemIconSrcForId(itemId: number | null | undefined): string | null {
        return this.getItemIconSrc(itemId);
    }

    /**
     * Generates the HTML for the power table.
     * @param powerRows - The array of power table rows to generate HTML for.
     * @param buildingOptions - The HTML string for the building options.
     * @param totalConsumption - The total consumption of the power table.
     *
     * @returns The generated HTML string for the power table.
     */
    public static generatePowerTable(powerRows: PowerTableRow[], buildingOptions: string, totalConsumption: number): string {
        const escapedTotalConsumption = this.escapeHtml(totalConsumption);
        const rowsHtml = powerRows.map((row, index) => {
            const escapedBuildingId = this.escapeHtml(row.buildingId);
            const escapedQuantity = this.escapeHtml(row.quantity);
            const escapedClockSpeed = this.escapeHtml(row.clockSpeed);
            const escapedConsumption = this.escapeHtml(row.Consumption);
            const escapedUserRow = this.escapeHtml(row.userRow ? 1 : 0);

            const optionsWithSelected = buildingOptions.replace(
                `<option value="${row.buildingId}">`,
                `<option value="${escapedBuildingId}" selected>`
            );

            return `
      <tr>
        <td class="m-0 p-0 w-50">
          <select class="form-control rounded-0" name="power_building_id[]" min="0">
            ${optionsWithSelected}
          </select>
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="${escapedQuantity}" class="form-control rounded-0" name="power_amount[]" min="0" step="any" data-index="${index}" onchange="updateConsumption()">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="${escapedClockSpeed}" class="form-control rounded-0" name="power_clock_speed[]" min="1" max="250" step="any" data-index="${index}" onchange="updateConsumption()">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="number" value="${escapedConsumption}" class="form-control rounded-0" disabled name="power_Consumption[]" min="0" step="any">
        </td>
        <td class="m-0 p-0 w-25">
          <input type="hidden" value="${escapedUserRow}" class="form-control rounded-0" readonly name="user[]" min="0">
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
          <input type="number" name="total_consumption" readonly class="form-control rounded-0" id="totalConsumption" value="${escapedTotalConsumption}">
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
            const formattedQuantity = this.formatNumber(row.quantity);
            const escapedQuantity = this.escapeHtml(formattedQuantity);

            const iconSrc = this.getItemIconSrc(row.itemId);
            const safeIconSrc = iconSrc != null ? this.escapeHtml(iconSrc) : null;
            const iconHtml = safeIconSrc
                ? `<img class="pl-item-icon" data-role="import-icon" loading="lazy" src="${safeIconSrc}" alt="">`
                : `<img class="pl-item-icon" data-role="import-icon" loading="lazy" style="display:none" alt="">`;

            const itemIdStr = row.itemId != null ? String(row.itemId) : '';
            const escapedItemId = this.escapeHtml(itemIdStr);

            const optionsWithSelected = ItemOptions.replace(
                `value="${itemIdStr}"`,
                `value="${escapedItemId}" selected`
            );

            return `
            <tr>
                <td class="m-0 p-0 w-75" data-label="Item">
                    <div class="pl-field-with-icon">
                        ${iconHtml}
                        <select name="imports_item_id[]" class="form-control rounded-0">
                            ${optionsWithSelected}
                        </select>
                    </div>
                </td>
                <td class="m-0 p-0 w-25" data-label="Qty / min">
                    <input min="0" type="number" name="imports_ammount[]" class="form-control rounded-0" value="${escapedQuantity}" readonly>
                </td>
            </tr>
        `;
        }).join('');

        return rowsHTML;
    }

    public static generateImportsCards(importsTableRows: ImportsTableRow[], readOnly: boolean = false): string {
        const rows = readOnly
            ? importsTableRows.filter(r => Number(r.itemId) > 0 && Number(r.quantity) > 0)
            : importsTableRows;

        return rows.map((row, index) => {
            const formattedQuantity = this.formatNumber(row.quantity);

            const iconSrc = this.getItemIconSrc(row.itemId);
            const iconHtml = iconSrc
                ? `<img class="pl-item-icon" data-role="import-icon" loading="lazy" src="${iconSrc}" alt="">`
                : `<img class="pl-item-icon" data-role="import-icon" loading="lazy" style="display:none" alt="">`;

            const iconCollapsedHtml = iconSrc
                ? `<img class="pl-item-icon" data-role="import-icon-collapsed" loading="lazy" src="${iconSrc}" alt="">`
                : `<img class="pl-item-icon" data-role="import-icon-collapsed" loading="lazy" style="display:none" alt="">`;

            const itemField = readOnly
                ? `
                    <input type="number" hidden name="imports_item_id[]" data-field="itemId" value="${row.itemId}">
                    <select class="form-control rounded-0 pl-readonly-select" disabled aria-disabled="true" tabindex="-1" data-sp-skip="true" data-field="itemId">
                        ${ItemOptions.replace(`value="${row.itemId}"`, `value="${row.itemId}" selected`)}
                    </select>
                  `
                : `
                    <select name="imports_item_id[]" class="form-control rounded-0" data-field="itemId">
                        ${ItemOptions.replace(`value="${row.itemId}"`, `value="${row.itemId}" selected`)}
                    </select>
                  `;

            const qtyField = readOnly
                ? `
                    <input type="number" hidden step="any" name="imports_ammount[]" data-field="quantity" value="${formattedQuantity}">
                    <div class="pl-value pl-number" data-role="import-qty-display">${formattedQuantity}</div>
                  `
                : `
                    <input min="0" type="number" name="imports_ammount[]" class="form-control rounded-0" data-field="quantity" value="${formattedQuantity}">
                  `;

            const collapsedClass = readOnly ? ' is-collapsed' : '';
            const nameCollapsed = (row.product || '').toString();

            return `
                <div class="pl-row pl-import-row${collapsedClass}" data-row-index="${index}">
                    ${readOnly ? '' : `
                    <button type="button" class="btn btn-sm pl-import-collapse-toggle" aria-label="Collapse/expand import" aria-expanded="true">
                        <i class="fa-solid fa-chevron-up"></i>
                    </button>
                    `}

                    <div class="pl-import-collapsed" aria-hidden="true">
                        ${iconCollapsedHtml}
                        <div class="pl-import-collapsed-main">
                            <div class="pl-import-collapsed-name" data-role="import-name-collapsed">${nameCollapsed}</div>
                            <div class="pl-import-collapsed-qty">
                                <span class="pl-number" data-role="import-qty-collapsed">${formattedQuantity}</span>
                                <span class="text-muted">/min</span>
                            </div>
                        </div>
                    </div>

                    <div class="pl-import-details">
                        <div class="pl-row-header">
                            <div class="pl-field">
                                <div class="pl-label">Item</div>
                                <div class="pl-field-with-icon">
                                    ${iconHtml}
                                    ${itemField}
                                </div>
                            </div>
                            <div class="pl-field">
                                <div class="pl-label">Qty / min</div>
                                ${qtyField}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    public static generateProductionCards(productionTableRows: ProductionTableRow[]): string {
        return productionTableRows.map((row, index) => {
            const recipe = row.recipe;

            const output1Src = recipe ? this.getItemIconSrc(recipe.item_id) : null;
            const out1IconHtml = output1Src
                ? `<img class="pl-item-icon" data-role="output1" loading="lazy" src="${output1Src}" alt="">`
                : `<img class="pl-item-icon" data-role="output1" loading="lazy" style="display:none" alt="">`;

            const buildingClass = recipe?.building?.class_name;
            const buildingSrc = buildingClass
                ? `/image/items/${buildingClass.replaceAll('_', '-').replace(/build/gi, 'desc').toLowerCase()}_256.png`
                : null;

            const output2Src = recipe && recipe.item_id2 ? this.getItemIconSrc(recipe.item_id2) : null;
            const out2IconHtml = output2Src
                ? `<img class="pl-item-icon" data-role="output2" loading="lazy" src="${output2Src}" alt="">`
                : `<img class="pl-item-icon" data-role="output2" loading="lazy" style="display:none" alt="">`;

            const isVisible = row.doubleExport && row.extraCells !== null;

            const usage1 = Number(row.Usage ?? 0);
            const exp1 = Number(row.exportPerMin ?? 0);

            const usage2 = Number(row.extraCells?.Usage ?? 0);
            const exp2 = Number(row.extraCells?.ExportPerMin ?? 0);

            return `
                <div class="pl-row pl-production-row" data-row-index="${index}">
                    <input type="hidden" name="production_id[]" value="${row.row_id}">

                    <i class="fa-solid fa-gear open-p-settings link-primary text-muted z-1"></i>
                    <img class="pl-building-icon" data-role="building" loading="lazy" ${buildingSrc ? `src=\"${buildingSrc}\"` : 'style=\"display:none\"'} alt="">

                    <div class="pl-row-header">
                        <div class="pl-field">
                            <div class="pl-label">Recipe</div>
                            <select name="production_recipe_id[]" class="form-control rounded-0" data-field="recipeId">
                                ${RecipeOptions.replace(`value=\"${row.recipeId}\"`, `value=\"${row.recipeId}\" selected`)}
                            </select>
                        </div>
                        <div class="pl-field">
                            <div class="pl-label">Qty / min</div>
                            <input min="0" type="text" name="production_quantity[]" step="any" required class="form-control rounded-0 production-quantity" data-field="quantity" value="${row.quantity}">
                        </div>
                    </div>

                    <div class="pl-row-flow">
                        <div class="pl-field">
                            <div class="pl-label"><i class="fa-solid fa-arrow-right text-muted me-1 pl-flow-arrow"></i>Output</div>
                            <div class="pl-field-with-icon">
                                ${out1IconHtml}
                                <input type="hidden" class="product-name" value="${row.product || ''}">
                                <div class="pl-value" data-role="product1-text">${row.product || ''}</div>
                            </div>
                        </div>

                        <div class="pl-field">
                            <div class="pl-label">Local usage / min</div>
                            <input type="hidden" class="usage-amount" value="${usage1}">
                            <div class="pl-value pl-number" data-role="usage1-text">${usage1}</div>
                        </div>

                        <div class="pl-field">
                            <div class="pl-label">Export / min</div>
                            <input type="hidden" class="export-amount" value="${exp1}">
                            <div class="pl-value pl-number" data-role="export1-text">${exp1}</div>
                        </div>

                        <div class="pl-actions">
                            <button type="button" class="btn btn-outline-danger btn-outline-soft-red btn-sm delete-production-row" data-id="${row.row_id}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pl-extra-output extra-output ${isVisible ? 'is-visible' : ''}">
                        <div class="pl-row-flow" style="grid-template-columns: 1.2fr 1fr 1fr;">
                            <div class="pl-field">
                                <div class="pl-label"><i class="fa-solid fa-arrow-right text-muted me-1 pl-flow-arrow"></i>By-product</div>
                                <div class="pl-field-with-icon">
                                    ${out2IconHtml}
                                    <input type="hidden" data-sp-skip="true" class="product-name" value="${row.extraCells?.Product || ''}">
                                    <div class="pl-value" data-role="product2-text">${row.extraCells?.Product || ''}</div>
                                </div>
                            </div>

                            <div class="pl-field">
                                <div class="pl-label">Local usage / min</div>
                                <input type="hidden" data-sp-skip="true" class="usage-amount" value="${usage2}">
                                <div class="pl-value pl-number" data-role="usage2-text">${usage2}</div>
                            </div>

                            <div class="pl-field">
                                <div class="pl-label">Export / min</div>
                                <input type="hidden" data-sp-skip="true" class="export-amount" value="${exp2}">
                                <div class="pl-value pl-number" data-role="export2-text">${exp2}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
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
                <p class="card-text"><span class="productionAmount">${productionAmount}</span> per min - <span class="buildingAmount">${this.formatNumber(buildingAmount)}</span> <span class="buildingName">${building}</span></p>
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