import {TableHandler} from "./TableHandler";
import {ImportNodes} from "./Data/Visualization/ImportNodes";
import {ProductionNodes} from "./Data/Visualization/ProductionNodes";
import {ExportNodes} from "./Data/Visualization/ExportNodes";
import {Connection} from "./Data/Visualization/Connection";
import cytoscape from 'cytoscape';
import 'cytoscape-qtip';

cytoscape.use(require('cytoscape-qtip'));


// global variables
const NODE_SIZE = 50;
const INNER_NODE_SIZE = 60;
const ROW_SPACING = 250;
const COLUMN_SPACING = 500;
const IMPORT_ROW_SPACING = 300;
const INSIDE_IMPORT_COLUMN_SPACING = 100;
const START_X = 200;
const START_Y = 100;
const TEXT_SIZE = 25;
const DOUBLE_OFFSET = 100;
const ARROW_SIZE = 10;
const LINE_WIDTH = 3;


/**
 * Visualization class
 *
 * This class is responsible for creating the visualization of the production line
 * @class
 * @property {TableHandler} TableHandler - The table handler object
 * @property {ImportNodes[]} importNodes - The import nodes array
 * @property {ProductionNodes[]} productionNodes - The production nodes array
 * @property {ExportNodes[]} exportNodes - The export nodes array
 *
 * @property {Connection[]} importConnections - The import connections array
 * @property {Connection[]} productionConnections - The production connections array
 * @property {Connection[]} exportConnections - The export connections array
 */
export class Visualization {

    public TableHandler: TableHandler;
    public importNodes: ImportNodes[] = [];
    public productionNodes: ProductionNodes[] = [];
    public exportNodes: ExportNodes[] = [];

    public importConnections: Connection[] = [];
    public productionConnections: Connection[] = [];
    public exportConnections: Connection[] = [];

    public layout: 'breadthfirst' | 'cose' = 'breadthfirst';

    private showExport: boolean = false;
    private showImport: boolean = true;
    private useRoots: boolean = true;


    /**
     * Constructor for the Visualization class
     * @constructor
     * @param {TableHandler} tableHandler - The table handler object
     */
    constructor(tableHandler: TableHandler) {
        this.TableHandler = tableHandler;

        // Get the data
        this.getterData();

        // Create the visualization
        this.createVisualization();

        // Add event listeners
        this.addEventListeners();
    }

    public update(): void {
        this.getterData();
        this.createVisualization();
    }

