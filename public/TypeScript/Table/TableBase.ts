import {TableRow} from "./Utils/TableRow";
import {TableHeader} from "./Utils/TableHeader";

export class Table{
    tableId: string = '';
    tableHeaders : TableHeader[] = [];
    tableRows: TableRow[] = [];

    constructor(tableId: string, disableChangeEventListeners: boolean = false) {
        this.tableId = tableId;

        if (!disableChangeEventListeners) {
            this.attachChangeEventListeners();
        }
    }

    public consoleLog() : void {
        console.log(this);
    }

    public async addRow() : Promise<void>{
        let tableRow = new TableRow();
        for (let i = 0; i < this.tableHeaders.length; i++) {
            tableRow.cells.push(this.tableHeaders[i].default);
        }

        this.tableRows.push(tableRow);
    }

    public async addRowBegin() : Promise<void>{
        let tableRow = new TableRow();
        for (let i = 0; i < this.tableHeaders.length; i++) {
            tableRow.cells.unshift(this.tableHeaders[i].default);
        }

        this.tableRows.unshift(tableRow);
    }

    public async addRowAfter(index: number) : Promise<void>{
        let tableRow = new TableRow();
        for (let i = 0; i < this.tableHeaders.length; i++) {
            tableRow.cells.push(this.tableHeaders[i].default);
        }

        this.tableRows.splice(index + 1, 0, tableRow);
    }

    public swapRows(index1: number, index2: number) : void {
        let temp = this.tableRows[index1];
        this.tableRows[index1] = this.tableRows[index2];
        this.tableRows[index2] = temp;
    }

    public updateRow(row: TableRow, index: number){
        this.tableRows[index] = row;
    }

    public deleteRow(index: number) : void {
        this.tableRows.splice(index, 1);
    }

    public deleteAllRows() : void {
        this.tableRows = [];
    }

    public async ReadRows(skipLastRows: number = 0) : Promise<void> {
        let $table = $(`#${this.tableId}`);

        if ($table == null) {
            return;
        }

        let $rows = $table.find('tbody tr');

        if ($rows.length == 0) {
            console.error('No rows found in table')
            return;
        }
        this.tableRows = [];
        // Assuming that TableRow and other required classes/variables are defined

        for (let i = 0; i <= $rows.length - 1; i++) {
            let doubleExport = false;

            // Check if the next row has the class extra-output, which indicates a double export
            if (i + 1 < $rows.length && $rows[i + 1].classList.contains('extra-output')) {
                doubleExport = true;
            }

            let row = new TableRow(doubleExport);
            let $cells = $($rows[i]).find('td');
            let $otherCells;

            // Push the cells to the class
            $cells.each((index, cell) => {
                let $cell = $(cell);
                let inputValue = $cell.find('input').val();
                let selectValue = $cell.find('select').val();

                // Determine whether to use the input or select value
                let value = inputValue !== undefined ? inputValue : selectValue;
                row.cells.push(<string>value);
            });

            // If the row has a double export, read the next row as well
            if (doubleExport) {
                $otherCells = $($rows[i + 1]).find('td');

                // Push the extra cells to the class
                $otherCells.each((index, cell) => {
                    let $cell = $(cell);
                    let inputValue = $cell.find('input').val();
                    let selectValue = $cell.find('select').val();

                    // Determine whether to use the input or select value
                    let value = inputValue !== undefined ? inputValue : selectValue;
                    row.extraCells.push(<string>value);
                });

                i++; // Skip the next row as it has been processed
            }

            this.tableRows.push(row);
        }
    }

