import {TableHandler} from "./TableHandler";

interface IChecklist {
    recipeName: string;
    productionAmount: number;
    buildingAmount: number;

    beenBuild: boolean
    beenTested: boolean;
}

export class Checklist {
    private tableHandler: TableHandler;
    private checklist: IChecklist[] = [];

    constructor(tableHandler: TableHandler) {
        this.tableHandler = tableHandler;

        this.CheckForExistingChecklist();

        console.log(this.checklist);
    }

    CheckForExistingChecklist(){
        const checklist = $("#Checklist");
        console.log(checklist);
        const checks = checklist.find(".card");
        console.log(checks);
        if(checks.length > 0){
            checks.each((index, check) => {
                const recipeName = $(check).find(".recipeName").text();
                const productionAmount = parseInt($(check).find(".productionAmount").text());
                const buildingAmount = parseInt($(check).find(".buildingAmount").text());
                const beenBuild = $(check).find(".beenBuild").is(":checked");
                const beenTested = $(check).find(".beenTested").is(":checked");

                this.checklist.push({recipeName, productionAmount, buildingAmount, beenBuild, beenTested});
            });
        }
    }


}