    /**
     * Create the visualization of the production line
     */
    public createVisualization(): void {
        let elements = [];
        let roots = [];
        let layout = {};

        if (this.showImport) {
            for (let i = 0; i < this.importNodes.length; i++) {
                elements.push({
                    data: {
                        id: `import_${this.importNodes[i].id}`,
                        label: `${this.importNodes[i].product}\n${this.importNodes[i].quantity}`,
                        color: 'blue',
                        title: `Import: ${this.importNodes[i].product}<br>Amount: ${this.importNodes[i].quantity}`,
                    }
                });
                roots.push(`import_${this.importNodes[i].id}`);
            }
            for (let i = 0; i < this.importConnections.length; i++) {
                elements.push({
                    data: {
                        id: `importConnection_${this.importConnections[i].id}`,
                        source: `import_${this.importConnections[i].sourceId}`,
                        target: `production_${this.importConnections[i].targetId}`,
                        label: `${this.importConnections[i].product} ${this.importConnections[i].quantity}`,
                        color: 'blue'
                    }
                });
            }
        }
        for (let i = 0; i < this.productionNodes.length; i++) {
            elements.push({
                data: {
                    id: `production_${this.productionNodes[i].id}`,
                    label: `${this.productionNodes[i].product}\n${this.productionNodes[i].quantity}\n\n\n\n${this.productionNodes[i].building}\n${this.productionNodes[i].buildingAmount}`,
                    color: 'green',
                    title: `Recipe: ${this.productionNodes[i].product}<br>Amount: ${this.productionNodes[i].quantity}<br><hr>Building: ${this.productionNodes[i].building}<br>Amount of building: ${this.productionNodes[i].buildingAmount}`
                },
                classes: 'top-text'
            });
        }
        for (let i = 0; i < this.productionConnections.length; i++) {
            elements.push({
                data: {
                    id: `productionConnection_${i}`,
                    source: `production_${this.productionConnections[i].sourceId}`,
                    target: `production_${this.productionConnections[i].targetId}`,
                    label: `${this.productionConnections[i].product} ${this.productionConnections[i].quantity}`,
                    color: 'green'
                }
            });
        }
        if (this.showExport) {
            for (let i = 0; i < this.exportNodes.length; i++) {
                elements.push({
                    data: {
                        id: `export_${this.exportNodes[i].id}`,
                        label: `${this.exportNodes[i].product}\n${this.exportNodes[i].quantity}`,
                        color: 'red',
                        title: `Export: ${this.exportNodes[i].product}<br>Amount: ${this.exportNodes[i].quantity}`,
                    }
                });
            }
            for (let i = 0; i < this.exportConnections.length; i++) {
                elements.push({
                    data: {
                        id: `exportConnection_${this.exportConnections[i].id}`,
                        source: `production_${this.exportConnections[i].sourceId}`,
                        target: `export_${this.exportConnections[i].targetId}`,
                        label: `${this.exportConnections[i].product} ${this.exportConnections[i].quantity}`,
                        color: 'red',
                    }
            });
            }
        }

        switch (this.layout) {
            case 'breadthfirst':
                layout = {
                    name: "breadthfirst",      // Layout for production chains
                    directed: false,            // Forces direction (e.g., top to bottom)
                    padding: 20,               // Adds padding around the graph
                    spacingFactor: 3.0,        // Increases space between nodes
                    animate: true,
                    nodeDimensionsIncludeLabels: true, // Accounts for label dimensions in layout
                    avoidOverlap: true,        // Prevents node overlap
                }
                if (this.showImport && this.useRoots) {
                    // @ts-ignore
                    layout['roots'] = roots;
                }
                break;
            case 'cose':
                layout = {
                    name: "cose",
                    idealEdgeLength: 100,    // Controls the preferred length of edges
                    nodeRepulsion: 40000,     // Increases spacing between nodes
                    gravity: 1.2,            // Helps to avoid nodes being too spread out
                    numIter: 1000,           // Number of iterations for better arrangement
                    animate: true,
                }
                if (this.showImport && this.useRoots) {
                    // @ts-ignore
                    layout['roots'] = roots;
                }
                break;
            default: {
                layout = {
                    name: "breadthfirst",      // Layout for production chains
                    directed: false,            // Forces direction (e.g., top to bottom)
                    padding: 20,               // Adds padding around the graph
                    spacingFactor: 1.0,        // Increases space between nodes
                    animate: true,
                    nodeDimensionsIncludeLabels: true, // Accounts for label dimensions in layout
                    avoidOverlap: true,        // Prevents node overlap
                }
                if (this.showImport && this.useRoots) {
                    // @ts-ignore
                    layout['roots'] = roots;
                }
            }
        }

        // @ts-ignore
        // @ts-ignore
        const cy = cytoscape({
            container: document.getElementById("graph"), // container to render in
            elements: elements,

            style: [
                {
                    selector: "node",
                    style: {
                        "label": "data(label)",
                        'background-color': 'data(color)', // Middle label
                        "text-halign": "center",
                        "text-valign": "center",
                        'width': 200,
                        'height': 200,// Align middle
                        "color": "black",
                        "font-size": TEXT_SIZE,
                        "text-wrap": "wrap",
                    }
                },
                {
                    selector: "node.top-text",
                    style: {
                        'text-margin-y': -60,           // Adjusts position to appear above center
                    }
                },
                {
                    selector: "edge",
                    style: {
                        "label": "data(label)",         // Display label from data
                        "font-size": TEXT_SIZE,            // Customize font size if needed
                        "text-background-color": "white",
                        "text-background-opacity": 0.5,
                        "text-background-padding": '3px',
                        "text-background-shape": "roundrectangle",
                        "color": "black",               // Text color
                        "width": 5,                     // Line width
                        "line-color": "data(color)",    // Line color
                        "target-arrow-color": "#333",
                        "target-arrow-shape": "triangle",
                        "target-arrow-fill": "filled",
                        "curve-style": "bezier",
                        "control-point-step-size": 40,

                    }
                }
            ],
            // @ts-ignore
            layout: layout
        });

        cy.ready(() => {
            cy.nodes().forEach(node => {
                node.qtip({
                    content: node.data('title'),
                    position: {
                        my: 'top center',
                        at: 'bottom center'
                    },
                    style: {
                        classes: 'qtip-bootstrap',
                        tip: {
                            width: 16,
                            height: 8
                        }
                    },
                    show: {
                        event: 'mouseover',
                        delay: 100
                    },
                });

                node.on('drag', () => {
                    if (node.qtip('api').visible) {
                        node.qtip('api').hide();
                    }
                });

                node.on('mouseout', () => {
                    node.qtip('api').hide();

                });

            });

            cy.edges().forEach(edge => {
                edge.qtip({
                    content: edge.data('label'),
                    position: {
                        my: 'top center',
                        at: 'bottom center'
                    },
                    style: {
                        classes: 'qtip-bootstrap',
                        tip: {
                            width: 16,
                            height: 8
                        }
                    },
                    show: {
                        event: 'mouseover',
                        delay: 100
                    },
                    hide: {
                        event: 'mouseout'
                    }
                });
            });
        });
    }

