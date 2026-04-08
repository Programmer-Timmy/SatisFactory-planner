import {ImportsTableRow} from "./Data/ImportsTableRow";
import {ProductionTableRow} from "./Data/ProductionTableRow";
import {PowerTableRow} from "./Data/PowerTableRow";
import {ExtraProductionRow} from "./Data/ExtraProductionRow";
import {ProductionLineFunctions} from "./Functions/ProductionLineFunctions";
import {PowerTableFunctions} from "./Functions/PowerTableFunctions";
import {ImportsTableFunctions} from "./Functions/ImportsTableFunctions";
import {Settings} from "./Settings";
import {HtmlGeneration} from "./Functions/HtmlGeneration";
import {buildingOptions} from "./Data/BuildingOptions";
import {Visualization} from "./Visualization";
import {SaveFunctions} from "./Functions/SaveFunctions";
import {Building} from "./Types/Building";
import {Recipe} from "./Types/Recipe";
import {Checklist, IChecklist} from "./Checklist";
import {RecipeSetting} from "./RecipeSetting";
import {ProductionSelect} from "./ProductionSelect";
import {Calculations} from "./Functions/Calcuations";


/**
 * Class responsible for handling the manipulation and event handling of tables.
 */
export class TableHandler {
    public importsTableRows: ImportsTableRow[] = [];
    public productionTableRows: ProductionTableRow[] = [];
    public powerTableRows: PowerTableRow[] = [];
    public settings: Settings = new Settings();
    public checklist: Checklist | null = null;

    private visualisation: Visualization | null = null;
    private updated: boolean = false;
    private viewOnly: boolean = $('#viewOnly').val() === '1';

    // cache data
    private cacheVersion: number = <number>$('#dataVersion').val();
    private buildingCache: Building[] = [];
    private recipeCache: Recipe[] = [];

    // progress bar
    private progressBar = $('#loading-progress');
    private progressInterval: number = 0;
    private totalRows = 0;
    private finishedRows = 0;
    private recipeSettings: {"id":number, "clockSpeed":number, "useSomersloop":boolean}[] = [];


    constructor() {
        document.addEventListener('pl-settings-changed', () => this.onSettingsChanged());
        this.initialize();
    }

    private isTableElement(tableId: string): boolean {
        const el = $(`#${tableId}`);
        return el.length > 0 && el.is('table');
    }

    private getTableBody(tableId: string): JQuery<HTMLElement> {
        const el = $(`#${tableId}`);
        if (this.isTableElement(tableId)) {
            return el.find('tbody');
        }
        return el;
    }

    private getRows(tableId: string): JQuery<HTMLElement> {
        const el = $(`#${tableId}`);
        if (this.isTableElement(tableId)) {
            return el.find('tbody tr');
        }

        const rows = el.find('[data-row-index]');
        if (tableId === 'recipes') {
            return rows.not('[data-role="recipe-template"]');
        }
        return rows;
    }

    private renderProductionCardsFromTemplate() {
        const container = $('#recipes');
        const template = container.find('[data-role="recipe-template"]').first();
        if (!template.length) {
            // Fallback for non-production-line pages
            container.html(HtmlGeneration.generateProductionCards(this.productionTableRows));
            return;
        }

        const addCard = container.find('[data-role="add-recipe-card"]').first();
        const insertBefore = addCard.length ? addCard : template;

        // Remove existing rendered recipe cards, keep template + add card
        container.find('.pl-production-row').not(template).remove();

        // Render current rows
        const rowsToRender = this.productionTableRows.filter(r => Number(((r as any).recipeId ?? (r as any).recipe_id) || 0) > 0 || Number((r as any).quantity) > 0);
        rowsToRender.forEach((row, index) => {
            // Normalize import field names (some exports may use recipe_id)
            const normalizedRecipeId = Number(((row as any).recipeId ?? (row as any).recipe_id) || 0);
            (row as any).recipeId = normalizedRecipeId;

            const newRow = template.clone();
            newRow.removeClass('d-none pl-recipe-template');
            newRow.removeAttr('data-role');
            newRow.attr('data-row-index', index);
            newRow.removeClass('is-collapsed');

            // Persist row id for saving
            newRow.find('input[name="production_id[]"]').first().val((row as any).row_id ?? '');

            // Pre-fill recipe select (hidden id + visible text) before initializing ProductionSelect
            newRow.find('.recipe-select').each((_, el) => {
                const select = $(el);
                const recipeId = Number((row as any)?.recipeId || 0);

                const recipeIdInput = select.find('input.recipe-id[data-field="recipeId"]').first();
                if (recipeIdInput.length) {
                    recipeIdInput.val(recipeId ? String(recipeId) : '');
                }

                if (recipeId) {
                    const selected = select.find(`.select-item[data-recipe-id="${recipeId}"]`).first();
                    const recipeName = (selected.data('recipe-name') as string) || selected.find('.recipe-name').text();

                    const searchInput = select.find('input.search-input').first();
                    if (searchInput.length) {
                        searchInput.val(recipeName || '');
                        const productCount = selected.find('.recipe-product').length;
                        searchInput.css('height', productCount > 1 ? '78px' : '');
                    }

                    if (selected.length) {
                        selected.addClass('active').siblings().removeClass('active');
                    }
                }

                try {
                    new ProductionSelect(select);
                } catch {
                    // ignore
                }
            });

            // Insert and then populate computed display fields
            newRow.insertBefore(insertBefore);

            // Re-create RecipeSetting so clock speed + somersloop work after import
            const importedClock = (row as any)?.recipeSetting?.clockSpeed;
            const importedSomersloop = (row as any)?.recipeSetting?.useSomersloop;
            const persisted = this.recipeSettings.find((s) => s.id === Number((row as any).recipeId));
            const clockSpeed = Number.isFinite(importedClock) ? Number(importedClock) : (persisted?.clockSpeed || 100);
            const useSomersloop = typeof importedSomersloop === 'boolean' ? importedSomersloop : (persisted?.useSomersloop || false);
            (row as any).recipeSetting = new RecipeSetting(this, row as any, newRow, clockSpeed, useSomersloop);

            this.updateRowInTable('recipes', index, row);
        });

        this.reindexComponentRows('recipes');
        this.initTooltips(container);
    }

