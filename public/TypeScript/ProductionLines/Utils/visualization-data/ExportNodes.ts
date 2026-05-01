export class ExportNodes {
    public id: number;
    public product: string;
    public quantity: number;
    public X: number;
    public Y: number;

    constructor(id: number, product: string, quantity: number) {
        this.id = id;
        this.product = product;
        this.quantity = quantity;
        this.X = 0;
        this.Y = 0;
    }
}