    /**
     * Get the data from the tables
     * @private
     */
    private getterData(): void {
        this.importNodes = [];
        this.productionNodes = [];
        this.exportNodes = [];
        this.importConnections = [];
        this.productionConnections = [];
        this.exportConnections = [];

        this.getImportNodes();
        this.getProduction();
        this.getExportNodes();
        this.getImportConnection();
    }

    /**
     * Add event listeners to the visualization
     * @method
     * @private
     */
    private addEventListeners(): void {
        $('#layout').on('change', (e) => {
            const select = $(e.target);
            this.layout = select.val() as 'breadthfirst' | 'cose';
            this.createVisualization();
        });

        $('#export').on('change', (e) => {
            const select = $(e.target);
            this.showExport = select.prop('checked');
            this.createVisualization();
        });

        $('#import').on('change', (e) => {
            const select = $(e.target);
            this.showImport = select.prop('checked');
            this.createVisualization();
        });

        $('#refresh').on('click', () => {
            this.createVisualization();
        });

        $('#roots').on('change', (e) => {
            const select = $(e.target);
            this.useRoots = select.prop('checked');
            this.createVisualization();
        });
    }


    /**
     * Get all import connections from the production table and add them to the import connections array
     * @private
     */
    private getImportConnection(): void {
        let index = 0;
        for (let i = 0; i < this.TableHandler.productionTableRows.length; i++) {
            const row = this.TableHandler.productionTableRows[i];

            for (let j = 0; j < row.imports.length; j++) {
                const importRow = row.imports[j];
                this.importConnections.push(new Connection(index, importRow.index, i, +importRow.amount.toFixed(3), importRow.product));
                index++;
            }
        }
    }

    /**
     * Get all production nodes and connections from the production table
     * @private
     */
    private getProduction(): void {
        for (let i = 0; i < this.TableHandler.productionTableRows.length; i++) {
            const row = this.TableHandler.productionTableRows[i];

            const building = row.recipe?.building;
            const amount = row.quantity;
            const recipe = row.recipe;
            // @ts-ignore
            let amountOfBuilding = (amount / recipe.export_amount_per_min).toFixed(3);

            if (building && recipe) {
                this.productionNodes.push(new ProductionNodes(i, recipe.name, row.quantity, building.name, building.id, +amountOfBuilding));

            }

            for (let j = 0; j < row.productionImports.length; j++) {
                const importRow = row.productionImports[j];
                this.productionConnections.push(new Connection(i, importRow.index, i, +importRow.amount.toFixed(3), importRow.product));
            }
        }
    }