    private getRowIndexFromTarget(target: JQuery<HTMLElement>, tableId: string): number {
        const componentRow = target.closest('[data-row-index]');
        if (componentRow.length) {
            return Number(componentRow.data('row-index'));
        }

        const tr = target.closest('tr');
        const amountExtra = tr.prevAll('.extra-output').length;
        return tr.index() - amountExtra;
    }

    private reindexComponentRows(tableId: string) {
        if (this.isTableElement(tableId)) return;

        this.getRows(tableId).each((index, el) => {
            $(el).attr('data-row-index', index);
        });
    }

    private initTooltips(scope: JQuery<HTMLElement>) {
        const bootstrapAny = (window as any).bootstrap;
        if (!bootstrapAny?.Tooltip) return;

        scope.find('[data-bs-toggle="tooltip"]').each((_, el) => {
            try {
                if (bootstrapAny.Tooltip.getInstance && bootstrapAny.Tooltip.getInstance(el)) return;
                new bootstrapAny.Tooltip(el, {trigger: 'hover'});
            } catch {
                // ignore tooltip init errors
            }
        });
    }

    private onSettingsChanged() {
        if (this.viewOnly) return;

        if (this.settings.autoImportExport) {
            const data: { importsTableRows: ImportsTableRow[], indexes: number[] } = ImportsTableFunctions.calculateImports(this.productionTableRows);
            this.importsTableRows = data.importsTableRows;
            this.UpdateOnIndex(data.indexes);
            return;
        }

        // Manual mode: show editable imports again
        if (this.isTableElement('imports')) {
            const importsHtml = HtmlGeneration.generateImportsTableRows(this.importsTableRows);
            const $tbody = $('#imports tbody');
            $tbody.empty();
            const parsedRows = $.parseHTML(importsHtml, document, false) || [];
            $tbody.append(parsedRows);
        } else {
            $('#imports').html(HtmlGeneration.generateImportsCards(this.importsTableRows));
        }
        this.addSpecificEventListener('imports');
    }

    private async initialize(): Promise<void> {
        this.CheckCacheVersion();
        let timeOutTime = 500;

        // if page is reloaded set timeout to 0
        const pageAccessedByReload = (
            (window.performance.getEntriesByType('navigation').length === 0) ||
            window.performance
                .getEntriesByType('navigation')
                // @ts-ignore
                .map((nav) => nav.type)
                .includes('reload')
        );

        if (pageAccessedByReload) {
            timeOutTime = 0;
        }

        this.loadFromLocal();
        await this.getTableData();

        // Ensure component-mode rows show computed display values on initial render
        if (!this.isTableElement('recipes')) {
            this.productionTableRows.forEach((row, index) => {
                this.updateRowInTable('recipes', index, row);
            });
        }

        // Force-initialize Bootstrap tooltips after DOM has been rendered
        this.initTooltips($(document.body));

        this.addEventListeners();
        this.addButtonEventListeners();
        this.addShortcuts();

        // wait for a little bit so the user can see the 100% progress
        setTimeout(() => {
            this.hideLoading();
            this.enableButtons();

            if (this.viewOnly) {
                this.disableButtons();
                this.disableInputs();
            }
        }, timeOutTime);

        if (!this.viewOnly) {
            this.checklist = new Checklist(this);
        }
    }

    private async getTableData() {
        this.totalRows = this.getRows('recipes').length;
        this.progressInterval = 100 / this.totalRows;
        this.recipeSettings = JSON.parse($("#settings-data").text() ?? {})

        this.productionTableRows = await this.readTable<ProductionTableRow>('recipes', ProductionTableRow, true);

        await this.readTable<ImportsTableRow>('imports', ImportsTableRow).then(result => {
            this.importsTableRows = result;
        }).catch(error => {
            console.error('Failed to load imports table rows:', error);
        });

        this.readTable<PowerTableRow>('power', PowerTableRow).then(result => {
            this.powerTableRows = result;
            this.saveToLocal();
            $(document).ready(() => {
                this.showCacheAmount();
            });
        }).catch(error => {
            console.error('Failed to load power table rows:', error);
        });
    }

