<?php
global $site;
global $changelog;
?>
<div class="mt-5"></div>
<footer class="bg-dark text-white">
    <div class="container py-4">
        <div class="row">
            <div class="col">
                <p class="text-center">&copy; <?php echo date('Y') . ' ' . $site['siteName']; ?></p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS and Popper.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"></script>

<script>
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl,
        {
            trigger: 'hover'
        }
    ))

    // Theme toggle based on cookie
    const theme = document.cookie.replace(/(?:(?:^|.*;\s*)theme\s*=\s*([^;]*).*$)|^.*$/, "$1");
    if (theme === 'dark') {
        document.documentElement.setAttribute('data-bs-theme', 'dark');
    } else {
        document.documentElement.setAttribute('data-bs-theme', 'light');
    }
</script>
</body>
