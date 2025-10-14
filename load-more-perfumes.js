jQuery(document).ready(function($) {
    $('#load-more-perfumes').on('click', function() {
        var button = $(this);
        var page = parseInt(button.attr('data-page')) + 1;

        // Disable button to prevent multiple clicks
        button.prop('disabled', true).text('Loading...');

        $.ajax({
            url: perfume_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'load_more_perfumes',
                page: page
            },
            success: function(response) {
                if ($.trim(response) !== '') {
                    // Append new perfumes to the grid
                    $('#perfume-grid').append(response);

                    // Replace button with "VIEW CATALOGUE" link
                    button.replaceWith('<a id="view-catalogue" href="/catalogue" class="view-catalogue-button" style="margin:20px auto;display:block;text-align:center;">VIEW CATALOGUE</a>');
                } else {
                    // No more posts – replace button immediately
                    button.replaceWith('<a id="view-catalogue" href="/catalogue" class="view-catalogue-button" style="margin:20px auto;display:block;text-align:center;">VIEW CATALOGUE</a>');
                }
            },
            error: function() {
                // In case of AJAX error, re-enable the button
                button.prop('disabled', false).text('Load More');
            }
        });
    });
});
