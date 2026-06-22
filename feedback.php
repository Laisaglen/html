<?php
// feedback.php - LGK Tech Solutions Feedback Page
$page_title = "Feedback - LGK Tech Solutions";
include "includes/header.php";

// ============================================================
// HANDLE FORM SUBMISSION
// ============================================================
$success = false;
$error = '';
$submitted_data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include "config/database.php";
    
    // Get and sanitize input
    $name = trim(mysqli_real_escape_string($conn, $_POST['name'] ?? ''));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email'] ?? ''));
    $phone = trim(mysqli_real_escape_string($conn, $_POST['phone'] ?? ''));
    $service = trim(mysqli_real_escape_string($conn, $_POST['service'] ?? ''));
    $message = trim(mysqli_real_escape_string($conn, $_POST['message'] ?? ''));
    $rating = intval($_POST['rating'] ?? 0);
    
    // Store for repopulating form
    $submitted_data = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'service' => $service,
        'message' => $message,
        'rating' => $rating
    ];
    
    // ============================================================
    // VALIDATION
    // ============================================================
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Full name is required.";
    } elseif (strlen($name) > 100) {
        $errors[] = "Name is too long (maximum 100 characters).";
    }
    
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($message)) {
        $errors[] = "Feedback message is required.";
    } elseif (strlen($message) > 2000) {
        $errors[] = "Message is too long (maximum 2000 characters).";
    }
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a rating.";
    }
    
    // ============================================================
    // IF NO ERRORS - SAVE TO DATABASE
    // ============================================================
    if (empty($errors)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $query = "INSERT INTO feedback 
                  (name, email, phone, service_type, message, rating, status, ip_address, user_agent, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssisss", $name, $email, $phone, $service, $message, $rating, $ip, $user_agent);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
            $submitted_data = []; // Clear form data on success
            
            // Optional: Send notification email
            $admin_email = 'glenklaisa@gmail.com';
            $subject = "New Feedback from $name";
            $body = "Name: $name\n";
            $body .= "Email: $email\n";
            $body .= "Phone: $phone\n";
            $body .= "Service: $service\n";
            $body .= "Rating: " . str_repeat('⭐', $rating) . " ($rating/5)\n\n";
            $body .= "Message:\n$message\n";
            
            // Use mail() or PHPMailer if configured
            // mail($admin_email, $subject, $body, "From: noreply@lgktech.com");
            
        } else {
            $error = "Failed to submit feedback. Please try again.";
            error_log("Feedback submission failed: " . mysqli_error($conn));
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $error = implode("<br>", $errors);
    }
    
    mysqli_close($conn);
}

// ============================================================
// FETCH RECENT FEEDBACK FOR DISPLAY
// ============================================================
include "config/database.php";
$recent_query = "SELECT * FROM feedback WHERE status = 'replied' ORDER BY created_at DESC LIMIT 5";
$recent_result = mysqli_query($conn, $recent_query);
?>

<!-- ============================================================
     FEEDBACK HERO
     ============================================================ -->
<section class="feedback-hero">
    <div class="feedback-hero-content">
        <span class="section-tag">We Value Your Opinion</span>
        <h1>Share Your <span class="highlight">Feedback</span></h1>
        <p>
            Your feedback helps us improve and deliver better services. 
            Let us know about your experience with LGK Tech Solutions.
        </p>
    </div>
</section>

<!-- ============================================================
     FEEDBACK SECTION
     ============================================================ -->
