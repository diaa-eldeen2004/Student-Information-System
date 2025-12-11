// University Portal - Shared JavaScript

// Global Variables
let sidebarOpen = true;
let chatOpen = false;
let currentTheme = localStorage.getItem('theme') || 'light';

// Initialize the application
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializeSidebar();
    initializeChat();
    initializeTooltips();
    initializeAnimations();
});

// Theme Management
function initializeTheme() {
    const themeToggle = document.querySelector('.theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    
    // Apply saved theme
    applyTheme(currentTheme);
}

function toggleTheme() {
    currentTheme = currentTheme === 'light' ? 'dark' : 'light';
    applyTheme(currentTheme);
    localStorage.setItem('theme', currentTheme);
    
    // Show notification
    showNotification(`Switched to ${currentTheme} mode`, 'success');
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    
    // Update theme toggle icon
    const themeToggle = document.querySelector('.theme-toggle i');
    if (themeToggle) {
        themeToggle.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
    }
    
    // Update theme toggle tooltip
    const themeToggleBtn = document.querySelector('.theme-toggle');
    if (themeToggleBtn) {
        themeToggleBtn.title = `Switch to ${theme === 'light' ? 'dark' : 'light'} mode`;
    }
}

// Sidebar Management
function initializeSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener('click', toggleSidebar);
        
        // Check if sidebar should be collapsed on mobile
        if (window.innerWidth <= 768) {
            collapseSidebar();
        }
        
        // Handle window resize
        window.addEventListener('resize', handleResize);
    }
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && mainContent) {
        sidebarOpen = !sidebarOpen;
        
        if (sidebarOpen) {
            expandSidebar();
        } else {
            collapseSidebar();
        }
    }
}

function expandSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarToggle = document.querySelector('.sidebar-toggle i');
    
    if (sidebar && mainContent) {
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('sidebar-collapsed');
        sidebarOpen = true;
        
        // Update toggle icon
        if (sidebarToggle) {
            sidebarToggle.className = 'fas fa-bars';
        }
    }
}

function collapseSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebarToggle = document.querySelector('.sidebar-toggle i');
    
    if (sidebar && mainContent) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('sidebar-collapsed');
        sidebarOpen = false;
        
        // Update toggle icon
        if (sidebarToggle) {
            sidebarToggle.className = 'fas fa-bars';
        }
    }
}

function handleResize() {
    if (window.innerWidth <= 768) {
        collapseSidebar();
    } else if (window.innerWidth > 768 && !sidebarOpen) {
        expandSidebar();
    }
}

// Chat Management
function initializeChat() {
    const chatToggle = document.querySelector('.chat-toggle');
    const chatBox = document.querySelector('.chat-box');
    const chatClose = document.querySelector('.chat-close');
    const chatForm = document.querySelector('.chat-form');
    
    if (chatToggle && chatBox) {
        chatToggle.addEventListener('click', toggleChat);
        
        // Add pulse animation to chat button when there are unread messages
        addChatPulseAnimation();
    }
    
    if (chatClose && chatBox) {
        chatClose.addEventListener('click', closeChat);
    }
    
    if (chatForm) {
        chatForm.addEventListener('submit', handleChatSubmit);
        
        // Add auto-save functionality
        addChatAutoSave();
        
        // Add character counter for message field
        addMessageCharacterCounter();
        
        // Add quick contact suggestions
        addQuickContactSuggestions();
    }
    
    // Close chat when clicking outside
    document.addEventListener('click', function(e) {
        if (chatOpen && !chatBox.contains(e.target) && !chatToggle.contains(e.target)) {
            closeChat();
        }
    });
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter' && chatOpen) {
            const messageField = document.querySelector('textarea[name="message"]');
            if (messageField && messageField === document.activeElement) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        }
    });
}

function toggleChat() {
    const chatBox = document.querySelector('.chat-box');
    if (chatBox) {
        chatOpen = !chatOpen;
        if (chatOpen) {
            chatBox.classList.add('open');
            chatBox.style.display = 'flex';
        } else {
            chatBox.classList.remove('open');
            chatBox.style.display = 'none';
        }
    }
}

