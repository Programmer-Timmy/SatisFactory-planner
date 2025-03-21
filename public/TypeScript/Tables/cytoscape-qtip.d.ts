import cytoscape from 'cytoscape';

declare module 'cytoscape' {
    interface EdgeSingular {
        qtip(options: any): any;
    }

    interface NodeSingular {
        qtip(options: any): any;
    }
}

// src/types/cytoscape-qtip.d.ts
declare module "cytoscape-qtip" {
    const qtip: any;
    export default qtip;
}
