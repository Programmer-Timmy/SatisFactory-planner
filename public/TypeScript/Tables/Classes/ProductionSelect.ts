import TriggeredEvent = JQuery.TriggeredEvent;

export class ProductionSelect {

    private readonly element: JQuery<HTMLElement>;
    private recipeIdElement: JQuery<HTMLElement>;
    private searchElement: JQuery<HTMLElement>;
    private readonly selectItemsElement: JQuery<HTMLElement>;
    private selectItemsMenu: JQuery<HTMLElement>;
    private iconGroup: JQuery<HTMLElement>;
    private onResizeEvent?: any;
    private open = false;
    private showVisuals: boolean = true;
    private searchByMenuSettings: {
        show: boolean,
        searchByProducts: boolean,
        searchByIngredients: boolean
    } = {
        show: false,
        searchByProducts: true,
        searchByIngredients: false
    }

    /**
     * Constructs a new ProductionSelect instance.
     * It initializes the element, recipe ID element, search input, select items menu, and icon group.
     * It also sets up event handlers for user interactions such as clicks and input changes.
     * @param element The jQuery-wrapped HTML element representing the production select component.
     */
    public constructor(element: JQuery<HTMLElement>) {
        this.element = element;
        this.recipeIdElement = this.element.find('.recipe-id');
        this.searchElement = this.element.find('.search-input');
        this.selectItemsMenu = this.element.find('.select-items-menu');
        this.selectItemsElement = this.element.find('.select-items');
        this.iconGroup = this.element.find('.icon-group');
        this.showVisuals = localStorage.getItem('showVisuals') === 'true' || localStorage.getItem('showVisuals') === null
        // { show: boolen, searchByProdcuts: boolean, searchByingredients: boolean } its in one object in the localStorage
        this.searchByMenuSettings = localStorage.getItem('searchByMenuSettings') ? JSON.parse(localStorage.getItem('searchByMenuSettings') as string) : this.searchByMenuSettings;

        this.handleEvents();
        this.handleSearchInput({} as TriggeredEvent); // Initialize search input handling
        this.activateSelectedRecipe(this.recipeIdElement.val() as string); // Activate the selected recipe if it exists
        this.showHideVisuals(); // Set the initial state of visuals based on localStorage
        this.showHideSearchByMenu(); // Set the initial state of search by menu based on localStorage
    }

