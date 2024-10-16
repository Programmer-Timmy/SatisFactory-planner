<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">Production Line Guide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="welcome_section">
                    <h3 class="text-primary">Welcome to Your Production Line!</h3>
                    <p>
                        Welcome, Pioneer! This guid will help you get started with your production line. The production
                        line is a tool that helps you plan and optimize your production process. It allows you to
                        calculate the required imports, the number of buildings needed, and the total power usage for
                        your production line.
                    </p>
                    <p>
                        If you have any suggestions or feedback, please let me know. I'm always looking for ways to
                        improve the production line tool.
                    </p>
                </div>

                <h3 class="text-primary">Import Section</h3>
                <p>
                    The <strong>Import Section</strong> is located on the left side of the screen. This section will
                    <strong>automatically calculate</strong> when you add recipes to the Production Section. If you
                    prefer to manually set the import values, you can disable this feature in the settings accessible
                    from the <strong>Edit Screen</strong>.
                </p>

                <h3 class="text-primary">Production Section</h3>
                <p>
                    The <strong>Production Section</strong> is in the center of the screen. Here, you can add recipes to
                    your production line and specify the <strong>amount per minute</strong> for each recipe. If all the
                    automated functions are enabled, the production line will:
                </p>
                <ul>
                    <li>Calculate the required imports (It wil take the already produced items into account).</li>
                    <li>Calculate the number and type of buildings needed.</li>
                    <li>Calculate the total power usage.</li>
                </ul>

                <h3 class="text-primary">Toolbar</h3>
                <p>
                    Use the buttons at the top-right to:
                </p>
                <ul>
                    <li><i class="fa-solid fa-save" aria-hidden="true"></i> <strong>Save</strong> (hold <kbd>Shift</kbd>
                        to remain on the current screen after saving).
                    </li>
                    <li><i class="fa-solid fa-pencil" aria-hidden="true"></i> <strong>Edit</strong> your production
                        line.
                    </li>
                    <li><i class="fa-solid fa-bolt" aria-hidden="true"></i> <strong>View Power Usage</strong> to see the
                        buildings and their power consumption.
                    </li>
                    <li><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <strong>Go Back</strong> to the save
                        games without saving the current production line.
                    </li>
                </ul>

                <h3 class="text-primary">Power Section</h3>
                <p>
                    The <strong>Power Section</strong> can be accessed via the power button in the toolbar. It displays:
                </p>
                <ul>
                    <li>The <strong>power usage</strong> of the production line.</li>
                    <li>The number of buildings required for the production line.</li>
                    <li>The total <strong>power consumption</strong> needed.</li>
                </ul>
                <p>
                    Including miners and other equipment that can’t be automated in your production line is helpful.
                    This allows you to see a more accurate representation of <b>the power usage</b>. And shows you how
                    much power your power gird has left.
                </p>

                <h3 class="text-primary">Edit Section</h3>
                <p>
                    In the <strong>Edit Screen</strong>, you can modify the settings for your production line. The
                    settings are:
                </p>
                <ul>
                    <li><strong>Production Line Name:</strong> The name of your production line.</li>
                    <li>
                        <strong>Auto Import-Export:</strong> Enable or disable automatic import and export calculations
                        for your production line. This feature calculates the import and export values automatically and
                        is enabled by default.
                    </li>
                    <li>
                        <strong>Auto Power-Machine:</strong> Enable or disable automatic power and machine calculations
                        for your production line. This feature automatically calculates the power and machine values and
                        is enabled by default.
                    </li>
                    <li><strong>Active:</strong> This indicates whether the production line is running or not. If the
                        production line is not active, it will not be included in the global power usage calculation.
                    </li>
                    <li><strong>Import/Export:</strong> You can import and export your complete production line. This
                        allows you to share your production line with others that you don't want to share your save game
                        with.
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('showHelp').addEventListener('click', function () {
        const helpModal = $('#helpModal');
        helpModal.find('#welcome_section').hide();
        const popupModal = new bootstrap.Modal(helpModal);
        popupModal.show();
    });
</script>
