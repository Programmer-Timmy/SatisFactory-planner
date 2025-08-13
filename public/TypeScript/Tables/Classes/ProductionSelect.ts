import TriggeredEvent = JQuery.TriggeredEvent;

export class ProductionSelect {

    private element: JQuery<HTMLElement>;
    private recipeIdElement: JQuery<HTMLElement>;
    private searchElement: JQuery<HTMLElement>;
    private selectItemsElement: JQuery<HTMLElement>;
    private onResizeEvent?: any;

    public constructor(element: JQuery<HTMLElement>) {
        this.element = element;
        this.recipeIdElement = this.element.find('.recipe-id');
        this.searchElement = this.element.find('.search-input');
        this.selectItemsElement = this.element.find('.select-items');

        this.handleEvents();
        this.handleSearchInput({} as TriggeredEvent); // Initialize search input handling
        this.activateSelectedRecipe(this.recipeIdElement.val() as string); // Activate the selected recipe if it exists

    }

    private handleEvents() {
        // Example of handling a focus event
        // @ts-ignore
        this.searchElement.on('click', (event: JQuery.ClickEvent) => {
            if (event.button !== 0) {
                return;
            }

            this.handeFocus(event);
        });

        // @ts-ignore
        $('body').on('mouseup', (event: JQuery.ClickEvent) => {
            this.handleBlur(event);
        });

        this.selectItemsElement.on('click', '.select-item', (event: JQuery.ClickEvent) => {
            this.handleSelectItemClick(event);
        });

        this.searchElement.on('input', (event: JQuery.TriggeredEvent) => {
            this.handleSearchInput(event);
        });
    }

    private positionSelectItems() {
        this.selectItemsElement.detach().appendTo('body');
        this.selectItemsElement.addClass('show');

        // fully select the existing serach input
        this.searchElement.trigger("select");

        const windowInnerHeight = $(window).innerHeight() || 0;

        // set the position of the select items to the position of the search input
        const position = this.searchElement.offset() || {top: 0, left: 0};
        const outerHeight = this.searchElement.outerHeight() || 0;
        const outerWidth = this.searchElement.outerWidth() || 0;

        const selectItemsHeight = this.selectItemsElement.outerHeight() || 0;

        if (position.top + outerHeight + selectItemsHeight > windowInnerHeight) {
            this.selectItemsElement.css({
                bottom: windowInnerHeight - position.top,
                top: 'auto',
                left: position.left,
                width: outerWidth
            });
        } else {
            this.selectItemsElement.css({
                top: position.top + outerHeight,
                bottom: 'auto',
                left: position.left,
                width: outerWidth
            });
        }

    }

    private handeFocus(event: JQuery.ClickEvent) {
        // move the selected items to the top of the dom so it will overlap and not couse a scroll
        this.selectItemsElement.detach().appendTo('body');
        this.selectItemsElement.addClass('show');

        // set the position of the select items to the position of the search input
        this.positionSelectItems();

        this.onResizeEvent = $(window).on('resize', () => {
            this.positionSelectItems();
        });
    }

    private handleBlur(event: JQuery.ClickEvent) {
        // hide the select items
        if ((
            $(event.target).closest(this.selectItemsElement).length > 0 ||
            $(event.target).closest(this.searchElement).length > 0 ) &&
            event.button === 0 // only hide if the left mouse button is not pressed
        ) {
            return;
        }

        this.hideSelectItems();
    }

    private hideSelectItems() {
        // hide the select items
        this.selectItemsElement.removeClass('show');
        this.selectItemsElement.detach().appendTo(this.element);

        if (this.onResizeEvent) {
            this.onResizeEvent.off('resize');
        }
        // reset the position of the select items
        this.selectItemsElement.css({
            top: '',
            left: '',
            width: ''
        });
    }

    private handleSelectItemClick(event: JQuery.ClickEvent) {
        const target = $(event.currentTarget);
        if (target.closest('.select-item').length > 0) {
            // get the recipe id from the data attribute
            const recipeId = target.data('recipe-id');
            const recipeName = target.data('recipe-name');
            if (recipeId) {
                this.recipeIdElement.val(recipeId);
                this.searchElement.val(recipeName);
                this.hideSelectItems(); // hide the select items
                this.handleSearchInput({} as TriggeredEvent); // update the search input
                this.recipeIdElement.trigger('change'); // trigger change to update the recipe id

                // add the class active to the selected item
                target.closest('.select-item').addClass('active').siblings().removeClass('active');
            }
        }

    }

    private handleSearchInput(event: JQuery.TriggeredEvent) {
        const searchValue = this.searchElement.val() as string;
        let items = this.selectItemsElement.find('.select-item');

        const fullMatch: {element: JQuery<HTMLElement>, weight: number}[] = [];

        const sortedItems = items.toArray().sort((a, b) => {
            const aName = $(a).find('.recipe-name').text().toLowerCase();
            const bName = $(b).find('.recipe-name').text().toLowerCase();
            return aName.localeCompare(bName);
        });

        this.selectItemsElement.empty().append(sortedItems);

        items = this.selectItemsElement.find('.select-item'); // re-fetch items after sorting

        items.each((index, item) => {
            const $item = $(item);
            const productNames = $item.find('.recipe-product').map((_, el) => $(el).data('product-name')).get() as string[];
            const recipeName = $item.find('.recipe-name');
            // should match both product names and recipe name
            const matches = productNames.some(name => name.toLowerCase().includes(searchValue.toLowerCase())) ||
                recipeName.text().toLowerCase().includes(searchValue.toLowerCase());

            if (searchValue.toLowerCase() === recipeName.text().toLowerCase()) {
                fullMatch.push({
                    element: $item,
                    weight: 2 // full match has a higher weight
                })
            } else if ( productNames.some(name => name.toLowerCase() === searchValue.toLowerCase())) {
                fullMatch.push({
                    element: $item,
                    weight: 1 // partial match has a lower weight
                });
            }

            if (matches) {
                $item.show();
            } else {
                $item.hide();
            }
        });

        // place the full matches at the top of the list
        if (fullMatch.length > 0) {
            // sort full matches by weight (lower weight first)
            fullMatch.sort((a, b) => {
                if (a.weight !== b.weight) {
                    return a.weight - b.weight; // lower weight first
                }
                return b.element.text().localeCompare(a.element.text());
            });

            fullMatch.forEach(item => {
                item.element.detach().prependTo(this.selectItemsElement);
            });
        }

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

    private activateSelectedRecipe(recipeId: string) {
        // Find the select item with the matching recipe ID
        const selectedItem = this.selectItemsElement.find(`.select-item[data-recipe-id="${recipeId}"]`);
        if (selectedItem.length > 0) {
            // Add the active class to the selected item
            selectedItem.addClass('active').siblings().removeClass('active');
        }
    }
}