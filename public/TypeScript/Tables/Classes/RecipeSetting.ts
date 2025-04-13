import {TableHandler} from "./TableHandler";
import {ProductionTableRow} from "./Data/ProductionTableRow";

export class RecipeSetting {
    clockSpeed: number = 100;
    useSomersloop: boolean = false;

    productionTableRow: ProductionTableRow
    tableHandler: TableHandler

    htmlElement: JQuery<HTMLElement>
    contextMenu: JQuery<HTMLElement> | null = null;


    /**
     * Constructor for the RecipeSettings class.
     *
     * @param tableHandler - The TableHandler instance.
     * @param productionTableRow - The ProductionTableRow instance.
     * @param htmlElement - The HTML element to attach the context menu to.
     */
    constructor(tableHandler: TableHandler, productionTableRow: ProductionTableRow, htmlElement: JQuery<HTMLElement>) {
        this.tableHandler = tableHandler;
        this.productionTableRow = productionTableRow;
        this.htmlElement = htmlElement;
        this.addEventListeners();

        console.log(this)
    }

    /**
     * Add event listeners to the context menu and the window.
     */
    private addEventListeners() {
    //     on tr right click
        this.htmlElement.on('contextmenu', (event: JQuery.ContextMenuEvent) => {
            // if somthing is selected, do not show the context menu
            if (window.getSelection()?.toString()) {
                return;
            }
            if (this.contextMenu) {
                this.hideSettings();
            }

            event.preventDefault();
            console.log('right click on tr');
            this.showSettings(event);
        });

    //     on resizing the window, hide the context menu
        $(window).on('resize', () => {
            if (this.contextMenu) {
                this.hideSettings();
            }
        });

        // on click hide the context menu
        $(document).on('click', (event: JQuery.ClickEvent) => {
            if (!$(event.target).closest('.context-menu').length) {
                event.stopPropagation();
                this.hideSettings();
            }
        });

        $(document).on('contextmenu', (event: JQuery.ContextMenuEvent) => {
            console.log(event.target.closest('tr'))
            if ($(event.target).closest('tr').length) {
                return;
            }
            if (!$(event.target).closest('.context-menu').length) {
                event.stopPropagation();
                this.hideSettings();
            }
        });

    }

    /**
     * Show the settings menu for the recipe.
     *
     * @param event - The event that triggered the context menu.
     * @private
     */
    private showSettings(event: JQuery.Event) {
        // create new element
        const tr = this.htmlElement;
        const contextMenu = $('<div class="context-menu">');
        contextMenu.css('display', 'block');
        contextMenu.css(
            'top',
            ((tr?.offset()?.top ?? 0) + (tr?.height() ?? 0) * (this.productionTableRow.doubleExport ? 2 : 1)) + 'px'
        );
        contextMenu.addClass('bg-body p-2 shadow rounded-bottom border border-primary');
        contextMenu.css('left', tr.offset()?.left + 'px');
        contextMenu.css('z-index', '9999');
        contextMenu.css('width', tr.width() + 'px');
        contextMenu.css('height', 'auto');
        contextMenu.css('position', 'absolute');


        // on outside click hide the context menu
        $(document).on('click', (event: JQuery.ClickEvent) => {
            if (!$(event.target).closest('.context-menu').length) {
                event.stopPropagation();
                this.hideSettings();
            }
        });

        this.showOptions(contextMenu);

        // append the context menu to the body
        contextMenu.appendTo('body');
        this.contextMenu = contextMenu;
        this.initCheckBoxes();
        console.log('show settings');
    }

    /**
     * Show the options in the context menu.
     *
     * @param renderElement - The element to render the options in.
     */
    showOptions(renderElement: JQuery<HTMLElement>) {
        const maxClockSpeedInput = $(`
        <input type="number" class="form-control" id="maxClockSpeed" min="0" max="250"  value="${this.clockSpeed}">
    `);
        const useSomersloopInput = $(`
        <input type="checkbox" class="form-check-input" id="useSomersloop" ${this.useSomersloop ? 'checked' : ''}>
    `);

        const wrapper = $('<div class="context-menu-wrapper">');


        const ClockSpeedWrapper = $('<div class="form-group">').append('<label class="form-check-label me-2" for="useSomersloop">Clock Speed</label>');
        ClockSpeedWrapper.append(maxClockSpeedInput);
        const checkboxWrapper = $('<div class="form-group">');
        checkboxWrapper.append('<label class="form-check-label me-2" for="useSomersloop">Use Somersloop</label>');
        checkboxWrapper.append(useSomersloopInput);

        wrapper.append(ClockSpeedWrapper, `<hr>`, checkboxWrapper);
        wrapper.appendTo(renderElement);

        // Optional: If you're using Bootstrap Toggle (plugin), initialize it
        if (typeof ($ as any).fn.bootstrapToggle === 'function') {
            useSomersloopInput.attr({
                'data-toggle': 'toggle',
                'data-onstyle': 'success',
                'data-offstyle': 'danger',
                'data-on': 'Yes',
                'data-off': 'No',
                // @ts-ignore
            }).bootstrapToggle();
        }

        const rowIndex = this.tableHandler.productionTableRows.findIndex((row) => row.row_id === this.productionTableRow.row_id);
        // Add event listeners for the inputs
        maxClockSpeedInput.on('change', () => {
            this.updateSettings();
            this.tableHandler.HandleProductionTable(this.productionTableRow, rowIndex, this.productionTableRow.quantity, 'recipes', this.htmlElement);
        });
        useSomersloopInput.on('change', () => {
            this.updateSettings();
            this.tableHandler.HandleProductionTable(this.productionTableRow, rowIndex, this.productionTableRow.quantity, 'recipes', this.htmlElement);
        });
    }

    /**
     * Update the settings based on the inputs.
     *
     * @private
     */
    private updateSettings() {
        const maxClockSpeedInput = $('#maxClockSpeed');
        const useSomersloopInput = $('#useSomersloop');

        if (maxClockSpeedInput.length > 0) {
            this.clockSpeed = this.validateClockSpeed(parseInt(maxClockSpeedInput.val() as string));
            maxClockSpeedInput.val(this.clockSpeed);
        }

        if (useSomersloopInput.length > 0) {
            this.useSomersloop = useSomersloopInput.is(':checked');
        }
    }

    private validateClockSpeed(clockSpeed: number): number {
        if (clockSpeed > 250) {
            clockSpeed = 250;
        }

        if (clockSpeed < 0) {
            clockSpeed = 0;
        }

        return clockSpeed
    }

    /**
     * Hide the settings menu.
     */
    hideSettings() {
        const contextMenu = this.contextMenu;
        if (!contextMenu) return;

        if (contextMenu.length > 0) {
            contextMenu.remove();
            this.contextMenu = null;
        }
    }

    /**
     * Initialize the checkboxes in the context menu.
     *
     * @private
     */
    private initCheckBoxes() {
            if (!this.contextMenu) return;
            const checkboxes = this.contextMenu.find('input[type="checkbox"]');
            checkboxes.each((index, checkbox) => {
                const $checkbox = $(checkbox);
                // @ts-ignore
                $checkbox.bootstrapToggle();
            });
    }
}
