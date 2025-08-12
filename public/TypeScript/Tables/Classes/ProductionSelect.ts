export class ProductionSelect {

    public selectedRecipe: number;

    private options: {'recipe': string, 'id': number}[] = [];

    public constructor(element: JQuery<HTMLElement>, selectedRecipe: number = 0) {
        this.selectedRecipe = selectedRecipe;

    }
}