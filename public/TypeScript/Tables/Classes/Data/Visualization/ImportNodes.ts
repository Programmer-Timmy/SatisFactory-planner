/**
 * Class that represents the data of the import nodes.
 * @class
 * @classdesc Class that represents the data of the import nodes.
 *
 * @param {number} id - The id of the import node.
 * @param {string} product - The product of the import node.
 * @param {number} quantity - The quantity of the import node.
 *
 * @example
 * let importNode = new ImportNodes(1, 'Iron', 100);
 * console.log(importNode);
 * // Output: ImportNodes { id: 1, product: 'Iron', quantity: 100 }
 *
 * @returns {ImportNodes} A new ImportNodes object
 *
 * @constructor
 * @param {number} id - The id of the import node.
 * @param {string} product - The product of the import node.
 * @param {number} quantity - The quantity of the import node.
 */
export class ImportNodes {
    public id: number;
    public product: string;
    public quantity: number;

    constructor(id: number, product: string, quantity: number) {
        this.id = id;
        this.product = product;
        this.quantity = quantity;
    }
}
