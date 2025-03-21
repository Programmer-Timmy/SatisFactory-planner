import {Building} from "../Types/Building";
import {Ajax} from "../Functions/Ajax";

export class PowerTableRow {
    public buildingId: number;
    public quantity: number;
    public clockSpeed: number;
    public Consumption: number;
    public userRow: boolean;
    public building: Building | null;

    constructor(buildingId: number = NaN, quantity: number = 0, clockSpeed: number = 100, Consumption: number = 0, userRow: boolean = true, building: Building | null = null) {
        this.buildingId = buildingId;
        this.quantity = quantity;
        this.clockSpeed = clockSpeed;
        this.Consumption = Consumption;
        this.userRow = userRow;
        this.building = building;
    }

    static async create(
        buildingId: number = NaN,
        quantity: number = 0,
        clockSpeed: number = 100,
        Consumption: number = 0,
        userRow: boolean | string = true,
        building: Building | null = null,
        buildingCache: Building[] = []
    ): Promise<PowerTableRow> {
        if (typeof userRow === 'string') {
            switch (userRow.toLowerCase()) {
                case '0':
                case 'false':
                    userRow = false;
                    break;
                case '1':
                case 'true':
                    userRow = true;
                    break;
                default:
                    userRow = true;
                    break;
            }
        }
        const instance = new PowerTableRow(
            buildingId,
            quantity,
            clockSpeed,
            Consumption,
            userRow,
            building
        );

        if (!building && buildingId) {
            instance.building = buildingCache.find(b => b.id === +buildingId) || null;
            if (!instance.building) {
                const building = await Ajax.getBuilding(buildingId);
                // if not in cache, add it
                if (building && !buildingCache.find(b => b.id === building.id)) {
                    buildingCache.push(building);
                }
                instance.building = building;
            }
        }

        return instance;
    }
}
