(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);throw new Error("Cannot find module '"+o+"'")}var f=n[o]={exports:{}};t[o][0].call(f.exports,function(e){var n=t[o][1][e];return s(n?n:e)},f,f.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
/* global wpforms_edit_post_education */

/**
 * WPForms Edit Post Education function.
 *
 * @since 1.8.1
 */

'use strict';

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _iterableToArrayLimit(arr, i) { var _i = null == arr ? null : "undefined" != typeof Symbol && arr[Symbol.iterator] || arr["@@iterator"]; if (null != _i) { var _s, _e, _x, _r, _arr = [], _n = !0, _d = !1; try { if (_x = (_i = _i.call(arr)).next, 0 === i) { if (Object(_i) !== _i) return; _n = !1; } else for (; !(_n = (_s = _x.call(_i)).done) && (_arr.push(_s.value), _arr.length !== i); _n = !0); } catch (err) { _d = !0, _e = err; } finally { try { if (!_n && null != _i.return && (_r = _i.return(), Object(_r) !== _r)) return; } finally { if (_d) throw _e; } } return _arr; } }
function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }
var WPFormsEditPostEducation = window.WPFormsEditPostEducation || function (document, window, $) {
  /**
   * Public functions and properties.
   *
   * @since 1.8.1
   *
   * @type {object}
   */
  var app = {
    /**
     * Determine if the notice was showed before.
     *
     * @since 1.8.1
     */
    isNoticeVisible: false,
    /**
     * Start the engine.
     *
     * @since 1.8.1
     */
    init: function init() {
      $(window).on('load', function () {
        // In the case of jQuery 3.+, we need to wait for a ready event first.
        if (typeof $.ready.then === 'function') {
          $.ready.then(app.load);
        } else {
          app.load();
        }
      });
    },
    /**
     * Page load.
     *
     * @since 1.8.1
     */
    load: function load() {
      if (!app.isGutenbergEditor()) {
        app.maybeShowClassicNotice();
        app.bindClassicEvents();
        return;
      }
      if (!app.isFse()) {
        app.maybeShowGutenbergNotice();
        app.bindGutenbergEvents();
        return;
      }
      var iframe = document.querySelector('iframe[name="editor-canvas"]');
      var observer = new MutationObserver(function () {
        var iframeDocument = iframe.contentDocument || iframe.contentWindow.document || {};
        if (iframeDocument.readyState === 'complete' && iframeDocument.querySelector('.editor-post-title__input')) {
          app.maybeShowGutenbergNotice();
          app.bindFseEvents();
          observer.disconnect();
        }
      });
      observer.observe(document.body, {
        subtree: true,
        childList: true
      });
    },
    /**
     * Bind events for Classic Editor.
     *
     * @since 1.8.1
     */
    bindClassicEvents: function bindClassicEvents() {
      var $document = $(document);
      if (!app.isNoticeVisible) {
        $document.on('input', '#title', app.maybeShowClassicNotice);
      }
      $document.on('click', '.wpforms-edit-post-education-notice-close', app.closeNotice);
    },
    /**
     * Bind events for Gutenberg Editor.
     *
     * @since 1.8.1
     */
    bindGutenbergEvents: function bindGutenbergEvents() {
      if (app.isNoticeVisible) {
        return;
      }
      $(document).on('input', '.editor-post-title__input', app.maybeShowGutenbergNotice).on('DOMSubtreeModified', '.editor-post-title__input', app.maybeShowGutenbergNotice);
    },
    /**
     * Bind events for Gutenberg Editor in FSE mode.
     *
     * @since 1.8.1
     */
    bindFseEvents: function bindFseEvents() {
      var $iframe = $('iframe[name="editor-canvas"]');
      $iframe.contents().on('DOMSubtreeModified', '.editor-post-title__input', app.maybeShowGutenbergNotice);
    },
    /**
     * Determine if the editor is Gutenberg.
     *
     * @since 1.8.1
     *
     * @returns {boolean} True if the editor is Gutenberg.
     */
    isGutenbergEditor: function isGutenbergEditor() {
      return typeof wp !== 'undefined' && typeof wp.blocks !== 'undefined';
    },
    /**
     * Determine if the editor is Gutenberg in FSE mode.
     *
     * @since 1.8.1
     *
     * @returns {boolean} True if the Gutenberg editor in FSE mode.
     */
    isFse: function isFse() {
      return Boolean($('iframe[name="editor-canvas"]').length);
    },
    /**
     * Create a notice for Gutenberg.
     *
     * @since 1.8.1
     */
    showGutenbergNotice: function showGutenbergNotice() {
      wp.data.dispatch('core/notices').createInfoNotice(wpforms_edit_post_education.gutenberg_notice.template, app.getGutenbergNoticeSettings());

      // The notice component doesn't have a way to add HTML id or class to the notice.
      // Also, the notice became visible with a delay on old Gutenberg versions.
      var hasNotice = setInterval(function () {
        var noticeBody = $('.wpforms-edit-post-education-notice-body');
        if (!noticeBody.length) {
          return;
        }
        var $notice = noticeBody.closest('.components-notice');
        $notice.addClass('wpforms-edit-post-education-notice');
        $notice.find('.is-secondary, .is-link').removeClass('is-secondary').removeClass('is-link').addClass('is-primary');
        clearInterval(hasNotice);
      }, 100);
    },
    /**
     * Get settings for the Gutenberg notice.
     *
     * @since 1.8.1
     *
     * @returns {object} Notice settings.
     */
    getGutenbergNoticeSettings: function getGutenbergNoticeSettings() {
      var pluginName = 'wpforms-edit-post-product-education-guide';
      var noticeSettings = {
        id: pluginName,
        isDismissible: true,
        HTML: true,
        __unstableHTML: true,
        actions: [{
          className: 'wpforms-edit-post-education-notice-guide-button',
          variant: 'primary',
          label: wpforms_edit_post_education.gutenberg_notice.button
        }]
      };
      if (!wpforms_edit_post_education.gutenberg_guide) {
        noticeSettings.actions[0].url = wpforms_edit_post_education.gutenberg_notice.url;
        return noticeSettings;
      }
      var Guide = wp.components.Guide;
      var useState = wp.element.useState;
      var registerPlugin = wp.plugins.registerPlugin;
      var unregisterPlugin = wp.plugins.unregisterPlugin;
      var GutenbergTutorial = function GutenbergTutorial() {
        var _useState = useState(true),
          _useState2 = _slicedToArray(_useState, 2),
          isOpen = _useState2[0],
          setIsOpen = _useState2[1];
        if (!isOpen) {
          return null;
        }
        return (
          /*#__PURE__*/
          // eslint-disable-next-line react/react-in-jsx-scope
          React.createElement(Guide, {
            className: "edit-post-welcome-guide",
            onFinish: function onFinish() {
              unregisterPlugin(pluginName);
              setIsOpen(false);
            },
            pages: app.getGuidePages()
          })
        );
      };
      noticeSettings.onDismiss = app.updateUserMeta;
      noticeSettings.actions[0].onClick = function () {
        return registerPlugin(pluginName, {
          render: GutenbergTutorial
        });
      };
      return noticeSettings;
    },
    /**
     * Get Guide pages in proper format.
     *
     * @since 1.8.1
     *
     * @returns {Array} Guide Pages.
     */
    getGuidePages: function getGuidePages() {
      var pages = [];
      wpforms_edit_post_education.gutenberg_guide.forEach(function (page) {
        pages.push({
          /* eslint-disable react/react-in-jsx-scope */
          content: /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("h1", {
            className: "edit-post-welcome-guide__heading"
          }, page.title), /*#__PURE__*/React.createElement("p", {
            className: "edit-post-welcome-guide__text"
          }, page.content)),
          image: /*#__PURE__*/React.createElement("img", {
            className: "edit-post-welcome-guide__image",
            src: page.image,
            alt: page.title
          })
          /* eslint-enable react/react-in-jsx-scope */
        });
      });

      return pages;
    },
    /**
     * Show notice if the page title matches some keywords for Classic Editor.
     *
     * @since 1.8.1
     */
    maybeShowClassicNotice: function maybeShowClassicNotice() {
      if (app.isNoticeVisible) {
        return;
      }
      if (app.isTitleMatchKeywords($('#title').val())) {
        app.isNoticeVisible = true;
        $('.wpforms-edit-post-education-notice').removeClass('wpforms-hidden');
      }
    },
    /**
     * Show notice if the page title matches some keywords for Gutenberg Editor.
     *
     * @since 1.8.1
     */
    maybeShowGutenbergNotice: function maybeShowGutenbergNotice() {
      if (app.isNoticeVisible) {
        return;
      }
      var $postTitle = app.isFse() ? $('iframe[name="editor-canvas"]').contents().find('.editor-post-title__input') : $('.editor-post-title__input');
      var tagName = $postTitle.prop('tagName');
      var title = tagName === 'TEXTAREA' ? $postTitle.val() : $postTitle.text();
      if (app.isTitleMatchKeywords(title)) {
        app.isNoticeVisible = true;
        app.showGutenbergNotice();
      }
    },
    /**
     * Determine if the title matches keywords.
     *
     * @since 1.8.1
     *
     * @param {string} titleValue Page title value.
     *
     * @returns {boolean} True if the title matches some keywords.
     */
    isTitleMatchKeywords: function isTitleMatchKeywords(titleValue) {
      var expectedTitleRegex = new RegExp(/\b(contact|form)\b/i);
      return expectedTitleRegex.test(titleValue);
    },
    /**
     * Close a notice.
     *
     * @since 1.8.1
     */
    closeNotice: function closeNotice() {
      $(this).closest('.wpforms-edit-post-education-notice').remove();
      app.updateUserMeta();
    },
    /**
     * Update user meta and don't show the notice next time.
     *
     * @since 1.8.1
     */
    updateUserMeta: function updateUserMeta() {
      $.post(wpforms_edit_post_education.ajax_url, {
        action: 'wpforms_education_dismiss',
        nonce: wpforms_edit_post_education.education_nonce,
        section: 'edit-post-notice'
      });
    }
  };
  return app;
}(document, window, jQuery);
WPFormsEditPostEducation.init();
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6WyJXUEZvcm1zRWRpdFBvc3RFZHVjYXRpb24iLCJ3aW5kb3ciLCJkb2N1bWVudCIsIiQiLCJhcHAiLCJpc05vdGljZVZpc2libGUiLCJpbml0Iiwib24iLCJyZWFkeSIsInRoZW4iLCJsb2FkIiwiaXNHdXRlbmJlcmdFZGl0b3IiLCJtYXliZVNob3dDbGFzc2ljTm90aWNlIiwiYmluZENsYXNzaWNFdmVudHMiLCJpc0ZzZSIsIm1heWJlU2hvd0d1dGVuYmVyZ05vdGljZSIsImJpbmRHdXRlbmJlcmdFdmVudHMiLCJpZnJhbWUiLCJxdWVyeVNlbGVjdG9yIiwib2JzZXJ2ZXIiLCJNdXRhdGlvbk9ic2VydmVyIiwiaWZyYW1lRG9jdW1lbnQiLCJjb250ZW50RG9jdW1lbnQiLCJjb250ZW50V2luZG93IiwicmVhZHlTdGF0ZSIsImJpbmRGc2VFdmVudHMiLCJkaXNjb25uZWN0Iiwib2JzZXJ2ZSIsImJvZHkiLCJzdWJ0cmVlIiwiY2hpbGRMaXN0IiwiJGRvY3VtZW50IiwiY2xvc2VOb3RpY2UiLCIkaWZyYW1lIiwiY29udGVudHMiLCJ3cCIsImJsb2NrcyIsIkJvb2xlYW4iLCJsZW5ndGgiLCJzaG93R3V0ZW5iZXJnTm90aWNlIiwiZGF0YSIsImRpc3BhdGNoIiwiY3JlYXRlSW5mb05vdGljZSIsIndwZm9ybXNfZWRpdF9wb3N0X2VkdWNhdGlvbiIsImd1dGVuYmVyZ19ub3RpY2UiLCJ0ZW1wbGF0ZSIsImdldEd1dGVuYmVyZ05vdGljZVNldHRpbmdzIiwiaGFzTm90aWNlIiwic2V0SW50ZXJ2YWwiLCJub3RpY2VCb2R5IiwiJG5vdGljZSIsImNsb3Nlc3QiLCJhZGRDbGFzcyIsImZpbmQiLCJyZW1vdmVDbGFzcyIsImNsZWFySW50ZXJ2YWwiLCJwbHVnaW5OYW1lIiwibm90aWNlU2V0dGluZ3MiLCJpZCIsImlzRGlzbWlzc2libGUiLCJIVE1MIiwiX191bnN0YWJsZUhUTUwiLCJhY3Rpb25zIiwiY2xhc3NOYW1lIiwidmFyaWFudCIsImxhYmVsIiwiYnV0dG9uIiwiZ3V0ZW5iZXJnX2d1aWRlIiwidXJsIiwiR3VpZGUiLCJjb21wb25lbnRzIiwidXNlU3RhdGUiLCJlbGVtZW50IiwicmVnaXN0ZXJQbHVnaW4iLCJwbHVnaW5zIiwidW5yZWdpc3RlclBsdWdpbiIsIkd1dGVuYmVyZ1R1dG9yaWFsIiwiaXNPcGVuIiwic2V0SXNPcGVuIiwiZ2V0R3VpZGVQYWdlcyIsIm9uRGlzbWlzcyIsInVwZGF0ZVVzZXJNZXRhIiwib25DbGljayIsInJlbmRlciIsInBhZ2VzIiwiZm9yRWFjaCIsInBhZ2UiLCJwdXNoIiwiY29udGVudCIsInRpdGxlIiwiaW1hZ2UiLCJpc1RpdGxlTWF0Y2hLZXl3b3JkcyIsInZhbCIsIiRwb3N0VGl0bGUiLCJ0YWdOYW1lIiwicHJvcCIsInRleHQiLCJ0aXRsZVZhbHVlIiwiZXhwZWN0ZWRUaXRsZVJlZ2V4IiwiUmVnRXhwIiwidGVzdCIsInJlbW92ZSIsInBvc3QiLCJhamF4X3VybCIsImFjdGlvbiIsIm5vbmNlIiwiZWR1Y2F0aW9uX25vbmNlIiwic2VjdGlvbiIsImpRdWVyeSJdLCJzb3VyY2VzIjpbImZha2VfMWExZmI0YWMuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyogZ2xvYmFsIHdwZm9ybXNfZWRpdF9wb3N0X2VkdWNhdGlvbiAqL1xuXG4vKipcbiAqIFdQRm9ybXMgRWRpdCBQb3N0IEVkdWNhdGlvbiBmdW5jdGlvbi5cbiAqXG4gKiBAc2luY2UgMS44LjFcbiAqL1xuXG4ndXNlIHN0cmljdCc7XG5cbmNvbnN0IFdQRm9ybXNFZGl0UG9zdEVkdWNhdGlvbiA9IHdpbmRvdy5XUEZvcm1zRWRpdFBvc3RFZHVjYXRpb24gfHwgKCBmdW5jdGlvbiggZG9jdW1lbnQsIHdpbmRvdywgJCApIHtcblxuXHQvKipcblx0ICogUHVibGljIGZ1bmN0aW9ucyBhbmQgcHJvcGVydGllcy5cblx0ICpcblx0ICogQHNpbmNlIDEuOC4xXG5cdCAqXG5cdCAqIEB0eXBlIHtvYmplY3R9XG5cdCAqL1xuXHRjb25zdCBhcHAgPSB7XG5cblx0XHQvKipcblx0XHQgKiBEZXRlcm1pbmUgaWYgdGhlIG5vdGljZSB3YXMgc2hvd2VkIGJlZm9yZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqL1xuXHRcdGlzTm90aWNlVmlzaWJsZTogZmFsc2UsXG5cblx0XHQvKipcblx0XHQgKiBTdGFydCB0aGUgZW5naW5lLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICovXG5cdFx0aW5pdDogZnVuY3Rpb24oKSB7XG5cblx0XHRcdCQoIHdpbmRvdyApLm9uKCAnbG9hZCcsIGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRcdC8vIEluIHRoZSBjYXNlIG9mIGpRdWVyeSAzLissIHdlIG5lZWQgdG8gd2FpdCBmb3IgYSByZWFkeSBldmVudCBmaXJzdC5cblx0XHRcdFx0aWYgKCB0eXBlb2YgJC5yZWFkeS50aGVuID09PSAnZnVuY3Rpb24nICkge1xuXHRcdFx0XHRcdCQucmVhZHkudGhlbiggYXBwLmxvYWQgKTtcblx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRhcHAubG9hZCgpO1xuXHRcdFx0XHR9XG5cdFx0XHR9ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIFBhZ2UgbG9hZC5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqL1xuXHRcdGxvYWQ6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRpZiAoICEgYXBwLmlzR3V0ZW5iZXJnRWRpdG9yKCkgKSB7XG5cdFx0XHRcdGFwcC5tYXliZVNob3dDbGFzc2ljTm90aWNlKCk7XG5cdFx0XHRcdGFwcC5iaW5kQ2xhc3NpY0V2ZW50cygpO1xuXG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCAhIGFwcC5pc0ZzZSgpICkge1xuXG5cdFx0XHRcdGFwcC5tYXliZVNob3dHdXRlbmJlcmdOb3RpY2UoKTtcblx0XHRcdFx0YXBwLmJpbmRHdXRlbmJlcmdFdmVudHMoKTtcblxuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGNvbnN0IGlmcmFtZSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoICdpZnJhbWVbbmFtZT1cImVkaXRvci1jYW52YXNcIl0nICk7XG5cdFx0XHRjb25zdCBvYnNlcnZlciA9IG5ldyBNdXRhdGlvbk9ic2VydmVyKCBmdW5jdGlvbigpIHtcblxuXHRcdFx0XHRjb25zdCBpZnJhbWVEb2N1bWVudCA9IGlmcmFtZS5jb250ZW50RG9jdW1lbnQgfHwgaWZyYW1lLmNvbnRlbnRXaW5kb3cuZG9jdW1lbnQgfHwge307XG5cblx0XHRcdFx0aWYgKCBpZnJhbWVEb2N1bWVudC5yZWFkeVN0YXRlID09PSAnY29tcGxldGUnICYmIGlmcmFtZURvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoICcuZWRpdG9yLXBvc3QtdGl0bGVfX2lucHV0JyApICkge1xuXHRcdFx0XHRcdGFwcC5tYXliZVNob3dHdXRlbmJlcmdOb3RpY2UoKTtcblx0XHRcdFx0XHRhcHAuYmluZEZzZUV2ZW50cygpO1xuXG5cdFx0XHRcdFx0b2JzZXJ2ZXIuZGlzY29ubmVjdCgpO1xuXHRcdFx0XHR9XG5cdFx0XHR9ICk7XG5cdFx0XHRvYnNlcnZlci5vYnNlcnZlKCBkb2N1bWVudC5ib2R5LCB7IHN1YnRyZWU6IHRydWUsIGNoaWxkTGlzdDogdHJ1ZSB9ICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEJpbmQgZXZlbnRzIGZvciBDbGFzc2ljIEVkaXRvci5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqL1xuXHRcdGJpbmRDbGFzc2ljRXZlbnRzOiBmdW5jdGlvbigpIHtcblxuXHRcdFx0Y29uc3QgJGRvY3VtZW50ID0gJCggZG9jdW1lbnQgKTtcblxuXHRcdFx0aWYgKCAhIGFwcC5pc05vdGljZVZpc2libGUgKSB7XG5cdFx0XHRcdCRkb2N1bWVudC5vbiggJ2lucHV0JywgJyN0aXRsZScsIGFwcC5tYXliZVNob3dDbGFzc2ljTm90aWNlICk7XG5cdFx0XHR9XG5cblx0XHRcdCRkb2N1bWVudC5vbiggJ2NsaWNrJywgJy53cGZvcm1zLWVkaXQtcG9zdC1lZHVjYXRpb24tbm90aWNlLWNsb3NlJywgYXBwLmNsb3NlTm90aWNlICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEJpbmQgZXZlbnRzIGZvciBHdXRlbmJlcmcgRWRpdG9yLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICovXG5cdFx0YmluZEd1dGVuYmVyZ0V2ZW50czogZnVuY3Rpb24oKSB7XG5cblx0XHRcdGlmICggYXBwLmlzTm90aWNlVmlzaWJsZSApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHQkKCBkb2N1bWVudCApXG5cdFx0XHRcdC5vbiggJ2lucHV0JywgJy5lZGl0b3ItcG9zdC10aXRsZV9faW5wdXQnLCBhcHAubWF5YmVTaG93R3V0ZW5iZXJnTm90aWNlIClcblx0XHRcdFx0Lm9uKCAnRE9NU3VidHJlZU1vZGlmaWVkJywgJy5lZGl0b3ItcG9zdC10aXRsZV9faW5wdXQnLCBhcHAubWF5YmVTaG93R3V0ZW5iZXJnTm90aWNlICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEJpbmQgZXZlbnRzIGZvciBHdXRlbmJlcmcgRWRpdG9yIGluIEZTRSBtb2RlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICovXG5cdFx0YmluZEZzZUV2ZW50czogZnVuY3Rpb24oKSB7XG5cblx0XHRcdGNvbnN0ICRpZnJhbWUgPSAkKCAnaWZyYW1lW25hbWU9XCJlZGl0b3ItY2FudmFzXCJdJyApO1xuXG5cdFx0XHQkaWZyYW1lLmNvbnRlbnRzKClcblx0XHRcdFx0Lm9uKCAnRE9NU3VidHJlZU1vZGlmaWVkJywgJy5lZGl0b3ItcG9zdC10aXRsZV9faW5wdXQnLCBhcHAubWF5YmVTaG93R3V0ZW5iZXJnTm90aWNlICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIERldGVybWluZSBpZiB0aGUgZWRpdG9yIGlzIEd1dGVuYmVyZy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqXG5cdFx0ICogQHJldHVybnMge2Jvb2xlYW59IFRydWUgaWYgdGhlIGVkaXRvciBpcyBHdXRlbmJlcmcuXG5cdFx0ICovXG5cdFx0aXNHdXRlbmJlcmdFZGl0b3I6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRyZXR1cm4gdHlwZW9mIHdwICE9PSAndW5kZWZpbmVkJyAmJiB0eXBlb2Ygd3AuYmxvY2tzICE9PSAndW5kZWZpbmVkJztcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogRGV0ZXJtaW5lIGlmIHRoZSBlZGl0b3IgaXMgR3V0ZW5iZXJnIGluIEZTRSBtb2RlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJucyB7Ym9vbGVhbn0gVHJ1ZSBpZiB0aGUgR3V0ZW5iZXJnIGVkaXRvciBpbiBGU0UgbW9kZS5cblx0XHQgKi9cblx0XHRpc0ZzZTogZnVuY3Rpb24oKSB7XG5cblx0XHRcdHJldHVybiBCb29sZWFuKCAkKCAnaWZyYW1lW25hbWU9XCJlZGl0b3ItY2FudmFzXCJdJyApLmxlbmd0aCApO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBDcmVhdGUgYSBub3RpY2UgZm9yIEd1dGVuYmVyZy5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqL1xuXHRcdHNob3dHdXRlbmJlcmdOb3RpY2U6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHR3cC5kYXRhLmRpc3BhdGNoKCAnY29yZS9ub3RpY2VzJyApLmNyZWF0ZUluZm9Ob3RpY2UoXG5cdFx0XHRcdHdwZm9ybXNfZWRpdF9wb3N0X2VkdWNhdGlvbi5ndXRlbmJlcmdfbm90aWNlLnRlbXBsYXRlLFxuXHRcdFx0XHRhcHAuZ2V0R3V0ZW5iZXJnTm90aWNlU2V0dGluZ3MoKVxuXHRcdFx0KTtcblxuXHRcdFx0Ly8gVGhlIG5vdGljZSBjb21wb25lbnQgZG9lc24ndCBoYXZlIGEgd2F5IHRvIGFkZCBIVE1MIGlkIG9yIGNsYXNzIHRvIHRoZSBub3RpY2UuXG5cdFx0XHQvLyBBbHNvLCB0aGUgbm90aWNlIGJlY2FtZSB2aXNpYmxlIHdpdGggYSBkZWxheSBvbiBvbGQgR3V0ZW5iZXJnIHZlcnNpb25zLlxuXHRcdFx0Y29uc3QgaGFzTm90aWNlID0gc2V0SW50ZXJ2YWwoIGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRcdGNvbnN0IG5vdGljZUJvZHkgPSAkKCAnLndwZm9ybXMtZWRpdC1wb3N0LWVkdWNhdGlvbi1ub3RpY2UtYm9keScgKTtcblx0XHRcdFx0aWYgKCAhIG5vdGljZUJvZHkubGVuZ3RoICkge1xuXHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdGNvbnN0ICRub3RpY2UgPSBub3RpY2VCb2R5LmNsb3Nlc3QoICcuY29tcG9uZW50cy1ub3RpY2UnICk7XG5cdFx0XHRcdCRub3RpY2UuYWRkQ2xhc3MoICd3cGZvcm1zLWVkaXQtcG9zdC1lZHVjYXRpb24tbm90aWNlJyApO1xuXHRcdFx0XHQkbm90aWNlLmZpbmQoICcuaXMtc2Vjb25kYXJ5LCAuaXMtbGluaycgKS5yZW1vdmVDbGFzcyggJ2lzLXNlY29uZGFyeScgKS5yZW1vdmVDbGFzcyggJ2lzLWxpbmsnICkuYWRkQ2xhc3MoICdpcy1wcmltYXJ5JyApO1xuXG5cdFx0XHRcdGNsZWFySW50ZXJ2YWwoIGhhc05vdGljZSApO1xuXHRcdFx0fSwgMTAwICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBzZXR0aW5ncyBmb3IgdGhlIEd1dGVuYmVyZyBub3RpY2UuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEByZXR1cm5zIHtvYmplY3R9IE5vdGljZSBzZXR0aW5ncy5cblx0XHQgKi9cblx0XHRnZXRHdXRlbmJlcmdOb3RpY2VTZXR0aW5nczogZnVuY3Rpb24oKSB7XG5cblx0XHRcdGNvbnN0IHBsdWdpbk5hbWUgPSAnd3Bmb3Jtcy1lZGl0LXBvc3QtcHJvZHVjdC1lZHVjYXRpb24tZ3VpZGUnO1xuXHRcdFx0Y29uc3Qgbm90aWNlU2V0dGluZ3MgPSB7XG5cdFx0XHRcdGlkOiBwbHVnaW5OYW1lLFxuXHRcdFx0XHRpc0Rpc21pc3NpYmxlOiB0cnVlLFxuXHRcdFx0XHRIVE1MOiB0cnVlLFxuXHRcdFx0XHRfX3Vuc3RhYmxlSFRNTDogdHJ1ZSxcblx0XHRcdFx0YWN0aW9uczogW1xuXHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdGNsYXNzTmFtZTogJ3dwZm9ybXMtZWRpdC1wb3N0LWVkdWNhdGlvbi1ub3RpY2UtZ3VpZGUtYnV0dG9uJyxcblx0XHRcdFx0XHRcdHZhcmlhbnQ6ICdwcmltYXJ5Jyxcblx0XHRcdFx0XHRcdGxhYmVsOiB3cGZvcm1zX2VkaXRfcG9zdF9lZHVjYXRpb24uZ3V0ZW5iZXJnX25vdGljZS5idXR0b24sXG5cdFx0XHRcdFx0fSxcblx0XHRcdFx0XSxcblx0XHRcdH07XG5cblx0XHRcdGlmICggISB3cGZvcm1zX2VkaXRfcG9zdF9lZHVjYXRpb24uZ3V0ZW5iZXJnX2d1aWRlICkge1xuXG5cdFx0XHRcdG5vdGljZVNldHRpbmdzLmFjdGlvbnNbMF0udXJsID0gd3Bmb3Jtc19lZGl0X3Bvc3RfZWR1Y2F0aW9uLmd1dGVuYmVyZ19ub3RpY2UudXJsO1xuXG5cdFx0XHRcdHJldHVybiBub3RpY2VTZXR0aW5ncztcblx0XHRcdH1cblxuXHRcdFx0Y29uc3QgR3VpZGUgPSB3cC5jb21wb25lbnRzLkd1aWRlO1xuXHRcdFx0Y29uc3QgdXNlU3RhdGUgPSB3cC5lbGVtZW50LnVzZVN0YXRlO1xuXHRcdFx0Y29uc3QgcmVnaXN0ZXJQbHVnaW4gPSB3cC5wbHVnaW5zLnJlZ2lzdGVyUGx1Z2luO1xuXHRcdFx0Y29uc3QgdW5yZWdpc3RlclBsdWdpbiA9IHdwLnBsdWdpbnMudW5yZWdpc3RlclBsdWdpbjtcblx0XHRcdGNvbnN0IEd1dGVuYmVyZ1R1dG9yaWFsID0gZnVuY3Rpb24oKSB7XG5cblx0XHRcdFx0Y29uc3QgWyBpc09wZW4sIHNldElzT3BlbiBdID0gdXNlU3RhdGUoIHRydWUgKTtcblxuXHRcdFx0XHRpZiAoICEgaXNPcGVuICkge1xuXHRcdFx0XHRcdHJldHVybiBudWxsO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0cmV0dXJuIChcblx0XHRcdFx0XHQvLyBlc2xpbnQtZGlzYWJsZS1uZXh0LWxpbmUgcmVhY3QvcmVhY3QtaW4tanN4LXNjb3BlXG5cdFx0XHRcdFx0PEd1aWRlXG5cdFx0XHRcdFx0XHRjbGFzc05hbWU9XCJlZGl0LXBvc3Qtd2VsY29tZS1ndWlkZVwiXG5cdFx0XHRcdFx0XHRvbkZpbmlzaD17ICgpID0+IHtcblx0XHRcdFx0XHRcdFx0dW5yZWdpc3RlclBsdWdpbiggcGx1Z2luTmFtZSApO1xuXHRcdFx0XHRcdFx0XHRzZXRJc09wZW4oIGZhbHNlICk7XG5cdFx0XHRcdFx0XHR9IH1cblx0XHRcdFx0XHRcdHBhZ2VzPXsgYXBwLmdldEd1aWRlUGFnZXMoKSB9XG5cdFx0XHRcdFx0Lz5cblx0XHRcdFx0KTtcblx0XHRcdH07XG5cblx0XHRcdG5vdGljZVNldHRpbmdzLm9uRGlzbWlzcyA9IGFwcC51cGRhdGVVc2VyTWV0YTtcblx0XHRcdG5vdGljZVNldHRpbmdzLmFjdGlvbnNbMF0ub25DbGljayA9ICgpID0+IHJlZ2lzdGVyUGx1Z2luKCBwbHVnaW5OYW1lLCB7IHJlbmRlcjogR3V0ZW5iZXJnVHV0b3JpYWwgfSApO1xuXG5cdFx0XHRyZXR1cm4gbm90aWNlU2V0dGluZ3M7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIEdldCBHdWlkZSBwYWdlcyBpbiBwcm9wZXIgZm9ybWF0LlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJucyB7QXJyYXl9IEd1aWRlIFBhZ2VzLlxuXHRcdCAqL1xuXHRcdGdldEd1aWRlUGFnZXM6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHRjb25zdCBwYWdlcyA9IFtdO1xuXG5cdFx0XHR3cGZvcm1zX2VkaXRfcG9zdF9lZHVjYXRpb24uZ3V0ZW5iZXJnX2d1aWRlLmZvckVhY2goIGZ1bmN0aW9uKCBwYWdlICkge1xuXHRcdFx0XHRwYWdlcy5wdXNoKFxuXHRcdFx0XHRcdHtcblx0XHRcdFx0XHRcdC8qIGVzbGludC1kaXNhYmxlIHJlYWN0L3JlYWN0LWluLWpzeC1zY29wZSAqL1xuXHRcdFx0XHRcdFx0Y29udGVudDogKFxuXHRcdFx0XHRcdFx0XHQ8PlxuXHRcdFx0XHRcdFx0XHRcdDxoMSBjbGFzc05hbWU9XCJlZGl0LXBvc3Qtd2VsY29tZS1ndWlkZV9faGVhZGluZ1wiPnsgcGFnZS50aXRsZSB9PC9oMT5cblx0XHRcdFx0XHRcdFx0XHQ8cCBjbGFzc05hbWU9XCJlZGl0LXBvc3Qtd2VsY29tZS1ndWlkZV9fdGV4dFwiPnsgcGFnZS5jb250ZW50IH08L3A+XG5cdFx0XHRcdFx0XHRcdDwvPlxuXHRcdFx0XHRcdFx0KSxcblx0XHRcdFx0XHRcdGltYWdlOiA8aW1nIGNsYXNzTmFtZT1cImVkaXQtcG9zdC13ZWxjb21lLWd1aWRlX19pbWFnZVwiIHNyYz17IHBhZ2UuaW1hZ2UgfSBhbHQ9eyBwYWdlLnRpdGxlIH0gLz4sXG5cdFx0XHRcdFx0XHQvKiBlc2xpbnQtZW5hYmxlIHJlYWN0L3JlYWN0LWluLWpzeC1zY29wZSAqL1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0KTtcblx0XHRcdH0gKTtcblxuXHRcdFx0cmV0dXJuIHBhZ2VzO1xuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBTaG93IG5vdGljZSBpZiB0aGUgcGFnZSB0aXRsZSBtYXRjaGVzIHNvbWUga2V5d29yZHMgZm9yIENsYXNzaWMgRWRpdG9yLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICovXG5cdFx0bWF5YmVTaG93Q2xhc3NpY05vdGljZTogZnVuY3Rpb24oKSB7XG5cblx0XHRcdGlmICggYXBwLmlzTm90aWNlVmlzaWJsZSApIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAoIGFwcC5pc1RpdGxlTWF0Y2hLZXl3b3JkcyggJCggJyN0aXRsZScgKS52YWwoKSApICkge1xuXHRcdFx0XHRhcHAuaXNOb3RpY2VWaXNpYmxlID0gdHJ1ZTtcblxuXHRcdFx0XHQkKCAnLndwZm9ybXMtZWRpdC1wb3N0LWVkdWNhdGlvbi1ub3RpY2UnICkucmVtb3ZlQ2xhc3MoICd3cGZvcm1zLWhpZGRlbicgKTtcblx0XHRcdH1cblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogU2hvdyBub3RpY2UgaWYgdGhlIHBhZ2UgdGl0bGUgbWF0Y2hlcyBzb21lIGtleXdvcmRzIGZvciBHdXRlbmJlcmcgRWRpdG9yLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICovXG5cdFx0bWF5YmVTaG93R3V0ZW5iZXJnTm90aWNlOiBmdW5jdGlvbigpIHtcblxuXHRcdFx0aWYgKCBhcHAuaXNOb3RpY2VWaXNpYmxlICkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGNvbnN0ICRwb3N0VGl0bGUgPSBhcHAuaXNGc2UoKSA/XG5cdFx0XHRcdCQoICdpZnJhbWVbbmFtZT1cImVkaXRvci1jYW52YXNcIl0nICkuY29udGVudHMoKS5maW5kKCAnLmVkaXRvci1wb3N0LXRpdGxlX19pbnB1dCcgKSA6XG5cdFx0XHRcdCQoICcuZWRpdG9yLXBvc3QtdGl0bGVfX2lucHV0JyApO1xuXHRcdFx0Y29uc3QgdGFnTmFtZSA9ICRwb3N0VGl0bGUucHJvcCggJ3RhZ05hbWUnICk7XG5cdFx0XHRjb25zdCB0aXRsZSA9IHRhZ05hbWUgPT09ICdURVhUQVJFQScgPyAkcG9zdFRpdGxlLnZhbCgpIDogJHBvc3RUaXRsZS50ZXh0KCk7XG5cblx0XHRcdGlmICggYXBwLmlzVGl0bGVNYXRjaEtleXdvcmRzKCB0aXRsZSApICkge1xuXHRcdFx0XHRhcHAuaXNOb3RpY2VWaXNpYmxlID0gdHJ1ZTtcblxuXHRcdFx0XHRhcHAuc2hvd0d1dGVuYmVyZ05vdGljZSgpO1xuXHRcdFx0fVxuXHRcdH0sXG5cblx0XHQvKipcblx0XHQgKiBEZXRlcm1pbmUgaWYgdGhlIHRpdGxlIG1hdGNoZXMga2V5d29yZHMuXG5cdFx0ICpcblx0XHQgKiBAc2luY2UgMS44LjFcblx0XHQgKlxuXHRcdCAqIEBwYXJhbSB7c3RyaW5nfSB0aXRsZVZhbHVlIFBhZ2UgdGl0bGUgdmFsdWUuXG5cdFx0ICpcblx0XHQgKiBAcmV0dXJucyB7Ym9vbGVhbn0gVHJ1ZSBpZiB0aGUgdGl0bGUgbWF0Y2hlcyBzb21lIGtleXdvcmRzLlxuXHRcdCAqL1xuXHRcdGlzVGl0bGVNYXRjaEtleXdvcmRzOiBmdW5jdGlvbiggdGl0bGVWYWx1ZSApIHtcblxuXHRcdFx0Y29uc3QgZXhwZWN0ZWRUaXRsZVJlZ2V4ID0gbmV3IFJlZ0V4cCggL1xcYihjb250YWN0fGZvcm0pXFxiL2kgKTtcblxuXHRcdFx0cmV0dXJuIGV4cGVjdGVkVGl0bGVSZWdleC50ZXN0KCB0aXRsZVZhbHVlICk7XG5cdFx0fSxcblxuXHRcdC8qKlxuXHRcdCAqIENsb3NlIGEgbm90aWNlLlxuXHRcdCAqXG5cdFx0ICogQHNpbmNlIDEuOC4xXG5cdFx0ICovXG5cdFx0Y2xvc2VOb3RpY2U6IGZ1bmN0aW9uKCkge1xuXG5cdFx0XHQkKCB0aGlzICkuY2xvc2VzdCggJy53cGZvcm1zLWVkaXQtcG9zdC1lZHVjYXRpb24tbm90aWNlJyApLnJlbW92ZSgpO1xuXG5cdFx0XHRhcHAudXBkYXRlVXNlck1ldGEoKTtcblx0XHR9LFxuXG5cdFx0LyoqXG5cdFx0ICogVXBkYXRlIHVzZXIgbWV0YSBhbmQgZG9uJ3Qgc2hvdyB0aGUgbm90aWNlIG5leHQgdGltZS5cblx0XHQgKlxuXHRcdCAqIEBzaW5jZSAxLjguMVxuXHRcdCAqL1xuXHRcdHVwZGF0ZVVzZXJNZXRhKCkge1xuXG5cdFx0XHQkLnBvc3QoXG5cdFx0XHRcdHdwZm9ybXNfZWRpdF9wb3N0X2VkdWNhdGlvbi5hamF4X3VybCxcblx0XHRcdFx0e1xuXHRcdFx0XHRcdGFjdGlvbjogJ3dwZm9ybXNfZWR1Y2F0aW9uX2Rpc21pc3MnLFxuXHRcdFx0XHRcdG5vbmNlOiB3cGZvcm1zX2VkaXRfcG9zdF9lZHVjYXRpb24uZWR1Y2F0aW9uX25vbmNlLFxuXHRcdFx0XHRcdHNlY3Rpb246ICdlZGl0LXBvc3Qtbm90aWNlJyxcblx0XHRcdFx0fVxuXHRcdFx0KTtcblx0XHR9LFxuXHR9O1xuXG5cdHJldHVybiBhcHA7XG5cbn0oIGRvY3VtZW50LCB3aW5kb3csIGpRdWVyeSApICk7XG5cbldQRm9ybXNFZGl0UG9zdEVkdWNhdGlvbi5pbml0KCk7XG4iXSwibWFwcGluZ3MiOiJBQUFBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUEsWUFBWTs7QUFBQztBQUFBO0FBQUE7QUFBQTtBQUFBO0FBQUE7QUFFYixJQUFNQSx3QkFBd0IsR0FBR0MsTUFBTSxDQUFDRCx3QkFBd0IsSUFBTSxVQUFVRSxRQUFRLEVBQUVELE1BQU0sRUFBRUUsQ0FBQyxFQUFHO0VBRXJHO0FBQ0Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0VBQ0MsSUFBTUMsR0FBRyxHQUFHO0lBRVg7QUFDRjtBQUNBO0FBQ0E7QUFDQTtJQUNFQyxlQUFlLEVBQUUsS0FBSztJQUV0QjtBQUNGO0FBQ0E7QUFDQTtBQUNBO0lBQ0VDLElBQUksRUFBRSxnQkFBVztNQUVoQkgsQ0FBQyxDQUFFRixNQUFNLENBQUUsQ0FBQ00sRUFBRSxDQUFFLE1BQU0sRUFBRSxZQUFXO1FBRWxDO1FBQ0EsSUFBSyxPQUFPSixDQUFDLENBQUNLLEtBQUssQ0FBQ0MsSUFBSSxLQUFLLFVBQVUsRUFBRztVQUN6Q04sQ0FBQyxDQUFDSyxLQUFLLENBQUNDLElBQUksQ0FBRUwsR0FBRyxDQUFDTSxJQUFJLENBQUU7UUFDekIsQ0FBQyxNQUFNO1VBQ05OLEdBQUcsQ0FBQ00sSUFBSSxFQUFFO1FBQ1g7TUFDRCxDQUFDLENBQUU7SUFDSixDQUFDO0lBRUQ7QUFDRjtBQUNBO0FBQ0E7QUFDQTtJQUNFQSxJQUFJLEVBQUUsZ0JBQVc7TUFFaEIsSUFBSyxDQUFFTixHQUFHLENBQUNPLGlCQUFpQixFQUFFLEVBQUc7UUFDaENQLEdBQUcsQ0FBQ1Esc0JBQXNCLEVBQUU7UUFDNUJSLEdBQUcsQ0FBQ1MsaUJBQWlCLEVBQUU7UUFFdkI7TUFDRDtNQUVBLElBQUssQ0FBRVQsR0FBRyxDQUFDVSxLQUFLLEVBQUUsRUFB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
},{}]},{},[1])