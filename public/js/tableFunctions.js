function addInputRow(className) {
    // Clone the last row
    var lastRow = $("." + className).last().closest('tr').clone();
    // remove the onchange attribute from the cloned row
    $("." + className).removeAttr('onchange');

    // Reset any values or attributes if needed
    lastRow.find('input').val('0');

    // Append the cloned row after the last row
    lastRow.insertAfter($("." + className).last().closest('tr'));

}

function calculateTotalConsumption() {
    var total = 0;
    $('.consumption').each(function() {
        total += parseInt($(this).val()) || 0;
    });
    $('#totalConsumption').val(total);
}

function calculateConsumption(element) {
    let row = $(element).closest('tr');
    let quantity = row.find('.quantity').val();
    let building_id = row.find('.building').val();
    let clockSpeed = row.find('.clock-speed').val();
    let consumption = row.find('.consumption');
    let buildingConsumption = getBuildingConsumption(building_id);
    consumption.val(buildingConsumption * quantity * clockSpeed / 100);
    calculateTotalConsumption();

}

function getBuildingConsumption(building_id) {
    let consumption = 0;

    console.log(building_id);
    let building = getBuilding(building_id);
    return building.power_used;
}

function getBuilding(building_id) {
    let building;
    $.ajax({
        type: 'GET',
        url: 'getBuilding',
        data: {
            id: parseInt(building_id)
        },
        async: false,
        success: function(response) {
            building = response;
        },
    });
    return JSON.parse(building);
}

function getRecipe(recipe_id) {
    let recipe;
    $.ajax({
        type: 'GET',
        url: 'getRecipe',
        data: {
            id: parseInt(recipe_id)
        },
        async: false,
        success: function(response) {
            recipe = response;
        },
    });
    return JSON.parse(recipe);
}

function calculatePowerOfProduction(element) {
    deletePowerNonUserRows();
    let row = $(element).closest('tbody').find('tr');
    for (let i = 0; i < row.length; i++) {
        if (row.eq(i).find('.production-quantity').val() == 0 || row.eq(i).find('.production-quantity').val() == ''){
            continue;
        }
        let recipe = getRecipe(row.eq(i).find('.recipe').val());
        let building = getBuilding(recipe.buildings_id);
        let outputQuantity = row.eq(i).find('.production-quantity').val();
        insertBuildingRow(recipe, building, outputQuantity);

        let exportAmount = row.eq(i).find('.production-quantity').val() - row.eq(i).find('.usage-amount').val();
        if (exportAmount < 0){
            if (row.eq(i).find('.usage-amount').val > row.eq(i).find('.production-quantity').val()){
                row.eq(i).find('.production-quantity').val(row.eq(i).find('.usage-amount').val());
            }else{
                row.eq(i).find('.usage-amount').val(row.eq(i).find('.production-quantity').val());
            }
            exportAmount = 0;
            alert('The export amount is negative, please check the usage amount')
            calculatePowerOfProduction(element);
        }
        row.eq(i).find('.export-amount').val(exportAmount);
    }
}

function deletePowerNonUserRows(){
    $('.building').each(function(){
        if ($(this).closest('tr').hasClass('user')){
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
    console.log(building)

    // check if one of the buildings is already in the table and not from the user
    $('.building').each(function(){
        if ($(this).val() == building.id && !$(this).closest('tr').hasClass('user')){
            buildingAlreadyInTable ++;
        }
    });

    if (buildingAlreadyInTable == 0){
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
        let element = $('.building').filter(function() {
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

