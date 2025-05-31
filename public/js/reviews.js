/**
 * Reviews System for Captain's Brew Cafe
 */

const ReviewSystem = {
    /**
     * Initialize the review system
     */
    init: function() {
        this.setupReviewModal();
        this.setupRatingStars();
        this.setupReviewForm();
    },
    
    /**
     * Set up the review modal
     */
    setupReviewModal: function() {
        // Open review modal
        document.querySelectorAll('.open-review-modal').forEach(button => {
            button.addEventListener('click', function() {
                const itemId = this.getAttribute('data-item-id');
                const itemName = this.getAttribute('data-item-name');
                
                // Set item details in modal
                document.getElementById('review-item-id').value = itemId;
                document.getElementById('review-item-name').textContent = itemName;
                
                // Reset form
                document.getElementById('review-form').reset();
                document.querySelectorAll('.rating-star').forEach(star => {
                    star.classList.remove('active');
                });
                
                // Check if user has already reviewed this item
                ReviewSystem.checkExistingReview(itemId);
                
                // Show modal
                document.getElementById('review-modal').style.display = 'flex';
            });
        });
        
        // Close review modal
        document.querySelectorAll('.close-review-modal').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('review-modal').style.display = 'none';
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('review-modal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    },
    
    /**
     * Set up rating stars functionality
     */
    setupRatingStars: function() {
        const stars = document.querySelectorAll('.rating-star');
        
        stars.forEach((star, index) => {
            // Hover effect
            star.addEventListener('mouseover', function() {
                // Highlight current star and all stars before it
                for (let i = 0; i <= index; i++) {
                    stars[i].classList.add('hover');
                }
            });
            
            star.addEventListener('mouseout', function() {
                // Remove hover class from all stars
                stars.forEach(s => s.classList.remove('hover'));
            });
            
            // Click to select rating
            star.addEventListener('click', function() {
                const rating = index + 1;
                document.getElementById('rating').value = rating;
                
                // Update star display
                stars.forEach((s, i) => {
                    if (i < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
    },
    
    /**
     * Set up review form submission
     */
    setupReviewForm: function() {
        const form = document.getElementById('review-form');
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const itemId = document.getElementById('review-item-id').value;
            const rating = document.getElementById('rating').value;
            const comment = document.getElementById('comment').value;
            
            if (!rating) {
                Swal.fire({
                    icon: 'error',
                    title: 'Rating Required',
                    text: 'Please select a rating before submitting.',
                    confirmButtonColor: '#2C6E8A'
                });
                return;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('rating', rating);
            formData.append('comment', comment);
            
            // Show loading state
            Swal.fire({
                title: 'Submitting...',
                text: 'Please wait while we submit your review.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit form
            fetch('/controllers/handle-review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thank You!',
                        text: data.message,
                        confirmButtonColor: '#2C6E8A'
                    }).then(() => {
                        // Close modal
                        document.getElementById('review-modal').style.display = 'none';
                        
                        // Reload reviews if on item detail page
                        if (typeof loadItemReviews === 'function') {
                            loadItemReviews(itemId);
                        }
                        
                        // Update menu card rating if on menu page
                        const ratingElement = document.querySelector(`.menu-card[data-item-id="${itemId}"] .menu-card-rating`);
                        if (ratingElement) {
                            // Refresh the page to show updated ratings
                            location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Submission Failed',
                        text: data.message,
                        confirmButtonColor: '#2C6E8A'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Submission Failed',
                    text: 'There was an error submitting your review. Please try again.',
                    confirmButtonColor: '#2C6E8A'
                });
            });
        });
    },
    
    /**
     * Check if user has already reviewed this item
     * @param {number} itemId - The ID of the menu item
     */
    checkExistingReview: function(itemId) {
        fetch(`/controllers/get-reviews.php?item_id=${itemId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.user_review) {
                    const userReview = data.data.user_review;
                    
                    // Set existing rating
                    document.getElementById('rating').value = userReview.rating;
                    
                    // Update star display
                    const stars = document.querySelectorAll('.rating-star');
                    stars.forEach((star, index) => {
                        if (index < userReview.rating) {
                            star.classList.add('active');
                        } else {
                            star.classList.remove('active');
                        }
                    });
                    
                    // Set existing comment
                    document.getElementById('comment').value = userReview.comment || '';
                    
                    // Update submit button text
                    document.querySelector('#review-form button[type="submit"]').textContent = 'Update Review';
                }
            })
            .catch(error => {
                console.error('Error checking existing review:', error);
            });
    },
    
    /**
     * Load reviews for a menu item
     * @param {number} itemId - The ID of the menu item
     * @param {HTMLElement} container - The container to display reviews in
     */
    loadReviews: function(itemId, container) {
        fetch(`/controllers/get-reviews.php?item_id=${itemId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const reviewData = data.data;
                    
                    // Update average rating display
                    const avgRatingElement = container.querySelector('.avg-rating');
                    if (avgRatingElement) {
                        avgRatingElement.textContent = reviewData.avg_rating;
                    }
                    
                    // Update review count display
                    const reviewCountElement = container.querySelector('.review-count');
                    if (reviewCountElement) {
                        reviewCountElement.textContent = reviewData.review_count;
                    }
                    
                    // Update reviews list
                    const reviewsList = container.querySelector('.reviews-list');
                    if (reviewsList) {
                        if (reviewData.reviews.length === 0) {
                            reviewsList.innerHTML = '<div class="no-reviews">No reviews yet. Be the first to review!</div>';
                        } else {
                            reviewsList.innerHTML = '';
                            
                            reviewData.reviews.forEach(review => {
                                const reviewElement = document.createElement('div');
                                reviewElement.className = 'review-item';
                                
                                // Create stars HTML
                                let starsHtml = '';
                                for (let i = 1; i <= 5; i++) {
                                    if (i <= review.rating) {
                                        starsHtml += '<i class="fas fa-star"></i>';
                                    } else {
                                        starsHtml += '<i class="far fa-star"></i>';
                                    }
                                }
                                
                                reviewElement.innerHTML = `
                                    <div class="review-header">
                                        <img src="${review.profile_image}" alt="${review.username}" class="review-avatar">
                                        <div class="review-user-info">
                                            <div class="review-username">${review.username}</div>
                                            <div class="review-date">${review.date}</div>
                                        </div>
                                    </div>
                                    <div class="review-rating">${starsHtml}</div>
                                    ${review.comment ? `<div class="review-comment">${review.comment}</div>` : ''}
                                `;
                                
                                reviewsList.appendChild(reviewElement);
                            });
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error loading reviews:', error);
                if (container) {
                    container.innerHTML = '<div class="error-message">Failed to load reviews.</div>';
                }
            });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    ReviewSystem.init();
}); 