<section class="feedback-section">
    <div class="feedback-container">
        
        <!-- Feedback Form -->
        <div class="feedback-form-wrapper">
            <div class="form-card">
                <h2><i class="fas fa-star"></i> Rate Your Experience</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Thank You!</strong>
                            <p>Your feedback has been submitted successfully. We appreciate your input!</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Error!</strong>
                            <p><?php echo $error; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="feedback-form" id="feedbackForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" placeholder="Your full name" required
                                   value="<?php echo htmlspecialchars($submitted_data['name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" placeholder="Your email address" required
                                   value="<?php echo htmlspecialchars($submitted_data['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="Your phone number (optional)"
                                   value="<?php echo htmlspecialchars($submitted_data['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="service">Service Used</label>
                            <select id="service" name="service">
                                <option value="">Select a service</option>
                                <option value="website" <?php echo (isset($submitted_data['service']) && $submitted_data['service'] == 'website') ? 'selected' : ''; ?>>Website Development</option>
                                <option value="repair" <?php echo (isset($submitted_data['service']) && $submitted_data['service'] == 'repair') ? 'selected' : ''; ?>>Computer Repair</option>
                                <option value="analytics" <?php echo (isset($submitted_data['service']) && $submitted_data['service'] == 'analytics') ? 'selected' : ''; ?>>Data Analytics</option>
                                <option value="cybersecurity" <?php echo (isset($submitted_data['service']) && $submitted_data['service'] == 'cybersecurity') ? 'selected' : ''; ?>>Cybersecurity</option>
                                <option value="cloud" <?php echo (isset($submitted_data['service']) && $submitted_data['service'] == 'cloud') ? 'selected' : ''; ?>>Cloud Solutions</option>
                                <option value="server" <?php echo (isset($submitted_data['service']) && $submitted_data['service'] == 'server') ? 'selected' : ''; ?>>Server Installation</option>
                                <option value="other" <?php echo (isset($submitted_data['service']) && $submitted_data['service'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Star Rating -->
                    <div class="form-group rating-group">
                        <label>Rating <span class="required">*</span></label>
                        <div class="star-rating">
                            <input type="hidden" name="rating" id="rating" value="<?php echo $submitted_data['rating'] ?? 0; ?>" required>
                            <div class="stars" id="stars">
                                <span class="star" data-value="1" <?php echo (($submitted_data['rating'] ?? 0) >= 1) ? 'style="color:#ffd700;"' : ''; ?>>☆</span>
                                <span class="star" data-value="2" <?php echo (($submitted_data['rating'] ?? 0) >= 2) ? 'style="color:#ffd700;"' : ''; ?>>☆</span>
                                <span class="star" data-value="3" <?php echo (($submitted_data['rating'] ?? 0) >= 3) ? 'style="color:#ffd700;"' : ''; ?>>☆</span>
                                <span class="star" data-value="4" <?php echo (($submitted_data['rating'] ?? 0) >= 4) ? 'style="color:#ffd700;"' : ''; ?>>☆</span>
                                <span class="star" data-value="5" <?php echo (($submitted_data['rating'] ?? 0) >= 5) ? 'style="color:#ffd700;"' : ''; ?>>☆</span>
                            </div>
                            <div class="rating-label" id="ratingLabel">
                                <?php
                                $rating_labels = [
                                    1 => 'Poor - Needs improvement',
                                    2 => 'Fair - Average experience',
                                    3 => 'Good - Satisfied',
                                    4 => 'Very Good - Impressed',
                                    5 => 'Excellent - Outstanding!'
                                ];
                                echo ($submitted_data['rating'] ?? 0) > 0 ? ($rating_labels[$submitted_data['rating']] ?? 'Select a rating') : 'Select a rating';
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Feedback <span class="required">*</span></label>
                        <textarea id="message" name="message" rows="5" placeholder="Tell us about your experience..." required><?php echo htmlspecialchars($submitted_data['message'] ?? ''); ?></textarea>
                        <div class="char-counter">
                            <span id="charCount"><?php echo strlen($submitted_data['message'] ?? ''); ?></span> / 500 characters
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Recent Feedback -->
        <div class="recent-feedback-side">
            <h2><i class="fas fa-comment-dots"></i> What Others Say</h2>
            
            <?php if ($recent_result && mysqli_num_rows($recent_result) > 0): ?>
                <div class="recent-feedback-list">
                    <?php while($row = mysqli_fetch_assoc($recent_result)): ?>
                        <div class="recent-feedback-item">
                            <div class="feedback-header">
                                <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                <div class="stars-display">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $row['rating'] ? 'filled' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p><?php echo htmlspecialchars(substr($row['message'], 0, 120)) . (strlen($row['message']) > 120 ? '...' : ''); ?></p>
                            <span class="feedback-date"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-feedback">
                    <i class="fas fa-inbox"></i>
                    <p>No feedback yet. Be the first to share your experience!</p>
                </div>
            <?php endif; ?>
            
            <div class="feedback-cta">
                <p>Your feedback matters!</p>
                <a href="#feedbackForm" class="btn-secondary" style="display:inline-block;padding:8px 20px;border-radius:8px;background:rgba(255,255,255,0.05);color:#fff;text-decoration:none;">
                    <i class="fas fa-edit"></i> Leave Feedback
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     STYLES (Feedback Page Specific)
     ============================================================ -->
<style>
/* ============================================================
   FEEDBACK PAGE STYLES
   ============================================================ */

.feedback-hero {
    padding: 60px 30px 40px;
    text-align: center;
    background: linear-gradient(180deg, rgba(0, 212, 255, 0.03), transparent);
}

.feedback-hero-content h1 {
    font-size: 42px;
    font-weight: 700;
    margin: 10px 0 15px;
}

.feedback-hero-content h1 .highlight {
    background: linear-gradient(135deg, #00d4ff, #00ffa6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.feedback-hero-content p {
    color: #a0aab4;
    font-size: 18px;
    max-width: 500px;
    margin: 0 auto;
}

/* Feedback Section */
.feedback-section {
    padding: 40px 30px 80px;
    max-width: 1200px;
    margin: 0 auto;
}

.feedback-container {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 50px;
}

/* Form Styles */
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

.form-card h2 i {
    color: #ffd700;
    margin-right: 8px;
}

.feedback-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.feedback-form .form-group {
    margin-bottom: 15px;
}

.feedback-form label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 5px;
    color: #a0aab4;
}

.feedback-form label .required {
    color: #ff4d4d;
}

.feedback-form input,
.feedback-form select,
.feedback-form textarea {
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

.feedback-form input:focus,
.feedback-form select:focus,
.feedback-form textarea:focus {
    outline: none;
    border-color: #00d4ff;
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
}

.feedback-form select option {
    background: #0b1224;
    color: #ffffff;
}

.feedback-form textarea {
    resize: vertical;
    min-height: 100px;
}

.feedback-form .char-counter {
    text-align: right;
    font-size: 12px;
    color: #4a5568;
    margin-top: 5px;
}

/* Star Rating */
.rating-group {
    text-align: center;
}

.star-rating {
    padding: 10px 0;
}

.stars {
    display: flex;
    justify-content: center;
    gap: 15px;
    font-size: 40px;
    cursor: pointer;
}

.star {
    color: #4a5568;
    transition: all 0.3s ease;
    user-select: none;
}

.star:hover {
    transform: scale(1.2);
}

.rating-label {
    font-size: 14px;
    color: #a0aab4;
    margin-top: 10px;
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

/* Recent Feedback Side */
.recent-feedback-side h2 {
    font-size: 24px;
    margin-bottom: 20px;
}

.recent-feedback-side h2 i {
    color: #ffd700;
    margin-right: 8px;
}

.recent-feedback-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.recent-feedback-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 12px;
    padding: 20px;
    transition: all 0.3s ease;
}

.recent-feedback-item:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(0, 212, 255, 0.1);
}

.recent-feedback-item .feedback-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.recent-feedback-item .feedback-header strong {
    font-size: 15px;
}

.recent-feedback-item .stars-display .fa-star {
    color: #4a5568;
}

.recent-feedback-item .stars-display .fa-star.filled {
    color: #ffd700;
}

.recent-feedback-item p {
    color: #a0aab4;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 8px;
}

.recent-feedback-item .feedback-date {
    font-size: 11px;
    color: #4a5568;
}

.empty-feedback {
    text-align: center;
    padding: 40px 20px;
    color: #4a5568;
}

.empty-feedback i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.3;
}

.feedback-cta {
    margin-top: 20px;
    padding: 20px;
    background: rgba(0, 212, 255, 0.05);
    border: 1px solid rgba(0, 212, 255, 0.1);
    border-radius: 12px;
    text-align: center;
}

.feedback-cta p {
    color: #a0aab4;
    margin-bottom: 10px;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 1024px) {
    .feedback-container {
        grid-template-columns: 1fr 1fr;
        gap: 30px;
    }
}

@media (max-width: 768px) {
    .feedback-container {
        grid-template-columns: 1fr;
    }
    
    .feedback-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-card {
        padding: 25px;
    }
    
    .feedback-hero-content h1 {
        font-size: 32px;
    }
    
    .stars {
        font-size: 32px;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .form-card {
        padding: 20px;
    }
    
    .recent-feedback-item .feedback-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .stars {
        font-size: 28px;
        gap: 8px;
    }
}
</style>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================================
    // STAR RATING
    // ============================================================
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('rating');
    const ratingLabel = document.getElementById('ratingLabel');
    
    let selectedRating = parseInt(ratingInput.value) || 0;
    
    // Initialize stars
    updateStars(selectedRating);
    
    stars.forEach(star => {
        star.addEventListener('mouseenter', function() {
            const value = parseInt(this.dataset.value);
            updateStars(value);
        });
        
        star.addEventListener('mouseleave', function() {
            updateStars(selectedRating);
        });
        
        star.addEventListener('click', function() {
            selectedRating = parseInt(this.dataset.value);
            ratingInput.value = selectedRating;
            updateStars(selectedRating);
            
            const labels = {
                1: 'Poor - Needs improvement',
                2: 'Fair - Average experience',
                3: 'Good - Satisfied',
                4: 'Very Good - Impressed',
                5: 'Excellent - Outstanding!'
            };
            ratingLabel.textContent = labels[selectedRating] || 'Select a rating';
            ratingLabel.style.color = selectedRating > 0 ? '#00ffa6' : '#a0aab4';
        });
    });
    
    function updateStars(value) {
        stars.forEach(s => {
            const starValue = parseInt(s.dataset.value);
            if (starValue <= value) {
                s.textContent = '⭐';
                s.style.color = '#ffd700';
            } else {
                s.textContent = '☆';
                s.style.color = '#4a5568';
            }
        });
    }
    
    // ============================================================
    // CHARACTER COUNTER
    // ============================================================
    const messageInput = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            
            if (count > 450) {
                charCount.style.color = '#ff4d4d';
            } else {
                charCount.style.color = '#4a5568';
            }
            
            if (count >= 500) {
                this.value = this.value.substring(0, 500);
                charCount.textContent = 500;
            }
        });
    }
    
    // ============================================================
    // FORM SUBMISSION
    // ============================================================
    const form = document.getElementById('feedbackForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            if (selectedRating === 0) {
                e.preventDefault();
                ratingLabel.textContent = '⚠️ Please select a rating';
                ratingLabel.style.color = '#ff4d4d';
                document.querySelector('.rating-group').scrollIntoView({ behavior: 'smooth' });
                return false;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        });
    }
});

console.log('🚀 Feedback page loaded successfully!');
</script>

<?php include "includes/footer.php"; ?>