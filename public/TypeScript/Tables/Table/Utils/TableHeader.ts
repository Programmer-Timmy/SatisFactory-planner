interface Option {
    value: string;
    display: string;
    disabled?: boolean;
}

export type Options = {
    [key: string]: Option;
};

export class TableHeader {
    class : string = '';
    Name : string = '';
    InputType : string = '';
    ReadOnly : boolean = false;
    Options : Options = {};
    InputName : string = '';
    default : string = '';
    min : number = 0;
    max : number = 0;

    constructor(name: string, inputType: string, readOnly : boolean = false, options: Options = {}, inputName: string = '', defaultVal: string = '', min: number = 0, max: number = 0, htmlClass: string = '') {
        this.Name = name;
        this.InputType = inputType;
        this.ReadOnly = readOnly;
        this.Options = options;
        this.InputName = inputName;
        this.default = defaultVal;
        this.min = min;
        this.max = max;
        this.class = htmlClass;
    }
}