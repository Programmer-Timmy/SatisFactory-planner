<?php
$error = null;
if ($_POST && isset($_POST['link_name'])) {
    try {
        $linkName = $_POST['link_name'];
        $linkUrl = $_POST['link_url'];
        $linkDescription = $_POST['link_description'];

        if (!filter_var($linkUrl, FILTER_VALIDATE_URL)) {
            $error = 'The URL provided is not valid.';
        } elseif (strlen($linkName) > 100) {
            $error = 'The link name is too lengthy. Please use up to 100 characters.';
        } elseif ($linkName !== strip_tags($linkName)) {
            $error = 'Security Alert: Unauthorized characters detected in the link name. Nice try, but FICSIT Security has blocked that!';
        } elseif ($linkDescription !== strip_tags($linkDescription)) {
            $error = 'Security Alert: Unauthorized characters detected in the link description. Nice try, but FICSIT Security has blocked that!';
        } elseif (HelpfulLinks::linkExists($linkUrl)) {
            $error = 'The link already exists in the database.';
        }

        if (!$error) {
            HelpfulLinks::suggestLink($linkName, $linkUrl, $linkDescription);

            header('Location: /helpfulLinks?success=true');
            exit();
        }
    } catch (Exception $e) {
        $error = 'An error occurred while suggesting the link. Please try again or contact support.';
    }
}

?>
<div class="modal fade <?= $error ? 'show' : '' ?>" id="suggestionModal"
     tabindex="-1" <?= $error ? 'style="display: block;"' : '' ?>
     aria-labelledby="suggestionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="suggestionModalLabel">Suggest a New Link</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        data-umami-event="Link Suggestion Closed"
                        data-umami-event-popup="link-suggestion"></button>
            </div>
            <div class="modal-body">
                <?php if ($error) : ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST" id="linkSuggestionForm">
                    <div class="mb-3">
                        <label for="linkName" class="form-label">Link Name</label>
                        <input type="text" class="form-control" id="linkName" name="link_name" required maxlength="100"
                               value="<?= isset($linkName) ? htmlspecialchars($linkName) : '' ?>"
                               placeholder="The name of the link">
                    </div>
                    <div class="mb-3">
                        <label for="linkUrl" class="form-label">Link URL</label>
                        <input type="url" class="form-control" id="linkUrl" name="link_url" required
                               value="<?= isset($linkUrl) ? htmlspecialchars($linkUrl) : '' ?>"
                               placeholder="The URL of the link">
                    </div>
                    <div class="mb-3">
                        <label for="linkDescription" class="form-label">Link Description</label>
                        <textarea class="form-control" required id="linkDescription" name="link_description" rows="3"
                                  placeholder="A brief description of the link"><?= isset($linkDescription) ? htmlspecialchars($linkDescription) : '' ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"
                            data-umami-event="Submit Link Suggestion"
                            data-umami-event-popup="link-suggestion"
                            data-umami-event-action="submit">Submit Suggestion</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($error) : ?>
    <script>
        $(document).ready(function () {
            const modal = new bootstrap.Modal(document.getElementById('suggestionModal'));
            modal.show();
            if (window.umami && typeof window.umami.track === 'function') {
                window.umami.track('Link Suggestion Validation Error', {
                    has_error: true
                });
            }
        });
    </script>
<?php endif; ?>

<script>
    document.getElementById('linkSuggestionForm')?.addEventListener('submit', function () {
        if (!(window.umami && typeof window.umami.track === 'function')) {
            return;
        }

        const linkUrl = document.getElementById('linkUrl')?.value ?? '';
        let host = '';
        try {
            host = new URL(linkUrl).hostname;
        } catch (e) {
            host = 'invalid';
        }

        window.umami.track('Link Suggestion Submit Attempt', {
            link_host: host,
            name_length: (document.getElementById('linkName')?.value ?? '').trim().length,
            description_length: (document.getElementById('linkDescription')?.value ?? '').trim().length
        });
    });
</script>
