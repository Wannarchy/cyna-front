</div><!-- /content -->
</div><!-- /main -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  var sidebar = document.getElementById('adminSidebar');
  var backdrop = document.getElementById('sbBackdrop');
  var toggle = document.getElementById('sbToggle');
  if (!sidebar || !backdrop || !toggle) return;

  function closeSidebar() {
    sidebar.classList.remove('open');
    backdrop.classList.remove('show');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  function openSidebar() {
    sidebar.classList.add('open');
    backdrop.classList.add('show');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  toggle.addEventListener('click', function () {
    if (sidebar.classList.contains('open')) closeSidebar();
    else openSidebar();
  });

  backdrop.addEventListener('click', closeSidebar);
  sidebar.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', closeSidebar);
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth > 991) closeSidebar();
  });
})();
</script>
<?php
require_once __DIR__ . '/../includes/form_validation.php';
form_validation_include('fr');
?>
</body>
</html>