import {TableHandler} from "./TableHandler";
import {Ajax} from "./Functions/Ajax";
import {HtmlGeneration} from "./Functions/HtmlGeneration";

export interface IChecklist {
    recipeName: string;
    productionAmount: number;
    buildingAmount: number;
    buildingName: string;

    beenBuild: boolean
    beenTested: boolean;
}

export class Checklist {
    private tableHandler: TableHandler;
    private checklist: IChecklist[] = [];

    constructor(tableHandler: TableHandler) {
        this.tableHandler = tableHandler;

        this.CheckForExistingChecklist();

        this.attachEvents();
        console.log(this.checklist);
    }

    private CheckForExistingChecklist() {
        const checklist = $("#Checklist");
        const checks = checklist.find(".card");
        if (checks.length > 0) {
            checks.each((index, check) => {
                const recipeName = $(check).find(".recipeName").text();
                const productionAmount = parseInt($(check).find(".productionAmount").text());
                const buildingAmount = parseInt($(check).find(".buildingAmount").text());
                const beenBuild = $(check).find(".beenBuild").is(":checked");
                const beenTested = $(check).find(".beenTested").is(":checked");
                const buildingName = $(check).find(".buildingName").text();

                this.checklist.push({recipeName, productionAmount, buildingAmount, buildingName, beenBuild, beenTested});
            });
        } else {
            this.createChecklist();
        }
    }

    public createChecklist() {
        const checklist = $("#Checklist .offcanvas-body");
        checklist.empty();
        this.tableHandler.productionTableRows.forEach((row, index) => {
            if (index === this.tableHandler.productionTableRows.length - 1) return
            const productionAmount = row.quantity;
            const recipeName = row.recipe?.name || "Unknown";
            const buildingName = row.recipe?.building?.name || "Unknown";
            const productionPerMin = row.recipe?.export_amount_per_min || 0;
            const buildingAmount = +Math.ceil(productionAmount / productionPerMin).toFixed(5);

            const beenBuild = false;
            const beenTested = false;

            checklist.append(HtmlGeneration.createCard(recipeName, productionAmount, buildingAmount, beenBuild, beenTested, buildingName));
            this.checklist.push({recipeName, productionAmount, buildingAmount, buildingName, beenBuild, beenTested});
        });
        checklist.find("input[type='checkbox']").each((index, checkbox) => {
            // @ts-ignore
            $(checkbox).bootstrapToggle();
        })

        Ajax.saveChecklist(this.checklist);

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
            console.log(index);
            this.checklist[index].beenTested = $(event.target).is(":checked");
            Ajax.saveChecklist(this.checklist);
        });

        beenBuild.on("change", (event) => {
            const index = beenBuild.index(event.target);
            this.checklist[index].beenBuild = $(event.target).is(":checked");
            Ajax.saveChecklist(this.checklist);
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

}