function closeChat() {
    const chatBox = document.querySelector('.chat-box');
    if (chatBox) {
        chatBox.classList.remove('open');
        chatBox.style.display = 'none';
        chatOpen = false;
    }
}

function handleChatSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const from = formData.get('from');
    const to = formData.get('to');
    const subject = formData.get('subject');
    const message = formData.get('message');
    
    if (!from || !to || !subject || !message) {
        showNotification('Please fill in all fields', 'error');
        return;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(from) || !emailRegex.test(to)) {
        showNotification('Please enter valid email addresses', 'error');
        return;
    }
    
    // Show sending animation
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
    submitBtn.disabled = true;
    
    // Simulate sending email with delay
    setTimeout(() => {
        // Simulate sending email
        showNotification(`Message sent to ${to} successfully!`, 'success');
        e.target.reset();
        closeChat();
        
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        // Log the message for debugging
        console.log('Chat message sent:', {
            from: from,
            to: to,
            subject: subject,
            message: message,
            timestamp: new Date().toISOString()
        });
    }, 1500);
}

// Tooltip Management
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const element = e.target;
    const tooltipText = element.getAttribute('data-tooltip');
    
    if (tooltipText) {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = tooltipText;
        tooltip.style.cssText = `
            position: absolute;
            background-color: var(--text-primary);
            color: var(--background-color);
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            z-index: 1000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        
        document.body.appendChild(tooltip);
        
        const rect = element.getBoundingClientRect();
        tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
        
        setTimeout(() => {
            tooltip.style.opacity = '1';
        }, 10);
        
        element._tooltip = tooltip;
    }
}

function hideTooltip(e) {
    const element = e.target;
    if (element._tooltip) {
        element._tooltip.remove();
        delete element._tooltip;
    }
}

// Animation Management
function initializeAnimations() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
    
    // Add slide-in animation to sidebar
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.add('slide-in');
    }
}

// Notification System
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 1.2rem;">&times;</button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Form Validation
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = 'var(--error-color)';
            showNotification(`${input.name || 'Field'} is required`, 'error');
        } else {
            input.style.borderColor = 'var(--border-color)';
        }
    });
    
    return isValid;
}

// File Upload Handling
function initializeFileUpload() {
    const fileUploads = document.querySelectorAll('.file-upload');
    
    fileUploads.forEach(upload => {
        upload.addEventListener('dragover', handleDragOver);
        upload.addEventListener('dragleave', handleDragLeave);
        upload.addEventListener('drop', handleDrop);
        upload.addEventListener('click', () => {
            const input = upload.querySelector('input[type="file"]');
            if (input) input.click();
        });
    });
}

function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('dragover');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
}

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    const input = e.currentTarget.querySelector('input[type="file"]');
    
    if (input && files.length > 0) {
        input.files = files;
        updateFileDisplay(e.currentTarget, files);
    }
}

function updateFileDisplay(uploadElement, files) {
    const display = uploadElement.querySelector('.file-display');
    if (display) {
        display.innerHTML = '';
        Array.from(files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <i class="fas fa-file"></i>
                <span>${file.name}</span>
                <span class="file-size">(${formatFileSize(file.size)})</span>
            `;
            display.appendChild(fileItem);
        });
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Calendar Management
function initializeCalendar() {
    const calendarDays = document.querySelectorAll('.calendar-day');
    
    calendarDays.forEach(day => {
        day.addEventListener('click', function() {
            // Remove active class from all days
            calendarDays.forEach(d => d.classList.remove('active'));
            // Add active class to clicked day
            this.classList.add('active');
            
            // Show events for this day
            showDayEvents(this);
        });
    });
}

