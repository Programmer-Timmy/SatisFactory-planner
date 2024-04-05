function addRow() {
    // Clone the last row
    var lastRow = $(".input-item-id").last().closest('tr').clone();
    // remove the onchange attribute from the cloned row
    $('.input-item-id').removeAttr('onchange');


    // Reset any values or attributes if needed
    lastRow.find('input').val('');

    // Append the cloned row after the last row
    lastRow.insertAfter($(".input-item-id").last().closest('tr'));




}
