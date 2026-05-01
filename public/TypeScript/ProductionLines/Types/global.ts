export interface RecipeIngredient {
    id: number;
    form: string;
    name: string;
    quantity: number;
    class_name: string;
}

export interface RecipeBuilding {
    id: number;
    name: string;
    class_name: string;
    power_used: number;
    power_generated: number;
}

export interface RecipeProduct {
    id: number;
    form: string;
    name: string;
    quantity: number;
    class_name: string;
}

export interface Recipe {
    id: number;
    name: string;
    export_amount_per_min: number;
    export_amount_per_min2: number | null;
    class_name: string;
    buildings_id: number;
    item_id: number;
    item_id2: number | null;
    ingredients: RecipeIngredient[];
    building: RecipeBuilding[];
    products: RecipeProduct[];
}

export interface ProductionItem {
    id: number;
    item_name_1: string;
    item_class_name_1: string;
    item_name_2: string | null;
    item_class_name_2: string | null;
    local_usage2: number | null;
    export_ammount_per_min2: number | null;
    recipe_id: number;
    local_usage: number;
    recipe_name: string;
    export_amount_per_min: number;
    building_name: string;
    building_class_name: string;
    power_used: number;
    product_quantity: number;
    // allow empty string while editing clock
    clock_speed: number | '';
    use_somersloop: number | boolean | null;
}

export interface Item {
    id: number;
    name: string;
    class_name: string;
    form: string;
}

export interface ImportItem {
    ammount: number;
    name: string;
    items_id: number;
    item_class_name: string;
}
