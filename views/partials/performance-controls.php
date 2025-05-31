<?php
// Performance Controls Partial
// This file provides UI controls for toggling performance-related settings
?>

<div class="performance-controls" id="performance-controls">
    <button class="performance-toggle" id="toggle-performance-mode">
        <i class="fas fa-bolt"></i>
        <span>Performance Mode</span>
    </button>
    
    <div class="performance-panel" id="performance-panel">
        <div class="performance-header">
            <h3>Performance Settings</h3>
            <button class="close-panel" id="close-performance-panel">&times;</button>
        </div>
        
        <div class="performance-options">
            <div class="option">
                <label for="disable-notifications">
                    <input type="checkbox" id="disable-notifications">
                    <span>Disable Notifications</span>
                </label>
                <p class="option-description">Turns off real-time notifications to reduce server requests</p>
            </div>
            
            <div class="option">
                <label for="disable-animations">
                    <input type="checkbox" id="disable-animations">
                    <span>Disable Animations</span>
                </label>
                <p class="option-description">Turns off UI animations for smoother performance</p>
            </div>
            
            <div class="option">
                <label for="reduce-image-quality">
                    <input type="checkbox" id="reduce-image-quality">
                    <span>Reduce Image Quality</span>
                </label>
                <p class="option-description">Loads lower quality images for faster page loads</p>
            </div>
        </div>
        
        <div class="performance-actions">
            <button class="apply-settings" id="apply-performance-settings">Apply Settings</button>
        </div>
    </div>
</div>

<style>
.performance-controls {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

.performance-toggle {
    background-color: #2C6E8A;
    color: white;
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.performance-toggle i {
    font-size: 1.5rem;
    transition: transform 0.3s ease;
}

.performance-toggle span {
    position: absolute;
    white-space: nowrap;
    opacity: 0;
    transform: translateX(20px);
    transition: all 0.3s ease;
}

.performance-toggle:hover {
    width: auto;
    padding: 0 20px;
    border-radius: 25px;
}

.performance-toggle:hover i {
    transform: translateX(-30px);
}

.performance-toggle:hover span {
    opacity: 1;
    transform: translateX(10px);
}

.performance-panel {
    position: absolute;
    bottom: 60px;
    right: 0;
    width: 300px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    padding: 15px;
    display: none;
    transform: translateY(10px);
    opacity: 0;
    transition: all 0.3s ease;
}

.performance-panel.active {
    display: block;
    transform: translateY(0);
    opacity: 1;
}

.performance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.performance-header h3 {
    margin: 0;
    font-size: 1rem;
    color: #4a3b2b;
}

.close-panel {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #999;
    padding: 0;
    line-height: 1;
}

.performance-options {
    margin-bottom: 15px;
}

.option {
    margin-bottom: 12px;
}

.option label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.option input {
    margin-right: 10px;
}

.option span {
    font-weight: 500;
    color: #333;
}

.option-description {
    margin: 5px 0 0 25px;
    font-size: 0.8rem;
    color: #777;
}

.performance-actions {
    text-align: right;
}

.apply-settings {
    background-color: #2C6E8A;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background-color 0.2s ease;
}

.apply-settings:hover {
    background-color: #235A73;
}

@media (max-width: 768px) {
    .performance-panel {
        width: calc(100vw - 40px);
        right: -20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const toggleBtn = document.getElementById('toggle-performance-mode');
    const panel = document.getElementById('performance-panel');
    const closeBtn = document.getElementById('close-performance-panel');
    const applyBtn = document.getElementById('apply-performance-settings');
    const notificationsToggle = document.getElementById('disable-notifications');
    const animationsToggle = document.getElementById('disable-animations');
    const imageQualityToggle = document.getElementById('reduce-image-quality');
    
    // Load saved settings
    function loadSettings() {
        notificationsToggle.checked = localStorage.getItem('performance_mode') === 'true';
        animationsToggle.checked = localStorage.getItem('disable_animations') === 'true';
        imageQualityToggle.checked = localStorage.getItem('reduce_image_quality') === 'true';
        
        // Apply settings immediately on page load
        applyAnimationSettings();
        applyImageQualitySettings();
    }
    
    // Toggle panel visibility
    toggleBtn.addEventListener('click', function() {
        panel.classList.toggle('active');
    });
    
    // Close panel
    closeBtn.addEventListener('click', function() {
        panel.classList.remove('active');
    });
    
    // Apply settings
    applyBtn.addEventListener('click', function() {
        // Save settings to localStorage
        localStorage.setItem('performance_mode', notificationsToggle.checked);
        localStorage.setItem('disable_animations', animationsToggle.checked);
        localStorage.setItem('reduce_image_quality', imageQualityToggle.checked);
        
        // Apply notification settings
        if (window.NotificationSystem) {
            if (notificationsToggle.checked) {
                window.NotificationSystem.disableNotifications();
            } else {
                window.NotificationSystem.enableNotifications();
            }
        }
        
        // Apply other settings
        applyAnimationSettings();
        applyImageQualitySettings();
        
        // Close panel and show confirmation
        panel.classList.remove('active');
        showToast('Performance settings applied');
    });
    
    // Apply animation settings
    function applyAnimationSettings() {
        if (animationsToggle.checked) {
            document.body.classList.add('disable-animations');
        } else {
            document.body.classList.remove('disable-animations');
        }
    }
    
    // Apply image quality settings
    function applyImageQualitySettings() {
        if (imageQualityToggle.checked) {
            document.body.classList.add('reduce-image-quality');
        } else {
            document.body.classList.remove('reduce-image-quality');
        }
    }
    
    // Show toast message
    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'performance-toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
    
    // Add toast styles
    const style = document.createElement('style');
    style.textContent = `
        .performance-toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 0.9rem;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 10000;
        }
        
        .performance-toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        
        .disable-animations * {
            transition: none !important;
            animation: none !important;
        }
        
        .reduce-image-quality img:not(.critical-image) {
            filter: blur(0.5px);
            image-rendering: optimizeSpeed;
        }
    `;
    document.head.appendChild(style);
    
    // Load settings on init
    loadSettings();
});
</script> 