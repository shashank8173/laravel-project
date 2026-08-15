(function () {
    'use strict';

    var FILTERS_CACHE = null;
    var FILTERS_PROMISE = null;

    function qs(el, sel) {
        return el.querySelector(sel);
    }

    function qsa(el, sel) {
        return Array.prototype.slice.call(el.querySelectorAll(sel));
    }

    function debounce(fn, wait) {
        var t = null;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function lookupUrls() {
        var root = document.documentElement;
        return {
            search: root.getAttribute('data-emp-lookup-url') || '/lookups/employees',
            filters: root.getAttribute('data-emp-filters-url') || '/lookups/employee-filters',
        };
    }

    function loadFilters() {
        if (FILTERS_CACHE) {
            return Promise.resolve(FILTERS_CACHE);
        }
        if (FILTERS_PROMISE) {
            return FILTERS_PROMISE;
        }
        FILTERS_PROMISE = fetch(lookupUrls().filters, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                FILTERS_CACHE = {
                    departments: (json.departments || []),
                    designations: (json.designations || []),
                };
                return FILTERS_CACHE;
            })
            .catch(function () {
                FILTERS_CACHE = { departments: [], designations: [] };
                return FILTERS_CACHE;
            });
        return FILTERS_PROMISE;
    }

    function optionToItem(opt) {
        return {
            id: String(opt.value),
            name: opt.getAttribute('data-name') || (opt.textContent || '').trim(),
            department: opt.getAttribute('data-department') || '',
            designation: opt.getAttribute('data-designation') || '',
            department_id: opt.getAttribute('data-department-id') || '',
            designation_id: opt.getAttribute('data-designation-id') || '',
            emp_id: opt.getAttribute('data-emp-id') || '',
            disabled: !!opt.disabled,
            selected: !!opt.selected,
        };
    }

    function EmployeePicker(select) {
        this.select = select;
        this.multiple = !!select.multiple;
        this.placeholder = select.getAttribute('data-placeholder')
            || select.getAttribute('placeholder')
            || (this.multiple ? 'Search & select employees…' : 'Search employee…');
        this.ajax = select.getAttribute('data-ajax') !== '0';
        this.exclude = (select.getAttribute('data-exclude') || '')
            .split(',')
            .map(function (v) { return String(v).trim(); })
            .filter(Boolean);
        this.remoteItems = [];
        this.open = false;
        this.build();
        this.bind();
        this.syncFromSelect();
    }

    EmployeePicker.prototype.build = function () {
        var wrap = document.createElement('div');
        wrap.className = 'hrm-emp-wrap is-hidden-native';
        if (this.select.classList.contains('form-select-sm')) {
            wrap.classList.add('is-sm');
        }

        this.select.parentNode.insertBefore(wrap, this.select);
        wrap.appendChild(this.select);

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'hrm-emp-toggle';
        toggle.innerHTML =
            '<span class="hrm-emp-toggle-label is-placeholder"></span>' +
            '<i class="fa-solid fa-chevron-down hrm-emp-toggle-caret"></i>';
        wrap.appendChild(toggle);

        var chips = document.createElement('div');
        chips.className = 'hrm-emp-chips';
        chips.hidden = !this.multiple;
        wrap.appendChild(chips);

        var panel = document.createElement('div');
        panel.className = 'hrm-emp-panel';
        panel.innerHTML =
            '<div class="hrm-emp-filters">' +
                '<input type="search" class="hrm-emp-search" placeholder="Search by name / emp id / email…" autocomplete="off">' +
                '<div class="hrm-emp-filters-row">' +
                    '<select class="hrm-emp-dept"><option value="">All departments</option></select>' +
                    '<select class="hrm-emp-desig"><option value="">All designations</option></select>' +
                '</div>' +
            '</div>' +
            '<div class="hrm-emp-list"></div>' +
            (this.multiple
                ? '<div class="hrm-emp-actions"><button type="button" class="btn btn-sm btn-outline-secondary hrm-emp-clear">Clear</button><button type="button" class="btn btn-sm add-btn hrm-emp-done">Done</button></div>'
                : '');
        wrap.appendChild(panel);

        this.wrap = wrap;
        this.toggle = toggle;
        this.labelEl = qs(toggle, '.hrm-emp-toggle-label');
        this.chips = chips;
        this.panel = panel;
        this.searchInput = qs(panel, '.hrm-emp-search');
        this.deptSelect = qs(panel, '.hrm-emp-dept');
        this.desigSelect = qs(panel, '.hrm-emp-desig');
        this.listEl = qs(panel, '.hrm-emp-list');
        this.clearBtn = qs(panel, '.hrm-emp-clear');
        this.doneBtn = qs(panel, '.hrm-emp-done');
    };

    EmployeePicker.prototype.bind = function () {
        var self = this;
        var runFilter = debounce(function () { self.refreshList(); }, 180);

        this.toggle.addEventListener('click', function (e) {
            e.preventDefault();
            self.setOpen(!self.open);
        });

        this.searchInput.addEventListener('input', runFilter);
        this.deptSelect.addEventListener('change', function () { self.refreshList(); });
        this.desigSelect.addEventListener('change', function () { self.refreshList(); });

        if (this.clearBtn) {
            this.clearBtn.addEventListener('click', function () {
                self.clearSelection();
            });
        }
        if (this.doneBtn) {
            this.doneBtn.addEventListener('click', function () {
                self.setOpen(false);
            });
        }

        document.addEventListener('click', function (e) {
            if (!self.open) return;
            if (!self.wrap.contains(e.target)) {
                self.setOpen(false);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && self.open) {
                self.setOpen(false);
            }
        });

        this.select.addEventListener('change', function () {
            self.syncFromSelect();
        });
    };

    EmployeePicker.prototype.setOpen = function (open) {
        var self = this;
        this.open = !!open;
        this.wrap.classList.toggle('is-open', this.open);
        if (!this.open) return;

        var rect = this.toggle.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;
        this.panel.classList.toggle('is-dropup', spaceBelow < 320 && rect.top > spaceBelow);

        loadFilters().then(function (filters) {
            self.fillFilterSelects(filters);
        });

        this.searchInput.value = '';
        this.deptSelect.value = '';
        this.desigSelect.value = '';
        this.refreshList();
        setTimeout(function () { self.searchInput.focus(); }, 20);
    };

    EmployeePicker.prototype.fillFilterSelects = function (filters) {
        var local = this.localItems();
        var depts = filters.departments || [];
        var desigs = filters.designations || [];

        if (depts.length === 0 && local.length) {
            var map = {};
            local.forEach(function (item) {
                if (item.department_id && item.department) {
                    map[item.department_id] = item.department;
                }
            });
            depts = Object.keys(map).map(function (id) {
                return { id: id, name: map[id] };
            }).sort(function (a, b) {
                return String(a.name).localeCompare(String(b.name));
            });
        }

        if (desigs.length === 0 && local.length) {
            var map2 = {};
            local.forEach(function (item) {
                if (item.designation_id && item.designation) {
                    map2[item.designation_id] = item.designation;
                }
            });
            desigs = Object.keys(map2).map(function (id) {
                return { id: id, name: map2[id] };
            }).sort(function (a, b) {
                return String(a.name).localeCompare(String(b.name));
            });
        }

        this.fillSelect(this.deptSelect, depts, 'All departments');
        this.fillSelect(this.desigSelect, desigs, 'All designations');
    };

    EmployeePicker.prototype.fillSelect = function (el, rows, allLabel) {
        var current = el.value;
        el.innerHTML = '<option value="">' + escapeHtml(allLabel) + '</option>';
        rows.forEach(function (row) {
            var opt = document.createElement('option');
            opt.value = String(row.id);
            opt.textContent = row.name;
            el.appendChild(opt);
        });
        if (current) el.value = current;
    };

    EmployeePicker.prototype.localItems = function () {
        return qsa(this.select, 'option').filter(function (opt) {
            return opt.value !== '';
        }).map(optionToItem);
    };

    EmployeePicker.prototype.selectedIds = function () {
        if (this.multiple) {
            return qsa(this.select, 'option:checked').map(function (o) { return String(o.value); });
        }
        return this.select.value ? [String(this.select.value)] : [];
    };

    EmployeePicker.prototype.refreshList = function () {
        var self = this;
        var q = (this.searchInput.value || '').trim().toLowerCase();
        var dept = this.deptSelect.value;
        var desig = this.desigSelect.value;
        var local = this.localItems().filter(function (item) {
            return self.exclude.indexOf(item.id) === -1;
        });

        var useAjax = this.ajax && (local.length === 0 || local.length > 250 || q.length >= 2 || dept || desig);

        // Prefer local filtering when options are already present and list is manageable
        if (local.length > 0 && local.length <= 250) {
            useAjax = false;
        }

        if (!useAjax && local.length > 0) {
            var filtered = local.filter(function (item) {
                if (dept && String(item.department_id) !== String(dept)) return false;
                if (desig && String(item.designation_id) !== String(desig)) return false;
                if (!q) return true;
                var hay = [
                    item.name,
                    item.department,
                    item.designation,
                    item.emp_id,
                ].join(' ').toLowerCase();
                return hay.indexOf(q) !== -1;
            });
            this.renderList(filtered.slice(0, 80));
            return;
        }

        if (!this.ajax) {
            this.renderList([]);
            return;
        }

        this.listEl.innerHTML = '<div class="hrm-emp-loading">Searching…</div>';
        var urls = lookupUrls();
        var params = new URLSearchParams();
        if (q) params.set('q', q);
        if (dept) params.set('department_id', dept);
        if (desig) params.set('designation_id', desig);
        if (this.exclude.length) params.set('exclude', this.exclude.join(','));
        params.set('limit', '40');

        fetch(urls.search + '?' + params.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
        })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var rows = (json.data || []).map(function (row) {
                    return {
                        id: String(row.id),
                        name: row.name,
                        department: row.department || '',
                        designation: row.designation || '',
                        department_id: String(row.department_id || ''),
                        designation_id: String(row.designation_id || ''),
                        emp_id: row.emp_id || '',
                    };
                });
                self.remoteItems = rows;
                self.renderList(rows);
            })
            .catch(function () {
                self.listEl.innerHTML = '<div class="hrm-emp-empty">Could not load employees.</div>';
            });
    };

    EmployeePicker.prototype.renderList = function (items) {
        var self = this;
        var selected = this.selectedIds();
        if (!items.length) {
            this.listEl.innerHTML = '<div class="hrm-emp-empty">No employees found. Try another name, department or designation.</div>';
            return;
        }

        this.listEl.innerHTML = items.map(function (item) {
            var isSelected = selected.indexOf(item.id) !== -1;
            var sub = [item.designation, item.department, item.emp_id ? ('ID: ' + item.emp_id) : '']
                .filter(Boolean)
                .join(' · ');
            return (
                '<button type="button" class="hrm-emp-item' + (isSelected ? ' is-selected' : '') + '" data-id="' + escapeHtml(item.id) + '">' +
                    (self.multiple
                        ? '<span class="hrm-emp-item-check">' + (isSelected ? '<i class="fa-solid fa-square-check"></i>' : '<i class="fa-regular fa-square"></i>') + '</span>'
                        : '<span class="hrm-emp-item-check">' + (isSelected ? '<i class="fa-solid fa-check"></i>' : '') + '</span>') +
                    '<span class="hrm-emp-item-body">' +
                        '<span class="hrm-emp-item-name">' + escapeHtml(item.name) + '</span>' +
                        (sub ? '<div class="hrm-emp-item-sub">' + escapeHtml(sub) + '</div>' : '') +
                    '</span>' +
                '</button>'
            );
        }).join('');

        qsa(this.listEl, '.hrm-emp-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                self.pick(btn.getAttribute('data-id'));
            });
        });
    };

    EmployeePicker.prototype.ensureOption = function (id) {
        var existing = this.select.querySelector('option[value="' + CSS.escape(id) + '"]');
        if (existing) return existing;

        var item = this.remoteItems.find(function (r) { return String(r.id) === String(id); })
            || this.localItems().find(function (r) { return String(r.id) === String(id); });
        if (!item) {
            item = { id: id, name: 'Employee #' + id, department: '', designation: '' };
        }

        var opt = document.createElement('option');
        opt.value = String(item.id);
        opt.textContent = item.name;
        opt.setAttribute('data-name', item.name);
        opt.setAttribute('data-department', item.department || '');
        opt.setAttribute('data-designation', item.designation || '');
        opt.setAttribute('data-department-id', item.department_id || '');
        opt.setAttribute('data-designation-id', item.designation_id || '');
        if (item.emp_id) opt.setAttribute('data-emp-id', item.emp_id);
        this.select.appendChild(opt);
        return opt;
    };

    EmployeePicker.prototype.pick = function (id) {
        id = String(id);
        if (this.multiple) {
            var opt = this.ensureOption(id);
            opt.selected = !opt.selected;
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
            this.syncFromSelect();
            this.refreshList();
            return;
        }

        // single
        qsa(this.select, 'option').forEach(function (o) { o.selected = false; });
        var empty = this.select.querySelector('option[value=""]');
        if (empty) empty.selected = false;
        var opt = this.ensureOption(id);
        opt.selected = true;
        this.select.value = id;
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
        this.syncFromSelect();
        this.setOpen(false);
    };

    EmployeePicker.prototype.clearSelection = function () {
        if (this.multiple) {
            qsa(this.select, 'option').forEach(function (o) { o.selected = false; });
        } else {
            this.select.value = '';
            qsa(this.select, 'option').forEach(function (o) { o.selected = o.value === ''; });
        }
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
        this.syncFromSelect();
        this.refreshList();
    };

    EmployeePicker.prototype.syncFromSelect = function () {
        var selected = this.selectedIds();
        var items = this.localItems().filter(function (item) {
            return selected.indexOf(item.id) !== -1;
        });

        if (items.length === 0 && selected.length) {
            items = selected.map(function (id) {
                return { id: id, name: 'Employee #' + id };
            });
        }

        if (!items.length) {
            this.labelEl.textContent = this.placeholder;
            this.labelEl.classList.add('is-placeholder');
        } else if (this.multiple) {
            this.labelEl.textContent = items.length + ' selected';
            this.labelEl.classList.remove('is-placeholder');
        } else {
            var one = items[0];
            var meta = [one.designation, one.department].filter(Boolean).join(' · ');
            this.labelEl.innerHTML = escapeHtml(one.name)
                + (meta ? ' <span class="hrm-emp-toggle-meta">· ' + escapeHtml(meta) + '</span>' : '');
            this.labelEl.classList.remove('is-placeholder');
        }

        if (this.multiple) {
            this.chips.hidden = items.length === 0;
            this.chips.innerHTML = items.map(function (item) {
                return (
                    '<span class="hrm-emp-chip" data-id="' + escapeHtml(item.id) + '">' +
                        escapeHtml(item.name) +
                        '<button type="button" aria-label="Remove">&times;</button>' +
                    '</span>'
                );
            }).join('');
            var self = this;
            qsa(this.chips, '.hrm-emp-chip button').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var id = btn.parentElement.getAttribute('data-id');
                    var opt = self.select.querySelector('option[value="' + CSS.escape(id) + '"]');
                    if (opt) opt.selected = false;
                    self.select.dispatchEvent(new Event('change', { bubbles: true }));
                    self.syncFromSelect();
                    if (self.open) self.refreshList();
                });
            });
        }
    };

    function enhance(select) {
        if (!select || select.dataset.empPickerReady === '1') return;
        if (select.disabled) return;
        select.dataset.empPickerReady = '1';
        new EmployeePicker(select);
    }

    function init(root) {
        qsa(root || document, 'select.js-employee-select').forEach(enhance);
    }

    window.HrmEmployeeSelect = { init: init, enhance: enhance };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(document); });
    } else {
        init(document);
    }

    document.addEventListener('shown.bs.modal', function (e) {
        init(e.target || document);
    });
})();
