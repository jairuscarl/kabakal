/**
 * NetSkills - Main JavaScript
 * Modern networking tutorial platform
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initNavigation();
    initDropdowns();
    initFlashMessages();
    initSmoothScroll();
    initAnimations();
    initFormValidation();
    initSearchFilter();
    initProgressTracking();
    initCursorGlow();
    initLogoutConfirmation();
});

function initLogoutConfirmation() {
    const logoutLinks = document.querySelectorAll('[data-confirm-logout="true"]');
    if (!logoutLinks.length) return;

    let modal = document.getElementById('logoutConfirmModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'logoutConfirmModal';
        modal.className = 'logout-modal';
        modal.innerHTML = `
            <div class="logout-modal-card" role="dialog" aria-modal="true" aria-labelledby="logoutConfirmTitle">
                <h3 id="logoutConfirmTitle">Are you sure you want to logout?</h3>
                <div class="logout-modal-actions">
                    <button type="button" class="btn btn-secondary" id="logoutCancelBtn">Cancel</button>
                    <a href="/logout.php" class="btn btn-danger" id="logoutConfirmBtn">Logout</a>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    const cancelBtn = modal.querySelector('#logoutCancelBtn');
    const closeModal = () => modal.classList.remove('show');

    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            modal.classList.add('show');
        });
    });

    cancelBtn?.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
}

/**
 * Navigation Toggle for Mobile
 */
function initNavigation() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
            
            // Toggle hamburger animation
            const hamburger = this.querySelector('.hamburger');
            if (hamburger) {
                hamburger.classList.toggle('active');
            }
            
            // Close menu when clicking outside
            document.addEventListener('click', function closeMenu(e) {
                if (!navMenu.contains(e.target) && !navToggle.contains(e.target)) {
                    navMenu.classList.remove('active');
                    navToggle.classList.remove('active');
                    document.removeEventListener('click', closeMenu);
                }
            });
        });
    }
}

/**
 * Dropdown Menus
 */
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown');
    
    dropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        
        if (toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Close other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('active');
                    }
                });
                
                // Toggle current dropdown
                dropdown.classList.toggle('active');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    });
}

/**
 * Flash Messages Auto-dismiss
 */
function initFlashMessages() {
    const flashMessage = document.getElementById('flashMessage');
    
    if (flashMessage) {
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            flashMessage.style.opacity = '0';
            flashMessage.style.transform = 'translateY(-20px)';
            setTimeout(() => flashMessage.remove(), 300);
        }, 5000);
    }
}

/**
 * Smooth Scrolling for Anchor Links
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            
            if (target) {
                const navHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

/**
 * Scroll Animations
 */
function initAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe elements - use CSS class for initial hidden state instead of inline style
    const animateElements = document.querySelectorAll('.course-card, .stat-card, .testimonial-card, .section-header');
    animateElements.forEach(el => {
        // Check if element is already visible in viewport
        const rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
            // Already visible - no animation needed, ensure it's shown
            el.classList.add('animate-visible');
        } else {
            // Not yet visible - add hidden class and observe
            el.classList.add('animate-hidden');
            observer.observe(el);
        }
    });
}

/**
 * Cursor-following glow effect
 */
function initCursorGlow() {
    const isTouchDevice = window.matchMedia('(pointer: coarse)').matches || 'ontouchstart' in window;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (isTouchDevice || prefersReducedMotion) return;

    const glow = document.createElement('div');
    glow.className = 'cursor-glow';
    document.body.appendChild(glow);

    let mouseX = window.innerWidth / 2;
    let mouseY = window.innerHeight / 2;
    let currentX = mouseX;
    let currentY = mouseY;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
        glow.classList.add('active');
    });

    document.addEventListener('mouseleave', () => {
        glow.classList.remove('active');
    });

    function animateGlow() {
        currentX += (mouseX - currentX) * 0.14;
        currentY += (mouseY - currentY) * 0.14;

        glow.style.transform = `translate(${currentX}px, ${currentY}px) translate(-50%, -50%)`;
        requestAnimationFrame(animateGlow);
    }

    animateGlow();
}

