<?php
global $require;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-5">
  <div class="container-fluid">
    <a class="navbar-brand" href="/home">Satisfactroy Planner</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php if ($require === '/home') echo 'active'; ?>" aria-current="page" href="/home">Home</a>
        </li>
          <li class="nav-item">
          <a class="nav-link <?php if ($require === '/account') echo 'active'; ?>" aria-current="page" href="/account">Account</a>
        </li>
      </ul>
      <div class="d-flex">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="btn btn-success" aria-current="page" target="_blank" href="https://forms.gle/fAd5LrGRATYwFHzr7">Leave Feedback</a>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>