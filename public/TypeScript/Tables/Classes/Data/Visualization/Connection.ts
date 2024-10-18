/**
 * Class that represents a connection between two nodes.
 * @class
 *
 * @property {number} id - The id of the connection.
 * @property {number} sourceId - The id of the source node.
 * @property {number} targetId - The id of the target node.
 * @property {number} quantity - The quantity of the connection.
 * @property {string} product - The product of the connection.
 *
 * @example
 * let connection = new Connection(1, 2, 3, 100, 'Iron');
 * console.log(connection);
 * // Output: Connection { id: 1, sourceId: 2, targetId: 3, quantity: 100, product: 'Iron' }
 *
 * @returns {Connection} A new Connection object
 *
 * @constructor
 * @param {number} id - The id of the connection.
 * @param {number} sourceId - The id of the source node.
 * @param {number} targetId - The id of the target node.
 * @param {number} quantity - The quantity of the connection.
 */
export class Connection {
    public id: number;
    public sourceId: number;
    public targetId: number;
    public quantity: number;
    public product: string;

    constructor(id: number, sourceId: number, targetId: number, quantity: number, product: string) {
        this.id = id;
        this.sourceId = sourceId;
        this.targetId = targetId;
        this.quantity = quantity;
        this.product = product;
    }
}