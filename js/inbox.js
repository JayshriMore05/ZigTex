// Inbox Page JavaScript - Integrated with Dashboard
document.addEventListener('DOMContentLoaded', function() {
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    const emailItems = document.querySelectorAll('.email-item');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;
            
            emailItems.forEach(item => {
                const sender = item.querySelector('.sender-name')?.textContent.toLowerCase() || '';
                const subject = item.querySelector('.email-subject')?.textContent.toLowerCase() || '';
                const preview = item.querySelector('.email-preview')?.textContent.toLowerCase() || '';
                
                if (searchTerm === '' || 
                    sender.includes(searchTerm) || 
                    subject.includes(searchTerm) || 
                    preview.includes(searchTerm)) {
                    item.style.display = 'grid';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Update pagination info
            updatePaginationInfo(visibleCount);
        });
        
        // Clear search button
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.focus();
            emailItems.forEach(item => {
                item.style.display = 'grid';
            });
            updatePaginationInfo(emailItems.length);
        });
    }
    
    // Star functionality
    const starButtons = document.querySelectorAll('.email-star');
    starButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const icon = this.querySelector('i');
            
            if (icon.classList.contains('far')) {
                icon.classList.remove('far', 'fa-star');
                icon.classList.add('fas', 'fa-star');
                this.classList.add('starred');
                this.style.color = '#f59e0b';
                
                // Show notification
                showToast('Email starred', 'success');
            } else {
                icon.classList.remove('fas', 'fa-star');
                icon.classList.add('far', 'fa-star');
                this.classList.remove('starred');
                this.style.color = '';
                
                showToast('Email unstarred', 'info');
            }
        });
    });
    
    // Filter buttons
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            const filter = this.textContent.toLowerCase();
            let filteredCount = 0;
            
            emailItems.forEach(item => {
                switch(filter) {
                    case 'all':
                        item.style.display = 'grid';
                        filteredCount++;
                        break;
                    case 'unread':
                        if (item.classList.contains('unread')) {
                            item.style.display = 'grid';
                            filteredCount++;
                        } else {
                            item.style.display = 'none';
                        }
                        break;
                    case 'starred':
                        if (item.querySelector('.email-star').classList.contains('starred')) {
                            item.style.display = 'grid';
                            filteredCount++;
                        } else {
                            item.style.display = 'none';
                        }
                        break;
                    case 'important':
                        // For demo, mark random emails as important
                        if (Math.random() > 0.7) {
                            item.style.display = 'grid';
                            filteredCount++;
                        } else {
                            item.style.display = 'none';
                        }
                        break;
                }
            });
            
            updatePaginationInfo(filteredCount);
            
            // Show filter notification
            if (filter !== 'all') {
                showToast(`Showing ${filter} emails`, 'info');
            }
        });
    });
    
    // Email selection
    const checkboxes = document.querySelectorAll('.email-checkbox input');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const emailItem = this.closest('.email-item');
            
            if (this.checked) {
                emailItem.style.background = '#e8f0fe';
                emailItem.style.borderLeft = '3px solid #3b82f6';
            } else {
                if (emailItem.classList.contains('unread')) {
                    emailItem.style.background = '#f8fafc';
                } else {
                    emailItem.style.background = '';
                }
                emailItem.style.borderLeft = '';
            }
            
            updateSelectionCount();
        });
    });
    
    // Email item click
    emailItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Don't trigger if clicking on checkbox or star
            if (e.target.closest('.email-checkbox') || e.target.closest('.email-star')) {
                return;
            }
            
            // Mark as read
            this.classList.remove('unread');
            this.style.background = '';
            
            // For demo, show email content in modal
            const sender = this.querySelector('.sender-name').textContent;
            const subject = this.querySelector('.email-subject').textContent;
            const preview = this.querySelector('.email-preview')?.textContent || '';
            
            showEmailModal(sender, subject, preview);
        });
    });
    
    // Compose button
    const composeBtn = document.querySelector('.compose-btn');
    if (composeBtn) {
        composeBtn.addEventListener('click', function() {
            showComposeModal();
        });
    }
    
    // Pagination
    const paginationNext = document.querySelector('.pagination-btn:not(.disabled)');
    const paginationPrev = document.querySelector('.pagination-btn.disabled');
    
    if (paginationNext) {
        paginationNext.addEventListener('click', function() {
            showToast('Loading more emails...', 'info');
            
            // Simulate loading
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-chevron-right"></i>';
                this.disabled = false;
                showToast('Next page loaded', 'success');
            }, 1500);
        });
    }
    
    // Helper Functions
    function updatePaginationInfo(count) {
        const pageInfo = document.querySelector('.page-info');
        if (pageInfo) {
            const totalEmails = 127;
            pageInfo.textContent = `Showing 1-${Math.min(count, 12)} of ${totalEmails} emails`;
        }
    }
    
    function updateSelectionCount() {
        const selectedCount = document.querySelectorAll('.email-checkbox input:checked').length;
        if (selectedCount > 0) {
            showToast(`${selectedCount} email${selectedCount > 1 ? 's' : ''} selected`, 'info');
        }
    }
    
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 1000;
            animation: slideInUp 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-width: 300px;
        `;
        
        // Add toast animation styles
        if (!document.querySelector('#toastStyle')) {
            const style = document.createElement('style');
            style.id = 'toastStyle';
            style.textContent = `
                @keyframes slideInUp {
                    from {
                        transform: translateY(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideOutDown {
                    from {
                        transform: translateY(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateY(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOutDown 0.3s ease';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3000);
    }
    
    function showEmailModal(sender, subject, content) {
        const modal = document.createElement('div');
        modal.className = 'email-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>${subject}</h3>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="email-header">
                        <strong>From:</strong> ${sender}<br>
                        <strong>Subject:</strong> ${subject}
                    </div>
                    <div class="email-body">
                        <p>${content}</p>
                        <p>This is a preview of the email content. In a real application, this would show the full email with proper formatting, attachments, and reply options.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="reply-btn">Reply</button>
                    <button class="forward-btn">Forward</button>
                    <button class="delete-btn">Delete</button>
                </div>
            </div>
        `;
        
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        `;
        
        document.body.appendChild(modal);
        
        // Close modal
        modal.querySelector('.close-modal').addEventListener('click', () => {
            modal.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => modal.remove(), 300);
        });
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => modal.remove(), 300);
            }
        });
        
        // Add modal styles
        if (!document.querySelector('#modalStyle')) {
            const style = document.createElement('style');
            style.id = 'modalStyle';
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
                
                .email-modal .modal-content {
                    background: white;
                    border-radius: 12px;
                    width: 90%;
                    max-width: 600px;
                    max-height: 80vh;
                    display: flex;
                    flex-direction: column;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                }
                
                .email-modal .modal-header {
                    padding: 20px;
                    border-bottom: 1px solid #e5e7eb;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .email-modal .modal-header h3 {
                    margin: 0;
                    color: #1f2937;
                    font-size: 18px;
                    font-weight: 500;
                }
                
                .email-modal .close-modal {
                    background: none;
                    border: none;
                    font-size: 24px;
                    color: #6b7280;
                    cursor: pointer;
                    padding: 4px 12px;
                    border-radius: 4px;
                }
                
                .email-modal .close-modal:hover {
                    background: #f3f4f6;
                }
                
                .email-modal .modal-body {
                    flex: 1;
                    padding: 20px;
                    overflow-y: auto;
                }
                
                .email-modal .email-header {
                    background: #f9fafb;
                    padding: 16px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    font-size: 14px;
                    color: #6b7280;
                    line-height: 1.6;
                }
                
                .email-modal .email-body {
                    font-size: 15px;
                    color: #374151;
                    line-height: 1.6;
                }
                
                .email-modal .modal-footer {
                    padding: 16px 20px;
                    border-top: 1px solid #e5e7eb;
                    display: flex;
                    gap: 12px;
                    justify-content: flex-end;
                }
                
                .email-modal .reply-btn,
                .email-modal .forward-btn,
                .email-modal .delete-btn {
                    padding: 8px 20px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                }
                
                .email-modal .reply-btn {
                    background: #3b82f6;
                    color: white;
                }
                
                .email-modal .forward-btn {
                    background: #f3f4f6;
                    color: #374151;
                }
                
                .email-modal .delete-btn {
                    background: #fee2e2;
                    color: #dc2626;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    function showComposeModal() {
        const modal = document.createElement('div');
        modal.className = 'compose-modal';
        modal.innerHTML = `
            <div class="compose-content">
                <div class="compose-header">
                    <h3>New Message</h3>
                    <button class="close-compose">&times;</button>
                </div>
                <div class="compose-body">
                    <div class="compose-field">
                        <input type="text" placeholder="To" class="compose-input">
                    </div>
                    <div class="compose-field">
                        <input type="text" placeholder="Subject" class="compose-input">
                    </div>
                    <div class="compose-field">
                        <textarea placeholder="Type your message here..." class="compose-textarea" rows="10"></textarea>
                    </div>
                </div>
                <div class="compose-footer">
                    <button class="send-btn">Send</button>
                    <button class="discard-btn">Discard</button>
                </div>
            </div>
        `;
        
        modal.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 500px;
            max-width: 90vw;
            background: white;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            z-index: 2000;
            animation: slideUp 0.3s ease;
        `;
        
        document.body.appendChild(modal);
        
        // Close compose modal
        modal.querySelector('.close-compose').addEventListener('click', () => {
            modal.style.animation = 'slideDown 0.3s ease';
            setTimeout(() => modal.remove(), 300);
        });
        
        // Add compose modal styles
        if (!document.querySelector('#composeStyle')) {
            const style = document.createElement('style');
            style.id = 'composeStyle';
            style.textContent = `
                @keyframes slideUp {
                    from {
                        transform: translateY(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideDown {
                    from {
                        transform: translateY(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateY(100%);
                        opacity: 0;
                    }
                }
                
                .compose-modal .compose-content {
                    display: flex;
                    flex-direction: column;
                    height: 600px;
                    max-height: 70vh;
                }
                
                .compose-modal .compose-header {
                    padding: 16px 20px;
                    background: #3b82f6;
                    color: white;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-radius: 12px 12px 0 0;
                }
                
                .compose-modal .compose-header h3 {
                    margin: 0;
                    font-size: 16px;
                    font-weight: 500;
                }
                
                .compose-modal .close-compose {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 24px;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                .compose-modal .close-compose:hover {
                    background: rgba(255, 255, 255, 0.1);
                }
                
                .compose-modal .compose-body {
                    flex: 1;
                    padding: 0;
                    overflow: hidden;
                }
                
                .compose-modal .compose-field {
                    padding: 12px 20px;
                    border-bottom: 1px solid #e5e7eb;
                }
                
                .compose-modal .compose-field:last-child {
                    border-bottom: none;
                    height: calc(100% - 88px);
                }
                
                .compose-modal .compose-input,
                .compose-modal .compose-textarea {
                    width: 100%;
                    border: none;
                    outline: none;
                    font-family: inherit;
                    font-size: 14px;
                    color: #374151;
                }
                
                .compose-modal .compose-textarea {
                    height: 100%;
                    resize: none;
                }
                
                .compose-modal .compose-input::placeholder,
                .compose-modal .compose-textarea::placeholder {
                    color: #9ca3af;
                }
                
                .compose-modal .compose-footer {
                    padding: 12px 20px;
                    border-top: 1px solid #e5e7eb;
                    display: flex;
                    gap: 12px;
                    justify-content: flex-end;
                }
                
                .compose-modal .send-btn,
                .compose-modal .discard-btn {
                    padding: 8px 20px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                }
                
                .compose-modal .send-btn {
                    background: #3b82f6;
                    color: white;
                }
                
                .compose-modal .discard-btn {
                    background: #f3f4f6;
                    color: #374151;
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // Initialize
    updatePaginationInfo(emailItems.length);
    
    // Add some random starred emails for demo
    setTimeout(() => {
        const randomStars = document.querySelectorAll('.email-star');
        [1, 4, 8].forEach(index => {
            if (randomStars[index]) {
                randomStars[index].click();
            }
        });
    }, 1000);
});