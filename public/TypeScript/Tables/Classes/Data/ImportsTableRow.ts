export class ImportsTableRow {
    public itemId: number;
    public quantity: number;
    public product: string;

    constructor(itemId: number = 0, quantity: number = 0, product: string = '') {
        this.itemId = itemId;
        this.quantity = quantity;
        this.product = product;
    }

}