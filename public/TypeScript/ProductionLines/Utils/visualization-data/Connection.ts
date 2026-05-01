/**
 * Class that represents a connection between two nodes.
 */
export class Connection {
    public id: number;
    public sourceId: number;
    public targetId: number;
    public quantity: number;
    public product: string;
    public itemId: number;

    constructor(id: number, sourceId: number, targetId: number, quantity: number, product: string, itemId: number) {
        this.id = id;
        this.sourceId = sourceId;
        this.targetId = targetId;
        this.quantity = quantity;
        this.product = product;
        this.itemId = itemId;
    }
}
