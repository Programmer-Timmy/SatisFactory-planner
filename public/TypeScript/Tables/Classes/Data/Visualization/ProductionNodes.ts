/**
 * Class that holds the data for the ProductionNodes table.
 * @class
 *
 * @property {number} id - The id of the production node.
 * @property {string} product - The product of the production node.
 * @property {number} quantity - The quantity of the production node.
 * @property {string} building - The building of the production node.
 * @property {number} buildingId - The building id of the production node.
 *
 * @example
 * let productionNode = new ProductionNodes(1, 'Iron', 100, 'Smelter', 1);
 * console.log(productionNode);
 * // Output: ProductionNodes { id: 1, product: 'Iron', quantity: 100, building: 'Smelter', buildingId: 1 }
 *
 * @returns {ProductionNodes} A new ProductionNodes object
 *
 * @constructor
 * @param {number} id - The id of the production node.
 * @param {string} product - The product of the production node.
 * @param {number} quantity - The quantity of the production node.
 * @param {string} building - The building of the production node.
 * @param {number} buildingId - The building id of the production node.
 */
export class ProductionNodes {
    public id: number;
    public product: string;
    public quantity: number;
    public building: string;
    public buildingId: number;
    public X: number;
    public Y: number;


    constructor(id: number, product: string, quantity: number, building: string, buildingId: number) {
        this.id = id;
        this.product = product;
        this.quantity = quantity;
        this.building = building;
        this.buildingId = buildingId;
        this.X = 0;
        this.Y = 0;
    }
}