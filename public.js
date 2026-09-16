(function () {
  'use strict';

  var doc = document;
  var root = doc.documentElement;
  var theme = localStorage.getItem('selectedTheme') === 'dark' ? 'dark' : 'light';
  root.setAttribute('data-bs-theme', theme);

  function byId(id) { return doc.getElementById(id); }
  function escapeHtml(value) {
    return String(value).replace(/[&<>\"']/g, function (char) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
    });
  }
  function setThemeIcon() {
    var icon = byId('bd-theme-icon');
    if (icon) icon.className = root.getAttribute('data-bs-theme') === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
  }

  doc.addEventListener('DOMContentLoaded', function () {
    setThemeIcon();
    var themeButton = byId('bd-theme');
    if (themeButton) themeButton.addEventListener('click', function (event) {
      event.preventDefault();
      var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
      localStorage.setItem('selectedTheme', next);
      root.setAttribute('data-bs-theme', next);
      setThemeIcon();
    });

    var menuToggle = byId('mobileMenuToggle');
    var nav = byId('publicNavbar');
    if (menuToggle && nav) {
      menuToggle.addEventListener('click', function () {
        var open = nav.classList.toggle('show');
        menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      nav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
          nav.classList.remove('show');
          menuToggle.setAttribute('aria-expanded', 'false');
        });
      });
    }

    var trigger = byId('applicationTrigger');
    var applicationMenu = byId('applicationMenu');
    var select = byId('app_id');
    var duration = byId('duration');
    var submit = byId('btn_submit');
    var form = byId('licenseForm');
    var result = byId('validationResult');

    function closeApplicationMenu() {
      if (!trigger || !applicationMenu) return;
      applicationMenu.classList.remove('open');
      trigger.classList.remove('open');
      trigger.setAttribute('aria-expanded', 'false');
    }
    function showMessage(type, icon, message) {
      if (result) result.innerHTML = '<div class="alert alert-' + type + '"><i class="bi ' + icon + ' me-1"></i>' + escapeHtml(message) + '</div>';
    }
    function resetButton() {
      if (!submit) return;
      submit.disabled = false;
      submit.setAttribute('aria-disabled', 'false');
      submit.innerHTML = '<i class="bi bi-key-fill"></i> Generate';
    }

    if (trigger && applicationMenu) {
      trigger.addEventListener('click', function () {
        var open = !applicationMenu.classList.contains('open');
        applicationMenu.classList.toggle('open', open);
        trigger.classList.toggle('open', open);
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      doc.addEventListener('click', function (event) {
        if (!event.target.closest('.application-field')) closeApplicationMenu();
      });
      doc.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeApplicationMenu();
      });
    }

    doc.querySelectorAll('.application-option').forEach(function (optionButton) {
      optionButton.addEventListener('click', function () {
        if (optionButton.disabled) return;
        var id = optionButton.getAttribute('data-app-id');
        var selected = select ? select.querySelector('option[value="' + id + '"]') : null;
        if (!selected) return;
        var appName = optionButton.querySelector('.menu-name').textContent.trim();
        var appGame = optionButton.querySelector('.menu-meta').textContent.trim();
        var appLogo = optionButton.querySelector('img');
        var hours = selected.getAttribute('data-duration') || '5';
        select.value = id;
        duration.disabled = false;
        duration.innerHTML = '<option value="' + escapeHtml(hours) + '" selected>' + escapeHtml(hours) + ' Hours</option>';
        submit.disabled = false;
        submit.setAttribute('aria-disabled', 'false');
        byId('selectionHint').textContent = appName;
        byId('triggerTitle').textContent = appName;
        byId('triggerSubtitle').textContent = appGame;
        byId('triggerLogo').innerHTML = appLogo ? '<img src="' + escapeHtml(appLogo.getAttribute('src')) + '" alt="">' : '<i class="bi bi-box-seam"></i>';
        doc.querySelectorAll('.application-option').forEach(function (item) { item.classList.remove('selected'); item.setAttribute('aria-selected', 'false'); });
        optionButton.classList.add('selected');
        optionButton.setAttribute('aria-selected', 'true');
        if (result) result.innerHTML = '';
        closeApplicationMenu();
      });
    });

    if (form) form.addEventListener('submit', function (event) {
      event.preventDefault();
      var selected = select && select.options[select.selectedIndex];
      var appId = selected && selected.value;
      if (!appId) {
        submit.disabled = true;
        duration.disabled = true;
        duration.innerHTML = '<option value="" selected>Select duration</option>';
        showMessage('warning', 'bi-exclamation-triangle', 'Please select an application.');
        return;
      }
      if (selected.getAttribute('data-maintenance') === '1') {
        showMessage('warning', 'bi-exclamation-triangle', 'This API is under maintenance. Choose another API.');
        return;
      }
      submit.disabled = true;
      submit.setAttribute('aria-disabled', 'true');
      submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Generating...';
      if (result) result.innerHTML = '';
      fetch('api.php?action=generate', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}, body: 'app_id=' + encodeURIComponent(appId) })
        .then(function (response) { return response.json(); })
        .then(function (response) {
          if (response.success) { window.location.href = 'redirect.php?handoff=' + encodeURIComponent(response.handoff); return; }
          resetButton();
          showMessage('danger', 'bi-exclamation-triangle', response.error || 'Failed to generate license');
        })
        .catch(function () { resetButton(); showMessage('danger', 'bi-wifi-off', 'An error occurred. Please try again.'); });
    });
  });
})();
