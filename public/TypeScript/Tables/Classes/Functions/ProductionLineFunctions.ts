import {Ajax} from "./Ajax";
import {ProductionTableRow} from "../Data/ProductionTableRow";
import {ExtraProductionRow} from "../Data/ExtraProductionRow";
import {PowerTableFunctions} from "./PowerTableFunctions";

export class ProductionLineFunctions {

    private static formatNumber(value: any): string {
        const n = Number(value ?? 0);
        if (Number.isNaN(n)) return String(value ?? '');
        if (n % 1 === 0) return n.toFixed(0);
        // Round to 5 decimals, then remove trailing zeros
        const rounded = Math.round(n * 100000) / 100000;
        return rounded.toFixed(5).replace(/0+$/, '').replace(/\.$/, '');
    }

    public static updateRowDisplay(row: ProductionTableRow, rowToUpdate: JQuery<HTMLElement>): void {
        // Component-mode display fields (no readonly inputs)
        if (rowToUpdate.find('.pl-value').length) {
            rowToUpdate.find('[data-role="product1-text"]').text(row.product || '');
            rowToUpdate.find('[data-role="usage1-text"]').text(this.formatNumber(row.Usage));
            rowToUpdate.find('[data-role="export1-text"]').text(this.formatNumber(row.exportPerMin));

            const extra = rowToUpdate.find('.extra-output');
            if (extra.length && row.doubleExport && row.extraCells !== null) {
                extra.find('[data-role="product2-text"]').text(row.extraCells.Product || '');
                extra.find('[data-role="usage2-text"]').text(this.formatNumber(row.extraCells.Usage));
                extra.find('[data-role="export2-text"]').text(this.formatNumber(row.extraCells.ExportPerMin));
            }

            const collapsedRecipeEl = rowToUpdate.find('[data-role="collapsed-recipe-name"]');
            if (collapsedRecipeEl.length) {
                const nameFromRecipe = row.recipe?.name || '';
                const nameFromInput = String((rowToUpdate.find('.search-input').first().val() as any) ?? '').trim();
                const name = (nameFromRecipe || nameFromInput || 'Select recipe').trim();
                collapsedRecipeEl.text(name);
                collapsedRecipeEl.attr('title', name);
            }

            const output1Wrap = rowToUpdate.find('[data-role="collapsed-output1-wrap"]');
            const output1Rate = rowToUpdate.find('[data-role="collapsed-output1-rate"]');
            if (output1Wrap.length) {
                // show/hide is controlled by icons; just keep the number in sync
                if (output1Rate.length) {
                    output1Rate.text(`${this.formatNumber(row.quantity)}/min`);
                }
            }

            // Byproduct amount in collapsed header (only when double export exists)
            const byproductWrap = rowToUpdate.find('[data-role="collapsed-byproduct"]');
            const byproductRate = rowToUpdate.find('[data-role="collapsed-output2-rate"]');
            if (byproductWrap.length) {
                if (row.doubleExport && row.extraCells !== null) {
                    byproductWrap.css('display', '');
                    if (byproductRate.length) {
                        byproductRate.text(`${this.formatNumber(row.extraCells.ExportPerMin)}/min`);
                    }
                } else {
                    byproductWrap.css('display', 'none');
                    if (byproductRate.length) {
                        byproductRate.text('');
                    }
                }
            }

            const buildingAmountEl = rowToUpdate.find('[data-role="building-amount"]');
            if (buildingAmountEl.length) {
                if (row.recipe !== null) {
                    const amount = PowerTableFunctions.calculateBuildingAmount(row.recipe, row);
                    buildingAmountEl.text(this.formatNumber(amount));
                } else {
                    buildingAmountEl.text('');
                }
            }
        }
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

    private static normalizeBuildingClassName(className: string): string {
        // Mirrors PHP: str_ireplace('build','desc', str_replace('_','-', $className)) then strtolower
        return className
            .replaceAll('_', '-')
            .replace(/build/gi, 'desc')
            .toLowerCase();
    }

    private static getItemIconSrc(itemId: number | null | undefined): string | null {
        if (!itemId) return null;
        const map = this.getItemClassMap();
        const className = map[itemId.toString()];
        if (!className) return null;
        return `/image/items/${this.normalizeItemClassName(className)}_256.png`;
    }

    private static setImg($img: JQuery<HTMLElement>, src: string | null) {
        if (!$img.length) return;
        if (!src) {
            $img.attr('src', '');
            // keep layout tight
            $img.css('display', 'none');
            return;
        }
        $img.attr('src', src);
        $img.css('display', '');
    }

    public static updateRowIcons(row: ProductionTableRow, rowToUpdate: JQuery<HTMLElement>): void {
        const recipe = row.recipe;

        // Output 1 icon
        const output1Src = recipe ? this.getItemIconSrc(recipe.item_id) : null;
        this.setImg(rowToUpdate.find('img[data-role="output1"]'), output1Src);
        this.setImg(rowToUpdate.find('img[data-role="collapsed-output1"]'), output1Src);

        // Building icon
        const buildingClass = recipe?.building?.class_name;
        const buildingSrc = buildingClass
            ? `/image/items/${this.normalizeBuildingClassName(buildingClass)}_256.png`
            : null;
        this.setImg(rowToUpdate.find('img[data-role="building"]'), buildingSrc);

        // Output 2 icon (component-mode: inside .extra-output, table-mode: next <tr.extra-output>)
        const output2Src = recipe && recipe.item_id2 ? this.getItemIconSrc(recipe.item_id2) : null;
        const extraBlock = rowToUpdate.find('.extra-output');
        if (extraBlock.length) {
            this.setImg(extraBlock.find('img[data-role="output2"]'), output2Src);
        } else {
            const extraRow = rowToUpdate.next('.extra-output');
            this.setImg(extraRow.find('img[data-role="output2"]'), output2Src);
        }

        // Collapsed summary output 2 icon
        this.setImg(rowToUpdate.find('img[data-role="collapsed-output2"]'), output2Src);

        // Ensure collapsed wrappers only show when icons exist
        const out1Wrap = rowToUpdate.find('[data-role="collapsed-output1-wrap"]');
        if (out1Wrap.length) {
            out1Wrap.css('display', output1Src ? '' : 'none');
        }

        const byproductWrap = rowToUpdate.find('[data-role="collapsed-byproduct"]');
        if (byproductWrap.length) {
            byproductWrap.css('display', output2Src ? '' : 'none');
        }
    }

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
        // Component-mode: extra block lives inside the row
        const extraBlock = rowToUpdate.find('.extra-output');
        if (extraBlock.length) {
            if (row.doubleExport && row.extraCells !== null) {
                extraBlock.addClass('is-visible');
                extraBlock.find('input.product-name[data-sp-skip="true"]').val(row.extraCells.Product);
                extraBlock.find('input.usage-amount[data-sp-skip="true"]').val(this.formatNumber(row.extraCells.Usage));
                extraBlock.find('input.export-amount[data-sp-skip="true"]').val(this.formatNumber(row.extraCells.ExportPerMin));

                extraBlock.find('[data-role="product2-text"]').text(row.extraCells.Product);
                extraBlock.find('[data-role="usage2-text"]').text(this.formatNumber(row.extraCells.Usage));
                extraBlock.find('[data-role="export2-text"]').text(this.formatNumber(row.extraCells.ExportPerMin));
            } else {
                extraBlock.removeClass('is-visible');
                extraBlock.find('input.product-name[data-sp-skip="true"]').val('');
                extraBlock.find('input.usage-amount[data-sp-skip="true"]').val(0);
                extraBlock.find('input.export-amount[data-sp-skip="true"]').val(0);

                extraBlock.find('[data-role="product2-text"]').text('');
                extraBlock.find('[data-role="usage2-text"]').text('0');
                extraBlock.find('[data-role="export2-text"]').text('0');
            }
            return;
        }

        // Table-mode: extra export is a separate <tr>
        if (row.doubleExport && row.extraCells !== null) {
            if (!rowToUpdate.next('.extra-output').length) {
                rowToUpdate.find('td:nth-child(2)').attr('rowspan', 2);
                rowToUpdate.find('td:nth-child(3)').attr('rowspan', 2);
                rowToUpdate.find('td:nth-child(2) .search-input').css('height', '78px');
                rowToUpdate.find('td:nth-child(3) input').css('height', '78px');
                const deleteBtn = rowToUpdate.find('.delete-production-row');
                deleteBtn.parent().attr('rowspan', 2);
                deleteBtn.css('height', '78px');

                const extraRow = $(`
                    <tr class="extra-output">
                        <td class="m-0 p-0" data-label="By-product">
                            <div class="pl-field-with-icon">
                                <img class="pl-item-icon" data-role="output2" loading="lazy" style="display:none" alt="">
                                <input type="text" name="product" value="${row.extraCells.Product}" class="form-control rounded-0" readonly>
                            </div>
                        </td>
                        <td class="m-0 p-0" data-label="Local usage / min">
                            <input type="number" name="production_usage2[]" value="${this.formatNumber(row.extraCells.Usage)}" class="form-control rounded-0" readonly step="any">
                        </td>
                        <td class="m-0 p-0" data-label="Export / min">
                            <input type="number" name="production_export2[]" value="${this.formatNumber(row.extraCells.ExportPerMin)}" class="form-control rounded-0" readonly step="any">
                        </td>
                    </tr>
                `);
                extraRow.insertAfter(rowToUpdate);
            } else {
                const extraRow = rowToUpdate.next('.extra-output');
                extraRow.find('input[name="production_usage2[]"]').val(this.formatNumber(row.extraCells.Usage));
                extraRow.find('input[name="production_export2[]"]').val(this.formatNumber(row.extraCells.ExportPerMin));
            }
        } else if (rowToUpdate.next('.extra-output').length) {
            rowToUpdate.next('.extra-output').remove();
            rowToUpdate.find('td:nth-child(2)').removeAttr('rowspan');
            rowToUpdate.find('td:nth-child(3)').removeAttr('rowspan');
            rowToUpdate.find('td:nth-child(2) .search-input').css('height', '');
            rowToUpdate.find('td:nth-child(3) input').css('height', '');

            const deleteBtn = rowToUpdate.find('.delete-production-row');
            deleteBtn.parent().removeAttr('rowspan');
            deleteBtn.css('height', '39px');
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
