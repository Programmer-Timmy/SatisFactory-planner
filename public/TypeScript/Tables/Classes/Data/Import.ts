export class Import {
    public index: number;
    public amount: number;
    public doubleExport: boolean;
    public product: string;

    constructor(index: number = 0, amount: number = 0, product: string = '', doubleExport: boolean = false) {
        this.index = index;
        this.amount = amount;
        this.product = product;
        this.doubleExport = doubleExport;

    }
}