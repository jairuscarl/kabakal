<?php
require_once 'config/config.php';
require_once 'middleware/auth.php';
require_once 'models/Review.php';
require_once 'models/Course.php';

// Initialize models
$reviewModel = new Review($GLOBALS['db']);
$courseModel = new Course($GLOBALS['db']);

$errors = [];
$success = '';
$dailyPostLimit = 10;

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    verifyCSRF();
    $user = getCurrentUser();
    $todayPostCount = $reviewModel->getTodayPostCount($user['id']);

    if ($todayPostCount >= $dailyPostLimit) {
        $errors[] = "Daily limit reached. You can only post up to {$dailyPostLimit} comments/replies per day.";
    }

    $formAction = $_POST['form_action'] ?? 'create_review';

    if (empty($errors) && $formAction === 'create_reply') {
        $review_id = intval($_POST['review_id'] ?? 0);
        $reply_comment = sanitizeInput($_POST['reply_comment'] ?? '');

        if ($review_id <= 0) {
            $errors[] = "Invalid review selected for reply.";
        }

        if (empty($reply_comment)) {
            $errors[] = "Reply cannot be empty.";
        } elseif (strlen($reply_comment) < 2) {
            $errors[] = "Reply must be at least 2 characters long.";
        }

        if (empty($errors)) {
            $replyId = $reviewModel->createReply($review_id, $user['id'], $reply_comment);
            if ($replyId) {
                logActivity($GLOBALS['db'], $user['id'], 'review_reply_submitted', 'Submitted a review reply');
                $success = "Reply posted successfully.";
            } else {
                $errors[] = "Failed to post reply. Please try again.";
            }
        }
    }

    if (empty($errors) && $formAction === 'create_review') {
    
    $rating = intval($_POST['rating'] ?? 0);
    $comment = sanitizeInput($_POST['comment'] ?? '');
    $course_id = intval($_POST['course_id'] ?? 0);
    
    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a rating between 1 and 5.";
    }
    
    if (empty($comment)) {
        $errors[] = "Please write a review comment.";
    } elseif (strlen($comment) < 10) {
        $errors[] = "Review must be at least 10 characters long.";
    }
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['review_image']) && $_FILES['review_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['review_image'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, ALLOWED_IMAGE_TYPES)) {
            $errors[] = "Only JPG, PNG, and GIF images are allowed.";
        } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = "Image must be less than 5MB.";
        } else {
            if (!file_exists(UPLOAD_DIR . 'reviews/')) {
                mkdir(UPLOAD_DIR . 'reviews/', 0755, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $image_path = 'review_' . uniqid() . '.' . $extension;
            
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . 'reviews/' . $image_path)) {
                $errors[] = "Failed to upload image.";
                $image_path = null;
            }
        }
    }
    
    if (empty($errors)) {
        $review_id = $reviewModel->create($user['id'], $course_id ?: null, $rating, $comment, $image_path);
        
        if ($review_id) {
            $reviewModel->approve($review_id);
            logActivity($GLOBALS['db'], $user['id'], 'review_submitted', 'Submitted a review');
            $success = "Thank you for your review!";
        } else {
            $errors[] = "Failed to submit review. Please try again.";
        }
    }
    }
}

// Get reviews
$reviews = $reviewModel->getAll(null, 20, 0);
$reviewIds = array_map(function($r) { return (int)$r['id']; }, $reviews);
$repliesByReview = $reviewModel->getRepliesByReviewIds($reviewIds);

// Get user's liked reviews if logged in
$likedReviews = [];
if (isLoggedIn()) {
    $user = getCurrentUser();
    foreach ($reviews as $review) {
        if ($reviewModel->userHasLiked($review['id'], $user['id'])) {
            $likedReviews[] = $review['id'];
        }
    }
}

$userReviewCount = 0;
if (isLoggedIn()) {
    $user = getCurrentUser();
    $userReviews = $reviewModel->getUserReviews($user['id']);
    $userReviewCount = count($userReviews);
    $todayPostCount = $reviewModel->getTodayPostCount($user['id']);
} else {
    $todayPostCount = 0;
}

