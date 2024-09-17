export class TableRow {
    class : string = '';
    doubleExport : boolean = false;
    cells : string[] = [];
    extraCells : string[] = [];

    constructor(doubleExport : boolean = false) {
        this.doubleExport = doubleExport;

    }
}