import {Recipe} from "../Types/Recipe";
import {Building} from "../Types/Building";


export class Ajax {

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
                    data: JSON.stringify(data),
                    id: id
                },
                success: function (response) {
                    resolve(JSON.parse(response));
                },
                error: function (xhr, status, error) {
                    reject(error);
                },
            });
        });
    }
}