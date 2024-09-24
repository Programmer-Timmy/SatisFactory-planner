export class Ajax {

    public static getRecipe(recipe_id: number): Promise<object> {
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
                        // Parse the JSON response
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