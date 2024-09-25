import {Recipe} from "./Types/Recipe";

export class Ajax {

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
}