    /**
     * Generic method to read tables and convert rows into class instances.
     * @param {string} id - The ID of the table.
     * @param {new(...args: any[]) => T} rowClass - The class constructor for the table rows.
     * @param useProgress - Whether to use the progress bar.
     * @returns {T[]} An array of instances of the specified row class.
     */
    private async readTable<T>(id: string, rowClass: {
        new(...args: any[]): T
    }, useProgress: boolean = false): Promise<T[]> {
        const table = this.getRows(id);
        let lengthReduction = 0;
        if (id === 'power') {
            lengthReduction = 1;
        }
        const rowPromises: Promise<T>[] = [];

        for (let i = 0; i < table.length - lengthReduction; i++) {
            const row = table[i];
            const values = $(row).find('input, select, .recipe-select');
            const rowValues: any[] = [];

            values.each((_, value) => {

                const $value = $(value);
                if ($value.hasClass('recipe-select')) {
                    new ProductionSelect($value);
                    return
                }
                if ($value.data('sp-skip') === true) {
                    return;
                }
                const type = $value.attr('type');
                const isNumeric = type === 'number' || $value.hasClass('production-quantity') || $value.hasClass('usage-amount') || $value.hasClass('export-amount');
                if (isNumeric) {
                    rowValues.push(Number($value.val()));
                } else {
                    rowValues.push($value.val());
                }
            });

            // Handle double export in recipes table (table-mode: extra <tr>, component-mode: extra block inside row)
            if (id === 'recipes' && (table[i + 1] as any)?.classList?.contains?.('extra-output')) {
                const extraRow = table[i + 1];
                const extraRowValues = $(extraRow).find('input, select').map((_, el) => $(el).val()).get();

                const extraRowInstance = new ExtraProductionRow(
                    extraRowValues[0] as string,
                    Number(extraRowValues[1]),
                    Number(extraRowValues[2])
                );

                rowValues.push(true, extraRowInstance);

                i++;
                this.finishedRows++;
            } else if (id === 'recipes') {
                const extraBlock = $(row).find('.extra-output');
                if (extraBlock.length && extraBlock.hasClass('is-visible')) {
                    const product = extraBlock.find('input.product-name[data-sp-skip="true"]').val() as string;
                    const usage = extraBlock.find('input.usage-amount[data-sp-skip="true"]').val() as string;
                    const exp = extraBlock.find('input.export-amount[data-sp-skip="true"]').val() as string;

                    const extraRowInstance = new ExtraProductionRow(
                        product || '',
                        Number(usage || 0),
                        Number(exp || 0)
                    );
                    rowValues.push(true, extraRowInstance);
                } else {
                    rowValues.push(false, null);
                }
            }

            if (id === 'recipes') {
                rowValues.push(this.recipeCache);
            }

            if (id === 'power') {
                rowValues.push(null);
                rowValues.push(this.buildingCache);
            }

            // @ts-ignore
            const rowPromise = rowClass.create(...rowValues).then((createdRow: any) => {
                if (useProgress) {
                    this.updateProgress();
                }

                if (id === 'recipes') {
                    const settings = this.recipeSettings.find((setting) => setting.id === +rowValues[0]);
                    (createdRow as ProductionTableRow).recipeSetting = new RecipeSetting(
                        this,
                        createdRow as ProductionTableRow,
                        $(row),
                        settings?.clockSpeed || 100,
                        settings?.useSomersloop || false
                    );
                }

                return createdRow;
            });
            rowPromises.push(rowPromise);

        }
        return await Promise.all(rowPromises);

    }

    /**
     * save cache to local storage
     * @returns {void}
     */
    private saveToLocal() {
        localStorage.setItem('cachedData', JSON.stringify({
            Version: this.cacheVersion,
            Recipe: this.recipeCache,
            Building: this.buildingCache,
        }));
    }

    /**
     * show cache amount
     * @returns {void}
     */
    private showCacheAmount() {
        // waint until document is loaded

        $('#cachedRecipes').html(this.recipeCache.length.toString());
        $('#cachedBuildings').html(this.buildingCache.length.toString());
    }

    /**
     * empty cache from local storage
     * @returns {void}
     */
    private emptyLocal() {
        localStorage.removeItem('cachedData');
    }
    /**
     * load from local storage
     * @returns {void}
     */
    private loadFromLocal() {
        const data = JSON.parse(localStorage.getItem('cachedData') || '{}');
        this.recipeCache = data.Recipe || [];
        this.buildingCache = data.Building || [];
        $('#cachedRecipes').html(this.recipeCache.length.toString());
        $('#cachedBuildings').html(this.buildingCache.length.toString());
    }

    private CheckCacheVersion() {
        const data = JSON.parse(localStorage.getItem('cachedData') || '{}');
        if (data.Version !== this.cacheVersion.toString()) {
            this.emptyLocal();
            this.loadFromLocal();
        }
    }


    /**
     * Hides the loading screen and shows the main content.
     * @returns {void}
     * @private
     */
    public hideLoading() {
        $('#loading').addClass('d-none');
        $('#main-content').removeClass('d-none');
    }

    /**
     * Shows the loading screen and hides the main content.
     * @returns {void}
     * @private
     */
    public showLoading(showLoadingBar: boolean = true) {
        this.progressBar.parent().removeClass('d-none');
        $('#loading').removeClass('d-none');
        $('#main-content').addClass('d-none');
        if (!showLoadingBar) {
            this.progressBar.parent().addClass('d-none');
        }
    }

    /**
     * Updates the progress bar.
     * @returns {void}
     * @private
     */
    private updateProgress() {
        this.finishedRows++;
        const progress = Math.round(this.finishedRows * this.progressInterval)
        this.progressBar.css('width', `${progress}%`);
        this.progressBar.attr('aria-valuenow', progress);
        this.progressBar.html(`${progress}%`);
    }

