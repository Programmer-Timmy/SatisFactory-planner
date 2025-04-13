import {ExtraProductionRow} from "./ExtraProductionRow";
import {Recipe} from "../Types/Recipe";
import {Ajax} from "../Functions/Ajax";
import {ProductionLineFunctions} from "../Functions/ProductionLineFunctions";
import {Import} from "./Import";
import { v4 as uuidv4 } from 'uuid';
import {RecipeSetting} from "../RecipeSetting";


export class ProductionTableRow {
    public row_id: number | string;
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

    public recipeSetting: RecipeSetting | null = null;

    constructor(
        id: number | string = uuidv4(),
        recipeId: string = '',
        quantity: number = 0,
        product: string = '',
        Usage: number = 0,
        exportPerMin: number = 0,
        doubleExport: boolean = false,
        extraCells: ExtraProductionRow | null = null,
    ) {
        this.row_id = id;
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
        id: number = 0,
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
            id,
            recipeId,
            quantity,
            product,
            Usage,
            exportPerMin,
            doubleExport,
            extraCells,
        );

        if (instance.recipe == null) {
            instance.recipe = recipeCache.find(r => r.id === +recipeId) || null;
            if (instance.recipe == null && recipeId) {
                instance.recipe = await Ajax.getRecipe(+recipeId);
                if (instance.recipe && !recipeCache.find(r => r.id === instance.recipe?.id)) {
                    recipeCache.push(instance.recipe);
                }
            }
        }

        if (instance.extraCells !== null) {
            instance.extraCells.Quantity = <number>ProductionLineFunctions.calculateSecondExportPerMin(instance);
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