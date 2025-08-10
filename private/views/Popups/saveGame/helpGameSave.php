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
                <h5 class="modal-title" id="helpModalLabel">Save Game Guide</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="welcome">
                    <h3 class="text-primary">Welcome to the Save Game Guide</h3>
                    <p>
                        Welcome, Pioneer! This guide will walk you through how the Save Game Overview works. On this page, you'll find a detailed view of all your production lines, along with the power consumption and production of your factory. It provides a quick snapshot of your factory's performance showing how much power you're generating and consuming, what you're producing, and the output of each production line, along with their individual power usage.
                    </p>
                    <p>
                        If you have any suggestions or feedback, please let me know. I'm always looking for ways to
                        improve.
                    </p>
                </div>
                <div class="card-body">
                    <h3 class="text-primary">Sections Overview</h3>
                    <div class="list-group mb-3">
                        <a href="#productionLines" class="list-group-item list-group-item-action">Production Lines</a>
                        <a href="#powerOverview" class="list-group-item list-group-item-action">Power Overview
                            Section</a>
                        <a href="#manegingPowerGenerators" class="list-group-item list-group-item-action">Maneging Power
                            Generators</a>
                        <a href="#output" class="list-group-item list-group-item-action">Output</a>
                        <a href="#buttonOverview" class="list-group-item list-group-item-action">Button Overview</a>
                    </div>
                </div>
                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h3 class="text-primary" id="productionLines">Production Lines</h3>
                        <p>
                            This section shows you all the production lines you have in your factory. You can find this on the left side of the screen. You can see the name of the production line, the amount of power it consumes, when it was last updated and if it is enabled or disabled.
                        </p>
                        <h6 class="text-primary">Add a production line</h6>
                        <p>
                            You can add a production line
                            by clicking on the <kbd class="bg-primary text-dark"><i class="fa-solid fa-plus"></i></kbd> button.
                            This will open a popup where you can add the production line.
                            You can set the name of the production line.
                            When you add a production line you can manage your production line within its own page.
                            You can add, edit or delete a production line.
                            See the guide on that page for more info.
                        <h6 class="text-primary">Enable or disable a production line</h6>
                        <p>
                            You can enable or disable a production line by clicking on the <kbd
                                    class="bg-primary text-dark"><i class="fa-solid fa-toggle-on"></i></kbd> button.
                            This will disable the production line, and it will not be calculated in the total power
                            consumption and production in the <a href="#powerOverview">Power Overview</a> and <a href="#output">Output</a> section.
                        </p>
                        <h6 class="text-primary">Edit a production line</h6>
                        <p>
                            You can edit a production line by clicking on the <kbd class="bg-primary text-dark"><i class="fa-solid fa-gears"></i></kbd> button. This will open the production line, where you can edit the production line. You can change the settings of the production line in this page. See more info in the guide on that page.
                        </p>
                        <h6 class="text-primary">Delete a production line</h6>
                        <p>
                            You can delete a production line by clicking on the <kbd class="bg-danger text-dark"><i class="fa-solid fa-x"></i></kbd> button. This will delete the production line and all the data of this production line. This action can not be undone.
                        </p>
                        <h6 class="text-primary">Change the view</h6>
                        <p>
                            You can change the way you see the production lines by clicking on the <kbd class="bg-secondary text-dark"><i class="fa-solid fa-table"></i></kbd> or <kbd class="bg-secondary text-dark"><i class="fa-regular fa-square"></i></kbd> button. This will change the view of the production lines. You can see the production lines in a table or in a grid.
                        </p>
                    </div>
                </div>
                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h3 class="text-primary" id="powerOverview">Power Overview Section</h3>
                        <p>
                            This section shows you how much power you produce based and how much you consume with
                            your
                            production lines. You can find this in the top right of the screen.
                        </p>
                        <p>You can see this in the form of a gauge. In the bottom middle you see the
                            total power consumption and production. Next to this you see the total power production. The
                            gauge will show you in a visual way if you have enough power for your factory. If the gauge
                            is
                            in the yellow or red zone FICSIT advises you to build more power generators.
                        </p>
                        <div class="alert alert-warning mb-0" role="alert">
                            <h4 class="alert-heading">Note</h4>
                            <p class="mb-0">
                                When you disable a production line it will not be calculated in the total <a href="#powerOverview">power
                                    consumption</a>
                                and <a href="#output"> output</a>.
                                So you can see
                                how much power you produce and consume without this
                                production line.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h3 class="text-primary" id="manegingPowerGenerators">Managing Power Generators</h3>
                        <p>
                            To manage your power generators, you can click on the <kbd
                                    class="bg-primary text-dark"><i class="fa-solid fa-bolt-lightning"></i></kbd>
                            button.
                            This will open a popup where you can add, edit
                            or delete a power generator.
                            You can set the clock speed of the generator and the number of
                            generators you have with that clock speed.
                        </p>
                        <p class="mb-0">
                            All the changes you make will instantly be calculated and displayed in the <a
                                    href="#powerOverview">Power Production overview</a> section.
                        </p>
                    </div>
                </div>
                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h3 class="text-primary" id="output">Output</h3>
                        <p>
                            This section shows you the output of your production lines. You can see the number of items
                            you produce per minute for each production line. You can find this on the right side of the
                            screen under the <a href="#powerOverview">Power Overview</a> section. All the inputs will be
                            subtracted from the output. So you can see the real output of your production line.
                        </p>
                        <div class="alert alert-warning mb-0" role="alert">
                            <h4 class="alert-heading">Note</h4>
                            <p>
                                When you disable a production line, it will not be calculated in the output. So you can see the output of your production lines without this production line.
                            </p>
                        </div>

                    </div>
                </div>
                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h3 class="text-primary" id="buttonOverview">Button Overview</h3>
                        <p>Below is a quick guide to the various buttons used in the interface. These buttons help you
                            manage production lines, generators, and more.</p>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <kbd class="bg-info text-black"><i class="fa-regular fa-question-circle"></i></kbd> Open
                                the help guide
                            </li>
                            <li class="mb-2">
                                <kbd class="bg-primary text-dark"><i class="fa-solid fa-bolt"></i></kbd> Open the power
                                generator manager
                            </li>
                            <li class="mb-2">
                                <kbd class="bg-primary text-dark"><i class="fa-solid fa-plus"></i></kbd> Add a
                                production line
                            </li>
                            <li class="mb-2">
                                <kbd class="bg-secondary text-dark"><i class="fa-solid fa-table"></i></kbd> or
                                <kbd class="bg-secondary text-dark"><i class="fa-regular fa-square"></i></kbd> Change
                                the view of the production lines
                            </li>
                            <li class="mb-2">
                                <kbd class="bg-primary text-dark"><i class="fa-solid fa-gears"></i></kbd> Open a
                                production line
                            </li>
                            <li>
                                <kbd class="bg-danger text-dark"><i class="fa-solid fa-x"></i></kbd> Delete a production
                                line
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
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>

            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('showSaveGameHelp').addEventListener('click', function () {
        const helpModal = $('#helpModal');
        helpModal.find('#welcome').hide();
        const popupModal = new bootstrap.Modal(helpModal);
        popupModal.show();
    });
</script>