    /**
     * Adds event listeners for change events on all inputs and selects within tables.
     */
    private async addEventListeners() {
        this.bindRecipeCollapseControls($(document.body));
        this.bindImportCollapseControls();

        if (this.viewOnly) {
            return;
        }
        const tables = ['imports', 'recipes', 'power'];

        tables.forEach((tableId) => {
            const tableBody = this.getTableBody(tableId);
            const inputsAndSelects = tableBody.find('input:not([data-sp-skip="true"]), select:not([data-sp-skip="true"])');
            const deleteButton = tableBody.find('.delete-production-row');

            deleteButton.each((_, element) => {
                $(element).on('click', (event) => {
                    event.preventDefault();
                    const target = $(event.target as any);
                    const rowIndex = this.getRowIndexFromTarget(target, tableId);
                    this.deleteRow(tableId, rowIndex, target);
                });


            });

            inputsAndSelects.each((_, element) => {
                $(element).on('change', (event) => {
                    this.handleInputChange(event, tableId);
                });
            });
        });
    }

    private async addEventListenersRow(row: JQuery<HTMLElement>, tableId: string) {
        this.bindRecipeCollapseControls(row);

        row.find('input:not([data-sp-skip="true"]), select:not([data-sp-skip="true"])').each((_, element) => {
            $(element).on('change', (event) => {
                this.handleInputChange(event, tableId);
            });
        });

        row.find('.delete-production-row').on('click', (event) => {
            event.preventDefault();
            const target = $(event.target as any);
            const rowIndex = this.getRowIndexFromTarget(target, tableId);
            this.deleteRow(tableId, rowIndex, target);
        });
    }

    private addSpecificEventListener(tableId: string) {
        this.bindRecipeCollapseControls(this.getTableBody(tableId));
        const inputsAndSelects = this.getTableBody(tableId).find( 'input:not([data-sp-skip="true"]), select:not([data-sp-skip="true"])');

        inputsAndSelects.each((_, element) => {
            $(element).on('change', (event) => {
                this.handleInputChange(event, tableId);
            });
        });
    }

