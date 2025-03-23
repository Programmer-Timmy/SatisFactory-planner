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
import {Checklist} from "./Checklist";


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

    // cache data
    private cacheVersion: number = <number>$('#dataVersion').val();
    private buildingCache: Building[] = [];
    private recipeCache: Recipe[] = [];


    // progress bar
    private progressBar = $('#loading-progress');
    private progressInterval: number = 0;
    private totalRows = 0;
    private finishedRows = 0;

    constructor() {
        this.initialize();
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
        this.addEventListeners();
        this.addButtonEventListeners();
        this.addShortcuts();

        // wait for a little bit so the user can see the 100% progress
        setTimeout(() => {
            this.hideLoading();
            this.enableButtons();
        }, timeOutTime);

        this.checklist = new Checklist(this);
    }

    private async getTableData() {
        // if page is reloaded
        this.totalRows = $('#recipes tbody tr').length;
        this.progressInterval = 100 / this.totalRows;

        this.productionTableRows = await this.readTable<ProductionTableRow>('recipes', ProductionTableRow, true);

        await this.readTable<ImportsTableRow>('imports', ImportsTableRow).then(result => {
            this.importsTableRows = result;
        }).catch(error => {
            console.error('Failed to load imports table rows:', error);
        });

        this.readTable<PowerTableRow>('power', PowerTableRow).then(result => {
            this.powerTableRows = result;
            this.saveToLocal();
            this.showCacheAmount();
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
        const table = $(`#${id} tbody tr`);
        let lengthReduction = 0;
        if (id === 'power') {
            lengthReduction = 1;
        }
        const rowPromises: Promise<T>[] = [];

        for (let i = 0; i < table.length - lengthReduction; i++) {
            const row = table[i];
            const values = $(row).find('input, select');
            const rowValues: any[] = [];

            values.each((_, value) => {
                const type = $(value).attr('type');
                if (type === 'number') {
                    rowValues.push(Number($(value).val()));
                } else {
                    rowValues.push($(value).val());
                }
            });

            // Handle double export in recipes table
            if (id === 'recipes' && table[i + 1]?.classList.contains('extra-output')) {
                const extraRow = table[i + 1];
                const extraRowValues = $(extraRow).find('input, select').map((_, el) => $(el).val()).get();

                // Create instance of ExtraProductionRow
                const extraRowInstance = new ExtraProductionRow(
                    extraRowValues[0] as string,          // Product
                    Number(extraRowValues[1]),            // Usage
                    Number(extraRowValues[2])             // ExportPerMin
                );

                // Append extraRowInstance to rowValues
                rowValues.push(true, extraRowInstance);

                // Skip the extra row in the next iteration
                i++;
                this.finishedRows++;
            } else if (id === 'recipes') {
                rowValues.push(false, null);
            }

            if (id === 'recipes') {
                rowValues.push(this.recipeCache);
            }

            if (id === 'power') {
                rowValues.push(null);
                rowValues.push(this.buildingCache);
            }

            // @ts-ignore
            const rowPromise = rowClass.create(...rowValues).then((row) => {
                if (useProgress) {
                    this.updateProgress();
                }
                return row;
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
        const tables = ['imports', 'recipes', 'power'];

        tables.forEach((tableId) => {
            const inputsAndSelects = $(`#${tableId} tbody`).find('input, select');

            inputsAndSelects.each((_, element) => {
                $(element).on('change', (event) => {
                    this.handleInputChange(event, tableId);
                });
            });
        });
    }

    private addSpecificEventListener(tableId: string) {
        const inputsAndSelects = $(`#${tableId} tbody`).find('input, select');

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
        const rowIndex = target.closest('tr').index();
        const amountExtra = target.closest('tr').prevAll('.extra-output').length;
        const columnIndex = target.closest('td').index();
        const value = target.val();

        // If the last row is selected, add a new row
        if (this.checkIfLastRow(target, tableId) && this.checkIfSelect(target)) {
            this.addNewRow(tableId);
        //     add to the checklist
        //     this.checklist?.addCheckList(this.productionTableRows[rowIndex - amountExtra]);
        }

        const row = this.getRowByTableIdAndIndex(tableId, rowIndex - amountExtra);


        if (row && columnIndex >= 0) {
            this.updateRowData(row, columnIndex, value);

            switch (tableId) {
                case 'imports':
                    // Custom logic for imports table
                    break;
                case 'recipes':
                    await this.HandleProductionTable(row, rowIndex, value, tableId, target);
                    this.checklist?.updateCheckList(row);
                    break;
                case 'power':
                    await this.HandlePowerTable(row, rowIndex, value, tableId, target);
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

    /**
     * Updates the visual representation of the row in the table.
     * @param {string} tableId - The ID of the table.
     * @param {number} rowIndex - The index of the row to update.
     * @param {any} row - The updated row object.
     */
    private updateRowInTable(tableId: string, rowIndex: number, row: any) {
        const table = $(`#${tableId} tbody tr`);
        let rowToUpdate = $(table[rowIndex]);
        rowToUpdate.find('input, select').each((index, element) => {
            const key = Object.keys(row)[index];
            let value: any;

            if (row.recipe && row.recipe.hasOwnProperty(key)) {
                value = row.recipe[key];  // Use value from recipe
            } else {
                value = row[key];  // Use value from row

            }
            $(element).val(value);
        });
        if (tableId === 'recipes') {
            ProductionLineFunctions.handleDoubleExport(row, rowToUpdate);
        }
    }

    /**
     * Adds a new row to the table when the last row is modified.
     * @param {string} tableId - The ID of the table.
     */
    private addNewRow(tableId: string) {
        let lastRow
        if (tableId === 'power') {
            lastRow = $(`#${tableId} tbody tr:nth-last-child(2)`);
        } else {
            lastRow = $(`#${tableId} tbody tr:last`);
        }

        const newRow = lastRow.clone();
        newRow.find('input[type="number"]').val(0);
        newRow.find('input[type="text"]').val('');
        newRow.find('input[name="power_clock_speed[]"]').val(100);
        newRow.find('select').prop('selectedIndex', 0);
        newRow.insertAfter(lastRow);

        newRow.find('input, select').each((_, element) => {
            $(element).on('change', (event) => {
                this.handleInputChange(event, tableId);
            });
        });

        switch (tableId) {
            case 'imports':
                this.importsTableRows.push(new ImportsTableRow());
                break;
            case 'recipes':
                this.productionTableRows.push(new ProductionTableRow());
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
        return target.is('select');
    }

    private UpdateOnIndex(indexes: number[]) {

        for (let i = 0; i < indexes.length; i++) {
            const index = indexes[i];
            const target = this.productionTableRows[index];
            const checkAbleRows = this.productionTableRows.slice(0, index);
            const amountExtra = checkAbleRows.filter(row => row.doubleExport).length;
            const row = this.productionTableRows[index];
            this.updateRowInTable('recipes', index + amountExtra, row);
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

    public saveData(productionTable: ProductionTableRow[], powerTable: PowerTableRow[], importTable: ImportsTableRow[]) {
        this.productionTableRows = productionTable.pop() ? productionTable : productionTable;
        this.powerTableRows = powerTable.pop() ? powerTable : powerTable;
        this.importsTableRows = importTable.pop() ? importTable : importTable;
        this.generateTables();

        this.powerTableRows.push(new PowerTableRow());
        this.productionTableRows.push(new ProductionTableRow());
        this.importsTableRows.push(new ImportsTableRow());
    }

    private generateTables() {
        $('#power tbody').html(HtmlGeneration.generatePowerTable(this.powerTableRows, buildingOptions, PowerTableFunctions.calculateTotalConsumption(this.powerTableRows)));
        $('#imports tbody').html(HtmlGeneration.generateImportsTableRows(this.importsTableRows));
        $('#recipes tbody').html(HtmlGeneration.generateProductionTableRows(this.productionTableRows));
        this.addEventListeners();
    }

    private disableButtons() {
        $('#save_button').addClass('disabled');
        $('#save_button').prop('disabled', true);

        $('#showPower').addClass('disabled');
        $('#showPower').prop('disabled', true);
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
    private async HandleProductionTable(row: ProductionTableRow, rowIndex: number, value: any, tableId: string, target: JQuery) {
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
    }
}

