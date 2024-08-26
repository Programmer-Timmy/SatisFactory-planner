<?php /** @noinspection PhpUndefinedVariableInspection */
global $site
?>
<!-- Bootstrap Popup Modal -->
<div class="modal" id="popupModalStart" tabindex="-1" aria-labelledby="popupModalStartLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalStartLabel"><?php echo $site['popupTitle']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Display your popup message here -->
                <p><?php echo $site['popupMessage']; ?></p>
            </div>
            <div class="modal-footer">
                <?php foreach ($site['popupButtons'] as $button): ?>
                    <a href="<?php echo $button['action']; ?>"
                       class="btn btn-primary"><?php echo $button['label']; ?></a>
                <?php endforeach; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to show the popup when the page loads -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const popupModalStart = new bootstrap.Modal(document.getElementById('popupModalStart'));
        popupModalStart.show();
    });
</script>