    /**
     * Handles the change event for table inputs/selects.
     * @param {JQuery.ChangeEvent} event - The change event object.
     * @param {string} tableId - The ID of the table where the event occurred.
     */
    private async handleInputChange(event: JQuery.ChangeEvent, tableId: string) {
        const target = $(event.target);
        const rowIndex = this.getRowIndexFromTarget(target, tableId);
        const domRowIndex = this.isTableElement(tableId) ? target.closest('tr').index() : rowIndex;
        const columnIndex = target.closest('td').index();
        let value = target.val();

        const isComponentRecipes = tableId === 'recipes' && !this.isTableElement(tableId);

        // If the last row is selected, add a new row (table mode). In component-mode recipes we use the "+" card.
        if (!isComponentRecipes && this.checkIfLastRow(target, tableId) && this.checkIfSelect(target)) {
            this.addNewRow(tableId);
        }

        const row = this.getRowByTableIdAndIndex(tableId, rowIndex);

        if (target.hasClass('production-quantity')) {
            const defaultValue = row.quantity;
            value = Calculations.applyMathCalculation(value, defaultValue);
            target.val(value);
        }

        if (row) {
            const field = (target.data('field') as string | undefined) || undefined;
            if (field) {
                const isNumeric = target.attr('type') === 'number' || target.hasClass('production-quantity');
                // @ts-ignore - field names map to row keys (e.g. quantity, recipeId)
                row[field] = isNumeric ? Number(value) : value;
            } else if (columnIndex >= 0) {
                this.updateRowData(row, columnIndex, value);
            }

            switch (tableId) {
                case 'imports':
                    // Keep import UI (icons + collapsed summary) in sync
                    this.updateRowInTable(tableId, domRowIndex, row);
                    break;
                case 'recipes':
                    await this.HandleProductionTable(row, domRowIndex, value, tableId, target);
                    this.checklist?.updateCheckList(row);
                    break;
                case 'power':
                    await this.HandlePowerTable(row, domRowIndex, value, tableId, target);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Retrieves the row from the corresponding table by tableId and rowIndex.
     * @param {string} tableId - The ID of the table.
     * @param {number} rowIndex - The index of the row to retrieve.
     * @returns {any} The row object.
     */
    private getRowByTableIdAndIndex(tableId: string, rowIndex: number): any {
        switch (tableId) {
            case 'imports':
                return this.importsTableRows[rowIndex];
            case 'recipes':
                return this.productionTableRows[rowIndex];
            case 'power':
                return this.powerTableRows[rowIndex];
            default:
                return null;
        }
    }

    /**
     * Updates the row object with new data based on the column index.
     * @param {any} row - The row object to update.
     * @param {number} columnIndex - The index of the column.
     * @param {any} value - The new value to set in the row.
     */
    private updateRowData(row: any, columnIndex: number, value: any) {
        const rowKeys = Object.keys(row);
        const key = rowKeys[columnIndex];
        row[key] = value;
    }

    private setRecipeRowCollapsed(rowEl: JQuery<HTMLElement>, collapsed: boolean) {
        const row = rowEl.closest('.pl-production-row');
        row.toggleClass('is-collapsed', collapsed);

        const btn = row.find('.pl-collapse-toggle').first();
        btn.attr('aria-expanded', (!collapsed).toString());

        const icon = btn.find('i');
        icon.removeClass('fa-chevron-up fa-chevron-down');
        icon.addClass(collapsed ? 'fa-chevron-down' : 'fa-chevron-up');
    }

    private setImportRowCollapsed(rowEl: JQuery<HTMLElement>, collapsed: boolean) {
        const row = rowEl.closest('.pl-import-row');
        row.toggleClass('is-collapsed', collapsed);

        const btn = row.find('.pl-import-collapse-toggle').first();
        btn.attr('aria-expanded', (!collapsed).toString());

        const icon = btn.find('i');
        icon.removeClass('fa-chevron-up fa-chevron-down');
        icon.addClass(collapsed ? 'fa-chevron-down' : 'fa-chevron-up');

        if (collapsed) {
            this.updateImportCollapsedSummary(row);
        }
    }

    private updateImportCollapsedSummary(row: JQuery<HTMLElement>) {
        const select = row.find('select[data-field="itemId"]').first();
        const hiddenItemId: any = row.find('input[data-field="itemId"]').first().val();

        const itemId = select.length
            ? (Number(select.val() as any) || 0)
            : (Number(hiddenItemId) || 0);

        const name = select.length ? (select.find('option:selected').text() || '') : '';
        row.find('[data-role="import-name-collapsed"]').text(name);

        const qtyVal: any = row.find('input[data-field="quantity"]').first().val();
        const qtyText = (qtyVal ?? '').toString();
        row.find('[data-role="import-qty-collapsed"]').text(qtyText);

        const iconSrc = HtmlGeneration.getItemIconSrcForId(itemId);

        const $imgs = row.find('img[data-role="import-icon"], img[data-role="import-icon-collapsed"]');
        if (!iconSrc) {
            $imgs.attr('src', '').css('display', 'none');
        } else {
            $imgs.attr('src', iconSrc).css('display', '');
        }

        // keep read-only qty display in sync when present
        if (row.find('[data-role="import-qty-display"]').length) {
            row.find('[data-role="import-qty-display"]').text(qtyText);
        }
    }

    private bindImportCollapseControls() {
        // Delegated handler so it keeps working when auto import/export re-renders #imports
        $(document.body)
            .off('click.pl-import-collapse', '.pl-import-collapse-toggle')
            .on('click.pl-import-collapse', '.pl-import-collapse-toggle', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const btn = $(event.currentTarget as any);
                const row = btn.closest('.pl-import-row');
                this.setImportRowCollapsed(row, !row.hasClass('is-collapsed'));
            });

        // Fill collapsed summaries for existing rows (including read-only rows without a button)
        $('#imports').find('.pl-import-row').each((_, el) => {
            this.updateImportCollapsedSummary($(el));
        });
    }

    private bindRecipeCollapseControls(scope: JQuery<HTMLElement>) {
        // Per-row toggle
        scope.find('.pl-collapse-toggle').off('click.pl-collapse').on('click.pl-collapse', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const btn = $(event.currentTarget as any);
            const row = btn.closest('.pl-production-row');
            this.setRecipeRowCollapsed(row, !row.hasClass('is-collapsed'));
        });

        // Global toggle
        $('#pl-toggle-collapse-all').off('click.pl-collapse').on('click.pl-collapse', (event) => {
            event.preventDefault();
            const button = $(event.currentTarget as any);
            const state = (button.data('state') as string) || 'expanded';
            const collapse = state === 'expanded';

            this.getRows('recipes').each((_, el) => {
                this.setRecipeRowCollapsed($(el), collapse);
            });

            button.data('state', collapse ? 'collapsed' : 'expanded');
            button.find('[data-role="label"]').text(collapse ? 'Expand all' : 'Collapse all');
            const icon = button.find('i');
            icon.removeClass('fa-compress fa-expand');
            icon.addClass(collapse ? 'fa-expand' : 'fa-compress');
        });
    }

    /**
     * Updates the visual representation of the row in the table.
     * @param {string} tableId - The ID of the table.
     * @param {number} rowIndex - The index of the row to update.
     * @param {any} row - The updated row object.
     */
    private updateRowInTable(tableId: string, rowIndex: number, row: any) {
        const table = this.getRows(tableId);
        let rowToUpdate = $(table[rowIndex]);

        if (this.isTableElement(tableId)) {
            rowToUpdate.find('input:not([data-sp-skip="true"]), select:not([data-sp-skip="true"])').each((index, element) => {
                const key = Object.keys(row)[index];
                let value: any;

                if (row.recipe && row.recipe.hasOwnProperty(key)) {
                    value = row.recipe[key];
                } else {
                    value = row[key];
                }
                $(element).val(value);
            });
        } else {
            // Component-mode: update only known fields explicitly (no column-index / Object.keys mapping)
            if (tableId === 'imports') {
                rowToUpdate.find('select[data-field="itemId"]').val(row.itemId);
                rowToUpdate.find('input[data-field="quantity"]').val(row.quantity);

                // keep collapsed summary (and icon) synced
                this.updateImportCollapsedSummary(rowToUpdate);
            }

            if (tableId === 'recipes') {
                // Keep recipe-select UI stable; only sync hidden id if present
                const recipeIdInput = rowToUpdate.find('[data-field="recipeId"]');
                if (recipeIdInput.length && row.recipeId) {
                    recipeIdInput.val(row.recipeId);
                }

                rowToUpdate.find('.production-quantity[data-field="quantity"]').val(row.quantity);

                rowToUpdate.find('input.product-name:not([data-sp-skip="true"])').val(row.product || '');
                rowToUpdate.find('input.usage-amount:not([data-sp-skip="true"])').val(row.Usage ?? 0);
                rowToUpdate.find('input.export-amount:not([data-sp-skip="true"])').val(row.exportPerMin ?? 0);
            }
        }
        if (tableId === 'recipes') {
            ProductionLineFunctions.handleDoubleExport(row, rowToUpdate);
            ProductionLineFunctions.updateRowIcons(row, rowToUpdate);
            ProductionLineFunctions.updateRowDisplay(row, rowToUpdate);
        }
    }

    /**
     * Adds a new row to the table when the last row is modified.
     * @param {string} tableId - The ID of the table.
     */
    private addNewRow(tableId: string) {
        const isComponent = !this.isTableElement(tableId);
        const isComponentRecipes = tableId === 'recipes' && isComponent;

        let sourceRow: JQuery<HTMLElement>;
        let insertBeforeEl: JQuery<HTMLElement> | null = null;
        let insertAfterEl: JQuery<HTMLElement> | null = null;

        if (isComponentRecipes) {
            const template = $('#recipes').find('[data-role="recipe-template"]').first();
            if (!template.length) return;

            sourceRow = template;
            const addCard = $('#recipes').find('[data-role="add-recipe-card"]').first();
            insertBeforeEl = addCard.length ? addCard : template;
        } else {
            if (tableId === 'power') {
                sourceRow = $(`#${tableId} tbody tr:nth-last-child(2)`);
            } else {
                sourceRow = this.getRows(tableId).last();
            }
            insertAfterEl = sourceRow;
        }

        const newRow = sourceRow.clone();
        if (isComponentRecipes) {
            newRow.removeClass('pl-recipe-template d-none').removeAttr('data-role');
            newRow.css('display', '');
        }
        newRow.find('input[type="number"]').val(0);
        newRow.find('input[type="text"]').val('');
        newRow.find('input[name="power_clock_speed[]"]').val(100);
        newRow.find('select').prop('selectedIndex', 0);
        newRow.find('.search-input').val('');
        newRow.find('input.recipe-id').val('');
        newRow.find('.extra-output').removeClass('is-visible');

        // Component UI: keep production defaults sane when cloning the last row
        newRow.find('.production-quantity').val('0');
        newRow.find('.pl-clock-speed').val(100);
        newRow.find('.pl-use-somersloop').prop('checked', false);

        // New rows should start expanded
        newRow.removeClass('is-collapsed');
        if (tableId === 'recipes') {
            this.setRecipeRowCollapsed(newRow, false);
        }
        if (tableId === 'imports') {
            this.setImportRowCollapsed(newRow, false);
        }

        if (insertBeforeEl) {
            newRow.insertBefore(insertBeforeEl);
        } else if (insertAfterEl) {
            newRow.insertAfter(insertAfterEl);
        }

        if (!this.isTableElement(tableId)) {
            this.reindexComponentRows(tableId);
        }

        this.addEventListenersRow(newRow, tableId);
        this.initTooltips(newRow);

        switch (tableId) {
            case 'imports':
                this.importsTableRows.push(new ImportsTableRow());
                break;
            case 'recipes':
                const productionRow = new ProductionTableRow();
                productionRow.recipeSetting = new RecipeSetting(this, productionRow, newRow);
                this.productionTableRows.push(productionRow);

                // Ensure the DOM row_id matches the in-memory row_id
                newRow.find('input[type="hidden"][name="production_id[]"]').val(productionRow.row_id as any);
                newRow.find('.delete-production-row').attr('data-id', productionRow.row_id as any);

                // Clear cloned display values/icons (new row should look empty)
                newRow.find('[data-role="building"]').attr('src', '').css('display', 'none');
                newRow.find('[data-role="output1"], [data-role="output2"], [data-role="collapsed-output1"], [data-role="collapsed-output2"]').attr('src', '').css('display', 'none');
                newRow.find('input.product-name').val('');
                newRow.find('[data-role="product1-text"], [data-role="product2-text"]').text('');
                newRow.find('[data-role="building-amount"]').text('');
                newRow.find('.usage-amount, .export-amount').val('0');
                newRow.find('[data-role="usage1-text"], [data-role="export1-text"], [data-role="usage2-text"], [data-role="export2-text"]').text('0');

                const recipeSelect = newRow.find('.recipe-select');
                if (recipeSelect.length) {
                    new ProductionSelect(recipeSelect);
                }
                break;
            case 'power':
                this.powerTableRows.push(new PowerTableRow());
                break;
            default:
                break;
        }
    }

    /**
     * Checks if the selected element is in the last row.
     * @param {JQuery} target - The target element.
     * @param {string} tableId - The ID of the table.
     * @returns {boolean} True if the element is in the last row, false otherwise.
     */
    private checkIfLastRow(target: JQuery, tableId: String): boolean {
        if (!this.isTableElement(tableId as string)) {
            const rowEl = target.closest('[data-row-index]');
            if (rowEl.length) {
                const idx = Number(rowEl.data('row-index'));
                return idx === this.getRows(tableId as string).length - 1;
            }
        }

        if (tableId === 'power') {
            return target.closest('tr').is(':nth-last-child(2)');
        }
        return target.closest('tr').is(':last-child');
    }

    /**
     * Checks if the selected element is a <select> element.
     * @param {JQuery} target - The target element.
     * @returns {boolean} True if the element is a <select>, false otherwise.
     */
    private checkIfSelect(target: JQuery): boolean {
        if (target.is('select')) {
            return true;
        }
        return target.parent().hasClass('recipe-select')
    }

    private UpdateOnIndex(indexes: number[]) {

        for (let i = 0; i < indexes.length; i++) {
            const index = indexes[i];
            const target = this.productionTableRows[index];
            const checkAbleRows = this.productionTableRows.slice(0, index);
            const amountExtra = checkAbleRows.filter(row => row.doubleExport).length;
            const row = this.productionTableRows[index];
            const tableRowIndex = this.isTableElement('recipes') ? index + amountExtra : index;
            this.updateRowInTable('recipes', tableRowIndex, row);
            // break;
        }
    }

    /**
     * Handles the change event for the power table.
     * @param row - The row object to update.
     * @param rowIndex - The index of the row in the table.
     * @param value - The new value to set in the row.
     * @param tableId - The ID of the table.
     * @param target - The target element that triggered the event.
     * @constructor
     * @private
     */
    private async HandlePowerTable(row: PowerTableRow, rowIndex: number, value: any, tableId: string, target: JQuery) {
        if (this.checkIfSelect(target)) {
            await PowerTableFunctions.updateBuilding(row, value);
        } else {
            row.Consumption = PowerTableFunctions.calculateSingleConsumption(row);
        }
        this.updateRowInTable(tableId, rowIndex, row);
        PowerTableFunctions.updateTotalConsumption(this.powerTableRows);
    }

    public showVisualization() {
        if (this.settings.autoImportExport) {
            const data: {
                importsTableRows: ImportsTableRow[],
                indexes: number[]
            } = ImportsTableFunctions.calculateImports(this.productionTableRows);
            if (this.viewOnly) {
                this.disableInputs()
            }
            this.importsTableRows = data.importsTableRows;
        }

        if (!this.visualisation) {
            this.updated = false;
            this.visualisation = new Visualization(this);
        } else if (this.updated) {
            this.updated = false;
            this.visualisation.update();
        } else {
            this.visualisation.updateNodeColors();
        }

        $('#showVisualization').modal('show');
    }

    public saveData(productionTable: ProductionTableRow[], powerTable: PowerTableRow[], importTable: ImportsTableRow[], checklist: IChecklist[] | null = null) {
        this.productionTableRows = productionTable.pop() ? productionTable : productionTable;
        this.powerTableRows = powerTable.pop() ? powerTable : powerTable;
        this.importsTableRows = importTable.pop() ? importTable : importTable;
        this.checklist?.setChecklist(checklist)
        this.generateTables();

        this.powerTableRows.push(new PowerTableRow());
        this.productionTableRows.push(new ProductionTableRow());
        this.importsTableRows.push(new ImportsTableRow());

        SaveFunctions.saveProductionLine(
            SaveFunctions.prepareSaveData(
                this.productionTableRows,
                this.powerTableRows,
                this.importsTableRows,
                this.checklist
            ),
            this
        );
    }

    private generateTables() {
        $('#power tbody').html(HtmlGeneration.generatePowerTable(this.powerTableRows, buildingOptions, PowerTableFunctions.calculateTotalConsumption(this.powerTableRows)));

        if (this.isTableElement('imports')) {
            $('#imports tbody').html(HtmlGeneration.generateImportsTableRows(this.importsTableRows));
        } else {
            $('#imports').html(HtmlGeneration.generateImportsCards(this.importsTableRows, this.settings.autoImportExport || this.viewOnly));
        }

        if (this.isTableElement('recipes')) {
            $('#recipes tbody').html(HtmlGeneration.generateProductionTableRows(this.productionTableRows));
        } else {
            this.renderProductionCardsFromTemplate();
        }

        this.addEventListeners();
    }

    private disableButtons() {
        $('#save_button').addClass('disabled');
        $('#save_button').prop('disabled', true);

        $('#edit_product_line').addClass('disabled');
        $('#edit_product_line').prop('disabled', true);

        $('#showCheckList').addClass('disabled');
        $('#showCheckList').prop('disabled', true);
    }

    private disableInputs() {
        $(".px-3.px-lg-5").find('input, select, .delete-production-row').each((_, element) => {
            const $element = $(element);
            $element.prop('disabled', true);
            $element.addClass('disabled');
        });
    }

    private enableButtons() {
        $('#save_button').removeClass('disabled');
        $('#save_button').prop('disabled', false);

        $('#showPower').removeClass('disabled');
        $('#showPower').prop('disabled', false);

        $('#showVisualizationButton').removeClass('disabled');
        $('#showVisualizationButton').prop('disabled', false);

        $('#edit_product_line').removeClass('disabled');
        $('#edit_product_line').prop('disabled', false);

        $('#showCheckList').removeClass('disabled');
        $('#showCheckList').prop('disabled', false);

    }

    /**
     * Handles the change event for the production table.
     * @param row - The row object to update.
     * @param rowIndex - The index of the row in the table.
     * @param value - The new value to set in the row.
     * @param tableId - The ID of the table.
     * @param target - The target element that triggered the event.
     * @constructor
     * @private
     */
    async HandleProductionTable(row: ProductionTableRow, rowIndex: number, value: any, tableId: string, target: JQuery) {
        this.updated = true;

        await ProductionLineFunctions.calculateProductionExport(row);

        if (this.checkIfSelect(target)) {
            await ProductionLineFunctions.updateRecipe(row, value);
        }

        this.updateRowInTable(tableId, rowIndex, row);

        if (this.settings.autoImportExport) {
            const data: {
                importsTableRows: ImportsTableRow[],
                indexes: number[]
            } = ImportsTableFunctions.calculateImports(this.productionTableRows);
            this.importsTableRows = data.importsTableRows;
            this.UpdateOnIndex(data.indexes);
        }

        if (this.settings.autoPowerMachine) {
            this.powerTableRows = PowerTableFunctions.calculateBuildings(this.productionTableRows, this.powerTableRows);
            this.addSpecificEventListener('power');
        }

    }

    private async addShortcuts() {
        const tableHandler = this;
        document.addEventListener("DOMContentLoaded", function () {
            document.addEventListener('keydown', (event) => {
                const powerModal = $('#showPowerModal');
                const editModal = $('#editProductionLine');
                const helpModal = $('#helpModal');
                const VisualizationModal = $('#showVisualization');

                function closeModals() {
                    powerModal.modal('hide');
                    editModal.modal('hide');
                    helpModal.modal('hide');
                    VisualizationModal.modal('hide');
                }

                // Save production line
                if (event.ctrlKey && event.key === 's') {
                    if (tableHandler.viewOnly) {
                        return;
                    }
                    event.preventDefault();
                    SaveFunctions.saveProductionLine(
                        SaveFunctions.prepareSaveData(
                            tableHandler.productionTableRows,
                            tableHandler.powerTableRows,
                            tableHandler.importsTableRows,
                            tableHandler.checklist
                        ),
                        tableHandler
                    );
                }

                // Open power modal
                if (event.ctrlKey && event.key === 'p') {
                    event.preventDefault();
                    if (powerModal.is(':hidden')) {
                        closeModals();
                        powerModal.modal('show');
                    } else {
                        powerModal.modal('hide');
                    }
                }

                // Open edit / settings modal
                if (event.ctrlKey && event.key === 'e') {
                    if (tableHandler.viewOnly) {
                        return;
                    }
                    event.preventDefault();
                    closeModals();
                    if (editModal.is(':hidden')) {
                        closeModals();
                        editModal.modal('show');
                    } else {
                        editModal.modal('hide');
                    }
                }

                // help modal
                if (event.ctrlKey && event.key === 'h') {
                    event.preventDefault();
                    if (helpModal.is(':hidden')) {
                        closeModals();
                        helpModal.find('#welcome').hide();
                        helpModal.modal('show');
                    } else {
                        helpModal.modal('hide');
                    }
                }

                // go back
                if (event.ctrlKey && event.key === 'q') {
                    event.preventDefault();
                    window.history.back();
                }

                // show visualization
                if (event.key === 'v' && event.ctrlKey) {
                    // if in input field, do not trigger
                    const ignoreTags = ['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON'];
                    // @ts-ignore
                    if (ignoreTags.includes(event.target.tagName)) {
                        return; // Early exit for these tags
                    }

                    event.preventDefault();
                    if (VisualizationModal.is(':hidden')) {
                        closeModals();
                        tableHandler.showVisualization();
                    } else {
                        VisualizationModal.modal('hide');
                    }
                }
            });
        });
    }

    private async addButtonEventListeners() {
        $('#showVisualizationButton').on('click', () => {
            this.showVisualization();
        });

        $('#removeCache').on('click', () => {
            this.emptyLocal();
            this.loadFromLocal()
            this.showCacheAmount();
        });

        $('#pl-add-recipe').on('click', (event) => {
            event.preventDefault();
            if (this.viewOnly) return;
            this.addNewRow('recipes');
        });
    }

    private deleteRow(tableId: string, rowIndex: number, target: JQuery<HTMLElement>) {
        const isComponentRecipes = tableId === 'recipes' && !this.isTableElement(tableId);
        if (!isComponentRecipes && this.checkIfLastRow(target, tableId)) {
            return;
        }
        switch (tableId) {
            case 'imports':
                this.importsTableRows.splice(rowIndex, 1);
                break;
            case 'recipes':
                this.productionTableRows.splice(rowIndex, 1);
                this.checklist?.removeChecklist(rowIndex);
                break;
            case 'power':
                this.powerTableRows.splice(rowIndex, 1);
                break;
            default:
                break;
        }
        this.updated = true;

        if (this.isTableElement(tableId)) {
            const tr = target.closest('tr');
            // if it has the class extra-output, remove one lower row too
            if (tr.next().hasClass('extra-output')) {
                tr.next().remove();
            }
            tr.remove();
        } else {
            target.closest('[data-row-index]').remove();
            this.reindexComponentRows(tableId);
        }
        if (tableId === 'recipes') {
            if (this.settings.autoImportExport) {
                const data: {
                    importsTableRows: ImportsTableRow[],
                    indexes: number[]
                } = ImportsTableFunctions.calculateImports(this.productionTableRows);
                this.importsTableRows = data.importsTableRows;
                this.UpdateOnIndex(data.indexes);
            }

            if (this.settings.autoPowerMachine) {
                this.powerTableRows = PowerTableFunctions.calculateBuildings(this.productionTableRows, this.powerTableRows);
                this.addSpecificEventListener('power');
            }
        }

        console.log(this.productionTableRows);
    }
}