$page_title = 'Reviews & Feedback';
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="hero" style="min-height: 40vh;">
    <div class="container">
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fas fa-star"></i> Community Reviews
            </span>
            <h1>Reviews & Feedback</h1>
            <p>Read what our students have to say about their learning experience</p>
        </div>
    </div>
</section>

<!-- Reviews Section -->
<section class="section">
    <div class="container">
        <div class="review-layout">
            <!-- Share Your Experience (full width on top) -->
            <div style="margin-bottom: var(--spacing-2xl);">
                <div class="card">
                    <div class="card-body">
                        <h3 style="margin-bottom: var(--spacing-lg);">
                            <i class="fas fa-pen" style="color: var(--primary-light);"></i> Share Your Experience
                        </h3>
                        
                        <?php if (!isLoggedIn()): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <span>Please <a href="login.php">login</a> or <a href="register.php">register</a> to submit a review.</span>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" enctype="multipart/form-data" class="review-form">
                                <?php echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">'; ?>
                                <input type="hidden" name="form_action" value="create_review">
                                
                                <div class="form-group">
                                    <label>Rating</label>
                                    <div class="star-rating-input">
                                        <input type="hidden" name="rating" id="ratingInput" required>
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <label class="star" style="cursor: pointer;">
                                                <input type="radio" name="rating" value="<?php echo $i; ?>" style="display: none;" required>
                                                <i class="far fa-star" style="font-size: 1.5rem; color: var(--graphite); transition: color 0.2s;"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comment">Your Review</label>
                                    <textarea id="comment" name="comment" class="form-control" rows="5" 
                                              placeholder="Share your thoughts about your learning experience..."
                                              required minlength="10"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="review_image">Attach Image (Optional)</label>
                                    <input type="file" id="review_image" name="review_image" 
                                           accept="image/*" class="form-control"
                                           style="padding: var(--spacing-sm);">
                                    <small class="form-hint">JPG, PNG, or GIF. Max 5MB.</small>
                                    <img id="imagePreview" style="display: none; max-width: 100%; max-height: 150px; margin-top: var(--spacing-sm); border-radius: var(--radius-md);">
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-paper-plane"></i> Submit Review
                                </button>
                                
                                <p style="margin-top: var(--spacing-md); font-size: var(--text-xs); color: var(--text-muted); text-align: center;">
                                    Your review will be visible immediately!
                                </p>
                            </form>
                        <?php endif; ?>
                        
                        <?php if (isLoggedIn()): ?>
                            <hr style="border-color: var(--glass-border); margin: var(--spacing-xl) 0;">
                            
                            <h4 style="margin-bottom: var(--spacing-md);">Your Reviews</h4>
                            <p style="font-size: var(--text-sm); color: var(--text-secondary);">
                                You have submitted <strong><?php echo $userReviewCount; ?></strong> review<?php echo $userReviewCount !== 1 ? 's' : ''; ?>
                            </p>
                            <p style="font-size: var(--text-sm); color: var(--text-secondary); margin-top: var(--spacing-sm);">
                                Today: <strong><?php echo (int)$todayPostCount; ?>/<?php echo (int)$dailyPostLimit; ?></strong> comments/replies used
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Reviews List -->
            <div>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($reviews)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3>No reviews yet</h3>
                        <p>Be the first to share your experience!</p>
                    </div>
                <?php else: ?>
                    <div class="testimonials-grid" style="grid-template-columns: 1fr;">
                        <?php foreach ($reviews as $review): ?>
                        <div class="testimonial-card" style="margin-bottom: var(--spacing-xl);">
                            <div class="testimonial-content">
                                <?php if ($review['image_path']): ?>
                                    <div class="review-image-container" role="button" tabindex="0" aria-label="View attached image">
                                        <img src="public/uploads/reviews/<?php echo htmlspecialchars($review['image_path']); ?>" 
                                             alt="Review attachment"
                                             onerror="this.style.display='none'">
                                    </div>
                                <?php endif; ?>
                                
                                <p class="testimonial-text" style="position: relative; z-index: 1;">
                                    "<?php echo nl2br(htmlspecialchars($review['comment'])); ?>"
                                </p>
                                
                                <div class="testimonial-author">
                                <img src="public/uploads/avatars/<?php echo htmlspecialchars($review['profile_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($review['full_name']); ?>"
                                     class="testimonial-avatar"
                                     onerror="this.onerror=null;this.src='public/images/default-avatar.png'">
                                    <div class="testimonial-info">
                                        <h4><?php echo htmlspecialchars($review['full_name']); ?> (@<?php echo htmlspecialchars($review['username']); ?>)</h4>
                                        <span><?php echo formatDate($review['created_at']); ?></span>
                                        <?php if ($review['course_title']): ?>
                                            <span style="display: block; color: var(--primary-light); font-size: var(--text-xs);">
                                                <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($review['course_title']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: var(--spacing-sm);">
                                        <div class="testimonial-rating">
                                            <div class="star-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <button class="like-btn <?php echo in_array($review['id'], $likedReviews) ? 'liked' : ''; ?>" 
                                                data-review-id="<?php echo $review['id']; ?>"
                                                onclick="toggleLike(<?php echo $review['id']; ?>)">
                                            <i class="fas fa-heart"></i>
                                            <span class="like-count"><?php echo $review['like_count']; ?></span>
                                        </button>
                                    </div>
                                </div>

                                <?php $reviewReplies = $repliesByReview[(int)$review['id']] ?? []; ?>
                                <div class="review-replies">
                                    <?php foreach ($reviewReplies as $reply): ?>
                                        <div class="reply-item">
                                            <img src="public/uploads/avatars/<?php echo htmlspecialchars($reply['profile_image']); ?>"
                                                 alt="<?php echo htmlspecialchars($reply['full_name']); ?>"
                                                 class="reply-avatar"
                                                 onerror="this.onerror=null;this.src='public/images/default-avatar.png'">
                                            <div class="reply-content">
                                                <div class="reply-meta">
                                                    <strong><?php echo htmlspecialchars($reply['full_name']); ?></strong>
                                                    <span>@<?php echo htmlspecialchars($reply['username']); ?></span>
                                                    <span>• <?php echo formatDate($reply['created_at']); ?></span>
                                                </div>
                                                <p><?php echo nl2br(htmlspecialchars($reply['comment'])); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <?php if (isLoggedIn()): ?>
                                        <form method="POST" class="reply-form">
                                            <?php echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">'; ?>
                                            <input type="hidden" name="form_action" value="create_reply">
                                            <input type="hidden" name="review_id" value="<?php echo (int)$review['id']; ?>">
                                            <textarea name="reply_comment" class="form-control" rows="2" maxlength="500" placeholder="Write a reply..." required></textarea>
                                            <button type="submit" class="btn btn-sm btn-secondary" style="margin-top: var(--spacing-sm);">
                                                <i class="fas fa-reply"></i> Reply
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div class="review-image-lightbox" id="reviewImageLightbox" aria-hidden="true">
    <button type="button" class="review-image-lightbox-close" id="reviewImageLightboxClose" aria-label="Close image preview">×</button>
    <img src="" alt="Expanded review attachment" id="reviewImageLightboxImg">
</div>

<style>
.star-rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 4px;
}

