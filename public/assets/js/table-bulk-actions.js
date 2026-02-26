(function () {
    window.BSS = window.BSS || {};

    window.BSS.initBulkTableActions = function (options) {
        var cfg = Object.assign({
            masterSelector: '#allRows',
            rowSelector: '.rowCheck',
            submitSelector: '',
            emptyMessage: 'Seleziona almeno un elemento.',
            confirmMessage: '',
            selectedClass: 'is-selected'
        }, options || {});

        var master = document.querySelector(cfg.masterSelector);
        var rows = Array.prototype.slice.call(document.querySelectorAll(cfg.rowSelector));
        var submit = cfg.submitSelector ? document.querySelector(cfg.submitSelector) : null;

        if (master) {
            master.addEventListener('change', function () {
                rows.forEach(function (r) {
                    r.checked = !!master.checked;
                    var tr = r.closest('tr');
                    if (tr) tr.classList.toggle(cfg.selectedClass, r.checked);
                });
            });
        }

        rows.forEach(function (r) {
            r.addEventListener('change', function () {
                var tr = r.closest('tr');
                if (tr) tr.classList.toggle(cfg.selectedClass, r.checked);
            });
        });

        if (submit) {
            submit.addEventListener('click', function (e) {
                var selected = document.querySelectorAll(cfg.rowSelector + ':checked').length;
                if (selected === 0) {
                    e.preventDefault();
                    alert(cfg.emptyMessage);
                    return;
                }
                if (cfg.confirmMessage && !confirm(cfg.confirmMessage)) {
                    e.preventDefault();
                }
            });
        }
    };
})();
