/**
 * ExportNodes class to create export nodes
 * @class
 * @classdesc ExportNodes class to create export nodes
 * @param {number} id - The id of the export node
 * @param {string} product - The product of the export node
 * @param {number} quantity - The quantity of the export node
 *
 * @example
 * let exportNode = new ExportNodes(1, 'Iron', 100);
 * console.log(exportNode);
 * // Output: ExportNodes { id: 1, product: 'Iron', quantity: 100 }
 *
 * @returns {ExportNodes} A new ExportNodes object
 *
 * @constructor
 * @param {number} id - The id of the export node
 * @param {string} product - The product of the export node
 * @param {number} quantity - The quantity of the export node
 */
export class ExportNodes {
    public id: number;
    public product: string;
    public quantity: number;

    constructor(id: number, product: string, quantity: number) {
        this.id = id;
        this.product = product;
        this.quantity = quantity;
    }
}