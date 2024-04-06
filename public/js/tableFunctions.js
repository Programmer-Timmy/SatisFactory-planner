function addInputRow(className) {
    // Clone the last row
    var lastRow = $("." + className).last().closest('tr').clone();
    // remove the onchange attribute from the cloned row
    $("." + className).removeAttr('onchange');

    // Reset any values or attributes if needed
    lastRow.find('input').val('');

    // Append the cloned row after the last row
    lastRow.insertAfter($("." + className).last().closest('tr'));

}
