
    jQuery(document).ready(function($) {
        $('#ai-send-button').on('click', function() {
            var message = $('#ai-chat-input').val();
            if (!message) return;

            $('.chat-body').append('<div><b>You:</b> ' + message + '</div>');
            $('#ai-chat-input').val('');

            $.post(aiHelperBots.ajaxUrl, {
                action: 'ai_helper_bots_request',
                nonce: aiHelperBots.nonce,
                message: message
            }, function(response) {
                if (response.success) {
                    $('.chat-body').append('<div><b>Bot:</b> ' + response.data.message + '</div>');
                } else {
                    $('.chat-body').append('<div><b>Bot:</b> Something went wrong.</div>');
                }
                $('.chat-body').scrollTop($('.chat-body')[0].scrollHeight);
            });
        });
    });
    