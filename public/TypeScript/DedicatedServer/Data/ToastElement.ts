export let ToastElement = $(`
  <div class="toast-container position-absolute p-3 bottom-0 end-0" id="toastPlacement" data-original-class="toast-container position-absolute p-3">
    <div class="toast" id="DedicatedServerStatus" data-bs-autohide="false" style="background-color: rgba(var(--bs-body-bg-rgb),1);">
      <div class="toast-header">
        <svg class="bd-placeholder-img rounded me-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" preserveAspectRatio="xMidYMid slice" focusable="false"><rect width="100%" height="100%" fill="#007aff"></rect></svg>
        <strong class="me-auto name">Dedicated Server status</strong>
         <small class="text-muted time">just now</small>
             <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body">
        Hello, world! This is a toast message.
      </div>
    </div>
  </div>
  
  
    <script>
        // Update the time every minute
        setInterval(() => {
        const toasts = document.querySelectorAll('.toast');
        toasts.forEach(toast => {
            const time = toast.querySelector('.time');
            time.textContent = moment().fromNow();
        });
        }, 60000);
    </script>
`);