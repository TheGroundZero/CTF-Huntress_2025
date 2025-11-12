$("#submitEmoji").click(function() {
    const emoji = selectedEmoji;
    $.post('/setEmoji', { emoji: emoji }, function(response) {
        $('#currentEmoji').text(response.profileEmoji);
        $('#selectedEmoji').text(response.profileEmoji);
        document.querySelectorAll('.emoji-btn').forEach(btn => {
            btn.classList.remove('selected-emoji');
            if (btn.textContent === response.profileEmoji) {
                btn.classList.add('selected-emoji');
            }
        });
        showNotification('Emoji updated successfully!', 'success');
    }).fail(function() {
        showNotification('Failed to update emoji. Please try again.', 'error');
    });
});

function showNotification(message, type) {
    const notification = $(`
        <div class="alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `);
    
    $('body').append(notification);
    
    setTimeout(() => {
        notification.alert('close');
    }, 3000);
}
