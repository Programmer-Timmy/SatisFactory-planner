import TriggeredEvent = JQuery.TriggeredEvent;

export class ProductionSelect {

    private element: JQuery<HTMLElement>;
    private recipeIdElement: JQuery<HTMLElement>;
    private searchElement: JQuery<HTMLElement>;
    private selectItemsElement: JQuery<HTMLElement>;
    private selectedItemId?: number;
    private onResizeEvent?: any;

    public constructor(element: JQuery<HTMLElement>) {
        console.log("ProductionSelect constructor called");

        this.element = element;
        this.recipeIdElement = this.element.find('.recipe-id');
        this.searchElement = this.element.find('.search-input');
        this.selectItemsElement = this.element.find('.select-items');

        this.handleEvents();
        this.handleSearchInput({} as TriggeredEvent); // Initialize search input handling

    }

    private handleEvents() {
        // Example of handling a focus event
        this.searchElement.on('focus', (event: JQuery.FocusEvent) => {
            this.handeFocus(event);
        });

        // @ts-ignore
        this.searchElement.on('blur', (event: JQuery.FocusEvent) => {
            this.handleBlur(event);
        });

        this.selectItemsElement.on('click', '.select-item', (event: JQuery.ClickEvent) => {
            console.log("Select item clicked", event);
            this.handleSelectItemClick(event);
        });

        this.searchElement.on('input', (event: JQuery.TriggeredEvent) => {
            this.handleSearchInput(event);
        });
    }

    private positionSelectItems() {
        this.selectItemsElement.detach().appendTo('body');
        this.selectItemsElement.addClass('show');

        const windowInnerHeight = $(window).innerHeight() || 0;

        // set the position of the select items to the position of the search input
        const position = this.searchElement.offset() || { top: 0, left: 0 };
        const outerHeight = this.searchElement.outerHeight() || 0;
        const outerWidth = this.searchElement.outerWidth() || 0;

        const selectItemsHeight = this.selectItemsElement.outerHeight() || 0;

        if (position.top + outerHeight + selectItemsHeight > windowInnerHeight) {
            this.selectItemsElement.css({
                top: position.top - selectItemsHeight,
                left: position.left,
                width: outerWidth
            });
        } else {
            this.selectItemsElement.css({
                top: position.top + outerHeight,
                left: position.left,
                width: outerWidth
            });
        }

    }

    private handeFocus(event: JQuery.FocusEvent) {
        // move the selected items to the top of the dom so it will overlap and not couse a scroll
        this.selectItemsElement.detach().appendTo('body');
        this.selectItemsElement.addClass('show');

        // set the position of the select items to the position of the search input
        this.positionSelectItems();

        this.onResizeEvent = $(window).on('resize', () => {
            this.positionSelectItems();
        });
    }

    private handleBlur(event: JQuery.FocusEvent) {
        // hide the select items
        setTimeout(() => {

            this.selectItemsElement.removeClass('show');
            this.selectItemsElement.detach().appendTo(this.element);
            // remove the resize event
            if (this.onResizeEvent) {
                this.onResizeEvent.off('resize');
            }
            // reset the position of the select items
            this.selectItemsElement.css({
                top: '',
                left: '',
                width: ''
            });
        }, 200);
    }

    private handleSelectItemClick(event: JQuery.ClickEvent) {
        const target = $(event.currentTarget);
        if (target.closest('.select-item').length > 0) {
            // get the recipe id from the data attribute
            const recipeId = target.data('recipe-id');
            const recipeName = target.data('recipe-name');
            if (recipeId) {
                this.selectedItemId = recipeId;
                this.recipeIdElement.val(recipeId);
                this.searchElement.val(recipeName);
                this.element.trigger('blur'); // trigger blur to hide the select items
                this.recipeIdElement.trigger('change'); // trigger change to update the recipe id

                // add the class active to the selected item
                target.closest('.select-item').addClass('active').siblings().removeClass('active');
            }
        }

    }

    private handleSearchInput(event: JQuery.TriggeredEvent) {
        console.log("Search input changed", event);
        const searchValue = this.searchElement.val() as string;
        const items = this.selectItemsElement.find('.select-item');


        items.each((index, item) => {
            const $item = $(item);
            const productNames = $item.find('.recipe-product').map((_, el) => $(el).data('product-name')).get() as string[];
            const recipeName = $item.find('.recipe-name');

            console.log("Checking item:", $item, "with product names:", productNames, "and recipe name:", recipeName.text());
            // should match both product names and recipe name
            const matches = productNames.some(name => name.toLowerCase().includes(searchValue.toLowerCase())) ||
                recipeName.text().toLowerCase().includes(searchValue.toLowerCase());
            if (matches) {
                $item.show();
            } else {
                $item.hide();
            }
        });

        // if no items are visible, show a message but if message is already shown, do not show it again
        const visibleItems = items.filter((_, item) => $(item).css('display') !== 'none');

        if (visibleItems.length === 0) {
            if (this.selectItemsElement.find('.no-results').length === 0) {
                this.selectItemsElement.append('<div class="no-results text-muted text-center p-2">No results found</div>');
            }
        } else {
            this.selectItemsElement.find('.no-results').remove();
        }
    }
}