import {TableHandler} from "./TableHandler";
import {Ajax} from "./Functions/Ajax";
import {HtmlGeneration} from "./Functions/HtmlGeneration";
import {ProductionTableRow} from "./Data/ProductionTableRow";

export interface IChecklist {
    index: number;
    productionRow: ProductionTableRow;

    beenBuild: boolean;
    beenTested: boolean;
}

export class Checklist {
    private htmlElement: JQuery<HTMLElement> = $("#Checklist");
    private canvasBody: JQuery<HTMLElement> = this.htmlElement.find(".offcanvas-body");
    private tableHandler: TableHandler;
    private checklist: IChecklist[] = [];

    constructor(tableHandler: TableHandler) {
        this.tableHandler = tableHandler;

        this.CheckForExistingChecklist();

        this.attachEvents();
        console.log(this.checklist);
    }

    private CheckForExistingChecklist() {
        const checks = this.htmlElement.find('#checkListData').text();
        if (checks) {
            const JsonChecks = JSON.parse(checks);
            JsonChecks.forEach((check: any, index: number) => {
                const productionId = check.production_id;
                const beenBuild = check.been_build === 1;
                const beenTested = check.been_tested === 1;

                const productionRow = this.tableHandler.productionTableRows.find(row => row.row_id == productionId);

                if (productionRow) {
                    this.checklist.push({index, productionRow, beenBuild, beenTested});
                }
            });

            this.buildChecklist();
            this.generateMissingChecklist();
        } else {
            this.createChecklist();
        }
    }

    private buildChecklist() {
        this.checklist.forEach((check, index) => {
           this.createChecklistCard(check.productionRow, index, check.beenBuild, check.beenTested);
        });
        this.initCheckBoxes();
    }

    public createChecklist() {
        this.checklist = [];
        const checklist = $("#Checklist .offcanvas-body");
        checklist.empty();
        this.tableHandler.productionTableRows.forEach((row, index) => {
            this.createChecklistCard(row, index);
            this.checklist.push({index ,productionRow: row, beenBuild: false, beenTested: false});
        });
        this.initCheckBoxes();
    }

    private createChecklistCard(row: ProductionTableRow, index: number, beenBuild: boolean = false, beenTested: boolean = false) {
        if (index === this.tableHandler.productionTableRows.length - 1) return
        const productionAmount = row.quantity;
        const recipeName = row.recipe?.name || "Unknown";
        const buildingName = row.recipe?.building?.name || "Unknown";
        const productionPerMin = row.recipe?.export_amount_per_min || 0;
        const buildingAmount = +Math.ceil(productionAmount / productionPerMin).toFixed(5);

        if (productionAmount <= 0) return;

        this.canvasBody.append(HtmlGeneration.createCard(recipeName, productionAmount, buildingAmount, beenBuild, beenTested, buildingName));
    }

    public updateCheckList(productionRow: ProductionTableRow) {
        const check = this.checklist.find(check => check.productionRow.row_id == productionRow.row_id);
        if (check) {
            const row = check.productionRow;
            const productionAmount = row.quantity;
            const recipeName = row.recipe?.name || "Unknown";
            const buildingName = row.recipe?.building?.name || "Unknown";
            const productionPerMin = row.recipe?.export_amount_per_min || 0;
            const buildingAmount = +Math.ceil(productionAmount / productionPerMin).toFixed(5);

            const card = this.canvasBody.find(`.card`)[check.index];
            $(card).replaceWith(HtmlGeneration.createCard(recipeName, productionAmount, buildingAmount, false, false, buildingName));
            this.checklist[check.index] = {index: check.index, productionRow, beenBuild: false, beenTested: false};
        } else {
            this.createChecklistCard(productionRow, this.tableHandler.productionTableRows.length - 1);
            this.checklist.push({index: this.tableHandler.productionTableRows.length - 1, productionRow, beenBuild: false, beenTested: false});
        }
        this.initCheckBoxes();

    }

    private attachEvents() {
        const input = $("#Checklist #searchChecklist")
        const clearSearch = $("#Checklist #resetSearchChecklist")

        input.on("input", () => {
            const value = input.val() as string;
            this.searchChecklist(value);
        });

        clearSearch.on("click", () => {
            input.val("");
            this.clearSearch();
        });

        const beenTested = $("#Checklist .offcanvas-body").find("input[type='checkbox'][for='tested']");
        const beenBuild = $("#Checklist .offcanvas-body").find("input[type='checkbox'][for='build']");

        beenTested.on("change", (event) => {
            const index = beenTested.index(event.target);
            this.checklist[index].beenTested = $(event.target).is(":checked");
        });

        beenBuild.on("change", (event) => {
            const index = beenBuild.index(event.target);
            this.checklist[index].beenBuild = $(event.target).is(":checked");
        });
    }

    private generateMissingChecklist() {
        const missing = [];
        this.tableHandler.productionTableRows.forEach((row, index) => {
            const check = this.checklist.find(check => check.productionRow.row_id == row.row_id);
            if (!check) {
                this.createChecklistCard(row, index);
                this.checklist.push({index, productionRow: row, beenBuild: false, beenTested: false});
            }
        });
    }

    private searchChecklist(recipeName: string) {
        recipeName = recipeName.toLowerCase();
        const $offcanvasBody = $("#Checklist .offcanvas-body");
        $offcanvasBody.find(".alert").remove();
        const $cards = $offcanvasBody.find(".card");
        $cards.each((index, card) => {
            const cardRecipeName = $(card).find(".recipeName").text().toLowerCase()
            if (cardRecipeName.includes(recipeName)) {
                $(card).show();
            } else {
                $(card).hide();
            }
        });

        const anyVisible = $cards.toArray().some(card => $(card).is(":visible"));
        if (!anyVisible) {
            $offcanvasBody.append("<div class='alert alert-danger'>No results found</div>");
        }
    }

    private clearSearch() {
        $("#Checklist .offcanvas-body").find(".card").each((index, card) => {
            $(card).show();
        });
    }

    public getChecklist() {
        // filter out the checks that have 0 per min and 0 quantity
        const checklist = this.checklist.filter(check => {
            return +check.productionRow.quantity !== 0 && check.productionRow.quantity
        });
        console.log(checklist)
        return checklist;
    }

    private initCheckBoxes() {
        this.canvasBody.find("input[type='checkbox']").each((index, checkbox) => {
            // @ts-ignore
            $(checkbox).bootstrapToggle();
        })
    }

}