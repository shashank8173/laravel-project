/**
 * Leadforgrow HRM — click-to-open Summernote rich editor
 * Usage: <textarea class="form-control js-rich-editor" name="description"></textarea>
 */
(function ($) {
    'use strict';

    if (!$) return;

    var TOOLBAR = [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'clear']],
        ['fontname', ['fontname']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'picture', 'hr']],
        ['view', ['fullscreen', 'codeview']]
    ];

    function isBlankHtml(html) {
        if (!html) return true;
        var text = $('<div>').html(html).text().replace(/\u00a0/g, ' ').trim();
        return text === '';
    }

    function activate($ta, focus) {
        if ($ta.data('summernote')) {
            if (focus) $ta.summernote('focus');
            return;
        }

        var height = parseInt($ta.data('height'), 10) || Math.max(160, ($ta.attr('rows') || 4) * 28);

        $ta.removeClass('hrm-rich-source').show();
        $ta.summernote({
            height: height,
            placeholder: $ta.attr('placeholder') || 'Write description…',
            toolbar: TOOLBAR,
            dialogsInBody: true,
            disableDragAndDrop: false,
            followingToolbar: false,
            callbacks: {
                onImageUpload: function (files) {
                    // Keep images as data-URL for simplicity (no separate upload endpoint)
                    var editor = $(this);
                    Array.prototype.forEach.call(files, function (file) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            editor.summernote('insertImage', e.target.result, file.name || 'image');
                        };
                        reader.readAsDataURL(file);
                    });
                },
                onInit: function () {
                    // Prevent Bootstrap 5 from hijacking Summernote-lite dropdown buttons
                    var $editor = $ta.next('.note-editor');
                    $editor.find('[data-toggle="dropdown"]').each(function () {
                        $(this).removeAttr('data-toggle').removeAttr('data-bs-toggle');
                    });
                    $editor.find('.dropdown-toggle').each(function () {
                        $(this).removeAttr('data-bs-toggle');
                    });
                }
            }
        });

        if (focus) {
            setTimeout(function () { $ta.summernote('focus'); }, 40);
        }
    }

    function buildShell($ta) {
        $ta.siblings('.hrm-rich-shell').remove();
        $ta.addClass('hrm-rich-source');

        if ($ta.prop('required')) {
            $ta.data('was-required', 1);
            $ta.prop('required', false);
        }

        var html = $ta.val() || '';
        var empty = isBlankHtml(html);
        var $shell = $('<div class="hrm-rich-shell" tabindex="0" role="button" aria-label="Open editor"></div>');
        if (empty) $shell.addClass('is-empty');

        $shell.append('<span class="hrm-rich-hint">Click to edit</span>');
        var $preview = $('<div class="hrm-rich-preview"></div>');
        if (empty) {
            $preview.text($ta.attr('placeholder') || 'Click here to open the editor…');
        } else {
            $preview.html(html);
        }
        $shell.append($preview);
        $shell.insertAfter($ta);

        function openEditor(e) {
            if (e) e.preventDefault();
            $shell.remove();
            activate($ta, true);
        }

        $shell.on('click', openEditor);
        $shell.on('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') openEditor(e);
        });
    }

    function prepare($ta) {
        if (!$ta.length || !$ta.is('textarea')) return;

        // Already live editor
        if ($ta.next('.note-editor').length || $ta.data('summernote')) {
            // Refresh content if textarea value was updated (edit modals)
            try {
                $ta.summernote('code', $ta.val() || '');
            } catch (e) {}
            return;
        }

        buildShell($ta);
        $ta.data('rich-ready', 1);
    }

    function init(root) {
        if (typeof $.fn.summernote !== 'function') return;
        $(root || document).find('textarea.js-rich-editor').each(function () {
            prepare($(this));
        });
    }

    function destroyIn(root) {
        $(root || document).find('textarea.js-rich-editor').each(function () {
            var $ta = $(this);
            if ($ta.data('summernote')) {
                try { $ta.summernote('destroy'); } catch (e) {}
            }
            $ta.siblings('.hrm-rich-shell').remove();
            $ta.removeData('rich-ready');
            $ta.removeClass('hrm-rich-source').show();
            if ($ta.data('was-required')) {
                $ta.prop('required', true);
            }
        });
    }

    function syncForm(form) {
        $(form).find('textarea.js-rich-editor').each(function () {
            var $ta = $(this);
            if ($ta.data('summernote')) {
                $ta.val($ta.summernote('code'));
            }
        });
    }

    function validateRequired(form) {
        var ok = true;
        $(form).find('textarea.js-rich-editor').each(function () {
            var $ta = $(this);
            if (!$ta.data('was-required') && !$ta.attr('required')) return;
            syncForm(form);
            if (isBlankHtml($ta.val())) {
                ok = false;
                if (!$ta.data('summernote')) {
                    $ta.siblings('.hrm-rich-shell').remove();
                    activate($ta, true);
                } else {
                    $ta.summernote('focus');
                }
                alert('Please fill in the description field.');
                return false;
            }
        });
        return ok;
    }

    $(function () {
        init(document);

        $(document).on('shown.bs.modal', '.modal', function () {
            init(this);
        });

        // When modal hides, destroy editors so next open rebuilds with fresh values
        $(document).on('hidden.bs.modal', '.modal', function () {
            destroyIn(this);
        });

        $(document).on('submit', 'form', function (e) {
            syncForm(this);
            if (!validateRequired(this)) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });
    });

    window.HrmRichEditor = {
        init: init,
        activate: function (el) { activate($(el), true); },
        destroy: destroyIn,
        setHtml: function (el, html) {
            var $ta = $(el);
            $ta.val(html || '');
            if ($ta.data('summernote')) {
                $ta.summernote('code', html || '');
            } else {
                $ta.removeData('rich-ready');
                $ta.siblings('.hrm-rich-shell').remove();
                prepare($ta);
            }
        }
    };
})(window.jQuery);
