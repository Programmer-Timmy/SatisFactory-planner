<!-- Help Modal -->
<style>
    #helpModal .list-group-item {
        transition: background-color 0.2s;
    }

    #helpModal .list-group-item:hover {
        background-color: var(--bs-primary-bg-subtle);
    }

</style>
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">Production Line Guide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="welcome">
                    <h3 class="text-primary">Welcome to Your Production Line!</h3>
                    <p>
                        Welcome, Pioneer! This guide will help you get started with your production line. The production
                        line is a tool that helps you plan and optimize your production process, allowing you to
                        calculate the required imports, the number of buildings needed, and the total power usage for
                        your production line.
                    </p>
                    <p>
                        If you have any suggestions or feedback, please let me know. I'm always looking for ways to
                        improve the production line tool.
                    </p>
                </div>
                <h3 class="text-primary">Sections Overview</h3>
                <div class="list-group mb-3">
                    <a href="#importSection" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-arrow-up-right-dots"></i> Import Section
                    </a>
                    <a href="#productionSection" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-cogs"></i> Production Section
                    </a>
                    <a href="#powerSection" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-bolt"></i> Power Section
                    </a>
                    <a href="#visualizationSection" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-project-diagram"></i> Visualization Section
                    </a>
                    <a href="#editSection" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-edit"></i> Edit Screen
                    </a>
                    <a href="#toolbar" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-tools"></i> Toolbar
                    </a>
                    <a href="#shortcuts" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-keyboard"></i> Shortcuts
                    </a>
                </div>

                <hr>


                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary" id="importSection"><i
                                    class="fa-solid fa-arrow-up-right-dots"></i> Import Section</h4>
                        <p>
                            The <strong>Import Section</strong> is located on the left side of the screen. This section
                            will
                            <strong>automatically calculate</strong> when you add recipes to the Production Section. If
                            you
                            prefer to manually set the import values, you can disable this feature in the settings
                            accessible
                            from the <strong>Edit Screen</strong>.
                        </p>
                    </div>
                </div>

                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary" id="productionSection"><i class="fa-solid fa-cogs"></i>
                            Production Section</h4>
                        <p>
                            The <strong>Production Section</strong> is in the center of the screen. Here, you can add
                            recipes to
                            your production line and specify the <strong>amount per minute</strong> for each recipe. If
                            all the
                            automated functions are enabled, the production line will:
                        </p>
                        <ul>
                            <li>Calculate the required imports (taking already produced items into account).</li>
                            <li>Calculate the number and type of buildings needed.</li>
                            <li>Calculate the total power usage.</li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary" id="visualizationSection"><i
                                    class="fa-solid fa-project-diagram"></i> Visualization Section</h4>
                        <p>
                            The <strong>Visualization Section</strong> can be accessed via the <kbd
                                    class="bg-info text-dark"><i class="fa-solid fa-project-diagram"
                                                                 aria-hidden="true"></i></kbd> button in the <a
                                    class="link-primary" href="#toolbar">Toolbar</a> or by pressing <kbd>Ctrl</kbd> +
                            <kbd>V</kbd>
                            . Here you can see a visual representation of your production line. The visualization is
                            generated automatically and shows the connections between the buildings and the flow of
                            items.
                        </p>
                        <p>
                            You can use the <strong>Layout</strong> dropdown to change the layout of the visualization.
                            The following layouts are available:
                        </p>
                        <ul>
                            <li><strong>Breadthfirst:</strong> A layout that arranges the nodes in a tree-like
                                structure.
                            </li>
                            <li><strong>Cose:</strong> A layout that arranges the nodes in a force-directed graph.</li>
                            <li><strong>Klay:</strong> A layout that arranges the nodes in a tree-like structure with
                                minimal edge crossings.
                            </li>
                            <li><strong>Fcose:</strong> A layout that arranges the nodes in a force-directed graph with
                                a focus on performance.
                            </li>
                        </ul>

                        <p>
                            You can also use the <strong>Export</strong> checkbox to show or hide the export buildings,
                            the <strong>Import</strong> checkbox to show or hide the import buildings, and the <strong>Roots</strong>
                            checkbox to show or hide the root nodes.
                        </p>
                    </div>
                </div>

                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary" id="powerSection"><i class="fa-solid fa-bolt"></i> Power
                            Section</h4>
                        <p>
                            The <strong>Power Section</strong> can be accessed via the <kbd class="bg-info text-dark"><i
                                        class="fa-solid fa-bolt" aria-hidden="true"></i></kbd> button in the <a
                                    class="link-primary" href="#toolbar">Toolbar</a> or by pressing <kbd>Ctrl</kbd> +
                            <kbd>P</kbd>.<br>
                            Here you can see the following information:
                        </p>
                        <ul>
                            <li>The <strong>power usage</strong> of the production line.</li>
                            <li>The number of buildings required for the production line.</li>
                            <li>The total <strong>power consumption</strong> needed.</li>
                        </ul>
                        <p>
                            Including miners and other equipment that can’t be automated in your production line is
                            helpful,
                            as it provides a more accurate representation of <b>the power usage</b> and shows how much
                            power
                            your power grid has left.
                        </p>
                    </div>
                </div>

                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary" id="editSection"><i class="fa-solid fa-edit"></i> Edit
                            Screen</h4>
                        <p>
                            In the <strong>Edit Screen</strong>, you can modify the settings for your production line.
                            Access the Edit Screen by clicking the <kbd class="bg-warning text-dark"><i
                                        class="fa-solid fa-pencil" aria-hidden="true"></i></kbd> button in the <a
                                    class="link-primary" href="#toolbar">Toolbar</a> or by pressing <kbd>Ctrl</kbd> +
                            <kbd>E</kbd>.
                        </p>
                        <p>
                            The following settings are available:
                        </p>
                        <ul>
                            <li><strong>Production Line Name:</strong> The name of your production line.</li>
                            <li>
                                <strong>Auto Import-Export:</strong> Enable or disable automatic import and export
                                calculations
                                for your production line. This feature is enabled by default.
                            </li>
                            <li>
                                <strong>Auto Power-Machine:</strong> Enable or disable automatic power and machine
                                calculations
                                for your production line. This feature is enabled by default.
                            </li>
                            <li><strong>Active:</strong> This indicates whether the production line is running. If not
                                active, it
                                will not be included in the global power usage calculation.
                            </li>
                            <li><strong>Import/Export:</strong> You can import and export your complete production line,
                                allowing
                                you to share it without sharing your save game.
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary" id="toolbar"><i class="fa-solid fa-tools"></i> Toolbar</h4>
                        <p>
                            Use the buttons at the top-right to:
                        </p>
                        <ul>
                            <li><i class="fa-solid fa-save" aria-hidden="true"></i> <strong>Save</strong> (hold <kbd>Shift</kbd>
                                to remain on the current screen after saving).
                            </li>
                            <li><i class="fa-solid fa-pencil" aria-hidden="true"></i> <strong>Edit</strong> your
                                production
                                line.
                            </li>
                            <li><i class="fa-solid fa-bolt" aria-hidden="true"></i> <strong>View Power Usage</strong> to
                                see the
                                buildings and their power consumption.
                            </li>
                            <li><i class="fa-solid fa-project-diagram" aria-hidden="true"></i> <strong>Production Line
                                    Visualization</strong> to see a visual representation of your production line.
                            </li>
                            <li><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <strong>Go Back</strong> to
                                the save
                                games without saving the current production line.
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h4 class="card-title text-primary" id="shortcuts"><i class="fa-solid fa-keyboard"></i>
                            Shortcuts</h4>
                        <p>
                            Use the following shortcuts to navigate the production line:
                        </p>
                        <ul>
                            <li><kbd>Ctrl</kbd> + <kbd>S</kbd> <strong>Save</strong> the production line.</li>
                            <li><kbd>Ctrl</kbd> + <kbd>E</kbd> <strong>Edit</strong> the production line.</li>
                            <li><kbd>Ctrl</kbd> + <kbd>P</kbd> <strong>View Power Usage</strong> of the production line.
                            </li>
                            <li><kbd>Ctrl</kbd> + <kbd>H</kbd> <strong>Open Help</strong> to view this guide.</li>
                            <li><kbd>Ctrl</kbd> + <kbd>V</kbd> <strong>Show/Hide Production Line Visualization</strong>
                                (doesn't work in input fields)
                            </li>
                            <li><kbd>Ctrl</kbd> + <kbd>Q</kbd> <strong>Go Back</strong> to the save games without saving
                                the
                                current production line.
                            </li>
                        </ul>
                    </div>
                </div>
                <p class="text-muted text-center">
                    If you have any feedback or suggestions, please let me know. <a
                            href="https://forms.gle/fAd5LrGRATYwFHzr7" target="_blank">Leave feedback</a>
                </p>
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
        helpModal.find('#welcome').hide();
        const popupModal = new bootstrap.Modal(helpModal);
        popupModal.show();
    });
</script>
