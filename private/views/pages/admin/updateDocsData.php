<?php
$defaultItemsNativeClasses = [
    'FGItemDescriptor',
    'FGItemDescriptorBiomass',
    'FGItemDescriptorNuclearFuel',
    'FGResourceDescriptor',
    'FGAmmoTypeSpreadshot',
    'FGAmmoTypeProjectile',
    'FGAmmoTypeInstantHit',
    'FGPowerShardDescriptor',
    'FGItemDescriptorPowerBoosterFuel',
];

$defaultBuildingNativeClasses = [
    'FGBuildableResourceExtractor',
    'FGBuildableManufacturer',
    'FGBuildableManufacturerVariablePower',
    'FGBuildableGeneratorNuclear',
    'FGBuildableGeneratorFuel',
    'FGBuildableWaterPump',
    'FGBuildablePortal',
    'FGBuildablePortalSatellite',
    'FGBuildablePowerBooster',
    'FGBuildablePipelinePump',
];

function renderNativeClassOptions(array $classes): void {
    foreach ($classes as $class) {
        $safeClass = htmlspecialchars($class, ENT_QUOTES);
        echo "<option value='{$safeClass}' selected>{$safeClass}</option>";
    }
}
?>

<div class="container">
    <div class="row align-items-center mb-3">
        <div class="col-lg-4"></div>
        <div class="col-lg-4">
            <h1 class="text-center">Update Docs Data</h1>
        </div>
        <div class="col-lg-4 text-center text-lg-end">
            <a href="/admin" class="btn btn-primary">Return to admin page</a>
        </div>
    </div>

    <div class="container">
        <form id="updateDocsDataForm" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label for="docsFile" class="form-label">Docs JSON file</label>
                <input id="docsFile" class="form-control" type="file" name="jsonFile" accept=".json,.jsont,application/json">
            </div>

            <div class="form-group mb-3">
                <label for="itemsNativeClasses" class="form-label">Items Native Classes</label>
                <select id="itemsNativeClasses" class="form-control" name="ItemsNativeClasses[]" multiple>
                    <?php renderNativeClassOptions($defaultItemsNativeClasses); ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="buildingNativeClasses" class="form-label">Building Native Classes</label>
                <select id="buildingNativeClasses" class="form-control" name="BuildingNativeClasses[]" multiple>
                    <?php renderNativeClassOptions($defaultBuildingNativeClasses); ?>
                </select>
            </div>
        </form>

        <div class="d-flex flex-column flex-md-row gap-2 my-3">
            <button id="previewDocsData" class="btn btn-secondary flex-fill">
                Preview Uploaded Data
            </button>
            <button id="updateDocsData" class="btn btn-primary flex-fill" disabled>
                Confirm Update
            </button>
        </div>

        <div id="updateDocsDataStatus"></div>
        <div id="updateDocsDataPreview"></div>
        <div id="updateDocsDataResponse"></div>
    </div>
</div>