/**
 * Form Validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                // Add loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                // Remove error state on input
                this.classList.remove('error');
                const errorMsg = this.parentElement.querySelector('.error-message');
                if (errorMsg) errorMsg.remove();
            });
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Required check
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }
    
    // Password validation
    if (field.type === 'password' && value) {
        if (value.length < 6) {
            isValid = false;
            errorMessage = 'Password must be at least 6 characters';
        }
    }
    
    // Confirm password
    if (field.id === 'confirm_password' && value) {
        const password = document.getElementById('password');
        if (password && value !== password.value) {
            isValid = false;
            errorMessage = 'Passwords do not match';
        }
    }
    
    // Show/hide error
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        clearFieldError(field);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('error');
    field.style.borderColor = 'var(--error)';
    const errorContainer = field.closest('.form-group') || field.parentElement;
    
    // Remove existing error message
    const existingError = errorContainer.querySelector('.error-message');
    if (existingError) existingError.remove();
    
    // Add new error message
    const errorDiv = document.createElement('small');
    errorDiv.className = 'error-message';
    errorDiv.style.color = 'var(--error)';
    errorDiv.style.fontSize = 'var(--text-xs)';
    errorDiv.style.marginTop = 'var(--spacing-xs)';
    errorDiv.style.display = 'block';
    errorDiv.textContent = message;
    errorContainer.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('error');
    field.style.borderColor = '';
    const errorContainer = field.closest('.form-group') || field.parentElement;
    const errorMsg = errorContainer.querySelector('.error-message');
    if (errorMsg) errorMsg.remove();
}

/**
 * Search and Filter Functionality
 */
function initSearchFilter() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const difficultyFilter = document.getElementById('difficultyFilter');
    const courseCards = document.querySelectorAll('.course-card');
    
    // Exit if no filters exist on this page
    if (!searchInput && !categoryFilter && !difficultyFilter) {
        return;
    }
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterCourses, 300));
    }
    
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterCourses);
    }
    
    if (difficultyFilter) {
        difficultyFilter.addEventListener('change', filterCourses);
    }
    
    function filterCourses() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const category = categoryFilter ? categoryFilter.value : '';
        const difficulty = difficultyFilter ? difficultyFilter.value : '';
        
        let visibleCount = 0;
        
        courseCards.forEach(card => {
            // Safely get the title element
            const titleElement = card.querySelector('.course-title');
            if (!titleElement) {
                card.style.display = '';
                visibleCount++;
                return;
            }
            
            const title = titleElement.textContent.toLowerCase();
            const cardCategory = card.dataset.category || '';
            const cardDifficulty = card.dataset.difficulty || '';
            
            const matchesSearch = !searchTerm || title.includes(searchTerm);
            const matchesCategory = !category || cardCategory === category;
            const matchesDifficulty = !difficulty || cardDifficulty === difficulty;
            
            if (matchesSearch && matchesCategory && matchesDifficulty) {
                card.style.display = '';
                card.style.opacity = '1';
                card.style.transform = '';
                card.style.animation = 'fadeInUp 0.3s ease forwards';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show empty state if no results
        const grid = document.querySelector('.course-grid');
        if (grid) {
            // Remove existing empty state first
            const existingEmpty = grid.parentElement.querySelector('.empty-state');
            
            if (visibleCount === 0) {
                if (!existingEmpty) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-state';
                    emptyDiv.innerHTML = `
                        <i class="fas fa-search"></i>
                        <h3>No courses found</h3>
                        <p>Try adjusting your search or filters</p>
                        <a href="tutorials.php" class="btn btn-primary" style="margin-top: var(--spacing-lg);">
                            <i class="fas fa-redo"></i> Reset Filters
                        </a>
                    `;
                    grid.parentElement.appendChild(emptyDiv);
                }
            } else if (existingEmpty) {
                existingEmpty.remove();
            }
        }
    }
}

