<?php
// contact.php - LGK Tech Solutions Contact Page
$page_title = "Contact Us - LGK Tech Solutions";
include "includes/header.php";

// Handle form submission
$success = false;
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include "config/database.php";
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($message)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Save to database
        $name = mysqli_real_escape_string($conn, $name);
        $email = mysqli_real_escape_string($conn, $email);
        $phone = mysqli_real_escape_string($conn, $phone);
        $subject = mysqli_real_escape_string($conn, $subject);
        $message = mysqli_real_escape_string($conn, $message);
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $query = "INSERT INTO feedback (name, email, phone, service_type, message, status, ip_address, created_at) 
                  VALUES ('$name', '$email', '$phone', '$subject', '$message', 'pending', '$ip', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $success = true;
            
            // Optional: Send email notification
            $to = "glenklaisa@gmail.com";
            $email_subject = "New Contact Message from $name";
            $email_body = "Name: $name\n";
            $email_body .= "Email: $email\n";
            $email_body .= "Phone: $phone\n";
            $email_body .= "Subject: $subject\n\n";
            $email_body .= "Message:\n$message\n";
            
            mail($to, $email_subject, $email_body, "From: noreply@lgktech.com");
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}
?>

<!-- ============================================================
     CONTACT HERO
     ============================================================ -->
<section class="contact-hero">
    <div class="contact-hero-content">
        <span class="section-tag">Get In Touch</span>
        <h1>Let's <span class="highlight">Connect</span></h1>
        <p>
            Have a question, project idea, or need support? 
            We're here to help. Reach out to us today.
        </p>
    </div>
</section>

<!-- ============================================================
     CONTACT SECTION
     ============================================================ -->
<section class="contact-section">
    <div class="contact-container">
        
        <!-- Contact Info -->
        <div class="contact-info-side">
            <h2>Contact Information</h2>
            <p>
                We'd love to hear from you. Here's how you can reach us:
            </p>
            
            <div class="contact-methods">
                <div class="contact-method">
                    <div class="method-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div>
                        <span class="method-label">Phone</span>
                        <a href="tel:0714468889">0714 468 889</a>
                    </div>
                </div>
                
                <div class="contact-method">
                    <div class="method-icon whatsapp">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div>
                        <span class="method-label">WhatsApp</span>
                        <a href="https://wa.me/254714468889" target="_blank">Chat with us</a>
                    </div>
                </div>
                
                <div class="contact-method">
                    <div class="method-icon email">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <span class="method-label">Email</span>
                        <a href="mailto:glenklaisa@gmail.com">glenklaisa@gmail.com</a>
                    </div>
                </div>
                
                <div class="contact-method">
                    <div class="method-icon location">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <span class="method-label">Location</span>
                        <span>Nairobi, Kenya</span>
                    </div>
                </div>
            </div>
            
            <div class="contact-hours">
                <h3><i class="fas fa-clock"></i> Business Hours</h3>
                <div class="hours-grid">
                    <div>
                        <span>Monday - Friday</span>
                        <span class="time">8:00 AM - 6:00 PM</span>
                    </div>
                    <div>
                        <span>Saturday</span>
                        <span class="time">9:00 AM - 2:00 PM</span>
                    </div>
                    <div>
                        <span>Sunday</span>
                        <span class="time">Closed</span>
                    </div>
                    <div>
                        <span>Emergency Support</span>
                        <span class="time">24/7 Available</span>
                    </div>
                </div>
            </div>
            
            <div class="contact-social-links">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        
        <!-- Contact Form -->
        <div class="contact-form-side">
            <div class="form-card">
                <h2>Send Us a Message</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Message Sent!</strong>
                            <p>Thank you for contacting us. We'll get back to you within 24 hours.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Error!</strong>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="contact-form" id="contactForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" placeholder="Your full name" required 
                                   value="<?php echo $_POST['name'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" placeholder="Your email address" required
                                   value="<?php echo $_POST['email'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="Your phone number"
                                   value="<?php echo $_POST['phone'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <select id="subject" name="subject">
                                <option value="">Select a subject</option>
                                <option value="general" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                                <option value="website" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'website') ? 'selected' : ''; ?>>Website Development</option>
                                <option value="repair" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'repair') ? 'selected' : ''; ?>>Computer Repair</option>
                                <option value="cybersecurity" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'cybersecurity') ? 'selected' : ''; ?>>Cybersecurity</option>
                                <option value="cloud" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'cloud') ? 'selected' : ''; ?>>Cloud Solutions</option>
                                <option value="server" <?php echo (isset($_POST['subject']) && $_POST['subject'] == 'server') ? 'selected' : ''; ?>>Server Installation</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message <span class="required">*</span></label>
                        <textarea id="message" name="message" rows="5" placeholder="Tell us about your project or question..." required><?php echo $_POST['message'] ?? ''; ?></textarea>
                        <div class="char-counter">
                            <span id="charCount">0</span> / 1000 characters
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     MAP SECTION
     ============================================================ -->
<section class="map-section">
    <div class="map-container">
        <div class="map-placeholder">
            <div class="map-content">
                <i class="fas fa-map-marked-alt"></i>
                <h3>Find Us Here</h3>
                <p>Nairobi, Kenya</p>
                <!-- Google Maps Embed (Optional) -->
                <div class="map-embed">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d255282.35853743783!2d36.68219672567943!3d-1.3028611!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f1172d84d49a7%3A0xf7cf0254b297924c!2sNairobi%2C%20Kenya!5e0!3m2!1sen!2s!4v1700000000000" 
                        width="100%" 
                        height="300" 
                        style="border:0;border-radius:12px;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* ============================================================
   CONTACT PAGE STYLES
   ============================================================ */

