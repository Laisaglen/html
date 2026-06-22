<?php
// includes/footer.php - Complete footer with newsletter form

$current_year = date('Y');
?>

<footer class="lgk-footer" role="contentinfo">
    <div class="footer-container">
        
        <!-- =========================
             FOOTER TOP
             ========================= -->
        <div class="footer-top">
            <!-- Brand Column -->
            <div class="footer-col brand-col">
                <div class="footer-logo">
                    <i class="fas fa-cogs"></i>
                    <span>LGK<span class="highlight">Tech</span></span>
                </div>
                <p class="footer-description">
                    Smart IT solutions for modern businesses. We deliver excellence 
                    in web development, cybersecurity, cloud services, computer 
                    repair, and data analytics.
                </p>
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="footer-col">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="/-/index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="/-/about.php"><i class="fas fa-chevron-right"></i> About</a></li>
                    <li><a href="/-/services.php"><i class="fas fa-chevron-right"></i> Services</a></li>
                    <li><a href="/-/contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    <li><a href="/-/feedback.php"><i class="fas fa-chevron-right"></i> Feedback</a></li>
                </ul>
            </div>
            
            <!-- Services -->
            <div class="footer-col">
                <h4 class="footer-title">Our Services</h4>
                <ul class="footer-links">
                    <li><a href="/-/services.php#web-development"><i class="fas fa-chevron-right"></i> Web Development</a></li>
                    <li><a href="/-/services.php#computer-repair"><i class="fas fa-chevron-right"></i> Computer Repair</a></li>
                    <li><a href="/-/services.php#data-analytics"><i class="fas fa-chevron-right"></i> Data Analytics</a></li>
                    <li><a href="/-/services.php#cybersecurity"><i class="fas fa-chevron-right"></i> Cybersecurity</a></li>
                    <li><a href="/-/services.php#cloud-solutions"><i class="fas fa-chevron-right"></i> Cloud Solutions</a></li>
                </ul>
            </div>
            
            <!-- Contact -->
            <div class="footer-col contact-col">
                <h4 class="footer-title">Get in Touch</h4>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-phone"></i>
                        <div>
                            <span class="label">Phone</span>
                            <a href="tel:0714468889">0714 468 889</a>
                        </div>
                    </li>
                    <li>
                        <i class="fab fa-whatsapp"></i>
                        <div>
                            <span class="label">WhatsApp</span>
                            <a href="https://wa.me/254714468889" target="_blank">Chat Now</a>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <div>
                            <span class="label">Email</span>
                            <a href="mailto:glenklaisa@gmail.com">glenklaisa@gmail.com</a>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <span class="label">Location</span>
                            <span>Nairobi, Kenya</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- =========================
             FOOTER MIDDLE - NEWSLETTER FORM
             ========================= -->
        <div class="footer-middle">
            <div class="newsletter-wrapper">
                <div class="newsletter-text">
                    <i class="fas fa-envelope-open-text"></i>
                    <div>
                        <h4>Subscribe to Our Newsletter</h4>
                        <p>Get the latest updates and offers directly to your inbox.</p>
                    </div>
                </div>
                
                <!-- ✅ NEWSLETTER FORM - PLACED HERE -->
                <form class="newsletter-form" id="newsletterForm" method="POST" action="/lgk/api/subscribe.php">
                    <div class="form-group">
                        <input type="email" name="email" id="newsletterEmail" 
                               placeholder="Enter your email address" required>
                    </div>
                    <div class="form-group" style="display:none;">
                        <input type="text" name="name" id="newsletterName" 
                               placeholder="Your name (optional)">
                    </div>
                    <div class="form-group checkbox" style="display:none;">
                        <label>
                            <input type="checkbox" name="consent" value="1" checked>
                            I agree to receive marketing emails
                        </label>
                    </div>
                    <button type="submit" class="subscribe-btn">
                        <i class="fas fa-paper-plane"></i> Subscribe
                    </button>
                    <div id="newsletterMessage" class="message-container"></div>
                </form>
            </div>
        </div>
        
        <!-- =========================
             FOOTER BOTTOM
             ========================= -->
        <div class="footer-bottom">
            <div class="footer-bottom-left">
                <p>
                    &copy; <?php echo $current_year; ?> 
                    <a href="/-/index.php">LGK Tech Solutions</a>. 
                    All rights reserved.
                </p>
            </div>
            <div class="footer-bottom-right">
                <a href="/-/privacy-policy.php">Privacy Policy</a>
                <span class="divider">|</span>
                <a href="/-/terms-of-service.php">Terms of Service</a>
                <span class="divider">|</span>
                <a href="/-/cookie-policy.php">Cookie Policy</a>
            </div>
        </div>
        
    </div>
</footer>

<!-- Scroll to Top Button -->
<button class="scroll-top-btn" id="scrollTopBtn" aria-label="Scroll to top">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Footer JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // ================================
    // NEWSLETTER SUBSCRIPTION
    // ================================
    const newsletterForm = document.getElementById('newsletterForm');
    const newsletterMessage = document.getElementById('newsletterMessage');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = this.querySelector('input[name="email"]');
            const name = this.querySelector('input[name="name"]');
            const consent = this.querySelector('input[name="consent"]');
            const submitBtn = this.querySelector('.subscribe-btn');
            const originalText = submitBtn.innerHTML;
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subscribing...';
            
            try {
                const formData = new FormData();
                formData.append('email', email.value.trim());
                formData.append('name', name ? name.value.trim() : '');
                formData.append('consent', consent ? consent.checked : false);
                formData.append('source', 'footer');
                
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNewsletterMessage('✅ ' + result.message, 'success');
                    email.value = '';
                    if (name) name.value = '';
                    if (consent) consent.checked = false;
                } else {
                    showNewsletterMessage('❌ ' + (result.message || 'Subscription failed. Please try again.'), 'error');
                }
            } catch (error) {
                showNewsletterMessage('❌ Network error. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
    
    function showNewsletterMessage(message, type) {
        if (newsletterMessage) {
            newsletterMessage.textContent = message;
            newsletterMessage.className = 'message-container ' + type;
            newsletterMessage.style.display = 'block';
            
            setTimeout(() => {
                newsletterMessage.style.display = 'none';
            }, 5000);
        }
    }
    
    // ================================
    // SCROLL TO TOP
    // ================================
    const scrollBtn = document.getElementById('scrollTopBtn');
    if (scrollBtn) {
        window.addEventListener('scroll', function() {
            scrollBtn.classList.toggle('visible', window.scrollY > 500);
        });
        
        scrollBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
});
</script>
</body>
</html>