/**
 * Progress Tracking
 */
function initProgressTracking() {
    const progressBars = document.querySelectorAll('.progress-fill');
    
    progressBars.forEach(bar => {
        const percentage = bar.dataset.progress || bar.style.width;
        animateProgress(bar, parseInt(percentage));
    });
}

function animateProgress(bar, target) {
    let current = 0;
    const increment = target / 50;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        bar.style.width = current + '%';
    }, 20);
}

/**
 * Complete Lesson Function
 */
function completeLesson(lessonId, courseId) {
    fetch('api/complete-lesson.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `lesson_id=${lessonId}&course_id=${courseId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const lessonElement = document.querySelector(`[data-lesson-id="${lessonId}"]`);
            if (lessonElement) {
                lessonElement.classList.add('completed');
                lessonElement.querySelector('.check-icon').style.display = 'block';
            }
            
            // Update progress bar
            updateCourseProgress(courseId);
            
            // Show success message
            showToast('Lesson completed!', 'success');
            
            // Check if course is complete
            if (data.courseCompleted) {
                showToast('Congratulations! Course completed!', 'success');
                // Redirect to certificate page
                setTimeout(() => {
                    window.location.href = `certificate.php?course_id=${courseId}`;
                }, 2000);
            }
        } else {
            showToast(data.message || 'Failed to complete lesson', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred', 'error');
    });
}

/**
 * Update Course Progress
 */
function updateCourseProgress(courseId) {
    fetch(`api/get-progress.php?course_id=${courseId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const progressBar = document.querySelector(`#progress-${courseId} .progress-fill`);
            const progressText = document.querySelector(`#progress-${courseId} .progress-text`);
            
            if (progressBar) {
                progressBar.style.width = data.progress + '%';
            }
            if (progressText) {
                progressText.textContent = data.progress + '% complete';
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

/**
 * Toast Notifications
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;
    
    toastContainer.appendChild(toast);
    
    // Animate in
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    `;
    document.body.appendChild(container);
    return container;
}

/**
 * Debounce Function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Star Rating Input
 */
function initStarRating() {
    const starContainers = document.querySelectorAll('.star-rating-input');
    
    starContainers.forEach(container => {
        const stars = container.querySelectorAll('.star');
        const input = container.querySelector('input[type="hidden"]');
        
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = star.dataset.value;
                input.value = rating;
                
                stars.forEach(s => {
                    if (s.dataset.value <= rating) {
                        s.classList.add('active');
                        s.querySelector('i').classList.remove('far');
                        s.querySelector('i').classList.add('fas');
                    } else {
                        s.classList.remove('active');
                        s.querySelector('i').classList.remove('fas');
                        s.querySelector('i').classList.add('far');
                    }
                });
            });
            
            star.addEventListener('mouseenter', () => {
                const rating = star.dataset.value;
                stars.forEach(s => {
                    if (s.dataset.value <= rating) {
                        s.querySelector('i').classList.remove('far');
                        s.querySelector('i').classList.add('fas');
                    }
                });
            });
            
            star.addEventListener('mouseleave', () => {
                const currentRating = input.value;
                stars.forEach(s => {
                    if (s.dataset.value > currentRating) {
                        s.querySelector('i').classList.remove('fas');
                        s.querySelector('i').classList.add('far');
                    }
                });
            });
        });
    });
}

/**
 * Image Preview for File Upload
 */
function initImagePreview() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

/**
 * Copy to Clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Failed to copy:', err);
        showToast('Failed to copy', 'error');
    });
}

/**
 * Print Certificate
 */
function printCertificate() {
    window.print();
}

/**
 * Download Certificate as PDF
 */
function downloadCertificate(certificateId) {
    window.location.href = `api/download-certificate.php?id=${certificateId}`;
}

// Initialize star rating and image preview when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initStarRating();
    initImagePreview();
});