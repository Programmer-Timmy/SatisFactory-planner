import cytoscape from 'cytoscape';

declare module 'cytoscape' {
    interface EdgeSingular {
        qtip(options: any): any;
    }

    interface NodeSingular {
        qtip(options: any): any;
    }
}
