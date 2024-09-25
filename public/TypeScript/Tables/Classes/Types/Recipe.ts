import {Resource} from "./Resource";
import {Building} from "./Building";

export type Recipe = {
    class_name: string;
    export_amount_per_min: number;
    export_amount_per_min2: number | null;
    id: number;
    itemName: string;
    item_id: number;
    item_id2: number | null;
    name: string;
    powerUsed: number;
    secondItemName: string | null;
    resources: Resource[];
    building: Building;
};