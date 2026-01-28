// Login Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-fill demo credentials and submit
    const autoFillBtn = document.getElementById('autoFillBtn');
    if (autoFillBtn) {
        autoFillBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Fill credentials
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            emailInput.value = 'demo@zigtex.com';
            passwordInput.value = 'demo123';
            
            // Add visual feedback
            emailInput.style.borderColor = '#10b981';
            emailInput.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
            
            passwordInput.style.borderColor = '#10b981';
            passwordInput.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
            
            // Update button state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            this.disabled = true;
            this.style.background = '#059669';
            
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'success-message';
            successMsg.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Credentials filled!</strong>
                    <p>Redirecting to dashboard...</p>
                </div>
            `;
            successMsg.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #10b981;
                color: white;
                padding: 16px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 1000;
                animation: slideIn 0.3s ease;
                max-width: 300px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            `;
            
            // Add success message styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
                
                .success-message i {
                    font-size: 24px;
                }
                
                .success-message strong {
                    display: block;
                    margin-bottom: 4px;
                }
                
                .success-message p {
                    margin: 0;
                    font-size: 14px;
                    opacity: 0.9;
                }
            `;
            document.head.appendChild(style);
            document.body.appendChild(successMsg);
            
            // Auto-submit form after 1 second
            setTimeout(() => {
                document.querySelector('.login-form').submit();
            }, 1000);
        });
    }
    
    // Copy to clipboard functionality
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-text');
            
            // Use modern clipboard API
            navigator.clipboard.writeText(textToCopy).then(() => {
                // Show copied feedback
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                this.style.color = '#10b981';
                
                // Show tooltip
                const tooltip = document.createElement('div');
                tooltip.textContent = 'Copied!';
                tooltip.style.cssText = `
                    position: absolute;
                    top: -30px;
                    left: 50%;
                    transform: translateX(-50%);
                    background: #1f2937;
                    color: white;
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 12px;
                    white-space: nowrap;
                    z-index: 100;
                `;
                
                this.appendChild(tooltip);
                
                // Reset after 1.5 seconds
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.style.color = '';
                    tooltip.remove();
                }, 1500);
            }).catch(err => {
                console.error('Failed to copy: ', err);
                
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = textToCopy;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Show fallback feedback
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i>';
                this.style.color = '#10b981';
                
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.style.color = '';
                }, 1500);
            });
        });
    });
    
    // Form validation
    const loginForm = document.querySelector('.login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Remove any existing error messages
            const existingError = this.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            // Basic validation
            if (!email.trim() || !password.trim()) {
                e.preventDefault();
                
                const errorMsg = document.createElement('div');
                errorMsg.className = 'error-message';
                errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please fill in all fields';
                
                this.appendChild(errorMsg);
                return false;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                
                const errorMsg = document.createElement('div');
                errorMsg.className = 'error-message';
                errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Please enter a valid email address';
                
                this.appendChild(errorMsg);
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('.login-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            submitBtn.disabled = true;
            
            // Simulate API call delay
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
    }
    
    // Input field animations
    const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
    inputs.forEach(input => {
        // Add floating label effect
        const parent = input.parentElement;
        const label = parent.querySelector('label');
        
        if (label) {
            // Check if input has value on load
            if (input.value) {
                label.style.transform = 'translateY(-20px) scale(0.85)';
                label.style.color = '#3b82f6';
            }
            
            input.addEventListener('focus', function() {
                label.style.transform = 'translateY(-20px) scale(0.85)';
                label.style.color = '#3b82f6';
                parent.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    label.style.transform = '';
                    label.style.color = '';
                }
                parent.classList.remove('focused');
            });
        }
        
        // Add character count for password
        if (input.type === 'password') {
            const counter = document.createElement('div');
            counter.className = 'password-counter';
            counter.style.cssText = `
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 12px;
                color: #9ca3af;
                background: white;
                padding: 2px 6px;
                border-radius: 10px;
                display: none;
            `;
            
            parent.style.position = 'relative';
            parent.appendChild(counter);
            
            input.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = length;
                counter.style.display = length > 0 ? 'block' : 'none';
                
                // Color based on strength
                if (length === 0) {
                    counter.style.color = '#9ca3af';
                } else if (length < 4) {
                    counter.style.color = '#ef4444';
                } else if (length < 8) {
                    counter.style.color = '#f59e0b';
                } else {
                    counter.style.color = '#10b981';
                }
            });
        }
    });
    
    // Show/hide password toggle
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        toggleBtn.className = 'password-toggle';
        toggleBtn.style.cssText = `
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 8px;
            z-index: 2;
        `;
        
        passwordInput.parentElement.style.position = 'relative';
        passwordInput.parentElement.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    // Remember me checkbox styling
    const rememberCheckbox = document.querySelector('input[name="remember"]');
    if (rememberCheckbox) {
        const checkboxWrapper = rememberCheckbox.closest('.checkbox');
        
        // Create custom checkbox
        const customCheckbox = document.createElement('span');
        customCheckbox.className = 'custom-checkbox';
        customCheckbox.style.cssText = `
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            position: relative;
            margin-right: 8px;
            vertical-align: middle;
            transition: all 0.3s;
        `;
        
        rememberCheckbox.style.display = 'none';
        checkboxWrapper.insertBefore(customCheckbox, rememberCheckbox.nextSibling);
        
        // Update custom checkbox based on actual checkbox
        function updateCustomCheckbox() {
            if (rememberCheckbox.checked) {
                customCheckbox.style.background = '#3b82f6';
                customCheckbox.style.borderColor = '#3b82f6';
                customCheckbox.innerHTML = '<i class="fas fa-check" style="color: white; font-size: 12px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);"></i>';
            } else {
                customCheckbox.style.background = '';
                customCheckbox.style.borderColor = '';
                customCheckbox.innerHTML = '';
            }
        }
        
        // Initial update
        updateCustomCheckbox();
        
        // Update on click
        checkboxWrapper.addEventListener('click', function(e) {
            if (e.target !== rememberCheckbox && !customCheckbox.contains(e.target)) {
                rememberCheckbox.checked = !rememberCheckbox.checked;
                updateCustomCheckbox();
            }
        });
        
        // Update when actual checkbox changes
        rememberCheckbox.addEventListener('change', updateCustomCheckbox);
    }
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + Enter to submit form
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            loginForm?.submit();
        }
        
        // Ctrl/Cmd + D for demo fill
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            autoFillBtn?.click();
        }
        
        // Escape to clear form
        if (e.key === 'Escape') {
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
            
            // Add visual feedback
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.style.borderColor = '#e5e7eb';
                input.style.boxShadow = 'none';
            });
        }
    });
    
    // Add welcome animation
    setTimeout(() => {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.3s ease';
        
        setTimeout(() => {
            document.body.style.opacity = '1';
        }, 50);
    }, 100);
});