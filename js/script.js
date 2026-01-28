// Global JavaScript for all pages
document.addEventListener('DOMContentLoaded', function() {
    
    // Demo login auto-fill
    if (window.location.pathname.includes('login.php')) {
        const demoBtn = document.createElement('button');
        demoBtn.textContent = 'Auto-fill Demo Credentials';
        demoBtn.className = 'demo-fill-btn';
        demoBtn.style.cssText = `
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            display: block;
            width: 100%;
        `;
        
        demoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('email').value = 'demo@zigtex.com';
            document.getElementById('password').value = 'demo123';
            
            // Visual feedback
            this.style.background = '#059669';
            this.textContent = 'Credentials Filled!';
            setTimeout(() => {
                this.style.background = '#10b981';
                this.textContent = 'Auto-fill Demo Credentials';
            }, 1500);
        });
        
        const form = document.querySelector('.login-form');
        if (form) {
            form.appendChild(demoBtn);
        }
    }
    
    // Mobile menu toggle
    const createMobileMenuToggle = () => {
        if (window.innerWidth <= 768) {
            const header = document.querySelector('.main-header');
            if (header && !document.querySelector('.mobile-menu-toggle')) {
                const toggleBtn = document.createElement('button');
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                toggleBtn.className = 'mobile-menu-toggle';
                toggleBtn.style.cssText = `
                    background: none;
                    border: none;
                    font-size: 24px;
                    color: #6b7280;
                    cursor: pointer;
                    padding: 10px;
                    margin-right: 15px;
                `;
                
                const headerLeft = document.querySelector('.header-left');
                if (headerLeft) {
                    headerLeft.style.display = 'flex';
                    headerLeft.style.alignItems = 'center';
                    headerLeft.insertBefore(toggleBtn, headerLeft.firstChild);
                }
                
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) {
                    sidebar.style.display = 'none';
                    toggleBtn.addEventListener('click', () => {
                        sidebar.style.display = sidebar.style.display === 'none' ? 'flex' : 'none';
                    });
                }
            }
        } else {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.mobile-menu-toggle');
            if (sidebar && toggleBtn) {
                sidebar.style.display = 'flex';
                toggleBtn.remove();
            }
        }
    };
    
    createMobileMenuToggle();
    window.addEventListener('resize', createMobileMenuToggle);
    
    // Notification bell click
    const notificationBtn = document.querySelector('.icon-btn .fa-bell')?.closest('.icon-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            const badge = this.querySelector('.notification-badge');
            if (badge) {
                badge.style.display = 'none';
                
                // Create notification dropdown
                const dropdown = document.createElement('div');
                dropdown.className = 'notification-dropdown';
                dropdown.innerHTML = `
                    <div class="dropdown-header">
                        <h3>Notifications</h3>
                        <button class="mark-all-read">Mark all as read</button>
                    </div>
                    <div class="notification-list">
                        <div class="notification-item unread">
                            <i class="fas fa-reply" style="color: #10b981;"></i>
                            <div>
                                <p>New reply from john@example.com</p>
                                <span>2 minutes ago</span>
                            </div>
                        </div>
                        <div class="notification-item unread">
                            <i class="fas fa-envelope" style="color: #3b82f6;"></i>
                            <div>
                                <p>Campaign "Tech Outreach" completed</p>
                                <span>1 hour ago</span>
                            </div>
                        </div>
                        <div class="notification-item">
                            <i class="fas fa-user-plus" style="color: #8b5cf6;"></i>
                            <div>
                                <p>25 new prospects added</p>
                                <span>3 hours ago</span>
                            </div>
                        </div>
                    </div>
                `;
                
                dropdown.style.cssText = `
                    position: absolute;
                    top: 60px;
                    right: 0;
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 12px;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
                    width: 320px;
                    z-index: 1000;
                    animation: dropdownFade 0.2s ease;
                `;
                
                // Add CSS for dropdown
                const dropdownStyle = document.createElement('style');
                dropdownStyle.textContent = `
                    @keyframes dropdownFade {
                        from { opacity: 0; transform: translateY(-10px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    
                    .notification-dropdown .dropdown-header {
                        padding: 16px;
                        border-bottom: 1px solid #e5e7eb;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    }
                    
                    .notification-dropdown .dropdown-header h3 {
                        font-size: 16px;
                        color: #1f2937;
                        margin: 0;
                    }
                    
                    .notification-dropdown .mark-all-read {
                        background: none;
                        border: none;
                        color: #3b82f6;
                        font-size: 12px;
                        cursor: pointer;
                    }
                    
                    .notification-list {
                        max-height: 400px;
                        overflow-y: auto;
                    }
                    
                    .notification-item {
                        display: flex;
                        gap: 12px;
                        padding: 12px 16px;
                        border-bottom: 1px solid #f3f4f6;
                        transition: background 0.2s;
                    }
                    
                    .notification-item:hover {
                        background: #f9fafb;
                    }
                    
                    .notification-item.unread {
                        background: #eff6ff;
                    }
                    
                    .notification-item i {
                        font-size: 16px;
                        margin-top: 4px;
                    }
                    
                    .notification-item div {
                        flex: 1;
                    }
                    
                    .notification-item p {
                        margin: 0 0 4px 0;
                        font-size: 14px;
                        color: #1f2937;
                    }
                    
                    .notification-item span {
                        font-size: 12px;
                        color: #9ca3af;
                    }
                `;
                
                document.head.appendChild(dropdownStyle);
                this.appendChild(dropdown);
                
                // Mark all as read
                dropdown.querySelector('.mark-all-read').addEventListener('click', function() {
                    dropdown.querySelectorAll('.notification-item').forEach(item => {
                        item.classList.remove('unread');
                        item.style.background = 'white';
                    });
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function closeDropdown(e) {
                    if (!notificationBtn.contains(e.target) && !dropdown.contains(e.target)) {
                        dropdown.remove();
                        document.removeEventListener('click', closeDropdown);
                    }
                });
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#ef4444';
                    
                    if (!field.nextElementSibling?.classList.contains('error-msg')) {
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'error-msg';
                        errorMsg.textContent = 'This field is required';
                        errorMsg.style.cssText = `
                            color: #ef4444;
                            font-size: 12px;
                            margin-top: 5px;
                        `;
                        field.parentNode.appendChild(errorMsg);
                    }
                } else {
                    field.style.borderColor = '';
                    const errorMsg = field.parentNode.querySelector('.error-msg');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!valid) {
                e.preventDefault();
            }
        });
    });
    
    // Add loading state to buttons
    document.querySelectorAll('button[type="submit"], .create-btn, .login-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!this.classList.contains('loading')) {
                const originalText = this.innerHTML;
                this.classList.add('loading');
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                this.disabled = true;
                
                // Reset after 3 seconds for demo
                setTimeout(() => {
                    this.classList.remove('loading');
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 3000);
            }
        });
    });
    
    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});


