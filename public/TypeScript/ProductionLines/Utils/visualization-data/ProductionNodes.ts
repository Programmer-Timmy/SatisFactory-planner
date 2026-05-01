import {IChecklist} from "./IChecklist";

export class ProductionNodes {
    public id: number;
    public product: string;
    public quantity: number;
    public building: string;
    public buildingId: number;
    public buildingAmount: number;
    public checklist: IChecklist | undefined;
    public X: number;
    public Y: number;

    constructor(id: number, product: string, quantity: number, building: string, buildingId: number, buildingAmount: number, checklist: IChecklist | undefined) {
        this.id = id;
        this.product = product;
        this.quantity = quantity;
        this.building = building;
        this.buildingId = buildingId;
        this.buildingAmount = buildingAmount;
        this.checklist = checklist;
        this.X = 0;
        this.Y = 0;
    }
}
