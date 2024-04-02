jQuery(document).ready(function ($) {
    $('.vote-btn').on('click', function (e) {
        e.preventDefault();

        var $this = $(this); // The button that was clicked
        var post_id = $this.data('post_id');
        var vote = $this.data('vote');
        var nonce = $this.data('nonce');

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_vote',
                post_id: post_id,
                vote: vote,
                nonce: nonce,
            },
            success: function (response) {
                if (response.success) {
                    // Update the page with the new vote count or percentage
                    $('#thank-you-for-your-feedback').html(
                        '<div class="thank-you-feedback-label">Thank you for your feedback.</div>' +
                            '<div class="vote-results">' +
                            '<div class="vote-result ' +
                            response.data.yes_selected +
                            '">' +
                            '<span class="emoji-face happy-face"></span> ' +
                            response.data.yes_percentage_txt +
                            '</div>' +
                            '<div class="vote-result ' +
                            response.data.no_selected +
                            '">' +
                            '<span class="emoji-face unhappy-face"></span> ' +
                            response.data.no_percentage_txt +
                            '</div>' +
                            '</div>'
                    );
                } else {
                    // Handle errors
                    alert(
                        'There was a problem with your vote. Please try again.'
                    );
                }
            },
            error: function () {
                alert('There was an error with the AJAX request.');
            },
        });
    });
});
