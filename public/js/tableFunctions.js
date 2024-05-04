function addInputRow(className) {
    // Clone the last row
    var lastRow = $("." + className).last().closest('tr').clone();
    // remove the onchange attribute from the cloned row
    var row = $("." + className).last().closest('tr');
    $("." + className).removeAttr('onchange');

    // get the recipe info from the api
    getRecipe(row.find('.recipe').val())
        .then(function (recipe) {

            row.find('.product-name').val(recipe.itemName);
            // Update the recipe name
            if (recipe.secondItemName !== 'empty' && recipe.secondItemName != null) {
                let secondItemClone = row.clone();
                secondItemClone.addClass('extra-output');
                secondItemClone.find('.product-name').val(recipe.secondItemName);
                // delete first two tds
                secondItemClone.find('td').slice(0, 2).remove();
                // insert the clone
                secondItemClone.insertAfter(row);
                row.find('td:eq(0)').attr('rowspan', '2');
                row.find('td:eq(1)').attr('rowspan', '2');
                row.find('td:eq(0)').find('select').css('height', '78px');
                row.find('td:eq(1)').find('input').css('height', '78px');

                row.find('.export-amount').attr('name', 'production_export2[]');
                row.find('.usage-amount').attr('name', 'production_usage2[]');

                }
        });


    lastRow.find('input').val('0');
    lastRow.find('.product-name').val('');

    // Append the cloned row after the last row
    lastRow.insertAfter($("." + className).last().closest('tr'));

}

function calculateTotalConsumption() {
    var total = 0;
    $('.consumption').each(function () {
        total += parseInt($(this).val()) || 0;
    });
    $('#totalConsumption').val(total);
}

function calculateConsumption(element) {
    let button = $('#save_button');
    let row = $(element).closest('tr');
    let quantity = row.find('.quantity').val();
    let building_id = row.find('.building').val();
    let clockSpeed = row.find('.clock-speed').val();
    let consumption = row.find('.consumption');

    button.prop('disabled', true);

    Promise.all([
        getBuildingConsumption(building_id),
        Promise.resolve(quantity),
        Promise.resolve(clockSpeed)
    ])
        .then(function(results) {
            let buildingConsumption = results[0];
            let quantity = results[1];
            let clockSpeed = results[2];

            // Perform the calculation using all the data
            let calculatedValue = buildingConsumption * quantity * clockSpeed / 100;

            // Update the consumption value
            consumption.val(calculatedValue);

            // Recalculate total consumption if needed
            calculateTotalConsumption();
            button.prop('disabled', false);
        })
        .catch(function(error) {
            console.error('Error fetching building consumption:', error);
            // Handle the error if necessary
        });

}

function getBuildingConsumption(building_id) {
    // Call getBuilding asynchronously
    return getBuilding(building_id)
        .then(function (building) {
            // Once the building data is available, return the power_used property
            return building.power_used;
        })
        .catch(function (error) {
            console.error('Error fetching building:', error);
            return 0; // Return a default value in case of error
        });
}

