/**
 * BlogHub — app.js
 * Features:
 *  - Debounced search (500ms) with sessionStorage caching
 *  - jQuery AJAX filter requests with 10s timeout
 *  - Category chip toggling
 *  - Date filter with custom range and client-side validation (Property 13, 14)
 *  - Loading / error states
 *  - Header scroll effect
 *  - Post card template rendering from JSON
 */

(function ($) {
    'use strict';

    /* ─────────────────────────────────────────────────────────
       STATE
    ───────────────────────────────────────────────────────── */
    var state = {
        search:    '',
        category:  '',
        dateType:  'all',
        dateStart: '',
        dateEnd:   ''
    };

    var debounceTimer = null;
    var currentXhr    = null;

    /* ─────────────────────────────────────────────────────────
       CACHE KEY
    ───────────────────────────────────────────────────────── */
    function cacheKey(s) {
        return 'bloghub_filter_' + JSON.stringify(s);
    }

    function getCached(s) {
        try {
            var data = sessionStorage.getItem(cacheKey(s));
            return data ? JSON.parse(data) : null;
        } catch (e) { return null; }
    }

    function setCache(s, data) {
        try { sessionStorage.setItem(cacheKey(s), JSON.stringify(data)); } catch (e) {}
    }

    /* ─────────────────────────────────────────────────────────
       CLIENT-SIDE DATE VALIDATION (Property 13 & 14)
    ───────────────────────────────────────────────────────── */
    function validateDateRange(start, end) {
        var $err = $('#date-error');
        $err.hide().prop('hidden', true).text('');

        if (!start || !end) return true;

        var s = new Date(start);
        var e = new Date(end);

        // Property 13: start must be <= end
        if (s > e) {
            showDateError('Start date must be before or equal to end date.');
            return false;
        }

        // Property 14: max 5 years (1826 days for leap year)
        var diffDays = (e - s) / (1000 * 60 * 60 * 24);
        if (diffDays > 1826) {
            showDateError('Date range is too large. Maximum span is 5 years.');
            return false;
        }

        return true;
    }

    function showDateError(msg) {
        var $err = $('#date-error');
        $err.text(msg).prop('hidden', false).show();
    }

    /* ─────────────────────────────────────────────────────────
       POST CARD TEMPLATE (Property 4: Rendering Completeness)
    ───────────────────────────────────────────────────────── */
    function formatDate(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function esc(str) {
        return $('<div>').text(str || '').html();
    }

    function renderPostCard(post) {
        var imagePart = '';
        if (post.image_path) {
            imagePart = '<img src="/' + esc(post.image_path) + '" alt="' + esc(post.title) +
                '" class="post-card-img" loading="lazy">';
        } else {
            imagePart = '<div class="post-card-img-placeholder" aria-hidden="true">✦</div>';
        }

        var categoryBadge = '';
        if (post.category) {
            categoryBadge = '<span class="post-card-category">' + esc(post.category) + '</span>';
        }

        var excerpt = post.short_description || '';
        if (excerpt.length > 150) { excerpt = excerpt.substring(0, 150) + '…'; }

        var dateDisplay = formatDate(post.created_date);

        return '<article class="post-card" id="post-card-' + post.id + '" aria-labelledby="post-title-' + post.id + '">' +
            '<a href="/blogs/' + post.id + '" class="post-card-link" tabindex="-1" aria-hidden="true">' +
                '<div class="post-card-image">' + imagePart + categoryBadge + '</div>' +
            '</a>' +
            '<div class="post-card-body">' +
                '<time class="post-card-date" datetime="' + esc(post.created_date) + '">' + esc(dateDisplay) + '</time>' +
                '<h2 class="post-card-title" id="post-title-' + post.id + '">' +
                    '<a href="/blogs/' + post.id + '" class="post-card-title-link">' + esc(post.title) + '</a>' +
                '</h2>' +
                '<p class="post-card-excerpt">' + esc(excerpt) + '</p>' +
                '<a href="/blogs/' + post.id + '" class="post-card-read-more" aria-label="Read more about ' + esc(post.title) + '">' +
                    'Read more <span aria-hidden="true">→</span>' +
                '</a>' +
            '</div>' +
        '</article>';
    }

    function renderEmptyState() {
        return '<div class="posts-empty" id="posts-empty">' +
            '<div class="empty-icon">🔍</div>' +
            '<h2 class="empty-title">No posts found</h2>' +
            '<p class="empty-subtitle">Try adjusting your filters or search query.</p>' +
        '</div>';
    }

    /* ─────────────────────────────────────────────────────────
       UI HELPERS
    ───────────────────────────────────────────────────────── */
    function showLoading() {
        $('#listing-loading').prop('hidden', false).show();
        $('#listing-error').prop('hidden', true).hide();
        $('#posts-grid').css('opacity', '0.4');
    }

    function hideLoading() {
        $('#listing-loading').prop('hidden', true).hide();
        $('#posts-grid').css('opacity', '1');
    }

    function showError(msg) {
        hideLoading();
        $('#listing-error-msg').text(msg || 'Could not load posts. Please try again.');
        $('#listing-error').prop('hidden', false).show();
        $('#posts-grid').css('opacity', '1');
    }

    function updateResultsBar(count) {
        var noun = count === 1 ? 'article' : 'articles';
        $('#results-bar').html('<span id="results-count">' + count + '</span> ' + noun + ' found');
    }

    /* ─────────────────────────────────────────────────────────
       FILTER REQUEST
    ───────────────────────────────────────────────────────── */
    function doFilter() {
        // Only run on listing page
        if (!$('#posts-grid').length) return;

        // Abort previous request
        if (currentXhr) { currentXhr.abort(); currentXhr = null; }

        var payload = {
            category: state.category,
            date_range: {
                type:  state.dateType,
                start: state.dateStart || null,
                end:   state.dateEnd   || null
            },
            search: state.search
        };

        // Check cache first
        var cached = getCached(payload);
        if (cached) {
            renderResults(cached);
            return;
        }

        showLoading();

        currentXhr = $.ajax({
            url:         '/blogs/filter',
            type:        'POST',
            contentType: 'application/json',
            data:        JSON.stringify(payload),
            timeout:     10000,    // 10 second timeout
            success: function (resp) {
                if (resp && resp.success) {
                    setCache(payload, resp);
                    renderResults(resp);
                } else {
                    showError(resp && resp.error ? resp.error : 'Filter failed. Please try again.');
                }
            },
            error: function (xhr, status) {
                if (status === 'abort') return;
                showError('Network error. Please check your connection and retry.');
            }
        });
    }

    function renderResults(resp) {
        hideLoading();
        updateResultsBar(resp.total || 0);
        var $grid = $('#posts-grid');
        $grid.empty();

        if (!resp.data || resp.data.length === 0) {
            $grid.html(renderEmptyState());
        } else {
            var html = '';
            for (var i = 0; i < resp.data.length; i++) {
                html += renderPostCard(resp.data[i]);
            }
            $grid.html(html);
        }
    }

    /* ─────────────────────────────────────────────────────────
       DEBOUNCE TRIGGER
    ───────────────────────────────────────────────────────── */
    function triggerFilterDebounced(delay) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doFilter, delay || 500);
    }

    /* ─────────────────────────────────────────────────────────
       SEARCH INPUT (500ms debounce)
    ───────────────────────────────────────────────────────── */
    $(document).on('input', '#search-input', function () {
        state.search = $(this).val().trim();
        var $clear = $('#search-clear');
        if (state.search.length > 0) {
            $clear.prop('hidden', false).show();
        } else {
            $clear.prop('hidden', true).hide();
        }
        triggerFilterDebounced(500);
    });

    $(document).on('click keydown', '#search-clear', function (e) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        $('#search-input').val('');
        state.search = '';
        $(this).prop('hidden', true).hide();
        triggerFilterDebounced(0);
    });

    /* ─────────────────────────────────────────────────────────
       CATEGORY CHIPS
    ───────────────────────────────────────────────────────── */
    $(document).on('click', '[data-filter="category"]', function () {
        var $btn = $(this);
        $('[data-filter="category"]').removeClass('filter-chip--active').attr('aria-pressed', 'false');
        $btn.addClass('filter-chip--active').attr('aria-pressed', 'true');
        state.category = $btn.data('value') || '';
        triggerFilterDebounced(0);
    });

    /* ─────────────────────────────────────────────────────────
       DATE FILTER SELECT
    ───────────────────────────────────────────────────────── */
    $(document).on('change', '#date-filter-select', function () {
        var val = $(this).val();
        state.dateType = val;
        state.dateStart = '';
        state.dateEnd   = '';

        if (val === 'custom') {
            $('#custom-date-range').prop('hidden', false).show();
        } else {
            $('#custom-date-range').prop('hidden', true).hide();
            $('#date-error').prop('hidden', true).hide();
            triggerFilterDebounced(0);
        }
    });

    /* ─────────────────────────────────────────────────────────
       CUSTOM DATE RANGE — Apply button
    ───────────────────────────────────────────────────────── */
    $(document).on('click', '#apply-date-btn', function () {
        var start = $('#date-start').val();
        var end   = $('#date-end').val();

        if (!validateDateRange(start, end)) return;

        state.dateStart = start;
        state.dateEnd   = end;
        triggerFilterDebounced(0);
    });

    /* ─────────────────────────────────────────────────────────
       RETRY BUTTON
    ───────────────────────────────────────────────────────── */
    $(document).on('click', '#listing-retry-btn', function () {
        doFilter();
    });

    /* ─────────────────────────────────────────────────────────
       HEADER SCROLL EFFECT
    ───────────────────────────────────────────────────────── */
    $(window).on('scroll.header', function () {
        var $header = $('#site-header, #admin-header');
        if ($(window).scrollTop() > 20) {
            $header.css('background', 'hsl(232 24% 7% / 0.98)');
        } else {
            $header.css('background', '');
        }
    });

    /* ─────────────────────────────────────────────────────────
       INIT: clear stale cache on full page load
    ───────────────────────────────────────────────────────── */
    $(document).ready(function () {
        // Clear cache entries older than 5 minutes (simple key-scan)
        try {
            var now = Date.now();
            var prefix = 'bloghub_filter_';
            Object.keys(sessionStorage).forEach(function (key) {
                if (key.indexOf(prefix) === 0) {
                    var entry = JSON.parse(sessionStorage.getItem(key));
                    if (entry && entry._ts && (now - entry._ts) > 300000) {
                        sessionStorage.removeItem(key);
                    }
                }
            });
        } catch (e) {}
    });

}(window.jQuery || { ajax: function(){}, fn: {} }));
