/**
 * Notifications System for CuptainsBrew
 * Handles real-time notifications for both admin and users
 */

// Configuration
const NOTIFICATION_CHECK_INTERVAL = 120000; // Check for new notifications every 120 seconds (increased from 60s)
let notificationCheckTimer = null;
let lastNotificationCount = 0;
let notificationsEnabled = true; // Flag to enable/disable notifications

// DOM Elements
let notificationBell;
let notificationCounter;
let notificationDropdown;
let notificationList;

// Initialize the notification system
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing notification system...');
    initializeNotifications();
    
    // Check for performance mode in localStorage
    if (localStorage.getItem('performance_mode') === 'true') {
        disableNotifications();
    }
    
    // Initialize audio context for browsers with strict autoplay policies
    initializeAudioContext();
});

function initializeNotifications() {
    // Find notification elements in the DOM
    notificationBell = document.getElementById('notification-bell');
    notificationCounter = document.getElementById('notification-counter');
    notificationDropdown = document.getElementById('notification-dropdown');
    notificationList = document.getElementById('notification-list');
    
    if (!notificationBell || !notificationCounter || !notificationDropdown || !notificationList) {
        console.error('Notification elements not found in DOM:', {
            bell: !!notificationBell,
            counter: !!notificationCounter,
            dropdown: !!notificationDropdown,
            list: !!notificationList
        });
        return;
    }
    
    console.log('Notification elements found in DOM');
    
    // Set up event listeners
    notificationBell.addEventListener('click', toggleNotificationDropdown);
    
    // Add long press event to toggle performance mode
    let pressTimer;
    notificationBell.addEventListener('mousedown', function() {
        pressTimer = window.setTimeout(function() {
            togglePerformanceMode();
        }, 2000); // 2 second long press
    });
    
    notificationBell.addEventListener('mouseup', function() {
        clearTimeout(pressTimer);
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!notificationBell.contains(event.target) && !notificationDropdown.contains(event.target)) {
            notificationDropdown.classList.remove('active');
        }
    });
    
    // Only start checking for notifications if they're enabled
    if (notificationsEnabled) {
        // Initial check for notifications
        checkForNotifications();
        
        // Start periodic checks
        startNotificationTimer();
    }
}

function startNotificationTimer() {
    if (notificationCheckTimer) {
        clearInterval(notificationCheckTimer);
    }
    
    if (notificationsEnabled) {
        notificationCheckTimer = setInterval(checkForNotifications, NOTIFICATION_CHECK_INTERVAL);
    }
}

function toggleNotificationDropdown() {
    const isOpening = !notificationDropdown.classList.contains('active');
    notificationDropdown.classList.toggle('active');
    
    // If opening the dropdown and notifications are enabled, load notification details and mark as read
    if (isOpening && notificationsEnabled) {
        loadNotificationDetails();
        markAllNotificationsAsRead();
    } else if (isOpening && !notificationsEnabled) {
        // Show message that notifications are disabled
        notificationList.innerHTML = '<li class="no-notifications">Notifications are disabled for better performance</li>';
    }
}

function togglePerformanceMode() {
    if (notificationsEnabled) {
        disableNotifications();
        localStorage.setItem('performance_mode', 'true');
        alert('Performance mode enabled: Notifications are now disabled for better performance');
    } else {
        enableNotifications();
        localStorage.setItem('performance_mode', 'false');
        alert('Performance mode disabled: Notifications are now enabled');
    }
}

function disableNotifications() {
    notificationsEnabled = false;
    if (notificationCheckTimer) {
        clearInterval(notificationCheckTimer);
    }
    if (notificationBell) {
        notificationBell.classList.add('disabled');
    }
}

function enableNotifications() {
    notificationsEnabled = true;
    if (notificationBell) {
        notificationBell.classList.remove('disabled');
    }
    checkForNotifications();
    startNotificationTimer();
}

function checkForNotifications() {
    // Skip if notifications are disabled
    if (!notificationsEnabled) return;
    
    // Only check for unread count, not full details
    fetch('/controllers/get-notifications.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Only update the counter, not the full dropdown
                updateNotificationCounter(data.unread_count);
            }
        })
        .catch(error => {
            console.error('Error checking for notifications:', error);
        });
}

