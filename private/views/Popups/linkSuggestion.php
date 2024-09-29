<?php
if ($_POST && isset($_POST['link_name'])) {
    try {
        $linkName = $_POST['link_name'];
        $linkUrl = $_POST['link_url'];
        $linkDescription = $_POST['link_description'];

        HelpfulLinks::suggestLink($linkName, $linkUrl, $linkDescription);

        header('Location: /helpfulLinks?success=true');
    } catch (Exception $e) {
        header('Location: /helpfulLinks?success=false');
    }
    exit();
}

?>
<div class="modal fade" id="suggestionModal" tabindex="-1" aria-labelledby="suggestionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="suggestionModalLabel">Suggest a New Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="linkName" class="form-label">Link Name</label>
                        <input type="text" class="form-control" id="linkName" name="link_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="linkUrl" class="form-label">Link URL</label>
                        <input type="url" class="form-control" id="linkUrl" name="link_url" required>
                    </div>
                    <div class="mb-3">
                        <label for="linkDescription" class="form-label">Link Description</label>
                        <textarea class="form-control" required id="linkDescription" name="link_description" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Suggestion</button>
                </form>
            </div>
        </div>
    </div>
</div>