<script>
    const defaultItemsNativeClasses = <?= json_encode($defaultItemsNativeClasses) ?>;
    const defaultBuildingNativeClasses = <?= json_encode($defaultBuildingNativeClasses) ?>;
    let hasValidPreview = false;

    document.addEventListener('DOMContentLoaded', function () {
        $('#docsFile').on('change', async function () {
            hasValidPreview = false;
            setConfirmEnabled(false);
            clearResponse();
            await populateNativeClassSelectsFromUpload(this.files[0]);
        });

        $('#updateDocsDataForm select').on('change', function () {
            hasValidPreview = false;
            setConfirmEnabled(false);
            $('#updateDocsDataResponse').empty();
        });

        $('#previewDocsData').on('click', async function () {
            await sendDocsDataRequest('preview');
        });

        $('#updateDocsData').on('click', async function () {
            if (!hasValidPreview || !window.confirm('Apply the previewed Docs data changes?')) {
                return;
            }

            await sendDocsDataRequest('submit');
        });
    });

    async function populateNativeClassSelectsFromUpload(file) {
        if (!file) {
            resetSelectOptions();
            return;
        }

        try {
            const json = JSON.parse(await readJsonFileText(file));
            const itemClasses = [];
            const buildingClasses = [];

            if (!Array.isArray(json)) {
                return;
            }

            json.forEach(function (entry) {
                const nativeClass = extractNativeClass(entry.NativeClass || '');
                if (!nativeClass) {
                    return;
                }

                if (nativeClass.includes('Build')) {
                    buildingClasses.push(nativeClass);
                } else if (nativeClass !== 'FGRecipe') {
                    itemClasses.push(nativeClass);
                }
            });

            replaceSelectOptions($('#itemsNativeClasses'), uniqueValues(itemClasses), defaultItemsNativeClasses);
            replaceSelectOptions($('#buildingNativeClasses'), uniqueValues(buildingClasses), defaultBuildingNativeClasses);
        } catch (error) {
            showStatus('warning', 'The class lists could not be read in the browser. You can still preview the upload; the server will validate the JSON.');
        }
    }

    function extractNativeClass(nativeClass) {
        const parts = nativeClass.split('.');
        return parts.length >= 3 ? parts[2].replaceAll("'", '') : '';
    }

    function replaceSelectOptions(select, classes, defaults) {
        select.empty();

        const allClasses = uniqueValues(defaults.concat(classes));
        allClasses.forEach(function (className) {
            const option = $('<option></option>').val(className).text(className);
            option.prop('selected', defaults.includes(className));
            select.append(option);
        });
    }

    function resetSelectOptions() {
        replaceSelectOptions($('#itemsNativeClasses'), [], defaultItemsNativeClasses);
        replaceSelectOptions($('#buildingNativeClasses'), [], defaultBuildingNativeClasses);
    }

    function uniqueValues(values) {
        return [...new Set(values.filter(Boolean))];
    }

    async function sendDocsDataRequest(action) {
        const file = $('#docsFile')[0].files[0];

        if (!file) {
            showStatus('danger', 'Choose a Docs JSON file before continuing.');
            return;
        }

        setLoading(action, true);
        clearResponse();

        try {
            const preparedJson = await prepareMinifiedJson(file);
            let response;

            try {
                response = await uploadPreparedJson(action, preparedJson, 'plain');
            } catch (error) {
                if (!isUploadSizeError(error)) {
                    throw error;
                }

                showStatus('warning', 'The minified JSON is still too large. Trying gzip upload.');
                response = await uploadPreparedJson(action, preparedJson, 'gzip');
            }

            handleDocsDataResponse(response);
        } catch (error) {
            if (isUploadSizeError(error)) {
                try {
                    showStatus('warning', 'The compressed upload is still too large. Uploading in chunks.');
                    const preparedJson = await prepareMinifiedJson(file);
                    const response = await uploadPreparedJson(action, preparedJson, 'chunk');
                    handleDocsDataResponse(response);
                    return;
                } catch (chunkError) {
                    handleDocsDataError(chunkError);
                    return;
                }
            }

            handleDocsDataError(error);
        } finally {
            setLoading(action, false);
        }
    }

    async function prepareMinifiedJson(file) {
        const text = await readJsonFileText(file);

        try {
            const minifiedJson = JSON.stringify(JSON.parse(text));

            return {
                filename: file.name,
                text: minifiedJson,
                file: new File([minifiedJson], file.name, {type: 'application/json'})
            };
        } catch (error) {
            const position = extractJsonParsePosition(error.message || '', text);
            throw createUploadError(
                buildBrowserJsonErrorMessage(position),
                null,
                {
                    details: error.message || 'Browser JSON parser failed.',
                    line: position.line,
                    column: position.column,
                    excerpt: position.excerpt
                }
            );
        }
    }

    async function readJsonFileText(file) {
        const buffer = await file.arrayBuffer();
        const bytes = new Uint8Array(buffer);

        if (bytes.length >= 3 && bytes[0] === 0xEF && bytes[1] === 0xBB && bytes[2] === 0xBF) {
            return new TextDecoder('utf-8', {fatal: true}).decode(bytes.slice(3));
        }

        if (looksLikeUtf16(bytes)) {
            throw createUploadError(
                'The selected Docs file appears to use UTF-16 encoding. Satisfactory Docs.json is expected to be UTF-8 without BOM. Export or convert the file to UTF-8 and try again.',
                null,
                {details: 'Unexpected file encoding.'}
            );
        }

        try {
            return new TextDecoder('utf-8', {fatal: true}).decode(bytes);
        } catch (error) {
            throw createUploadError(
                'The selected Docs file is not valid UTF-8 text. Satisfactory Docs.json is expected to be UTF-8 without BOM.',
                null,
                {details: error.message || 'UTF-8 decoding failed.'}
            );
        }
    }

    function looksLikeUtf16(bytes) {
        let evenNulls = 0;
        let oddNulls = 0;
        const sampleLength = Math.min(bytes.length, 200);

        for (let index = 0; index < sampleLength; index++) {
            if (bytes[index] !== 0) {
                continue;
            }

            if (index % 2 === 0) {
                evenNulls++;
            } else {
                oddNulls++;
            }
        }

        if (oddNulls > 5 && oddNulls > evenNulls * 2) {
            return true;
        }

        if (evenNulls > 5 && evenNulls > oddNulls * 2) {
            return true;
        }

        return false;
    }

    function extractJsonParsePosition(message, text) {
        const positionMatch = message.match(/position\s+(\d+)/i);
        const lineColumnMatch = message.match(/line\s+(\d+)\s+column\s+(\d+)/i);

        if (lineColumnMatch) {
            const line = Number(lineColumnMatch[1]);
            const column = Number(lineColumnMatch[2]);

            return {
                line,
                column,
                excerpt: getJsonLineExcerpt(text, line)
            };
        }

        if (positionMatch) {
            return getLineColumnFromOffset(text, Number(positionMatch[1]));
        }

        return {
            line: null,
            column: null,
            excerpt: ''
        };
    }

    function getLineColumnFromOffset(text, offset) {
        const before = text.slice(0, offset);
        const lines = before.split(/\r\n|\r|\n/);
        const line = lines.length;
        const column = lines[lines.length - 1].length + 1;

        return {
            line,
            column,
            excerpt: getJsonLineExcerpt(text, line)
        };
    }

    function getJsonLineExcerpt(text, line) {
        const lines = text.split(/\r\n|\r|\n/);
        const lineText = lines[line - 1] || '';

        if (lineText.length <= 160) {
            return lineText.trim();
        }

        return lineText.slice(0, 160).trim() + '...';
    }

    function buildBrowserJsonErrorMessage(position) {
        let message = 'The selected Docs file is not valid JSON.';

        if (position.line && position.column) {
            message += ' Check line ' + position.line + ', column ' + position.column + '.';
        }

        message += ' Common causes are a missing comma, trailing comma, unclosed quote, or invalid character.';

        return message;
    }

    async function uploadPreparedJson(action, preparedJson, mode) {
        if (mode === 'gzip') {
            if (typeof CompressionStream === 'undefined') {
                throw createUploadError('Gzip compression is not supported by this browser.', null, {retryChunk: true});
            }

            const gzippedBlob = await gzipText(preparedJson.text);
            const formData = createDocsDataFormData(action);
            formData.set('jsonFile', gzippedBlob, preparedJson.filename + '.gz');
            formData.append('compressed', 'gzip');

            return postDocsData(formData);
        }

        if (mode === 'chunk') {
            return uploadJsonInChunks(action, preparedJson);
        }

        const formData = createDocsDataFormData(action);
        formData.set('jsonFile', preparedJson.file, preparedJson.filename);

        return postDocsData(formData);
    }

    async function gzipText(text) {
        const stream = new Blob([text])
            .stream()
            .pipeThrough(new CompressionStream('gzip'));

        return await new Response(stream).blob();
    }

    async function uploadJsonInChunks(action, preparedJson) {
        const chunkSize = 1024 * 1024;
        const uploadId = createUploadId();
        let uploadBlob = new Blob([preparedJson.text], {type: 'application/json'});
        let compressed = '';
        let filename = preparedJson.filename;

        if (typeof CompressionStream !== 'undefined') {
            uploadBlob = await gzipText(preparedJson.text);
            compressed = 'gzip';
            filename += '.gz';
        }

        const totalChunks = Math.ceil(uploadBlob.size / chunkSize);
        let response = null;

        for (let index = 0; index < totalChunks; index++) {
            const chunk = uploadBlob.slice(index * chunkSize, Math.min(uploadBlob.size, (index + 1) * chunkSize));
            const formData = createDocsDataFormData(action);
            formData.set('jsonFile', chunk, filename + '.part' + index);
            formData.append('chunkUpload', '1');
            formData.append('uploadId', uploadId);
            formData.append('index', String(index));
            formData.append('totalChunks', String(totalChunks));
            formData.append('filename', filename);

            if (compressed) {
                formData.append('compressed', compressed);
            }

            showStatus('warning', 'Uploading chunk ' + (index + 1) + ' of ' + totalChunks + '.');
            response = await postDocsData(formData);
        }

        return response;
    }

    function createDocsDataFormData(action) {
        const formData = new FormData();
        formData.append('action', action);

        appendMultiSelectValues(formData, 'ItemsNativeClasses[]', $('#itemsNativeClasses').val() || []);
        appendMultiSelectValues(formData, 'BuildingNativeClasses[]', $('#buildingNativeClasses').val() || []);

        return formData;
    }

    function appendMultiSelectValues(formData, name, values) {
        values.forEach(function (value) {
            formData.append(name, value);
        });
    }

    async function postDocsData(formData) {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest'
        };
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await fetch('/updateDocsData', {
            method: 'POST',
            body: formData,
            headers
        });

        const responseText = await response.text();
        let data;

        if (!responseText.trim()) {
            throw createUploadError(
                'The server returned an empty response. Check the PHP error log for the Docs update request.',
                response.status
            );
        }

        try {
            data = JSON.parse(responseText);
        } catch (error) {
            throw createUploadError(
                'The server did not return JSON. Response started with: ' + responseText.slice(0, 250),
                response.status
            );
        }

        if (!response.ok) {
            throw createUploadError(data.error || 'The Docs data request failed.', response.status, data);
        }

        return data;
    }

    function createUploadId() {
        if (window.crypto && window.crypto.randomUUID) {
            return window.crypto.randomUUID();
        }

        return String(Date.now()) + '-' + Math.random().toString(16).slice(2);
    }

    function createUploadError(message, status, data = {}) {
        const error = new Error(message);
        error.status = status;
        error.data = data;
        return error;
    }

    function isUploadSizeError(error) {
        const data = error && error.data ? error.data : {};
        const code = Number(data.code);
        const message = String(error && error.message ? error.message : '').toLowerCase();

        return code === 1
            || code === 2
            || data.retryChunk === true
            || message.includes('larger than the server upload limit')
            || message.includes('larger than the form limit')
            || message.includes('no data sent');
    }

    function handleDocsDataResponse(response) {
        if (response.mode === 'chunk' && response.complete === false) {
            return;
        }

        if (response.mode === 'preview') {
            hasValidPreview = true;
            $('#updateDocsDataPreview').html(response.html || '');
            $('#updateDocsDataResponse').empty();
            setConfirmEnabled(true);
            return;
        }

        hasValidPreview = false;
        $('#updateDocsDataResponse').html(response.html || '');
        $('#updateDocsDataPreview').empty();
        setConfirmEnabled(false);
        showStatus('success', 'Docs data update completed.');
    }

    function handleDocsDataError(error) {
        const message = formatDocsDataError(error);

        hasValidPreview = false;
        setConfirmEnabled(false);
        showStatus('danger', message);
    }

    function formatDocsDataError(error) {
        const data = error && error.data ? error.data : {};

        if (data.message) {
            let message = data.message;

            if (data.excerpt) {
                message += ' Near: ' + data.excerpt;
            }

            return message;
        }

        if (error && error.message === 'Invalid JSON file') {
            return 'The selected Docs file is not valid JSON. Check for missing commas, trailing commas, unclosed strings, or invalid characters.';
        }

        const details = data.details ? ' Details: ' + data.details : '';
        return error && error.message ? error.message + details : 'The Docs data request failed.';
    }

    function setLoading(action, isLoading) {
        const previewButton = $('#previewDocsData');
        const submitButton = $('#updateDocsData');
        const loadingText = action === 'preview' ? 'Previewing Docs Data' : 'Updating Docs Data';

        previewButton.prop('disabled', isLoading);
        submitButton.prop('disabled', isLoading || !hasValidPreview);

        if (isLoading) {
            const spinner = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ';
            (action === 'preview' ? previewButton : submitButton).html(spinner + loadingText);
            return;
        }

        previewButton.text('Preview Uploaded Data');
        submitButton.text('Confirm Update');
    }

    function setConfirmEnabled(enabled) {
        $('#updateDocsData').prop('disabled', !enabled);
    }

    function clearResponse() {
        $('#updateDocsDataStatus').empty();
        $('#updateDocsDataPreview').empty();
        $('#updateDocsDataResponse').empty();
    }

    function showStatus(type, message) {
        $('#updateDocsDataStatus').html(
            '<div class="alert alert-' + type + '" role="alert">' + escapeHtml(message) + '</div>'
        );
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
</script>
