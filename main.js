// Subscribe to newsletter
async function subscribeToNewsletter(email, name = '') {
    const formData = new FormData();
    formData.append('email', email);
    formData.append('name', name);
    
    try {
        const response = await fetch('/lgk/api/subscribe.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('✅ ' + result.message, 'success');
        } else {
            showToast('❌ ' + result.message, 'error');
        }
        
        return result;
    } catch (error) {
        showToast('❌ Network error. Please try again.', 'error');
        return { success: false, message: 'Network error' };
    }
}
// Subscribe to newsletter
async function subscribeToNewsletter(email, name = '') {
    const formData = new FormData();
    formData.append('email', email);
    formData.append('name', name);
    
    try {
        const response = await fetch('/lgk/api/subscribe.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('✅ ' + result.message, 'success');
        } else {
            showToast('❌ ' + result.message, 'error');
        }
        
        return result;
    } catch (error) {
        showToast('❌ Network error. Please try again.', 'error');
        return { success: false, message: 'Network error' };
    }
}
// Check system status
async function checkSystemStatus() {
    try {
        const response = await fetch('/lgk/api/check.php?action=status');
        const data = await response.json();
        
        console.log('System Status:', data);
        return data;
    } catch (error) {
        console.error('Status check failed:', error);
        return null;
    }
}
// API Logout
async function apiLogout() {
    try {
        const response = await fetch('/lgk/api/logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = '/lgk/admin/login.php?logout=success';
        }
        
        return result;
    } catch (error) {
        console.error('Logout failed:', error);
        return { success: false };
    }
}
// Check if user is authenticated
async function checkAuth() {
    try {
        const response = await fetch('/lgk/api/check.php?action=auth');
        const data = await response.json();
        
        return data.authenticated;
    } catch (error) {
        return false;
    }
}
// Get system statistics (requires authentication)
async function getSystemStats() {
    try {
        const response = await fetch('/lgk/api/check.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            return data.stats;
        }
        return null;
    } catch (error) {
        console.error('Failed to get stats:', error);
        return null;
    }
}
// Newsletter subscription handler
document.addEventListener('DOMContentLoaded', function() {
    const newsletterForm = document.getElementById('newsletterForm');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[name="email"]');
            const name = this.querySelector('input[name="name"]');
            const consent = this.querySelector('input[name="consent"]');
            const submitBtn = this.querySelector('button[type="submit"]');
            const messageContainer = document.getElementById('newsletterMessage');
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subscribing...';
            
            // Clear previous messages
            if (messageContainer) {
                messageContainer.className = 'message-container';
                messageContainer.textContent = '';
            }
            
            try {
                const formData = new FormData();
                formData.append('email', email.value.trim());
                formData.append('name', name ? name.value.trim() : '');
                formData.append('consent', consent ? consent.checked : false);
                formData.append('source', 'footer');
                
                const response = await fetch('/lgk/api/subscribe.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage(result.message, 'success');
                    email.value = '';
                    if (name) name.value = '';
                    if (consent) consent.checked = false;
                    
                    // Trigger success animation
                    if (submitBtn) {
                        submitBtn.style.background = '#00ffa6';
                        submitBtn.textContent = '✅ Subscribed!';
                        setTimeout(() => {
                            submitBtn.style.background = '';
                        }, 3000);
                    }
                } else {
                    showMessage(result.message || 'Subscription failed. Please try again.', 'error');
                }
            } catch (error) {
                console.error('Subscription error:', error);
                showMessage('Network error. Please check your connection and try again.', 'error');
            } finally {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Subscribe';
            }
        });
    }
    
    function showMessage(message, type) {
        const container = document.getElementById('newsletterMessage');
        if (container) {
            container.textContent = message;
            container.className = 'message-container ' + type;
            container.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                container.style.display = 'none';
            }, 5000);
        }
    }
});
// In main.js - Popup newsletter
document.addEventListener('DOMContentLoaded', function() {
    // Show popup after 5 seconds
    setTimeout(function() {
        showNewsletterPopup();
    }, 5000);
    
    // Show popup on exit intent
    document.addEventListener('mouseleave', function(e) {
        if (e.clientY < 0) {
            showNewsletterPopup();
        }
    });
});

function showNewsletterPopup() {
    // Check if already shown
    if (sessionStorage.getItem('newsletter_shown')) return;
    
    // Create popup
    const popup = document.createElement('div');
    popup.className = 'newsletter-popup';
    popup.innerHTML = `
        <div class="popup-overlay"></div>
        <div class="popup-content">
            <button class="popup-close">&times;</button>
            <div class="popup-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h3>Subscribe to Our Newsletter</h3>
            <p>Get the latest tech tips and exclusive offers.</p>
            
            <form class="newsletter-form" id="newsletterFormPopup" method="POST" action="/lgk/api/subscribe.php">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Your email address" required>
                </div>
                <button type="submit" class="btn-primary">Subscribe Now</button>
                <div id="newsletterMessagePopup" class="message-container"></div>
                <div class="form-group checkbox" style="display:none;">
                    <label>
                        <input type="checkbox" name="consent" value="1" checked>
                        I agree to receive marketing emails
                    </label>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(popup);
    
    // Close popup
    popup.querySelector('.popup-close').addEventListener('click', function() {
        popup.remove();
        sessionStorage.setItem('newsletter_shown', 'true');
    });
    
    popup.querySelector('.popup-overlay').addEventListener('click', function() {
        popup.remove();
        sessionStorage.setItem('newsletter_shown', 'true');
    });
}