    /**
     * Get all import nodes from the import table and add them to the import nodes array
     * @private
     */
    private getImportNodes(): void {
        for (let i = 0; i < this.TableHandler.importsTableRows.length; i++) {
            const row = this.TableHandler.importsTableRows[i];
            if (row.product !== '' && row.quantity > 0) {
            this.importNodes.push(new ImportNodes(i, row.product, row.quantity));
            }
        }
    }

    /**
     * Get all export nodes from the production table and add them to the export nodes array and connections array
     * @private
     */
    private getExportNodes(): void {
        let index = 0;
        for (let i = 0; i < this.TableHandler.productionTableRows.length; i++) {
            const row = this.TableHandler.productionTableRows[i];

            if (row.exportPerMin > 0) {
                this.exportNodes.push(new ExportNodes(index, row.product, row.exportPerMin));
                this.exportConnections.push(new Connection(index, i, this.exportNodes.length - 1, row.exportPerMin, row.product));
                index++;
            }

            // @ts-ignore
            if (row.extraCells?.ExportPerMin > 0) {
                // @ts-ignore
                this.exportNodes.push(new ExportNodes(index, row.product, row.extraCells?.ExportPerMin));
                // @ts-ignore
                this.exportConnections.push(new Connection(index, i, this.exportNodes.length - 1, row.extraCells?.ExportPerMin, row.product));
                index++;
            }

        }
    }


    // old code



    async getPosition(): Promise<void> {
        const levels: Map<number, number[]> = new Map();
        const positions: Map<number, { X: number, Y: number }> = new Map();
        const SHIFT_AMOUNT = ROW_SPACING;  // Amount to shift nodes down

        // Helper function for node positioning
        const positionNode = async (nodeId: number, level: number): Promise<void> => {
            if (!levels.has(level)) {
                levels.set(level, []);
            }

            const currentNode = this.productionNodes[nodeId];

            if (positions.has(nodeId)) {
                return;
            }

            // Set initial Y position
            let yPosition = (levels.get(level)?.length || 0) * ROW_SPACING + START_Y;
            levels.get(level)?.push(nodeId);

            positions.set(nodeId, {
                X: START_X + level * COLUMN_SPACING,
                Y: yPosition
            });

            // Process child nodes recursively
            const childConnections = this.productionConnections.filter(conn => conn.sourceId === nodeId);
            await Promise.all(childConnections.map(conn => positionNode(conn.targetId, level + 1)));
        };

        // Position root nodes (no incoming connections)
        const rootNodes = this.productionNodes.filter(node =>
            !this.productionConnections.some(conn => conn.targetId === node.id)
        );

        await Promise.all(rootNodes.map(node => positionNode(node.id, 0)));

        // Remove crossing connections

        // Apply final positions
        levels.forEach((nodes, level) => {
            nodes.forEach((nodeId, index) => {
                const {X, Y} = positions.get(nodeId)!;
                this.productionNodes[nodeId].X = X;
                this.productionNodes[nodeId].Y = Y;
            });
        });
    }

    private removeCrossingConnections(): void {
        let counter = 0;
        //     check if one of the nodes connection is crossing another connection or node. if so try moving it to the row under the node it needs to connect to
        for (let i = 0; i < this.productionConnections.length; i++) {
            const connection = this.productionConnections[i];
            for (let j = 0; j < this.productionConnections.length; j++) {
                const otherConnection = this.productionConnections[j];
                if (connection.sourceId === otherConnection.sourceId || connection.targetId === otherConnection.targetId) {
                    continue;
                }

                const sourceNode = this.productionNodes[connection.sourceId];
                const targetNode = this.productionNodes[connection.targetId];
                const otherSourceNode = this.productionNodes[otherConnection.sourceId];
                const otherTargetNode = this.productionNodes[otherConnection.targetId];

                // add a litle bit of padding to the nodes
                const padding = 20;
                if (this.checkLineIntersection(sourceNode.X + padding, sourceNode.Y + padding, targetNode.X - padding, targetNode.Y - padding, otherSourceNode.X + padding, otherSourceNode.Y + padding, otherTargetNode.X - padding, otherTargetNode.Y - padding)) {
                    counter++;

                }
            }
        }
        // if (counter > 0) {
        //     this.removeCrossingConnections();
        // }
    }

