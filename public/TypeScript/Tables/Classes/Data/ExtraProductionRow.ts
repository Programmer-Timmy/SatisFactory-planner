export class ExtraProductionRow {
    public Product: string;
    public Usage: number;
    public ExportPerMin: number;
    public Quantity: number;

    constructor(Product: string = "", Usage: number = 0, ExportPerMin: number = 0, quantity: number = 0) {
        this.Product = Product;
        this.Usage = Usage;
        this.ExportPerMin = ExportPerMin;
        this.Quantity = quantity;
    }
}
