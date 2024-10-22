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
                    <h3 class="text-primary">Welcome to the Power Production Guide</h3>
                    <p>
                        Welcome, Pioneer! This guide will walk you through how the Power Production Tool works and how
                        you can use it to optimize your power management. With this tool, you’ll be able to monitor how
                        much power your production lines generate and consume. Plus, it allows you to easily manage your
                        power generators to keep everything running smoothly.
                    </p>
                    <p>
                        If you have any suggestions or feedback, please let me know. I'm always looking for ways to
                        improve the production line tool.
                    </p>
                </div>
                <div class="card-body">
                    <h3 class="text-primary">Sections Overview</h3>
                    <div class="list-group mb-3">
                        <a href="#powerOverview" class="list-group-item list-group-item-action">Power Overview
                            Section</a>
                        <a href="#manegingPowerGenerators" class="list-group-item list-group-item-action">Maneging Power
                            Generators</a>
                    </div>
                </div>
                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h3 class="text-primary" id="powerOverview">Power Overview Section</h3>
                        <p>
                            This section shows you how much power you produce in total and how much you consume with
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
                                When you disable a production line it will not be calculated in the total power
                                consumption
                                and production. So you can see how much power you produce and consume without this
                                production line.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="card mb-3 border-0">
                    <div class="card-body">
                        <h3 class="text-primary" id="manegingPowerGenerators">Maneging Power Generators</h3>
                        <p>
                            To add a power generator to your production line you can click on the <kbd
                                    class="bg-primary text-dark"><i class="fa-solid fa-bolt-lightning"></i></kbd>
                            button. This will open a popup where you can manage your power generators. You can add, edit
                            or delete a power generator. You can set the clocks peed of the generator and the amount of
                            generators you have with that clocks peed.
                        </p>
                        <p class="mb-0">
                            All the changes you make will instantly be calculated and displayed in the <a
                                    href="#powerOverview">Power Production overview</a> section.
                        </p>
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
    document.getElementById('showPowerHelp').addEventListener('click', function () {
        const helpModal = $('#helpModal');
        helpModal.find('#welcome').hide();
        const popupModal = new bootstrap.Modal(helpModal);
        popupModal.show();
    });
</script>