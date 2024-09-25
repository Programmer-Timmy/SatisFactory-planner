import {ExtraProductionRow} from "./ExtraProductionRow";
import {Ajax} from "../Ajax";


export type Recipe = {
    buildings_id: number;
    class_name: string;
    export_amount_per_min: number;
    export_amount_per_min2: number | null;
    id: number;
    itemName: string;
    item_id: number;
    item_id2: number | null;
    name: string;
    secondItemName: string | null;
};

export class ProductionTableRow {
    public recipeId: string;
    public quantity: number;
    public product: string;
    public Usage: number;
    public exportPerMin: number;
    public doubleExport: boolean;
    public extraCells: ExtraProductionRow | null;
    public recipe: Recipe | null;


    constructor(recipeId: string = '', quantity: number = 0, product: string = '', Usage: number = 0, exportPerMin: number = 0, doubleExport: boolean = false, extraCells: ExtraProductionRow | null = null) {
        this.recipeId = recipeId;
        this.quantity = quantity;
        this.product = product;
        this.Usage = Usage;
        this.exportPerMin = exportPerMin;
        this.doubleExport = doubleExport;
        this.extraCells = extraCells;
        this.recipe = null;
    }

    public async getBuilding(recipeId: string): Promise<void> {
        this.recipe = await Ajax.getRecipe(+recipeId);
    }
}