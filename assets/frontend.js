(function () {
  'use strict';

  function initFilter(input) {
    var listId = input.getAttribute('data-rgaa-lsa-filter');
    var statusId = input.getAttribute('data-rgaa-lsa-status');
    var list = listId ? document.getElementById(listId) : null;
    var status = statusId ? document.getElementById(statusId) : null;
    if (!list) return;

    var items = Array.prototype.slice.call(list.querySelectorAll('.rgaa-lsa-turn'));

    function applyFilter() {
      var q = (input.value || '').trim().toLowerCase();
      var visible = 0;
      for (var i = 0; i < items.length; i++) {
        var match = !q || items[i].textContent.toLowerCase().indexOf(q) !== -1;
        items[i].hidden = !match;
        if (match) visible++;
      }
      if (status) {
        if (!q) {
          status.textContent = '';
        } else if (visible === 0) {
          status.textContent = 'Aucun passage trouvé.';
        } else if (visible === 1) {
          status.textContent = '1 passage trouvé.';
        } else {
          status.textContent = visible + ' passages trouvés.';
        }
      }
    }

    input.addEventListener('input', applyFilter);
  }

  function boot() {
    var inputs = document.querySelectorAll('[data-rgaa-lsa-filter]');
    for (var i = 0; i < inputs.length; i++) {
      initFilter(inputs[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