function showDayEvents(dayElement) {
    const date = dayElement.getAttribute('data-date');
    const events = getEventsForDate(date);
    
    // Create or update events display
    let eventsDisplay = document.querySelector('.events-display');
    if (!eventsDisplay) {
        eventsDisplay = document.createElement('div');
        eventsDisplay.className = 'events-display';
        document.querySelector('.calendar').appendChild(eventsDisplay);
    }
    
    eventsDisplay.innerHTML = '';
    if (events.length > 0) {
        events.forEach(event => {
            const eventElement = document.createElement('div');
            eventElement.className = 'event-item';
            eventElement.innerHTML = `
                <h4>${event.title}</h4>
                <p>${event.time}</p>
                <p>${event.description}</p>
            `;
            eventsDisplay.appendChild(eventElement);
        });
    } else {
        eventsDisplay.innerHTML = '<p>No events for this day</p>';
    }
}

function getEventsForDate(date) {
    // Mock data - replace with actual API call
    const mockEvents = {
        '2024-01-15': [
            { title: 'Math Exam', time: '10:00 AM', description: 'Final exam for Mathematics course' },
            { title: 'Physics Lab', time: '2:00 PM', description: 'Laboratory session' }
        ],
        '2024-01-16': [
            { title: 'English Assignment Due', time: '11:59 PM', description: 'Submit essay assignment' }
        ]
    };
    
    return mockEvents[date] || [];
}

// Table Management
function initializeTables() {
    const tables = document.querySelectorAll('.table');
    
    tables.forEach(table => {
        // Add sorting functionality
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => sortTable(table, header));
        });
    });
}

function sortTable(table, header) {
    const column = header.getAttribute('data-sort');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const isAscending = header.classList.contains('sort-asc');
    
    rows.sort((a, b) => {
        const aValue = a.querySelector(`td[data-sort="${column}"]`).textContent;
        const bValue = b.querySelector(`td[data-sort="${column}"]`).textContent;
        
        if (isAscending) {
            return aValue.localeCompare(bValue);
        } else {
            return bValue.localeCompare(aValue);
        }
    });
    
    // Clear tbody and append sorted rows
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
    
    // Update header classes
    table.querySelectorAll('th').forEach(th => th.classList.remove('sort-asc', 'sort-desc'));
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
}

// Search Functionality
function initializeSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const targetTable = document.querySelector(this.getAttribute('data-target'));
            
            if (targetTable) {
                const rows = targetTable.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    });
}

// Modal Management
function initializeModals() {
    const modalTriggers = document.querySelectorAll('[data-modal]');
    const modals = document.querySelectorAll('.modal');
    
    modalTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                showModal(modal);
            }
        });
    });
    
    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => hideModal(modal));
        }
        
        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                hideModal(this);
            }
        });
    });
}

function showModal(modal) {
    modal.style.display = 'flex';
    modal.classList.add('fade-in');
    document.body.style.overflow = 'hidden';
}

function hideModal(modal) {
    modal.style.display = 'none';
    modal.classList.remove('fade-in');
    document.body.style.overflow = '';
}

// Progress Bar Management
function updateProgressBar(progressBar, percentage) {
    if (progressBar) {
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
    }
}

// Utility Functions
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

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Enhanced Chat Functions
function addChatPulseAnimation() {
    const chatToggle = document.querySelector('.chat-toggle');
    if (chatToggle) {
        // Simulate unread messages (in real app, this would be based on actual data)
        const hasUnreadMessages = Math.random() > 0.7; // 30% chance
        
        if (hasUnreadMessages) {
            chatToggle.classList.add('pulse');
            chatToggle.title = 'You have unread messages';
            
            // Add notification badge
            const badge = document.createElement('span');
            badge.className = 'chat-badge';
            badge.textContent = '1';
            badge.style.cssText = `
                position: absolute;
                top: -5px;
                right: -5px;
                background-color: var(--error-color);
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                font-size: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
            `;
            chatToggle.style.position = 'relative';
            chatToggle.appendChild(badge);
        }
    }
}