    private checkLineIntersection(x1: number, y1: number, x2: number, y2: number, x3: number, y3: number, x4: number, y4: number): boolean {
        const det = (x1 - x2) * (y3 - y4) - (y1 - y2) * (x3 - x4);
        if (det === 0) {
            return false;
        }

        const t = ((x1 - x3) * (y3 - y4) - (y1 - y3) * (x3 - x4)) / det;
        const u = -((x1 - x2) * (y1 - y3) - (y1 - y2) * (x1 - x3)) / det;

        return t >= 0 && t <= 1 && u >= 0 && u <= 1;
    }


    private positionImportNodes(): void {
        for (let i = 0; i < this.importNodes.length; i++) {
            // if it hase a connection to a production node move it closer to the production node
            const connection = this.importConnections.filter(conn => conn.sourceId === i);

            if (connection && connection.length > 1) {
                const ConnectionAmount = connection.length;

                // grab the middle connection if not even move it to the middle of the connections
                const middleConnection = connection[Math.floor(ConnectionAmount / 2)];
                const X = this.productionNodes[middleConnection.targetId].X - IMPORT_ROW_SPACING;
                let Y = this.productionNodes[middleConnection.targetId].Y;

                if (X > START_X) {
                    Y -= INSIDE_IMPORT_COLUMN_SPACING;
                }

                this.importNodes[i].X = X;
                this.importNodes[i].Y = Y;
            } else if (connection && connection.length === 1) {
                const samePosition = this.importNodes.filter(node => node.X === this.productionNodes[connection[0].targetId].X - IMPORT_ROW_SPACING && node.Y === this.productionNodes[connection[0].targetId].Y);
                const X = this.productionNodes[connection[0].targetId].X - IMPORT_ROW_SPACING;
                let Y = this.productionNodes[connection[0].targetId].Y;

                if (X > START_X) {
                    Y -= INSIDE_IMPORT_COLUMN_SPACING;
                }

                if (samePosition.length > 0) {
                    this.importNodes[i].X = X;
                    this.importNodes[i].Y = Y + DOUBLE_OFFSET;
                    samePosition[0].Y = Y - DOUBLE_OFFSET;
                } else {
                    this.importNodes[i].X = X;
                    this.importNodes[i].Y = Y;
                }
            }
        }
    }

    private positionExportNodes(): void {
        for (let i = 0; i < this.exportNodes.length; i++) {
            const connection = this.exportConnections.filter(conn => conn.sourceId === i);
            if (connection && connection.length > 1) {
                const ConnectionAmount = connection.length;

                // grab the middle connection if not even move it to the middle of the connections
                const middleConnection = connection[Math.floor(ConnectionAmount / 2)];
                const X = this.productionNodes[middleConnection.sourceId].X;
                let Y = this.productionNodes[middleConnection.sourceId].Y;

                if (X > START_X) {
                    Y -= INSIDE_IMPORT_COLUMN_SPACING;
                }

                this.exportNodes[i].X = X;
                this.exportNodes[i].Y = Y;
            } else if (connection && connection.length === 1) {
                const samePosition = this.exportNodes.filter(node => node.X === this.productionNodes[connection[0].sourceId].X + IMPORT_ROW_SPACING && node.Y === this.productionNodes[connection[0].sourceId].Y);
                const X = this.productionNodes[connection[0].sourceId].X;
                let Y = this.productionNodes[connection[0].sourceId].Y;

                if (X >= START_X) {
                    Y -= INSIDE_IMPORT_COLUMN_SPACING;
                }

                if (samePosition.length > 0) {
                    this.exportNodes[i].X = X + IMPORT_ROW_SPACING;
                    this.exportNodes[i].Y = Y;
                    samePosition[0].X = X - IMPORT_ROW_SPACING;
                } else {
                    this.exportNodes[i].X = X;
                    this.exportNodes[i].Y = Y;
                }
            }
        }
    }
}