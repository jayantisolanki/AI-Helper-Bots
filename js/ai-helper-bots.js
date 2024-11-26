jQuery(document).ready(function ($) {
    const botMessages = $('#ai-bot-messages');

    $('#ai-bot-send').on('click', function () {
        const userMessage = $('#ai-bot-input').val();
        if (!userMessage) return;

        botMessages.append('<div class="user-message">' + userMessage + '</div>');
        $('#ai-bot-input').val('');

        $.post(aiHelperBotsData.ajax_url, {
            action: 'ai_helper_bots_query',
            security: aiHelperBotsData.nonce,
            message: userMessage,
        })
        .done(function (response) {
            if (response.success) {
                botMessages.append('<div class="bot-message">' + response.data.message + '</div>');
            } else {
                botMessages.append('<div class="bot-message">Error: ' + response.data.message + '</div>');
            }
        })
        .fail(function () {
            botMessages.append('<div class="bot-message">Error connecting to the server.</div>');
        });
    });
});
