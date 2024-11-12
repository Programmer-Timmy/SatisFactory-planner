import {ExtraProductionRow} from "./ExtraProductionRow";
import {Recipe} from "../Types/Recipe";
import {Ajax} from "../Functions/Ajax";
import {ProductionLineFunctions} from "../Functions/ProductionLineFunctions";
import {Import} from "./Import";

export class ProductionTableRow {
    public recipeId: number;
    public quantity: number;
    public product: string;
    public Usage: number;
    public exportPerMin: number;
    public doubleExport: boolean;
    public extraCells: ExtraProductionRow | null;
    public recipe: Recipe | null;
    public imports: Import[];
    public productionImports: Import[];

    constructor(
        recipeId: string = '',
        quantity: number = 0,
        product: string = '',
        Usage: number = 0,
        exportPerMin: number = 0,
        doubleExport: boolean = false,
        extraCells: ExtraProductionRow | null = null,
    ) {
        this.recipeId = +recipeId;
        this.quantity = quantity;
        this.product = product;
        this.Usage = Usage;
        this.exportPerMin = exportPerMin;
        this.doubleExport = doubleExport;
        this.extraCells = extraCells;
        this.recipe = null;
        this.imports = [];
        this.productionImports = [];
    }

    static async create(
        recipeId: string = '',
        quantity: number = 0,
        product: string = '',
        Usage: number = 0,
        exportPerMin: number = 0,
        doubleExport: boolean = false,
        extraCells: ExtraProductionRow | null = null,
        recipeCache: Recipe[] = []
    ): Promise<ProductionTableRow> {
        const instance = new ProductionTableRow(
            recipeId,
            quantity,
            product,
            Usage,
            exportPerMin,
            doubleExport,
            extraCells,
        );

        if (instance.recipe == null) {
            if (instance.recipe == null) {
                instance.recipe = await Ajax.getRecipe(+recipeId);
                recipeCache.push(instance.recipe);
            }
        }

        return instance;
    }

    public async getRecipe(recipeId: string): Promise<void> {
        this.recipe = await Ajax.getRecipe(+recipeId);


    }

    public saveDoubleExportQuantity(): void {
        if (this.doubleExport && this.extraCells !== null) {
            this.extraCells.Quantity = <number>ProductionLineFunctions.calculateSecondExportPerMin(this);
        }
    }
}