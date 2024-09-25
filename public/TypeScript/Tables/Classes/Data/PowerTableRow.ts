import {Building} from "../Types/Building";

export class PowerTableRow {
    public buildingId: number;
    public quantity: number;
    public clockSpeed: number;
    public Consumption: number;
    public userRow: boolean;
    public building: Building | null;

    constructor(buildingId: number = NaN, quantity: number = 0, clockSpeed: number = 100, Consumption: number = 0, userRow: boolean = true) {
        this.buildingId = buildingId;
        this.quantity = quantity;
        this.clockSpeed = clockSpeed;
        this.Consumption = Consumption;
        this.userRow = userRow;
        this.building = null;
    }
}