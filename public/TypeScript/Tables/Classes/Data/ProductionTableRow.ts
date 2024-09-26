import {ExtraProductionRow} from "./ExtraProductionRow";
import {Recipe} from "../Types/Recipe";
import {Ajax} from "../Functions/Ajax";
import {ProductionLineFunctions} from "../Functions/ProductionLineFunctions";

export class ProductionTableRow {
    public recipeId: number;
    public quantity: number;
    public product: string;
    public Usage: number;
    public exportPerMin: number;
    public doubleExport: boolean;
    public extraCells: ExtraProductionRow | null;
    public recipe: Recipe | null;


    constructor(recipeId: string = '', quantity: number = 0, product: string = '', Usage: number = 0, exportPerMin: number = 0, doubleExport: boolean = false, extraCells: ExtraProductionRow | null = null) {
        this.recipeId = +recipeId;
        this.quantity = quantity;
        this.product = product;
        this.Usage = Usage;
        this.exportPerMin = exportPerMin;
        this.doubleExport = doubleExport;
        this.extraCells = extraCells;
        this.recipe = null;
        this.getRecipe(recipeId).then(r =>
            this.saveDoubleExportQuantity()
        );
    }

    public async getRecipe(recipeId: string): Promise<void> {
        this.recipe = await Ajax.getRecipe(+recipeId);
    }

    public saveDoubleExportQuantity(): void {
        if (this.doubleExport && this.extraCells !== null) {
            console.log(this);
            this.extraCells.Quantity = <number>ProductionLineFunctions.calculateSecondExportPerMin(this);
            console.log(this.extraCells);
        }
    }
}