function getBuilding(building_id) {
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
                    var building = JSON.parse(response);
                    resolve(building);
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

function getRecipe(recipe_id) {
    return new Promise(function (resolve, reject) {
        $.ajax({
            type: 'GET',
            url: 'getRecipe',
            data: {
                id: parseInt(recipe_id)
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


function calculatePowerOfProduction(element) {
    deletePowerNonUserRows();
    let row = $(element).closest('tbody').find('tr');
    row = row.not('.extra-output');
    // Create an array to store all promises
    let promises = [];

    for (let i = 0; i < row.length; i++) {
        if (row.eq(i).find('.production-quantity').val() == 0 || row.eq(i).find('.production-quantity').val() == '') {
            continue;
        }
        // Call getRecipe asynchronously and push the promise to the array
        let recipePromise = getRecipe(row.eq(i).find('.recipe').val());
        promises.push(recipePromise);
    }

    // Wait for all promises to resolve using Promise.all()
    Promise.all(promises)
        .then(function (recipes) {
            // Create an array to store promises for getBuilding
            let buildingPromises = [];

            // Iterate over recipes and getBuilding asynchronously
            recipes.forEach(function (recipe) {
                let buildingPromise = getBuilding(recipe.buildings_id);
                buildingPromises.push(buildingPromise);
            });

            // Wait for all getBuilding promises to resolve
            return Promise.all(buildingPromises)
                .then(function (buildings) {
                    // Now all recipe and building data is available
                    for (let i = 0; i < row.length -1; i++) {
                        // if the tr has extra-output then skip


                        if (row.eq(i).find('.production-quantity').val() == 0 || row.eq(i).find('.production-quantity').val() == '') {
                            continue;
                        }
                        var outputQuantity = row.eq(i).find('.production-quantity').val();
                        let recipe = recipes[i];
                        let building = buildings[i];
                        insertBuildingRow(recipe, building, outputQuantity);

                        // if recipe has a second item then add it to the table if the next row is not the extra-output row
                        if (recipe.secondItemName !== 'empty' && recipe.secondItemName != null) {
                            var existingTr = row.eq(i);
                            var tr = existingTr.next();
                            if (!tr.hasClass('extra-output')) {
                                let secondItemClone = existingTr.clone();
                                secondItemClone.addClass('extra-output');
                                // delete first two tds
                                secondItemClone.find('td').slice(0, 2).remove();



                                // insert the clone
                                secondItemClone.insertAfter(existingTr);

                                existingTr.find('td:eq(0)').attr('rowspan', '2');
                                existingTr.find('td:eq(1)').attr('rowspan', '2');
                                existingTr.find('td:eq(0)').find('select').css('height', '78px');
                                existingTr.find('td:eq(1)').find('input').css('height', '78px');
                                // change the name of the element to production_usage2
                                existingTr.find('.export-amount').attr('name', 'production_export2');
                                existingTr.find('.usage-amount').attr('name', 'production_usage2');


                            }

                        } else {
                            var existingTr = row.eq(i);
                            var tr = existingTr.next();
                            if (tr.hasClass('extra-output')) {
                                tr.remove();
                                existingTr.find('td:eq(0)').attr('rowspan', '1');
                                existingTr.find('td:eq(1)').attr('rowspan', '1');
                                existingTr.find('td:eq(0) select').css('height', 'auto');
                                existingTr.find('td:eq(1) input').css('height', 'auto');

                            }
                        }

                        let exportAmount = row.eq(i).find('.production-quantity').val() - row.eq(i).find('.usage-amount').val();
                        if (exportAmount < 0) {
                            if (row.eq(i).find('.usage-amount').val > row.eq(i).find('.production-quantity').val()) {
                                row.eq(i).find('.production-quantity').val(row.eq(i).find('.usage-amount').val());
                            } else {
                                row.eq(i).find('.usage-amount').val(row.eq(i).find('.production-quantity').val());
                            }
                            alert('The export amount is negative, please check the usage amount');
                            calculatePowerOfProduction(element);
                        }
                        row.eq(i).find('.export-amount').val(exportAmount);
                        row.eq(i).find('.product-name').val(recipe.itemName);
                        if (recipe.secondItemName !== 'empty' && recipe.secondItemName != null) {
                            var tr = row.eq(i).closest('tr').next();
                            var usage = tr.find('.usage-amount').val();
                            var secondExportAmount = row.eq(i).find('.production-quantity').val() - usage;
                            var amountPerOne = recipe.export_amount_per_min2 / recipe.export_amount_per_min;

                            if (secondExportAmount < 0) {
                                if (row.eq(i).find('.usage-amount').val > row.eq(i).find('.production-quantity').val()) {
                                    row.eq(i).find('.production-quantity').val(row.eq(i).find('.usage-amount').val());
                                } else {
                                    row.eq(i).find('.usage-amount').val(row.eq(i).find('.production-quantity').val());
                                }
                                alert('The export amount is negative, please check the usage amount');
                                calculatePowerOfProduction(element);
                            }

                            tr.find('.export-amount').val(outputQuantity * amountPerOne - usage);
                            tr.find('.product-name').val(recipe.secondItemName);
                            tr.find('.production-quantity').val(usage);

                        }

                    }
                });
        })
        .catch(function (error) {
            console.error('Error fetching recipe/building:', error);
        });
}



function deletePowerNonUserRows() {
    $('.building').each(function () {
        if ($(this).closest('tr').hasClass('user')) {
            return;
        }
        $(this).closest('tr').remove();
    });
    calculateTotalConsumption();
}

function insertBuildingRow(recipe, building, outputQuantity) {
    let row = $('.building').first().closest('tr').clone();
    let quantity = outputQuantity / recipe.export_amount_per_min;
    let roundedQuantity = Math.floor(quantity)
    let clockSpeed = (quantity % 1).toFixed(5) * 100;
    let buildingAlreadyInTable = 0;

    // check if one of the buildings is already in the table and not from the user
    $('.building').each(function () {
        if ($(this).val() == building.id && !$(this).closest('tr').hasClass('user')) {
            buildingAlreadyInTable++;
        }
    });

    if (buildingAlreadyInTable == 0) {
        if (roundedQuantity !== 0) {
            row.find('.building').val(building.id);
            row.find('.quantity').val(roundedQuantity);
            row.find('.user').val(0);
            row.removeClass('user');
            calculateConsumption(row)
            row.insertBefore($('.building').last().closest('tr'));
        }
        if (clockSpeed != 0) {
            let row = $('.building').last().closest('tr').clone();
            row.find('.building').val(building.id);
            row.find('.quantity').val(1);
            row.find('.user').val(0);
            row.find('.clock-speed').val(clockSpeed);
            row.removeClass('user');
            calculateConsumption(row)
            row.insertBefore($('.building').last().closest('tr'));
        }
    } else {
        // add clockspeed to the already existing building
        let element = $('.building').filter(function () {
            return $(this).val() == building.id;
        }).first().closest('tr');
        const totalQuantity = parseInt(element.find('.quantity').val()) + roundedQuantity;
        element.find('.quantity').val(totalQuantity);
        calculateConsumption(element);

        if (clockSpeed != 0) {
            let row = element.clone();
            row.find('.building').val(building.id);
            row.find('.quantity').val(1);
            row.find('.user').val(0);
            row.find('.clock-speed').val(clockSpeed);
            calculateConsumption(row);
            row.insertAfter(element);
        }
    }

    calculateTotalConsumption();

}

function calculateImports(requierdResources) {
    let row = $('.resource').first().closest('tr').clone();
    let resources = requierdResources.split(',');
    for (let i = 0; i < resources.length; i++) {
        let resource = resources[i].split(':');
        row.find('.resource').val(resource[0]);
        row.find('.quantity').val(resource[1]);
        row.removeClass('user');
        row.insertBefore($('.resource').last().closest('tr'));
        row = row.clone();
    }
}

