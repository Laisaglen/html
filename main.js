$(document).ready(function() {
    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Like post functionality
    $('.like-btn').click(function() {
        const postId = $(this).data('post-id');
        const btn = $(this);
        
        $.ajax({
            url: 'ajax/like_post.php',
            method: 'POST',
            data: { post_id: postId },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    const likeCount = btn.find('.like-count');
                    likeCount.text(data.likes);
                    btn.toggleClass('liked');
                }
            }
        });
    });

    // Share post functionality
    $('.share-btn').click(function() {
        const postId = $(this).data('post-id');
        const btn = $(this);
        
        $.ajax({
            url: 'ajax/share_post.php',
            method: 'POST',
            data: { post_id: postId },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    const shareCount = btn.find('.share-count');
                    shareCount.text(data.shares);
                    alert('Post shared successfully!');
                }
            }
        });
    });

    // Friend request functionality
    $('.add-friend-btn').click(function() {
        const userId = $(this).data('user-id');
        const btn = $(this);
        
        $.ajax({
            url: 'ajax/add_friend.php',
            method: 'POST',
            data: { user_id: userId },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    btn.text('Friend Request Sent');
                    btn.prop('disabled', true);
                    btn.removeClass('btn-primary').addClass('btn-success');
                }
            }
        });
    });

    // Chat functionality
    function loadMessages(receiverId) {
        $.ajax({
            url: 'ajax/get_messages.php',
            method: 'GET',
            data: { receiver_id: receiverId },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    const messagesContainer = $('#chat-messages');
                    messagesContainer.html(data.html);
                    messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
                }
            }
        });
    }

    // Send message
    $('#send-message-btn').click(function() {
        const receiverId = $('#receiver-id').val();
        const message = $('#message-input').val();
        const fileInput = $('#file-input')[0];
        const formData = new FormData();

        formData.append('receiver_id', receiverId);
        formData.append('message', message);
        
        if (fileInput.files.length > 0) {
            formData.append('file', fileInput.files[0]);
        }

        $.ajax({
            url: 'ajax/send_message.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $('#message-input').val('');
                    $('#file-input').val('');
                    loadMessages(receiverId);
                }
            }
        });
    });

    // Real-time message polling
    let lastMessageId = 0;
    function pollMessages() {
        const receiverId = $('#receiver-id').val();
        if (receiverId) {
            $.ajax({
                url: 'ajax/poll_messages.php',
                method: 'GET',
                data: { 
                    receiver_id: receiverId,
                    last_id: lastMessageId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success && data.new_messages) {
                        loadMessages(receiverId);
                    }
                }
            });
        }
    }

    // Poll every 3 seconds
    setInterval(pollMessages, 3000);

    // Image preview before upload
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(previewId).attr('src', e.target.result).show();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    $('#profile-photo-input').change(function() {
        previewImage(this, '#profile-photo-preview');
    });

    $('#cover-photo-input').change(function() {
        previewImage(this, '#cover-photo-preview');
    });

    // Market search
    $('#search-products').on('keyup', function() {
        const searchTerm = $(this).val();
        $.ajax({
            url: 'ajax/search_products.php',
            method: 'GET',
            data: { search: searchTerm },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $('#product-grid').html(data.html);
                }
            }
        });
    });

    // Friend search
    $('#search-friends').on('keyup', function() {
        const searchTerm = $(this).val();
        $.ajax({
            url: 'ajax/search_friends.php',
            method: 'GET',
            data: { search: searchTerm },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $('#friends-list').html(data.html);
                }
            }
        });
    });

    // Settings navigation
    $('.settings-tab').click(function() {
        const tab = $(this).data('tab');
        $('.settings-tab').removeClass('active');
        $(this).addClass('active');
        $('.settings-panel').hide();
        $('#' + tab + '-panel').show();
    });

    // Auto-expire counter for posts
    function updateExpiryTimes() {
        $('.post-expiry').each(function() {
            const expiryTime = new Date($(this).data('expiry')).getTime();
            const now = new Date().getTime();
            const diff = expiryTime - now;
            
            if (diff > 0) {
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                $(this).text(`Expires in ${hours}h ${minutes}m`);
            } else {
                $(this).text('Expired');
                $(this).closest('.post-card').fadeOut();
            }
        });
    }

    // Update expiry times every minute
    setInterval(updateExpiryTimes, 60000);
    updateExpiryTimes();

    // Admin report generation
    $('#generate-report-btn').click(function() {
        $.ajax({
            url: 'ajax/generate_report.php',
            method: 'GET',
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    window.open(data.report_url, '_blank');
                }
            }
        });
    });

    // Video upload validation
    $('#video-input').change(function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 30 * 1024 * 1024) {
                alert('Video file size cannot exceed 30MB');
                this.value = '';
                return false;
            }
            if (!file.type.startsWith('video/')) {
                alert('Please select a valid video file');
                this.value = '';
                return false;
            }
        }
    });

    // Image upload validation
    $('.image-input').change(function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                alert('Image file size cannot exceed 5MB');
                this.value = '';
                return false;
            }
            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file');
                this.value = '';
                return false;
            }
        }
    });
});

// Notification functions
function showNotification(message, type = 'success') {
    const notification = $('<div class="notification ' + type + '">' + message + '</div>');
    $('body').append(notification);
    notification.fadeIn();
    setTimeout(function() {
        notification.fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}

// AJAX error handling
$(document).ajaxError(function(event, jqXHR, settings, error) {
    console.error('AJAX Error:', error);
    showNotification('An error occurred. Please try again.', 'error');
});