    public renderTable(footer: false | JQuery<HTMLElement> = false) : void {
        // create a tablebody element
        let table :string = '';

        // loop through the rows

        for (let i = 0; i < this.tableRows.length; i++) {
            let row = this.tableRows[i];
            let extraRow = '';

            table += '<tr>';
            for (let j = 0; j < row.cells.length; j++) {
                const header = this.tableHeaders[j];
                const readOnly: string = header.ReadOnly ? 'readonly' : '';
                const min: string = header.min >= 0 ? 'min="' + header.min + '"' : '';
                const max: string = header.max > 0 ? 'max="' + header.max + '"' : '';
                const step: string = header.InputType === 'number' ? 'step="any"' : '';

                let rowspan = '';
                let height = '';
                if (row.doubleExport && j < 2) {
                    rowspan = 'rowspan="2"';
                    height = 'style="height: 78px"';
                }
                if (header.InputType === 'select') {
                    table += '<td class="m-0 p-0" ' + rowspan + '><select class="form-control rounded-0" ' + readOnly + ' name="'+ header.InputName +'" ' + height + ' ' + min + ' ' + max + '>';
                    for (const key in header.Options) {
                        let selected = header.Options[key].value == row.cells[j] ? 'selected' : '';
                        const disabled = header.Options[key].disabled ? 'disabled' : '';

                        // If it is the last row and the value is 0, select it
                        if (i == this.tableRows.length - 1 && key == '0') {
                            selected = 'selected';
                        }

                        table += '<option value="' + header.Options[key].value + '" ' + selected + ' ' + disabled +'>' + header.Options[key].display + '</option>';
                    }
                    table += '</select></td>';
                } else {
                    table += '<td class="m-0 p-0" ' + rowspan + '><input type="' + this.tableHeaders[j].InputType + '" value="' + row.cells[j] + '" class="form-control rounded-0" ' + readOnly + ' name="'+ header.InputName +'" ' + height + ' ' + min + ' ' + max + ' ' + step + '></td>';
                }
            }
            table += '</tr>';
            row = this.tableRows[i];
            if (row.doubleExport) {
                extraRow = '<tr class="extra-output">';
                for (let j = 0; j < row.extraCells.length; j++) {
                    const header = this.tableHeaders[j + 2];
                    const readOnly: string = header.ReadOnly ? 'readonly' : '';
                    const min: string = header.min > 0 ? 'min="' + header.min + '"' : '';
                    const max: string = header.max > 0 ? 'max="' + header.max + '"' : '';
                    const step: string = header.InputType === 'number' ? 'step="any"' : '';
                    // split header input name on [ then add 2 infront of the second part
                    let name = header.InputName;
                    name = name.replace('[', '2[')

                    extraRow += '<td class="m-0 p-0" ><input type="' + header.InputType + '" value="' + row.extraCells[j] + '" class="form-control rounded-0" ' + readOnly + ' name="'+ name +'" ' + step + ' ' + min + ' ' + max + '></td>';
                }
                extraRow += '</tr>';
            }

            table += extraRow;
        }

        if (footer) {
            table += footer.html();
        }

        $('#' + this.tableId).find('tbody').html(table);

        this.attachChangeEventListeners();

    }

    private attachChangeEventListeners(): void {
        const table = document.getElementById(this.tableId);

        if (!table) {
            console.error(`Table with ID ${this.tableId} not found.`);
            return;
        }

        // Attach event listeners to inputs and selects within the table
        const inputs = table.getElementsByTagName('input');
        const selects = table.getElementsByTagName('select');

        for (let i = 0; i < inputs.length; i++) {
            inputs[i].addEventListener('change', this.handleChange.bind(this));
        }

        for (let i = 0; i < selects.length; i++) {
            selects[i].addEventListener('change', this.handleChange.bind(this));
        }
    }

    public handleChange(event: Event): void {
        // Default implementation, can be overridden by subclasses
        console.log('Table element changed:', event);
    }

    public checkIfLastRow(element: JQuery<HTMLElement>) {
        return element.is(':last-child');
    }

    public checkIfSelect(element: JQuery<HTMLElement>) {
        return element.is('select');
    }

    public checkIfInputNumber(element: JQuery<HTMLElement>) {
        return element.is('input[type="number"]');
    }

    public getRecipe(recipe_id:number): Promise<object> {
        return new Promise(function (resolve, reject) {
            $.ajax({
                type: 'GET',
                url: 'getRecipe',
                data: {
                    id: recipe_id
                },
                success: function (response) {

                    try {
                        // Parse the JSON response
                        resolve(JSON.parse(response));
                    } catch (error) {
                        reject(error);
                    }
                },
                error: function (xhr, status, error) {
                    reject(error);
                }
            });
        });
    }

    public getBuilding(building_id: string) : Promise<object> {
        return new Promise(function (resolve, reject) {
            $.ajax({
                type: 'GET',
                url: 'getBuilding',
                data: {
                    id: parseInt(building_id)
                },
                success: function (response) {
                    try {
                        // Parse the JSON response
                        resolve(JSON.parse(response));
                    } catch (error) {
                        reject(error);
                    }
                },
                error: function (xhr, status, error) {
                    reject(error);
                }
            });
        });
    }

    public importData(json: Record<string, any>) {
        this.tableRows = json.tableRows;

        this.renderTable();
    }
}