.star-rating-input .star:hover,
.star-rating-input .star:hover ~ .star,
.star-rating-input .star input:checked ~ .star {
    color: var(--warning) !important;
}

.star-rating-input .star input:checked ~ .star i,
.star-rating-input .star:hover i,
.star-rating-input .star:hover ~ .star i {
    color: var(--warning);
}

.star-rating-input .star.active i {
    color: var(--warning);
}

.like-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-full);
    color: var(--text-muted);
    font-size: var(--text-sm);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.like-btn:hover {
    border-color: var(--error);
    color: var(--error);
}

.like-btn.liked {
    background: rgba(220, 53, 69, 0.1);
    border-color: var(--error);
    color: var(--error);
}

.like-btn.liked i {
    animation: heartBeat 0.3s ease;
}

.review-replies {
    margin-top: var(--spacing-md);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--glass-border);
}

.reply-item {
    display: flex;
    gap: var(--spacing-sm);
    margin-bottom: var(--spacing-sm);
}

.reply-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.reply-content {
    flex: 1;
    background: rgba(255,255,255,0.03);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-md);
    padding: var(--spacing-sm);
}

.reply-meta {
    display: flex;
    gap: 6px;
    font-size: var(--text-xs);
    color: var(--text-muted);
    margin-bottom: 4px;
}