    /**
     * Handles the events for the production select component.
     * It binds click events to the search input, body, select items, and icon group.
     * It also handles input events on the search input for filtering select items.
     */
    private handleEvents() {
        // Example of handling a focus event
        // @ts-ignore
        this.searchElement.on('click', (event: JQuery.ClickEvent) => {
            if (event.button !== 0) {
                return;
            }

            this.handeFocus(event);
            this.updateSearchByMenuSettings()
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

        this.iconGroup.on('click', (event: JQuery.ClickEvent) => {
            // check if name attr is showHideRecipeVisuals
            const target = $(event.target).closest('.search-by-menu-button');
            if (target.attr('name') === 'showHideRecipesVisuals') {
                // toggle the showVisuals variable
                this.showVisuals = !this.showVisuals;
                localStorage.setItem('showVisuals', this.showVisuals.toString());
                this.showHideVisuals();
            }

            if(target.attr('name') === 'searchByMenu') {
                this.searchByMenuSettings.show = !this.searchByMenuSettings.show;
                    localStorage.setItem('searchByMenuSettings', JSON.stringify(this.searchByMenuSettings));

                this.showHideSearchByMenu();
            }
        })

        // Handle search by products and ingredients toggling
        this.selectItemsMenu.on('click', '.search-by-products', (event: JQuery.ClickEvent) => {
            this.searchByMenuSettings.searchByProducts = !this.searchByMenuSettings.searchByProducts;
            localStorage.setItem('searchByMenuSettings', JSON.stringify(this.searchByMenuSettings));
            $(event.currentTarget).toggleClass('active', this.searchByMenuSettings.searchByProducts);
            this.handleSearchInput({} as TriggeredEvent); // Re-filter items based on the new settings
        });

        this.selectItemsMenu.on('click', '.search-by-ingredients', (event: JQuery.ClickEvent) => {
            this.searchByMenuSettings.searchByIngredients = !this.searchByMenuSettings.searchByIngredients;
            localStorage.setItem('searchByMenuSettings', JSON.stringify(this.searchByMenuSettings));
            $(event.currentTarget).toggleClass('active', this.searchByMenuSettings.searchByIngredients);
            this.handleSearchInput({} as TriggeredEvent); // Re-filter items based on the new settings
        });
    }


    private updateSearchByMenuSettings() {
        this.searchByMenuSettings = localStorage.getItem('searchByMenuSettings') ? JSON.parse(localStorage.getItem('searchByMenuSettings') as string) : this.searchByMenuSettings;
        // check the checkboxes of the inputs
        this.selectItemsMenu.find('.search-by-products')
            .first()
            .prop('checked', this.searchByMenuSettings.searchByProducts);

        this.selectItemsMenu.find('.search-by-ingredients')
            .first()
            .prop('checked', this.searchByMenuSettings.searchByIngredients);


    }

    /**
     * Toggles the visibility of the search by menu based on the settings stored in localStorage.
     * It adds or removes the 'show' class from the search-by-menu element in the select items menu.
     * The state is also saved to localStorage.
     */
    private showHideSearchByMenu() {
        this.searchByMenuSettings.show = localStorage.getItem('searchByMenuSettings') ? JSON.parse(localStorage.getItem('searchByMenuSettings') as string).show : false;
        if (this.searchByMenuSettings.show) {
            this.selectItemsMenu.find('.search-by-menu').addClass('show');
        } else {
            this.selectItemsMenu.find('.search-by-menu').removeClass('show');
        }
    }

    /**
     * Toggles the visibility of visuals in the select items menu based on the showVisuals variable.
     * It updates the class of the select items menu and the icon in the icon group accordingly.
     * The state is also saved to localStorage.
     */
    private showHideVisuals() {
        this.showVisuals = localStorage.getItem('showVisuals') === 'true' || localStorage.getItem('showVisuals') === null;
        if (this.showVisuals) {
            this.selectItemsMenu.removeClass('hide-visuals');
            this.iconGroup.find('.search-by-menu-button[name="showHideRecipesVisuals"] i')
                .removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            this.selectItemsMenu.addClass('hide-visuals');
            this.iconGroup.find('.search-by-menu-button[name="showHideRecipesVisuals"] i')
                .removeClass('fa-eye-slash').addClass('fa-eye');
        }
        this.moveIcons();
    }

    /**
     * Positions the select items menu relative to the search input.
     * It calculates the position based on the search input's offset and the window's inner height.
     * If the select items menu would overflow the window, it adjusts its position accordingly.
     */
    private positionSelectItems() {
        this.selectItemsMenu.detach().appendTo('body');
        this.selectItemsMenu.addClass('show');

        // fully select the existing serach input
        this.searchElement.trigger("select");

        const windowInnerHeight = $(window).innerHeight() || 0;

        // set the position of the select items to the position of the search input
        const position = this.searchElement.offset() || {top: 0, left: 0};
        const outerHeight = this.searchElement.outerHeight() || 0;
        const outerWidth = this.searchElement.outerWidth() || 0;

        const selectItemsHeight = this.selectItemsMenu.outerHeight() || 0;

        if (position.top + outerHeight + selectItemsHeight > windowInnerHeight) {
            this.selectItemsMenu.css({
                bottom: windowInnerHeight - position.top,
                top: 'auto',
                left: position.left,
                width: outerWidth
            });
        } else {
            this.selectItemsMenu.css({
                top: position.top + outerHeight,
                bottom: 'auto',
                left: position.left,
                width: outerWidth
            });
        }
    }

    /**
     * Handles the focus event on the search input, which is triggered when the user clicks on the search input.
     * It opens the select items menu and positions it relative to the search input.
     * @param event The click event triggered by focusing on the search input.
     */
    private handeFocus(event: JQuery.ClickEvent) {
        if (this.open) {
            // if the select items are already open, do nothing
            return;
        }
        this.selectItemsElement.scrollTop(0);
        this.selectItemsElement.trigger('scroll');

        this.selectItemsMenu.detach().appendTo('body');
        this.selectItemsMenu.addClass('show');

        // set the position of the select items to the position of the search input
        this.positionSelectItems();

        this.onResizeEvent = $(window).on('resize', () => {
            this.positionSelectItems();
        });

        this.open = true;

        this.checkForScrollbar();
        this.handleSearchInput({} as TriggeredEvent); // update the search input becouse of the changing factors
    }

    /**
     * Handles the blur event on the body, which is triggered when the user clicks outside the select items menu.
     * It hides the select items if the click is outside of the select items menu, search input, or select items element.
     * @param event The click event triggered by clicking outside the select items menu.
     */
    private handleBlur(event: JQuery.ClickEvent) {
        // hide the select items
        if ((
                $(event.target).closest(this.selectItemsElement).length > 0 ||
                $(event.target).closest(this.searchElement).length > 0 ||
                $(event.target).closest(this.selectItemsMenu).length > 0
            ) && event.button === 0 // only hide if the left mouse button is not pressed
        ) {
            return;
        }

        this.hideSelectItems();
        this.open = false;
    }

    /**
     * Hides the select items menu and resets its position.
     * It also removes the resize event listener if it exists.
     */
    private hideSelectItems() {
        // hide the select items
        this.selectItemsMenu.removeClass('show');
        this.selectItemsMenu.detach().appendTo(this.element);

        if (this.onResizeEvent) {
            this.onResizeEvent.off('resize');
        }
        // reset the position of the select items
        this.selectItemsMenu.css({
            top: '',
            left: '',
            width: ''
        });

        this.open = false;
    }

    /**
     * Handles the click event on a select item.
     * It retrieves the recipe ID and name from the clicked item, updates the search input and recipe ID element,
     * hides the select items, and adds the 'active' class to the selected item.
     * @param event The click event triggered by selecting an item.
     */
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

    /**
     * Handles the search input event, filtering the select items based on the search value.
     * It sorts the items alphabetically and prioritizes full matches over partial matches.
     * @param event The triggered event from the search input.
     */
    private handleSearchInput(event: JQuery.TriggeredEvent) {
        this.searchByMenuSettings = localStorage.getItem('searchByMenuSettings') ? JSON.parse(localStorage.getItem('searchByMenuSettings') as string) : this.searchByMenuSettings;
        const searchValue = this.searchElement.val() as string;
        let items = this.selectItemsElement.find('.select-item');

        const fullMatch: { element: JQuery<HTMLElement>, weight: number }[] = [];

        const sortedItems = items.toArray().sort((a, b) => {
            const aName = $(a).find('.recipe-name').text().toLowerCase();
            const bName = $(b).find('.recipe-name').text().toLowerCase();
            return aName.localeCompare(bName);
        });

        // delete all elements exept from .search-by-menu items
        this.selectItemsElement.find('.select-item').not('.search-by-menu').remove();
        this.selectItemsElement.append(sortedItems);

        items = this.selectItemsElement.find('.select-item'); // re-fetch items after sorting

        items.each((index, item) => {
            const $item = $(item);
            const productNames = $item.find('.recipe-product').map((_, el) => $(el).data('product-name')).get() as string[];
            const ingredientNames = $item.find('.recipe-ingredient').map((_, el) => $(el).data('ingredient-name')).get() as string[];
            const recipeName = $item.find('.recipe-name').text().toLowerCase();
            // should match both product names and recipe name
            const searchByProduct = this.searchByMenuSettings.searchByProducts && productNames.length > 0;
            const searchByIngredient = this.searchByMenuSettings.searchByIngredients && ingredientNames.length > 0;

            const search = searchValue.toLowerCase();

            const matches =
                recipeName.includes(search) ||
                (searchByProduct && productNames.some(name => name.toLowerCase().includes(search))) ||
                (searchByIngredient && ingredientNames.some(name => name.toLowerCase().includes(search)));

            if (searchValue.toLowerCase() === recipeName) {
                fullMatch.push({
                    element: $item,
                    weight: 2 // full match has a higher weight
                })
            } else if (searchByProduct && productNames.some(name => name.toLowerCase() === searchValue.toLowerCase())) {
                fullMatch.push({
                    element: $item,
                    weight: 1 // partial match has a lower weight
                });
            }
            if (searchByIngredient && ingredientNames.some(name => name.toLowerCase() === searchValue.toLowerCase())) {
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

        this.checkForScrollbar();
    }

    /**
     * Activates the selected recipe by adding the 'active' class to the corresponding select item.
     * @param recipeId The ID of the recipe to activate.
     */
    private activateSelectedRecipe(recipeId: string) {
        // Find the select item with the matching recipe ID
        const selectedItem = this.selectItemsElement.find(`.select-item[data-recipe-id="${recipeId}"]`);
        if (selectedItem.length > 0) {
            // Add the active class to the selected item
            selectedItem.addClass('active').siblings().removeClass('active');
        }
    }

    /**
     * Checks if the select items menu has a scrollbar and adds/removes the 'has-scrollbar' class accordingly.
     * Also moves icons based on the height of the select items and shows/hides visuals.
     */
    private checkForScrollbar() {
        this.selectItemsMenu.removeClass('has-scrollbar');
        const outerHeight = this.selectItemsElement.outerHeight() || 0;
        const scrollHeight = this.selectItemsElement[0].scrollHeight || 0;

        if (scrollHeight > outerHeight) {
            this.selectItemsMenu.addClass('has-scrollbar');
        }

        // Move icons based on the height of the select items
        this.moveIcons();
        this.showHideVisuals();
        this.showHideVisuals();
        this.showHideSearchByMenu();
    }

    /**
     * Moves the icons in the select items menu based on the height of the select items.
     * If the height is greater than 50px, it adds a flex-column class to the icon group.
     * Otherwise, it removes the flex-column class.
     */
    private moveIcons() {
        const selectItemsHeight = this.selectItemsElement.outerHeight() || 0;
        const iconGroup = this.selectItemsMenu.find('.icon-group');
        if (selectItemsHeight > 50) {
            iconGroup.addClass(['flex-column'])
        } else {
            iconGroup.removeClass(['flex-column']);
        }
    }
}