function loadNotificationDetails() {
    // Skip if notifications are disabled
    if (!notificationsEnabled) return;
    
    console.log('Loading notification details...');
    
    // Load full notification details when dropdown is opened
    fetch('/controllers/get-notifications.php?details=1')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Notification details received:', data);
            if (data.success) {
                updateNotificationList(data.notifications);
            }
        })
        .catch(error => {
            console.error('Error loading notification details:', error);
        });
}

function updateNotificationCounter(unreadCount) {
    // Update notification counter
    if (unreadCount > 0) {
        notificationCounter.textContent = unreadCount > 99 ? '99+' : unreadCount;
        notificationCounter.classList.add('active');
        
        // Play sound if the count has increased
        if (unreadCount > lastNotificationCount) {
            playNotificationSound();
        }
    } else {
        notificationCounter.textContent = '';
        notificationCounter.classList.remove('active');
    }
    
    // Store current count for comparison next time
    lastNotificationCount = unreadCount;
}

function updateNotificationList(notifications) {
    // Update notification list
    console.log('Updating notification list with:', notifications);
    notificationList.innerHTML = '';
    
    if (!notifications || notifications.length === 0) {
        console.log('No notifications to display');
        notificationList.innerHTML = '<li class="no-notifications">No notifications</li>';
    } else {
        console.log(`Displaying ${notifications.length} notifications`);
        notifications.forEach(notification => {
            console.log('Processing notification:', notification);
            const notificationItem = document.createElement('li');
            notificationItem.classList.add('notification-item');
            
            // Check if this is an "Out for Delivery" notification
            const isOutForDelivery = notification.message && 
                (notification.message.toLowerCase().includes('out for delivery') || 
                 (notification.status && notification.status.toLowerCase() === 'out for delivery') ||
                 (notification.title && notification.title.toLowerCase().includes('out for delivery')));
            
            console.log(`Notification #${notification.id} isOutForDelivery:`, isOutForDelivery);
            
            if (isOutForDelivery) {
                notificationItem.classList.add('urgent-notification');
            }
            
            if (!notification.is_read) {
                notificationItem.classList.add('unread');
            }
            
            // Create notification content
            let notificationContent = '';
            
            // Add title if available
            if (notification.title) {
                notificationContent += `<div class="notification-title">${escapeHtml(notification.title)}</div>`;
            }
            
            // Add message
            notificationContent += `<div class="notification-message">${escapeHtml(notification.message)}</div>`;
            
            // Add time
            const notificationTime = new Date(notification.created_at);
            const timeString = formatNotificationTime(notificationTime);
            notificationContent += `<div class="notification-time">${timeString}</div>`;
            
            // If it's an "Out for Delivery" notification, add a direct link to the order
            if (isOutForDelivery && notification.order_id) {
                notificationContent += `
                    <div class="notification-actions">
                        <a href="/views/users/User-Purchase.php?order_id=${notification.order_id}" class="view-order-btn">
                            View Order
                        </a>
                    </div>
                `;
            }
            
            // Set the content
            notificationItem.innerHTML = notificationContent;
            
            // Add click handler to mark as read
            notificationItem.addEventListener('click', function() {
                if (!notification.is_read) {
                    console.log(`Marking notification #${notification.id} as read`);
                    markNotificationAsRead(notification.id);
                    notificationItem.classList.remove('unread');
                }
            });
            
            notificationList.appendChild(notificationItem);
        });
    }
}