.reply-content p {
    margin: 0;
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

.reply-form textarea {
    margin-top: var(--spacing-sm);
}

@keyframes heartBeat {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}
</style>
<style>
@media (max-width: 991px) {
    .review-layout { 
        display: flex;
        flex-direction: column;
    }
    .card[style] { position: relative !important; top: auto !important; }
}
</style>
<style>
@media (max-width: 1199px) {
    .review-layout {
        display: flex;
        flex-direction: column;
    }
    .review-layout > div:first-child {
        order: -1;
    }
}
</style>

<?php $isLoggedIn = isLoggedIn(); ?>
<script>
function toggleLike(reviewId) {
    <?php if (!$isLoggedIn): ?>
    window.location.href = 'login.php';
    return;
    <?php endif; ?>
    
    const btn = document.querySelector(`.like-btn[data-review-id="${reviewId}"]`);
    const isLiked = btn.classList.contains('liked');
    const action = isLiked ? 'unlike' : 'like';
    
    fetch('api/like-review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `review_id=${reviewId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.liked) {
                btn.classList.add('liked');
            } else {
                btn.classList.remove('liked');
            }
            btn.querySelector('.like-count').textContent = data.count;
        }
    })
    .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    // Ensure "Share Your Experience" card appears above reviews on tablet/mobile
    (function() {
        const reviewLayout = document.querySelector('.review-layout');
        if (!reviewLayout) return;
        const cards = Array.from(reviewLayout.children);
        for (let i = 0; i < cards.length; i++) {
            const h3 = cards[i].querySelector('h3');
            if (h3 && h3.textContent && h3.textContent.trim().includes('Share Your Experience')) {
                if (i !== 0) reviewLayout.insertBefore(cards[i], reviewLayout.firstChild);
                break;
            }
        }
    })();

    const stars = document.querySelectorAll('.star-rating-input .star');
    const ratingInput = document.getElementById('ratingInput');
    
    stars.forEach(star => {
        const radio = star.querySelector('input[type="radio"]');
        
        star.addEventListener('click', function() {
            ratingInput.value = radio.value;
            
            stars.forEach(s => {
                const sRadio = s.querySelector('input[type="radio"]');
                const icon = s.querySelector('i');
                
                if (parseInt(sRadio.value) <= parseInt(radio.value)) {
                    s.classList.add('active');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                } else {
                    s.classList.remove('active');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                }
            });
        });
        
        star.addEventListener('mouseenter', function() {
            stars.forEach(s => {
                const sRadio = s.querySelector('input[type="radio"]');
                const icon = s.querySelector('i');
                
                if (parseInt(sRadio.value) <= parseInt(radio.value)) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    icon.style.color = 'var(--warning)';
                }
            });
        });
        
        star.addEventListener('mouseleave', function() {
            stars.forEach(s => {
                const sRadio = s.querySelector('input[type="radio"]');
                const icon = s.querySelector('i');
                
                if (parseInt(sRadio.value) > parseInt(ratingInput.value)) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    icon.style.color = '';
                } else if (ratingInput.value) {
                    icon.style.color = 'var(--warning)';
                } else {
                    icon.style.color = '';
                }
            });
        });
    });
    
    const imageInput = document.getElementById('review_image');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });
    }

    const lightbox = document.getElementById('reviewImageLightbox');
    const lightboxImg = document.getElementById('reviewImageLightboxImg');
    const lightboxClose = document.getElementById('reviewImageLightboxClose');
    const reviewImageContainers = document.querySelectorAll('.review-image-container');

    if (lightbox && lightboxImg && lightboxClose) {
        reviewImageContainers.forEach(container => {
            const img = container.querySelector('img');
            if (!img) return;

            const openPreview = () => {
                lightboxImg.src = img.src;
                lightbox.classList.add('active');
                lightbox.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };

            container.addEventListener('click', openPreview);
            container.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openPreview();
                }
            });
        });

        const closePreview = () => {
            lightbox.classList.remove('active');
            lightbox.setAttribute('aria-hidden', 'true');
            lightboxImg.src = '';
            document.body.style.overflow = '';
        };

        lightboxClose.addEventListener('click', closePreview);
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) closePreview();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                closePreview();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>