/* Contact Hero */
.contact-hero {
    padding: 60px 30px 40px;
    text-align: center;
    background: linear-gradient(180deg, rgba(0, 212, 255, 0.03), transparent);
}

.contact-hero-content h1 {
    font-size: 42px;
    font-weight: 700;
    margin: 10px 0 15px;
}

.contact-hero-content h1 .highlight {
    background: linear-gradient(135deg, #00d4ff, #00ffa6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.contact-hero-content p {
    color: #a0aab4;
    font-size: 18px;
    max-width: 500px;
    margin: 0 auto;
}

/* Contact Section */
.contact-section {
    padding: 40px 30px 80px;
    max-width: 1200px;
    margin: 0 auto;
}

.contact-container {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 50px;
}

/* Contact Info Side */
.contact-info-side h2 {
    font-size: 28px;
    margin-bottom: 10px;
}

.contact-info-side > p {
    color: #a0aab4;
    margin-bottom: 30px;
}

.contact-methods {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 30px;
}

.contact-method {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 12px;
    transition: all 0.3s ease;
}

.contact-method:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(0, 212, 255, 0.15);
    transform: translateX(5px);
}

.method-icon {
    width: 45px;
    height: 45px;
    background: rgba(0, 212, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #00d4ff;
    font-size: 18px;
    flex-shrink: 0;
}

.method-icon.whatsapp { background: rgba(37, 211, 102, 0.1); color: #25d366; }
.method-icon.email { background: rgba(255, 165, 0, 0.1); color: #ffa500; }
.method-icon.location { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; }

.method-label {
    display: block;
    font-size: 11px;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contact-method a,
.contact-method span {
    color: #ffffff;
    text-decoration: none;
    font-size: 14px;
}

.contact-method a:hover {
    color: #00d4ff;
}

/* Business Hours */
.contact-hours {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.contact-hours h3 {
    font-size: 16px;
    margin-bottom: 15px;
}

.contact-hours h3 i {
    color: #00d4ff;
    margin-right: 8px;
}

.hours-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.hours-grid > div {
    display: flex;
    flex-direction: column;
    font-size: 13px;
}

.hours-grid > div span:first-child {
    color: #a0aab4;
}

.hours-grid > div .time {
    color: #ffffff;
    font-weight: 500;
}

/* Social Links */
.contact-social-links h3 {
    font-size: 16px;
    margin-bottom: 10px;
}

.social-icons {
    display: flex;
    gap: 10px;
}

.social-icons a {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #a0aab4;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-icons a:hover {
    background: rgba(0, 212, 255, 0.1);
    color: #00d4ff;
    transform: translateY(-3px);
}

/* Form Side */
.form-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 16px;
    padding: 35px;
}

.form-card h2 {
    font-size: 24px;
    margin-bottom: 20px;
}

/* Alerts */
.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.alert-success {
    background: rgba(0, 255, 166, 0.1);
    border: 1px solid rgba(0, 255, 166, 0.2);
    color: #00ffa6;
}

.alert-error {
    background: rgba(255, 77, 77, 0.1);
    border: 1px solid rgba(255, 77, 77, 0.2);
    color: #ff4d4d;
}

.alert i {
    font-size: 20px;
    margin-top: 2px;
}

.alert strong {
    display: block;
}

.alert p {
    margin: 0;
    font-size: 13px;
    color: #a0aab4;
}

/* Form */
.contact-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.contact-form .form-group {
    margin-bottom: 15px;
}

.contact-form label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 5px;
    color: #a0aab4;
}

.contact-form label .required {
    color: #ff4d4d;
}

.contact-form input,
.contact-form select,
.contact-form textarea {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 10px;
    color: #ffffff;
    font-size: 14px;
    transition: all 0.3s ease;
    font-family: inherit;
}

.contact-form input:focus,
.contact-form select:focus,
.contact-form textarea:focus {
    outline: none;
    border-color: #00d4ff;
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
}

.contact-form select option {
    background: #0b1224;
    color: #ffffff;
}

.contact-form textarea {
    resize: vertical;
    min-height: 100px;
}

.contact-form .char-counter {
    text-align: right;
    font-size: 12px;
    color: #4a5568;
    margin-top: 5px;
}

.contact-form .btn-primary {
    width: 100%;
    padding: 14px;
    font-size: 16px;
}

/* Map Section */
.map-section {
    padding: 0 30px 80px;
    max-width: 1200px;
    margin: 0 auto;
}

.map-placeholder {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 16px;
    overflow: hidden;
}

.map-content {
    padding: 30px;
    text-align: center;
}

.map-content i {
    font-size: 48px;
    color: #00d4ff;
    margin-bottom: 10px;
}

.map-content h3 {
    font-size: 24px;
    margin-bottom: 5px;
}

.map-content p {
    color: #a0aab4;
    margin-bottom: 20px;
}

.map-embed {
    max-width: 100%;
    overflow: hidden;
    border-radius: 12px;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 1024px) {
    .contact-container {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
}

@media (max-width: 768px) {
    .contact-container {
        grid-template-columns: 1fr;
    }
    
    .contact-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .hours-grid {
        grid-template-columns: 1fr;
    }
    
    .form-card {
        padding: 25px;
    }
    
    .contact-hero-content h1 {
        font-size: 32px;
    }
}

@media (max-width: 480px) {
    .contact-method {
        padding: 12px;
    }
    
    .form-card {
        padding: 20px;
    }
    
    .map-content {
        padding: 20px;
    }
}
</style>

<?php include "includes/footer.php"; ?>