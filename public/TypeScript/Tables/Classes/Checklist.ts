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

        this.createChecklist()
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

                this.checklist.push({recipeName, productionAmount, buildingAmount, beenBuild, beenTested});
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

            checklist.append(this.createCard(recipeName, productionAmount, buildingAmount, beenBuild, beenTested, buildingName));
            this.checklist.push({recipeName, productionAmount, buildingAmount, beenBuild, beenTested});
        });
        // console.log(checklist.find("input[type='checkbox']"))
        checklist.find("input[type='checkbox']").each((index, checkbox) => {
            console.log($(checkbox))
            // @ts-ignore
            $(checkbox).bootstrapToggle();
        })

    }

    private createCard(recipeName: string, productionAmount: number, buildingAmount: number, beenBuild: boolean, beenTested: boolean, building?: string) {
        return `
        <div class="card mb-2">
            <div class="card-body p-3">
                <h5 class="card-title recipeName">${recipeName}</h5>
                <p class="card-text"><span class="productionAmount">${productionAmount}</span> per min - <span class="buildingAmount">${buildingAmount}</span> ${building}</p>
                <div style="display: flex; justify-content: space-between;">
                    <div>
                        <input type="checkbox" data-toggle="toggle" data-onstyle="success" data-offstyle="dark"
                               data-onlabel="<i class='fa-solid fa-check'></i>" data-offlabel="<i class='fa-solid fa-times'></i>"
                               data-size="sm" data-style="ios" data-theme="dark" id="beenBuild" ${beenBuild ? "checked" : ""}/>
                        <label for="build">Build</label>
                    </div>
                    <div>
                        <!--                        same checkbox as above-->
                        <input type="checkbox" data-toggle="toggle" data-onstyle="success" data-offstyle="dark"
                               data-onlabel="<i class='fa-solid fa-check'></i>" data-offlabel="<i class='fa-solid fa-times'></i>"
                               data-size="sm" data-style="ios" data-theme="dark" id="beenTested" ${beenTested ? "checked" : ""}/>
                        <label for="tested">Tested</label>
                    </div>
                </div>
            </div>
        </div>
        `;
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