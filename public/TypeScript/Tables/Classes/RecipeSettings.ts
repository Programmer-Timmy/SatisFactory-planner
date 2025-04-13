import {TableHandler} from "./TableHandler";
import {ProductionTableRow} from "./Data/ProductionTableRow";

export class RecipeSettings {
    minClockSpeed: number = 0;
    maxClockSpeed: number = 100;
    useSomersloop: boolean = false;

    productionTableRow: ProductionTableRow
    tableHandler: TableHandler

    htmlElement: JQuery<HTMLElement>
    contextMenu: JQuery<HTMLElement> | null = null;


    constructor(tableHandler: TableHandler, productionTableRow: ProductionTableRow, htmlElement: JQuery<HTMLElement>) {
        this.tableHandler = tableHandler;
        this.productionTableRow = productionTableRow;
        this.htmlElement = htmlElement;
        this.addEventListeners();

        console.log(this)
    }


    private addEventListeners() {
    //     on tr right click
        this.htmlElement.on('contextmenu', (event: JQuery.Event) => {
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

    }

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

    showOptions(renderElement: JQuery<HTMLElement>) {
        const minClockSpeedInput = $(`
        <input type="number" class="form-control" id="minClockSpeed" value="${this.minClockSpeed}">
    `);
        const maxClockSpeedInput = $(`
        <input type="number" class="form-control" id="maxClockSpeed" value="${this.maxClockSpeed}">
    `);
        const useSomersloopInput = $(`
        <input type="checkbox" class="form-check-input" id="useSomersloop" ${this.useSomersloop ? 'checked' : ''}>
    `);

        const wrapper = $('<div class="context-menu-wrapper">');

        const minMax = $('<div class="row mb-3">');
        const colMin = $('<div class="col-6">').append(`
        <div class="form-group">
            <label for="minClockSpeed">Min Clock Speed</label>
        </div>
    `);
        colMin.find('.form-group').append(minClockSpeedInput);

        const colMax = $('<div class="col-6">').append(`
        <div class="form-group">
            <label for="maxClockSpeed">Max Clock Speed</label>
        </div>
    `);
        colMax.find('.form-group').append(maxClockSpeedInput);

        minMax.append(colMin, colMax);


        const checkboxWrapper = $('<div class="form-group">');
        checkboxWrapper.append('<label class="form-check-label me-2" for="useSomersloop">Use Somersloop</label>');
        checkboxWrapper.append(useSomersloopInput);

        wrapper.append(minMax, `<hr>`, checkboxWrapper);
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

        // Add event listeners here if needed
    }


    hideSettings() {
        const contextMenu = this.contextMenu;
        if (!contextMenu) return;

        if (contextMenu.length > 0) {
            contextMenu.remove();
            this.contextMenu = null;
        }
    }

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
