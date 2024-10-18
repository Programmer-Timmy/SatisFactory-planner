export class Import {
    public index: number;
    public amount: number;
    public doubleExport: boolean;

    constructor(index: number = 0, amount: number = 0, doubleExport: boolean = false) {
        this.index = index;
        this.amount = amount;
        this.doubleExport = doubleExport;
    }
}