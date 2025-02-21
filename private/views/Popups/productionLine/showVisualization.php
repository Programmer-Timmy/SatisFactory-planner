<!--modal with svg-->
<div class="modal fade" id="showVisualization" tabindex="-1" role="dialog" aria-labelledby="showVisualizationLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="showVisualizationLabel">Visualization</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="graph" class="border-0" style="height: 100%"></div>
            </div>
            <div class="modal-footer">
                <select id="layout" class="form-select" style="width: 200px;">
                    <option value="breadthfirst">Breadthfirst</option>
                    <option value="cose">Cose</option>
                    <option value="klay" selected>klay</option>
                    <option value="fcose">fcose</option>
                </select>
                <input type="checkbox" id="export" class="form-check-input" style="width: 20px; height: 20px;">
                <label for="export" class="form-check-label">Export</label>
                <input type="checkbox" id="import" class="form-check-input" style="width: 20px; height: 20px;" checked>
                <label for="import" class="form-check-label">Import</label>
                <input type="checkbox" id="roots" class="form-check-input" style="width: 20px; height: 20px;" checked>
                <label for="roots" class="form-check-label">Roots</label>
                <button type="button" class="btn btn-primary" id="refresh">Refresh</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
