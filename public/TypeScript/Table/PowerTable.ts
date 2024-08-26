import {Table} from "./TableBase";
import {Options, TableHeader} from "./Utils/TableHeader";
import {ProductionTable} from "./ProductionTable";
import {TableRow} from "./Utils/TableRow";

export class PowerTable extends Table {

    private readonly Footer: JQuery<HTMLElement>;
    private productionTable: ProductionTable;

    constructor(tableId: string, productionTable: ProductionTable, disableOnChange: boolean = false) {
        super(tableId, disableOnChange);

        this.productionTable = productionTable;

        let options: Options;
        // the last min 1 row
        let select = $('#power tbody tr')
        select = $(select[select.length - 2]).find('td select');
        if (select.length > 0) {
            // @ts-ignore
            options = select.find('option').map((index, option) => {
                // check if it is disabled
                const disabled = option.disabled ? true : false;
                return {
                    value: option.value,
                    display: option.text,
                    disabled: disabled
                }
            }).get();
        } else {
            options = {};
        }
        this.tableHeaders = [
            new TableHeader('Name', 'select', false, options, 'power_building_id[]'),
            new TableHeader('Amount', 'number', false, {}, 'power_amount[]', '1', 0),
            new TableHeader('Clock Speed', 'number', false, {}, 'power_clock_speed[]', '100', 1, 250),
            new TableHeader('Consumption', 'number', true, {}, 'power_Consumption[]'),
            new TableHeader('user', 'hidden', true, {}, 'user[]', '1'),
        ]

        this.ReadRows();

        // get last row becouse it is not in the tableRows array
        this.Footer = $(`#${this.tableId} tbody tr:last`);

        this.deleteRow(this.tableRows.length - 1);
    }

    override async renderTable() {
        super.renderTable(this.Footer);

        // add hidden input to save if a user has added the row
    }

    override async handleChange(event: Event) {
        // Get new changes
        await this.ReadRows();
        this.deleteRow(this.tableRows.length - 1);

        const $target = $(event.target as HTMLInputElement);
        const $row = $($target).closest('tr');

        if (this.checkIfSecondLastRow($row) && this.checkIfSelect($target)) {
            await this.addRow();
            // check if the last row was changed
        }

        await this.calculatePowerUsage();
    }

    private checkIfSecondLastRow(element: JQuery<HTMLElement>) {
        const $row = $(element).closest('tr');
        return $row.is($(`#${this.tableId} tbody tr:nth-last-child(2)`));
    }

    public async calculatePowerUsage() {
        this.productionTable.deleteRow(this.productionTable.tableRows.length - 1);
        this.deleteNonUserRows();

        // Collect all promises for processing the rows
        const promises = this.productionTable.tableRows.map(async row => {
            const recipe : {[key: string]: any} = await this.getRecipe(+row.cells[0]);
            const building : {[key: string]: any} = await this.getBuilding(recipe['buildings_id']);

            const buildingAmount = this.calculateBuildingAmount(+row.cells[1], +recipe['export_amount_per_min']);
            const existingRow = this.checkIfBuildingAlreadyExists(building['id']);

            // if the building already exists, add the amount to the existing row
            if (existingRow !== false) {
                const currentValue = Number(this.tableRows[existingRow].cells[1]) || 0;
                const additionalAmount = Number(buildingAmount.amount) || 0;
                const Amount = (currentValue + additionalAmount).toString();

                this.tableRows[existingRow].cells[1] = Amount;
                this.tableRows[existingRow].cells[3] = this.calculateConsumption(+Amount, 100, building['power_used']).toString();

                // if there is a remainder, add a new row with the remainder
                if (buildingAmount.remainder > 0) {
                    this.addRemainderRow(buildingAmount, building);
                }

                return;
            }


            // Add new row with building details
            if (buildingAmount.amount > 0) {
                this.addRowBegin();
                this.tableRows[0].cells = [
                    building['id'],
                    buildingAmount.amount,
                    100,
                    this.calculateConsumption(buildingAmount.amount, 100, building['power_used']),
                    0
                ];
            }

            if (buildingAmount.remainder > 0) {
                this.addRemainderRow(buildingAmount, building);
            }
        });

        await this.calculateUserRows();

        // Wait for all promises to resolve
        await Promise.all(promises);

        await this.applyTotalConsumption();

        await this.flipOrderOfNonUserRows();

        this.renderTable();
    }

    private calculateBuildingAmount(QuantityPerMin: number, ExportAmountPerMin: number) : {amount: number, remainder: number} {
        const amount = QuantityPerMin / ExportAmountPerMin;

        // if the amount is not a whole number, round it up and give back a extra clockspeed to compensate
        if (amount % 1 != 0) {
            return { amount: Math.floor(amount), remainder: amount % 1 };
        }

        return { amount: amount, remainder: 0 };
    }

    // returns the index row of the building if it already exists
    private checkIfBuildingAlreadyExists(buildingId: number) : false | number {
        for (let i = 0; i < this.tableRows.length; i++) {
            if (this.tableRows[i].cells[0] == buildingId.toString() && this.tableRows[i].cells[4] == '0' && this.tableRows[i].cells[2] == '100') {
                return i;
            }
        }

        return false;
    }

    private addRemainderRow(buildingAmount: {amount: number, remainder: number}, building: {[key: string]: any}) {
        let clockSpeed : number = +(100 * buildingAmount.remainder).toFixed(3);
        clockSpeed = clockSpeed * 1;

        this.addRowBegin();
        this.tableRows[0].cells = [
            building['id'],
            1,
            clockSpeed,
            this.calculateConsumption(1, clockSpeed, building['power_used']),
            0
        ];
    }

    private calculateConsumption(amount: number, ClockSpeed: number, Consumption: number) {
        return amount * Consumption * (ClockSpeed / 100);

    }

    private async calculateTotalConsumption() : Promise<number> {
        let totalConsumption = 0;
        for (let i = 0; i < this.tableRows.length; i++) {
            totalConsumption += +this.tableRows[i].cells[3];
        }

        return totalConsumption
    }

    private async applyTotalConsumption() {
        let totalConsumption = await this.calculateTotalConsumption();
        this.Footer.find('input').attr('value', totalConsumption.toString());
    }

    private deleteNonUserRows() {
        for (let i = 0; i < this.tableRows.length; i++) {
            if (this.tableRows[i].cells[4] != '1') {
                this.deleteRow(i);
                i--;
            }
        }
    }

    private async flipOrderOfNonUserRows() {
        for (let i = 0; i < this.tableRows.length; i++) {
            if (this.tableRows[i].cells[4] != '1') {
                this.addRowBegin();
                this.tableRows[0] = this.tableRows[i + 1];
                this.deleteRow(i + 1);
            }
        }
    }

    private async calculateUserRows() {
        for (let i = 0; i < this.tableRows.length; i++) {
            if (this.tableRows[i].cells[4] == '1') {
                if (this.tableRows[i].cells[0]) {
                    const building: { [key: string]: any } = await this.getBuilding(this.tableRows[i].cells[0]);

                    this.tableRows[i].cells[3] = this.calculateConsumption(+this.tableRows[i].cells[1], +this.tableRows[i].cells[2], building['power_used']).toString();
                }
            }
        }
    }
}