function addChatAutoSave() {
    const chatForm = document.querySelector('.chat-form');
    if (chatForm) {
        const inputs = chatForm.querySelectorAll('input, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('input', debounce(function() {
                const formData = new FormData(chatForm);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                localStorage.setItem('chatDraft', JSON.stringify(data));
            }, 1000));
        });
        
        // Restore draft on page load
        const savedDraft = localStorage.getItem('chatDraft');
        if (savedDraft) {
            try {
                const draft = JSON.parse(savedDraft);
                Object.keys(draft).forEach(key => {
                    const input = chatForm.querySelector(`[name="${key}"]`);
                    if (input && draft[key]) {
                        input.value = draft[key];
                    }
                });
            } catch (e) {
                console.error('Error restoring chat draft:', e);
            }
        }
        
        // Clear draft on successful send
        chatForm.addEventListener('submit', function() {
            setTimeout(() => {
                localStorage.removeItem('chatDraft');
            }, 2000);
        });
    }
}

function addMessageCharacterCounter() {
    const messageField = document.querySelector('textarea[name="message"]');
    if (messageField) {
        const maxLength = 1000;
        const counter = document.createElement('div');
        counter.className = 'message-counter';
        counter.style.cssText = `
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-align: right;
            margin-top: 0.25rem;
        `;
        
        messageField.parentNode.appendChild(counter);
        
        function updateCounter() {
            const length = messageField.value.length;
            counter.textContent = `${length}/${maxLength}`;
            
            if (length > maxLength * 0.9) {
                counter.style.color = 'var(--warning-color)';
            } else if (length > maxLength) {
                counter.style.color = 'var(--error-color)';
            } else {
                counter.style.color = 'var(--text-secondary)';
            }
        }
        
        messageField.addEventListener('input', updateCounter);
        updateCounter();
        
        // Prevent submission if over limit
        const chatForm = document.querySelector('.chat-form');
        if (chatForm) {
            chatForm.addEventListener('submit', function(e) {
                if (messageField.value.length > maxLength) {
                    e.preventDefault();
                    showNotification(`Message is too long. Maximum ${maxLength} characters allowed.`, 'error');
                }
            });
        }
    }
}

// Quick contact suggestions
function addQuickContactSuggestions() {
    const toField = document.querySelector('input[name="to"]');
    if (toField) {
        const suggestions = [
            'student@university.edu',
            'doctor@university.edu',
            'admin@university.edu',
            'support@university.edu'
        ];
        
        toField.addEventListener('focus', function() {
            if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('contact-suggestions')) {
                const suggestionsDiv = document.createElement('div');
                suggestionsDiv.className = 'contact-suggestions';
                suggestionsDiv.style.cssText = `
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    background: var(--surface-color);
                    border: 1px solid var(--border-color);
                    border-radius: 8px;
                    box-shadow: 0 4px 12px var(--shadow-color);
                    z-index: 1000;
                    max-height: 200px;
                    overflow-y: auto;
                `;
                
                suggestions.forEach(email => {
                    const suggestion = document.createElement('div');
                    suggestion.textContent = email;
                    suggestion.style.cssText = `
                        padding: 0.75rem;
                        cursor: pointer;
                        border-bottom: 1px solid var(--border-color);
                        transition: background-color 0.2s;
                    `;
                    
                    suggestion.addEventListener('mouseenter', function() {
                        this.style.backgroundColor = 'var(--background-color)';
                    });
                    
                    suggestion.addEventListener('mouseleave', function() {
                        this.style.backgroundColor = '';
                    });
                    
                    suggestion.addEventListener('click', function() {
                        toField.value = email;
                        suggestionsDiv.remove();
                    });
                    
                    suggestionsDiv.appendChild(suggestion);
                });
                
                this.parentNode.style.position = 'relative';
                this.parentNode.appendChild(suggestionsDiv);
            }
        });
        
        toField.addEventListener('blur', function() {
            setTimeout(() => {
                const suggestions = this.parentNode.querySelector('.contact-suggestions');
                if (suggestions) {
                    suggestions.remove();
                }
            }, 200);
        });
    }
}

// Export functions for global use
window.UniversityPortal = {
    toggleTheme,
    toggleSidebar,
    toggleChat,
    showNotification,
    validateForm,
    showModal,
    hideModal,
    updateProgressBar
};