function markNotificationAsRead(notificationId) {
    const formData = new FormData();
    formData.append('notification_id', notificationId);
    
    fetch('/controllers/mark-notification-read.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            checkForNotifications(); // Refresh notifications
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

function markAllNotificationsAsRead() {
    const formData = new FormData();
    formData.append('mark_all', true);
    
    fetch('/controllers/mark-notification-read.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            checkForNotifications(); // Refresh notifications
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
    });
}

function playNotificationSound() {
    try {
        // Create a custom notification sound using Web Audio API
        // Use a context that works with user interaction to avoid autoplay restrictions
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        
        // Check if AudioContext is available
        if (!AudioContext) {
            console.warn('Web Audio API not supported in this browser');
            return;
        }
        
        // Create audio context on first interaction if not already created
        if (!window.notificationAudioContext) {
            window.notificationAudioContext = new AudioContext();
        }
        
        const audioContext = window.notificationAudioContext;
        
        // Check if context is in suspended state (happens in Safari, Chrome)
        if (audioContext.state === 'suspended') {
            audioContext.resume();
        }
        
        // Create an oscillator for the notification sound
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        // Configure the oscillator for a pleasant notification sound
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(659.25, audioContext.currentTime); // E5
        oscillator.frequency.setValueAtTime(783.99, audioContext.currentTime + 0.1); // G5
        oscillator.frequency.setValueAtTime(1046.50, audioContext.currentTime + 0.2); // C6
        
        // Configure the gain node for volume control with a nice fade out
        gainNode.gain.setValueAtTime(0.2, audioContext.currentTime);
        gainNode.gain.setValueAtTime(0.2, audioContext.currentTime + 0.2);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.6);
        
        // Connect the nodes
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        // Play the sound
        oscillator.start();
        oscillator.stop(audioContext.currentTime + 0.6);
        
        console.log('Notification sound played');
    } catch (error) {
        console.error('Error playing notification sound:', error);
    }
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Helper function to format notification time
function formatNotificationTime(date) {
    if (!date || isNaN(date.getTime())) {
        return 'Unknown time';
    }
    
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.round(diffMs / 60000);
    const diffHours = Math.round(diffMs / 3600000);
    const diffDays = Math.round(diffMs / 86400000);
    
    if (diffMins < 1) {
        return 'Just now';
    } else if (diffMins < 60) {
        return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
    } else if (diffHours < 24) {
        return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
    } else if (diffDays < 7) {
        return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
    } else {
        return date.toLocaleDateString();
    }
}

// Add this to your CSS section or include in your stylesheet
const styleElement = document.createElement('style');
styleElement.textContent = `
    .urgent-notification {
        background-color: #fff8e1;
        border-left: 4px solid #ffb74d;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { border-left-color: #ffb74d; }
        50% { border-left-color: #ff9800; }
        100% { border-left-color: #ffb74d; }
    }
    
    .notification-actions {
        margin-top: 8px;
        text-align: right;
    }
    
    .view-order-btn {
        background-color: #2C6E8A;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 12px;
        display: inline-block;
    }
    
    .view-order-btn:hover {
        background-color: #1B4A5E;
    }
`;
document.head.appendChild(styleElement);

// Export functions for use in other scripts
window.NotificationSystem = {
    checkForNotifications,
    loadNotificationDetails,
    markNotificationAsRead,
    markAllNotificationsAsRead,
    updateNotificationCounter,
    togglePerformanceMode,
    disableNotifications,
    enableNotifications
};

// Initialize audio context on page load and user interaction
function initializeAudioContext() {
    // Try to create the audio context right away
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (AudioContext && !window.notificationAudioContext) {
            window.notificationAudioContext = new AudioContext();
        }
    } catch (e) {
        console.log('Audio context will be initialized on user interaction');
    }
    
    // Add event listeners to initialize on first user interaction
    const userInteractionEvents = ['click', 'touchstart', 'keydown'];
    const initAudioOnUserInteraction = function() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext && (!window.notificationAudioContext || window.notificationAudioContext.state === 'suspended')) {
                window.notificationAudioContext = new AudioContext();
            }
            
            // Remove event listeners once initialized
            userInteractionEvents.forEach(event => {
                document.removeEventListener(event, initAudioOnUserInteraction);
            });
        } catch (e) {
            console.error('Could not initialize audio context:', e);
        }
    };
    
    // Add the event listeners
    userInteractionEvents.forEach(event => {
        document.addEventListener(event, initAudioOnUserInteraction, { once: true });
    });
} 