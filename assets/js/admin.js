jQuery(document).ready(function($) {
    // Tooltips
    $('.tooltip').tooltipster({
        theme: 'tooltipster-light',
        animation: 'fade',
        delay: 100,
        side: 'top'
    });

    // Loading States
    function setLoading(element, isLoading) {
        if (isLoading) {
            element.addClass('is-loading')
                  .prop('disabled', true)
                  .find('.spinner')
                  .addClass('is-active');
        } else {
            element.removeClass('is-loading')
                  .prop('disabled', false)
                  .find('.spinner')
                  .removeClass('is-active');
        }
    }

    // Error Handling
    function showError(message, type = 'error') {
        const notice = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
        $('.xautoposter-wrap > h1').after(notice);
        notice.hide().slideDown();
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            notice.slideUp(() => notice.remove());
        }, 5000);
    }

    // Confirmation Dialog
    function confirm(message) {
        return new Promise((resolve) => {
            const dialog = $(`
                <div class="xautoposter-dialog">
                    <div class="xautoposter-dialog-content">
                        <p>${message}</p>
                        <div class="xautoposter-dialog-buttons">
                            <button class="button button-primary confirm">${xautoposter.strings.confirm}</button>
                            <button class="button cancel">${xautoposter.strings.cancel}</button>
                        </div>
                    </div>
                </div>
            `).appendTo('body');

            dialog.find('.confirm').on('click', () => {
                dialog.remove();
                resolve(true);
            });

            dialog.find('.cancel').on('click', () => {
                dialog.remove();
                resolve(false);
            });
        });
    }

    // API Settings
    $('#unlock-api-settings').on('click', async function(e) {
        e.preventDefault();
        
        const confirmed = await confirm(xautoposter.strings.confirm_unlock);
        if (!confirmed) return;

        const $button = $(this);
        setLoading($button, true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'xautoposter_reset_api_verification',
                nonce: xautoposter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('input[name^="xautoposter_options"]').prop('readonly', false);
                    $('.api-status-bar').slideUp();
                    showError(response.data.message, 'success');
                    $('input[type="submit"]').prop('disabled', false);
                } else {
                    showError(response.data.message || xautoposter.strings.error);
                }
            },
            error: function() {
                showError(xautoposter.strings.error);
            },
            complete: function() {
                setLoading($button, false);
            }
        });
    });

    // Posts Table
    var $postsTable = $('.posts-table');
    if ($postsTable.length) {
        // Select All
        $('#select-all-posts').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('input[name="posts[]"]:not(:disabled)').prop('checked', isChecked);
            updateShareButtonState();
        });

        // Individual Selections
        $postsTable.on('change', 'input[name="posts[]"]', function() {
            updateShareButtonState();
        });

        // Share Button State
        function updateShareButtonState() {
            var checkedCount = $('input[name="posts[]"]:checked').length;
            $('#share-selected')
                .prop('disabled', checkedCount === 0)
                .find('.count')
                .text(checkedCount ? ` (${checkedCount})` : '');
        }

        // Share Posts
        $('#share-selected').on('click', async function() {
            const $button = $(this);
            const posts = $('input[name="posts[]"]:checked').map(function() {
                return $(this).val();
            }).get();

            if (posts.length === 0) {
                showError(xautoposter.strings.no_posts_selected);
                return;
            }

            const confirmed = await confirm(
                xautoposter.strings.confirm_share.replace('%d', posts.length)
            );
            if (!confirmed) return;

            setLoading($button, true);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'xautoposter_share_posts',
                    posts: posts,
                    nonce: xautoposter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showError(response.data.message, 'success');
                        location.reload();
                    } else {
                        showError(response.data.message || xautoposter.strings.error);
                    }
                },
                error: function() {
                    showError(xautoposter.strings.error);
                },
                complete: function() {
                    setLoading($button, false);
                }
            });
        });

        // Initialize button state
        updateShareButtonState();
    }

    // Filters
    $('#category-filter, #date-sort').on('change', function() {
        $(this).closest('form').submit();
    });

    // Keyboard Shortcuts
    $(document).on('keydown', function(e) {
        // Alt + S to share selected posts
        if (e.altKey && e.key === 's') {
            e.preventDefault();
            $('#share-selected:not(:disabled)').click();
        }
        
        // Alt + A to select all posts
        if (e.altKey && e.key === 'a') {
            e.preventDefault();
            $('#select-all-posts').click();
        }
    });
});