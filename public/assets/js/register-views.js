/* =====================================================================
   Loan Register - Table / Cards views, pinned columns and an
   always-visible horizontal scrollbar.

   Loaded AFTER register.js, which keeps owning the DataTable, filters,
   selection, bulk actions, edit and delete. This file only layers views
   on top of it:
     - cards are rendered from the very same DataTables page, so filters,
       sorting, paging and the selection set are shared by both views
     - card checkboxes / Edit / Delete are proxied to the table's own
       controls, so every existing handler keeps working untouched
   ===================================================================== */
(function () {
    'use strict';

    var VIEW_KEY = 'loanRegisterView';

    // value = "<DataTables column index>:<direction>" (indexes follow the
    // columns[] order in register.js: 2 name, 3 surname, 7 amount,
    // 14 action date, 15 date loaded)
    var SORT_OPTIONS = [
        ['15:desc', 'Newest loaded'],
        ['15:asc',  'Oldest loaded'],
        ['14:asc',  'Action date: soonest'],
        ['14:desc', 'Action date: latest'],
        ['2:asc',   'Name A\u2013Z'],
        ['3:asc',   'Surname A\u2013Z'],
        ['7:desc',  'Amount: high to low'],
        ['7:asc',   'Amount: low to high']
    ];

    /* ---------------------------------------------------------------
       Pure helpers (also exposed on window.__lrViews for debugging)
       --------------------------------------------------------------- */
    function slug(s) {
        return String(s == null ? '' : s).toLowerCase().trim()
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }

    function initials(r) {
        var a = Array.from(String(r.name || '').trim())[0] || '';
        var b = Array.from(String(r.surname || '').trim())[0] || '';
        return (a + b).toUpperCase() || '?';
    }

    // stable avatar colour per client (same ID number => same colour)
    function avatarTone(r) {
        var key = String(r.id_number || r.id || ''), h = 0;
        for (var i = 0; i < key.length; i++) h = (h * 31 + key.charCodeAt(i)) >>> 0;
        return h % 6;
    }

    function detail(label, valueHtml, empty) {
        return '<div><dt>' + label + '</dt><dd' + (empty ? ' class="is-empty"' : '') + '>' + valueHtml + '</dd></div>';
    }
    function textDetail(label, v) {
        return v ? detail(label, escapeHtml(v), false) : detail(label, '&mdash;', true);
    }

    function cardHtml(r) {
        var full   = ((r.name || '') + ' ' + (r.surname || '')).trim();
        var tel    = String(r.work_contact || '').replace(/[^\d+]/g, '');
        var contact = r.work_contact
            ? (tel ? '<a href="tel:' + escapeHtml(tel) + '">' + escapeHtml(r.work_contact) + '</a>' : escapeHtml(r.work_contact))
            : '';
        var clientTel = String(r.phone || '').replace(/[^\d+]/g, '');
        var phone  = r.phone
            ? (clientTel ? '<a href="tel:' + escapeHtml(clientTel) + '">' + escapeHtml(r.phone) + '</a>' : escapeHtml(r.phone))
            : '';
        var tone   = avatarTone(r);
        var loanNo = parseInt(r.loan_count, 10);

        var h = '';
        h += '<article class="lr-card" data-id="' + escapeHtml(r.id) + '" data-status="' + escapeHtml(slug(r.status)) + '">';

        // header
        h +=   '<header class="lr-card__head">';
        h +=     '<label class="lr-check"><input type="checkbox" class="card-check" aria-label="Select ' + escapeHtml(full || 'loan') + '"></label>';
        h +=     '<span class="lr-avatar' + (tone ? ' lr-av-' + tone : '') + '" aria-hidden="true">' + escapeHtml(initials(r)) + '</span>';
        h +=     '<div class="lr-card__title">';
        h +=       '<h3 class="lr-card__name" title="' + escapeHtml(full) + '">' + escapeHtml(full || '\u2014') + '</h3>';
        h +=       '<div class="lr-card__sub"><i class="bi bi-person-vcard"></i>' + escapeHtml(r.id_number || '\u2014') + '</div>';
        h +=     '</div>';
        h +=     '<span class="group-pill ' + groupBadgeClass(r.loan_group) + '">' + escapeHtml(r.loan_group) + '</span>';
        h +=   '</header>';

        // reference + loan count
        h +=   '<div class="lr-card__ref">';
        h +=     '<span class="lr-ref">' + escapeHtml(r.reference_number) + '</span>';
        if (loanNo > 0) h += '<span class="lr-chip"><i class="bi bi-arrow-repeat"></i>Loan ' + loanNo + '</span>';
        h +=   '</div>';

        // money
        h +=   '<div class="lr-money">';
        h +=     '<div><span>Amount</span><strong>' + fmtMoney(r.amount) + '</strong></div>';
        h +=     '<div><span>Interest</span><strong>' + fmtMoney(r.interest_amount) + '</strong></div>';
        h +=     '<div class="lr-money__due"><span>Amount due</span><strong>' + fmtMoney(r.amount_due) + '</strong></div>';
        h +=   '</div>';

        // statuses
        h +=   '<div class="lr-card__badges">';
        h +=     '<span class="badge-status ' + statusBadgeClass(r.status) + '">' + escapeHtml(r.status) + '</span>';
        h +=     '<span class="badge-status ' + statusBadgeClass(r.repayment_status) + '">' + escapeHtml(r.repayment_status) + '</span>';
        h +=   '</div>';

        // details
        h +=   '<dl class="lr-details">';
        h +=     textDetail('Branch', r.branch_name);
        h +=     textDetail('Workplace', r.workplace_name);
        h +=     contact ? detail('Work contact', contact, false) : detail('Work contact', '&mdash;', true);
        h +=     phone   ? detail('Client phone', phone, false)   : detail('Client phone', '&mdash;', true);
        h +=     textDetail('Bank', r.bank_name);
        h +=     textDetail('Account no.', r.account_number);
        h +=     detail('Action date', escapeHtml(fmtDate(r.action_date)) || '&mdash;', !r.action_date);
        h +=     detail('Date loaded', escapeHtml(fmtDate(r.date_loaded)) || '&mdash;', !r.date_loaded);
        h +=   '</dl>';

        if (r.notes) h += '<p class="lr-card__notes"><i class="bi bi-chat-left-text"></i> ' + escapeHtml(r.notes) + '</p>';

        // actions (proxied to the table's own buttons)
        h +=   '<footer class="lr-card__actions">';
        h +=     '<button type="button" class="btn btn-sm btn-outline-brand lr-edit" data-id="' + escapeHtml(r.id) + '"><i class="bi bi-pencil"></i> Edit</button>';
        if (!window.IS_BRANCH) {
            h += '<button type="button" class="btn btn-sm btn-outline-danger lr-delete" data-id="' + escapeHtml(r.id) + '"><i class="bi bi-trash"></i> Delete</button>';
        }
        h +=   '</footer>';

        h += '</article>';
        return h;
    }

    function emptyHtml() {
        return '<div class="lr-empty"><i class="bi bi-inbox"></i>No loans found for the selected filters.</div>';
    }

    window.__lrViews = { cardHtml: cardHtml, emptyHtml: emptyHtml, slug: slug, initials: initials };

    /* ---------------------------------------------------------------
       Wiring
       --------------------------------------------------------------- */
    $(function () {
        var $table = $('#loanTable');
        var $panel = $('#lrPanel');
        if (!$table.length || !$panel.length || !$.fn.DataTable.isDataTable('#loanTable')) return;

        var api = $table.DataTable();

        // ---- layout: horizontal scroller around the table; cards sit inside
        //      the DataTables wrapper so its page-length / info / pager serve both views
        $table.wrap('<div class="lr-scroll" id="lrScroll"></div>');
        var scroller = document.getElementById('lrScroll');
        $('#lrCardsView').insertAfter('#lrScroll');

        var hbar  = document.getElementById('lrHbar');
        var track = document.getElementById('lrHbarTrack');

        // ---- sort control for cards (cards have no sortable headers)
        var $sort = $('#cardsSort');
        $sort.append('<option value="" hidden>Custom (table column)</option>');
        SORT_OPTIONS.forEach(function (o) { $sort.append($('<option>').val(o[0]).text(o[1])); });
        $sort.on('change', function () {
            var p = this.value.split(':');
            if (p.length === 2) api.order([parseInt(p[0], 10), p[1]]).draw();
        });
        function syncSortSelect() {
            var ord = api.order();
            var v = ord.length ? ord[0][0] + ':' + ord[0][1] : '';
            $sort.val($sort.find('option[value="' + v + '"]').length ? v : '');
        }

        // ---- view toggle (user choice, remembered; never decided by screen size)
        function setView(view, persist) {
            view = view === 'cards' ? 'cards' : 'table';
            $panel.attr('data-view', view);
            $('#lrViewToggle .lr-toggle__btn').each(function () {
                this.setAttribute('aria-pressed', this.getAttribute('data-view') === view ? 'true' : 'false');
            });
            if (persist) { try { localStorage.setItem(VIEW_KEY, view); } catch (e) { /* private mode */ } }
            updateStickyOffsets();
            scheduleBarUpdate();
        }
        $('#lrViewToggle').on('click', '.lr-toggle__btn', function () {
            setView(this.getAttribute('data-view'), true);
        });

        // ---- cards
        function renderCards() {
            var rows = api.rows({ page: 'current' }).data().toArray();
            $('#loanCards').html(rows.length ? rows.map(cardHtml).join('') : emptyHtml());
            $('#lrCount').text(rows.length + (rows.length === 1 ? ' loan' : ' loans') + ' on this page');
            syncSelection();
        }

        // ---- selection: one source of truth (register.js's selectedIds Set)
        function syncSelection() {
            var sel = (typeof selectedIds !== 'undefined') ? selectedIds : new Set();
            var total = 0, on = 0;
            $('#loanCards .lr-card').each(function () {
                var isOn = sel.has(String(this.getAttribute('data-id')));
                this.classList.toggle('is-selected', isOn);
                $(this).find('.card-check').prop('checked', isOn);
                total++; if (isOn) on++;
            });
            $('#loanTable tbody tr').each(function () {
                $(this).toggleClass('is-selected', $(this).find('.row-check').is(':checked'));
            });
            $('#cardsSelectAll').prop('checked', total > 0 && on === total).prop('indeterminate', on > 0 && on < total);
        }

        // card checkbox -> the matching table checkbox (existing handler updates the Set + bulk bar)
        $('#loanCards').on('change', '.card-check', function () {
            var id = String($(this).closest('.lr-card').attr('data-id'));
            $('#loanTable tbody .row-check').filter(function () { return this.value === id; })
                .prop('checked', this.checked).trigger('change');
        });
        $('#cardsSelectAll').on('change', function () {
            $('#selectAll').prop('checked', this.checked).trigger('change');
        });
        // keep cards/rows highlighted whichever view the change came from
        $('#loanTable tbody').on('change', '.row-check', syncSelection);
        $('#selectAll').on('change', syncSelection);

        // card Edit / Delete -> the table's own buttons (existing handlers do the work)
        function proxyClick(btnClass, id) {
            $('#loanTable tbody ' + btnClass).filter(function () { return this.getAttribute('data-id') === id; })
                .first().trigger('click');
        }
        $('#loanCards').on('click', '.lr-edit',   function () { proxyClick('.edit-loan-btn',   this.getAttribute('data-id')); });
        $('#loanCards').on('click', '.lr-delete', function () { proxyClick('.delete-loan-btn', this.getAttribute('data-id')); });

        // ---- pinned columns: measure the first two pinned widths so the
        //      next pinned column can sit exactly beside them
        function updateStickyOffsets() {
            var ths = $table.find('thead th');
            if (ths.length < 5) return;
            var w3 = ths[2].getBoundingClientRect().width;
            var w4 = ths[3].getBoundingClientRect().width;
            if (!w3) return; // table hidden (cards view) - re-measured when it is shown again
            $table[0].style.setProperty('--lr-left-4', w3 + 'px');
            $table[0].style.setProperty('--lr-left-5', (w3 + w4) + 'px');
        }

        // ---- fixed bottom scrollbar, mirrored with the table's own scroll position
        var barQueued = false;
        function scheduleBarUpdate() {
            if (barQueued) return;
            barQueued = true;
            requestAnimationFrame(function () { barQueued = false; updateBar(); });
        }
        function updateBar() {
            var cards    = $panel.attr('data-view') === 'cards';
            var overflow = scroller.scrollWidth > scroller.clientWidth + 1;
            var r        = scroller.getBoundingClientRect();
            var vh       = window.innerHeight || document.documentElement.clientHeight;
            var nativeBarOnScreen = r.bottom <= vh + 1;      // the table's own scrollbar is already visible
            var tableOnScreen     = r.top < vh - 24 && r.bottom > 0;
            var show = !cards && overflow && !nativeBarOnScreen && tableOnScreen;

            hbar.classList.toggle('is-visible', show);
            if (!show) return;

            hbar.style.left  = (r.left + scroller.clientLeft) + 'px';
            hbar.style.width = scroller.clientWidth + 'px';
            track.style.width = scroller.scrollWidth + 'px';
            if (Math.abs(hbar.scrollLeft - scroller.scrollLeft) > 1) hbar.scrollLeft = scroller.scrollLeft;
        }

        var lock = null;
        scroller.addEventListener('scroll', function () {
            $(scroller).toggleClass('is-scrolled', scroller.scrollLeft > 0);
            if (lock === 'bar') return;
            lock = 'scroller';
            hbar.scrollLeft = scroller.scrollLeft;
            requestAnimationFrame(function () { lock = null; });
        }, { passive: true });
        hbar.addEventListener('scroll', function () {
            if (lock === 'scroller') return;
            lock = 'bar';
            scroller.scrollLeft = hbar.scrollLeft;
            requestAnimationFrame(function () { lock = null; });
        }, { passive: true });

        window.addEventListener('scroll', scheduleBarUpdate, { passive: true });
        window.addEventListener('resize', function () { updateStickyOffsets(); scheduleBarUpdate(); });
        if (window.ResizeObserver) {
            var ro = new ResizeObserver(function () { updateStickyOffsets(); scheduleBarUpdate(); });
            ro.observe($table[0]);
            ro.observe(scroller);
        }

        // ---- DataTables events
        $table.on('draw.dt', function () {
            renderCards();
            updateStickyOffsets();
            syncSortSelect();
            scheduleBarUpdate();
        });
        $table.on('processing.dt', function (e, settings, processing) {
            $('#lrCardsView').toggleClass('is-loading', !!processing);
        });

        // ---- initial state
        var saved = null;
        try { saved = localStorage.getItem(VIEW_KEY); } catch (e) { /* ignore */ }
        setView(saved === 'cards' ? 'cards' : $panel.attr('data-view'), false);
        syncSortSelect();
        if (api.rows().count()) renderCards();
    });
})();
