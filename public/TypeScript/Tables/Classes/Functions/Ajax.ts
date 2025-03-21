import {Recipe} from "../Types/Recipe";
import {Building} from "../Types/Building";
import {IChecklist} from "../Checklist";


export class Ajax {
    private static gameSaveId: number = Number($('#gameSaveId').val()) ?? 0;
    private static url: URL = new URL(window.location.href);
    private static productionLineId: number = parseInt(this.url.searchParams.get('id') as string);

    /**
     * Get a recipe by its ID.
     *
     * @param recipe_id - The ID of the recipe to get.
     */
    public static getRecipe(recipe_id: number): Promise<Recipe> {
        return new Promise(function (resolve, reject) {
            $.ajax({
                type: 'GET',
                url: 'getRecipe',
                data: {
                    id: recipe_id
                },
                headers: {'X-CSRF-Token': Ajax._getCsrfToken()},
                dataType: 'json',
                success: function (response) {

                    try {
                        resolve(response);
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

    /**
     * Get a building by its ID.
     *
     * @param building_id - The ID of the building to get.
     */
    public static getBuilding(building_id: number): Promise<Building> {
        return new Promise(function (resolve, reject) {
            $.ajax({
                type: 'GET',
                url: 'getBuilding',
                data: {
                    id: building_id
                },
                headers: {'X-CSRF-Token': Ajax._getCsrfToken()},
                dataType: 'json',
                success: function (response) {
                    try {
                        resolve(response);
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

    /**
     * Save the production line data.
     *
     * @param data - The data to save.
     * @param id - The ID of the production line.
     * @returns The response from the server.
     */
    public static async saveData(data: Record<string, any>, id: number): Promise<Record<string, any>> {
        return new Promise((resolve, reject) => {
            $.ajax({
                type: 'POST',
                url: 'saveProductionLine',
                data: {
                    gameSaveId: this.gameSaveId,
                    data: JSON.stringify(data),
                    id: id
                },
                headers: {'X-CSRF-Token': Ajax._getCsrfToken()},
                success: function (response) {
                    resolve(JSON.parse(response));
                },
                error: function (xhr, status, error) {
                    reject(error);
                },
            });
        });
    }

    /**
     * Get the production line data.
     *
     * @returns The response from the server.
     * @param productionLineId
     * @param autoImportExport
     * @param autoPowerMachine
     * @param autoSave
     */
    public static saveSettings(productionLineId: number, autoImportExport: boolean, autoPowerMachine: boolean, autoSave: boolean): void {
        $.ajax({
            type: 'POST',
            url: 'updateProductionLineSettings',
            headers: {'X-CSRF-Token': Ajax._getCsrfToken()},
            data: {
                gameSaveId: this.gameSaveId,
                productionLineId: productionLineId,
                autoImportExport: autoImportExport,
                autoPowerMachine: autoPowerMachine,
                autoSave: autoSave,

            },
        });
    }

    private static _getCsrfToken(): string {
        const meta = $('meta[name="csrf-token"]');
        if (meta.length === 0 || meta.attr('content') === undefined) {
            throw new Error('CSRF token not found');
        }
        return <string>meta.attr('content');
    }

    static saveChecklist(checklists:IChecklist[], ): void {

        $.ajax({
            url: "/saveChecklist",
            method: "POST",
            data: {
                checklist: JSON.stringify(checklists),
                productionLineId: this.productionLineId
            },
            headers: {'X-CSRF-Token': Ajax._getCsrfToken()},
            success: (data) => {
                console.log(data);
            },
            error: (err) => {
                console.error(err);
            }
        });

    }
}