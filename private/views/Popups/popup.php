<?php /** @noinspection PhpUndefinedVariableInspection */
global $site
?>
<!-- Bootstrap Popup Modal -->
<div class="modal" id="popupModalStart" tabindex="-1" aria-labelledby="popupModalStartLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="popupModalStartLabel"><?php echo $site['popupTitle']; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        data-umami-event="Site Popup Closed"
                        data-umami-event-popup-title="<?php echo htmlspecialchars($site['popupTitle']); ?>"
                        data-umami-event-close-source="header"></button>
            </div>
            <div class="modal-body">
                <!-- Display your popup message here -->
                <p><?php echo $site['popupMessage']; ?></p>
            </div>
            <div class="modal-footer">
                <?php foreach ($site['popupButtons'] as $button): ?>
                    <a href="<?php echo $button['action']; ?>"
                       class="btn btn-primary"
                       data-umami-event="Site Popup CTA Click"
                       data-umami-event-popup-title="<?php echo htmlspecialchars($site['popupTitle']); ?>"
                       data-umami-event-button-label="<?php echo htmlspecialchars($button['label']); ?>"
                       data-umami-event-button-action="<?php echo htmlspecialchars($button['action']); ?>"><?php echo $button['label']; ?></a>
                <?php endforeach; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        data-umami-event="Site Popup Closed"
                        data-umami-event-popup-title="<?php echo htmlspecialchars($site['popupTitle']); ?>"
                        data-umami-event-close-source="footer">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript to show the popup when the page loads -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const popupModalStart = new bootstrap.Modal(document.getElementById('popupModalStart'));
        popupModalStart.show();
        if (window.umami && typeof window.umami.track === 'function') {
            window.umami.track('Site Popup Viewed', {
                popup_title: <?php echo json_encode($site['popupTitle']); ?>,
                button_count: <?php echo count($site['popupButtons']); ?>
            });
        }
    });
</script>
