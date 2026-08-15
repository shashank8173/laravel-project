/**
 * Poll employee inbox notifications + play tone on new items.
 */
(function (window, document) {
    'use strict';

    var pollUrl = window.HrmAppNotifyConfig && window.HrmAppNotifyConfig.pollUrl;
    var readUrlTpl = window.HrmAppNotifyConfig && window.HrmAppNotifyConfig.readUrlTpl;
    var readAllUrl = window.HrmAppNotifyConfig && window.HrmAppNotifyConfig.readAllUrl;
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var afterId = Number((window.HrmAppNotifyConfig && window.HrmAppNotifyConfig.latestId) || 0);
    var timer = null;

    function prefsSoundOn() {
        if (window.HrmNotifyPrefs && typeof window.HrmNotifyPrefs.appSound !== 'undefined') {
            return !!window.HrmNotifyPrefs.appSound;
        }
        return true;
    }

    function playTone() {
        if (!prefsSoundOn()) return;
        if (window.HrmNotifySounds && window.HrmNotifySounds.playMessageTone) {
            window.HrmNotifySounds.playMessageTone();
        }
    }

    function setBadge(n) {
        var badge = document.getElementById('hrmNotifyBadge');
        if (!badge) return;
        n = Number(n) || 0;
        if (n <= 0) {
            badge.style.display = 'none';
            badge.textContent = '0';
            return;
        }
        badge.style.display = 'inline-flex';
        badge.textContent = n > 99 ? '99+' : String(n);
    }

    function escapeHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderList(items) {
        var list = document.getElementById('hrmNotifyList');
        if (!list) return;
        if (!items || !items.length) {
            list.innerHTML = '<li class="px-2 py-3 text-muted small" id="hrmNotifyEmpty">No notifications</li>';
            return;
        }
        list.innerHTML = items.map(function (n) {
            return '<li class="px-2 py-2 border-bottom hrm-notify-item ' + (n.unread ? 'is-unread' : '') + '" data-id="' + n.id + '"'
                + (n.link ? ' data-link="' + escapeHtml(n.link) + '"' : '') + '>'
                + '<div class="fw-semibold" style="font-size:.88rem;line-height:1.3;">' + escapeHtml(n.title) + '</div>'
                + (n.body ? '<div class="small text-muted">' + escapeHtml(n.body).slice(0, 70) + '</div>' : '')
                + '<div class="small text-muted">' + escapeHtml(n.time || '') + '</div>'
                + '</li>';
        }).join('');
    }

    function markRead(id) {
        if (!readUrlTpl || !id) return;
        var url = readUrlTpl.replace('__ID__', String(id));
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data && typeof data.unread !== 'undefined') setBadge(data.unread);
        }).catch(function () {});
    }

    function markAllRead() {
        if (!readAllUrl) return;
        fetch(readAllUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).then(function (r) { return r.json(); }).then(function (data) {
            setBadge(0);
            document.querySelectorAll('#hrmNotifyList .hrm-notify-item').forEach(function (el) {
                el.classList.remove('is-unread');
            });
        }).catch(function () {});
    }

    function poll() {
        if (!pollUrl) return;
        var url = pollUrl + (pollUrl.indexOf('?') >= 0 ? '&' : '?') + 'after_id=' + encodeURIComponent(afterId);
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data) return;
            if (typeof data.unread !== 'undefined') setBadge(data.unread);
            if (data.items) renderList(data.items);
            if (data.fresh && data.fresh.length) {
                playTone();
                // Optional tiny toast for the newest
                try {
                    var newest = data.fresh[data.fresh.length - 1];
                    if (newest && window.console) {
                        // keep quiet in UI except tone + badge
                    }
                } catch (e) {}
            }
            if (typeof data.latest_id !== 'undefined' && Number(data.latest_id) > afterId) {
                afterId = Number(data.latest_id);
            }
            if (typeof data.sound !== 'undefined' && window.HrmNotifyPrefs) {
                window.HrmNotifyPrefs.appSound = !!data.sound;
            }
        }).catch(function () {});
    }

    function bindClicks() {
        var list = document.getElementById('hrmNotifyList');
        if (list) {
            list.addEventListener('click', function (e) {
                var item = e.target.closest('.hrm-notify-item');
                if (!item) return;
                var id = item.getAttribute('data-id');
                var link = item.getAttribute('data-link');
                item.classList.remove('is-unread');
                markRead(id);
                if (link) {
                    window.location.href = link;
                }
            });
        }
        document.getElementById('hrmNotifyReadAll')?.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            markAllRead();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindClicks();
        poll();
        timer = setInterval(poll, 20000);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') poll();
        });
    });
})(window, document);
