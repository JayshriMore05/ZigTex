// Dashboard-specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    
    // Data points tooltips
    const dataPoints = document.querySelectorAll('.data-point');
    
    dataPoints.forEach(point => {
        point.addEventListener('mouseenter', function(e) {
            const type = this.classList.contains('sent') ? 'Sent' : 
                        this.classList.contains('replied') ? 'Replied' : 'Bounced';
            const value = this.getAttribute('data-value');
            
            // Create tooltip
            const tooltip = document.createElement('div');
            tooltip.className = 'data-tooltip';
            tooltip.innerHTML = `
                <div class="tooltip-header">${type}</div>
                <div class="tooltip-value">${value} emails</div>
            `;
            
            // Position tooltip
            const rect = this.getBoundingClientRect();
            tooltip.style.cssText = `
                position: fixed;
                left: ${rect.left + window.scrollX + rect.width/2}px;
                top: ${rect.top + window.scrollY - 10}px;
                transform: translate(-50%, -100%);
                background: #1f2937;
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                z-index: 1000;
                white-space: nowrap;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            
            // Add arrow
            const arrow = document.createElement('div');
            arrow.style.cssText = `
                position: absolute;
                bottom: -4px;
                left: 50%;
                transform: translateX(-50%);
                width: 0;
                height: 0;
                border-left: 5px solid transparent;
                border-right: 5px solid transparent;
                border-top: 5px solid #1f2937;
            `;
            
            tooltip.appendChild(arrow);
            document.body.appendChild(tooltip);
            
            // Remove tooltip on mouse leave
            this.addEventListener('mouseleave', function() {
                tooltip.remove();
            });
            
            // Also remove tooltip if clicking elsewhere
            document.addEventListener('click', function removeTooltip() {
                tooltip.remove();
                document.removeEventListener('click', removeTooltip);
            });
        });
    });
    
    // Chart hover effect for lines
    const chartLines = document.querySelectorAll('.line');
    
    chartLines.forEach(line => {
        line.addEventListener('mouseenter', function() {
            const isSent = this.classList.contains('sent-line');
            const isReplied = this.classList.contains('replied-line');
            
            if (isSent) {
                document.querySelectorAll('.data-point.sent').forEach(p => {
                    p.style.transform = 'translate(-50%, 50%) scale(1.3)';
                    p.style.boxShadow = '0 0 0 4px rgba(59, 130, 246, 0.2)';
                });
                this.style.strokeWidth = '3';
            } else if (isReplied) {
                document.querySelectorAll('.data-point.replied').forEach(p => {
                    p.style.transform = 'translate(-50%, 50%) scale(1.3)';
                    p.style.boxShadow = '0 0 0 4px rgba(245, 158, 11, 0.2)';
                });
                this.style.strokeWidth = '3';
            }
        });
        
        line.addEventListener('mouseleave', function() {
            document.querySelectorAll('.data-point').forEach(p => {
                p.style.transform = 'translate(-50%, 50%) scale(1)';
                p.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
            });
            this.style.strokeWidth = '2';
        });
    });
    
    // Time select functionality
    const timeSelect = document.querySelector('.time-select');
    if (timeSelect) {
        timeSelect.addEventListener('change', function() {
            const selectedTime = this.value;
            
            // Simulate data change based on time selection
            const stats = document.querySelectorAll('.stat-value');
            const xValues = document.querySelectorAll('.x-values div');
            
            switch(selectedTime) {
                case 'Last 7 Days':
                    // Update x-axis
                    xValues[0].textContent = 'Mon';
                    xValues[1].textContent = 'Tue';
                    xValues[2].textContent = 'Wed';
                    xValues[3].textContent = 'Thu';
                    xValues[4].textContent = 'Fri';
                    break;
                    
                case 'Last 24 Hours':
                    // Update x-axis
                    xValues[0].textContent = '09:00';
                    xValues[1].textContent = '12:00';
                    xValues[2].textContent = '15:00';
                    xValues[3].textContent = '18:00';
                    xValues[4].textContent = '21:00';
                    break;
                    
                default: // Last 30 Days
                    xValues[0].textContent = '08 Jan';
                    xValues[1].textContent = '09 Jan';
                    xValues[2].textContent = '10 Jan';
                    xValues[3].textContent = '11 Jan';
                    xValues[4].textContent = '12 Jan';
            }
            
            // Show loading effect
            const chartSection = document.querySelector('.chart-section');
            chartSection.style.opacity = '0.7';
            
            setTimeout(() => {
                chartSection.style.opacity = '1';
                
                // Add visual feedback
                const feedback = document.createElement('div');
                feedback.textContent = `Showing data for ${selectedTime}`;
                feedback.style.cssText = `
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background: #10b981;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 8px;
                    font-size: 14px;
                    z-index: 1000;
                    animation: slideIn 0.3s ease;
                `;
                
                document.body.appendChild(feedback);
                
                setTimeout(() => {
                    feedback.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => feedback.remove(), 300);
                }, 2000);
            }, 300);
        });
    }
    
    // Real-time stats simulation
    function simulateRealTimeUpdates() {
        setInterval(() => {
            // Randomly increase emails sent
            const emailsSent = document.querySelector('.stat-card:nth-child(2) .stat-value');
            if (emailsSent && Math.random() > 0.7) {
                const current = parseInt(emailsSent.textContent);
                emailsSent.textContent = current + 1;
                
                // Update delivered
                const delivered = document.querySelector('.stat-card:nth-child(3) .stat-value');
                if (delivered) {
                    const deliveredNum = parseInt(delivered.textContent);
                    delivered.textContent = deliveredNum + 1;
                }
                
                // Randomly update replied
                if (Math.random() > 0.8) {
                    const replied = document.querySelector('.stat-card:nth-child(4) .stat-value');
                    if (replied) {
                        const repliedNum = parseInt(replied.textContent);
                        replied.textContent = repliedNum + 1;
                        
                        // Add animation
                        replied.style.color = '#10b981';
                        replied.style.transform = 'scale(1.1)';
                        setTimeout(() => {
                            replied.style.color = '';
                            replied.style.transform = '';
                        }, 500);
                    }
                }
            }
        }, 8000);
    }
    
    simulateRealTimeUpdates();
    
    // Export chart functionality
    const exportChart = document.createElement('button');
    exportChart.innerHTML = '<i class="fas fa-download"></i> Export Chart';
    exportChart.className = 'export-btn';
    exportChart.style.cssText = `
        position: absolute;
        right: 20px;
        bottom: 20px;
        background: white;
        border: 1px solid #e5e7eb;
        color: #374151;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        z-index: 10;
    `;
    
    const chartSection = document.querySelector('.chart-section');
    if (chartSection) {
        chartSection.style.position = 'relative';
        chartSection.appendChild(exportChart);
        
        exportChart.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-check"></i> Exported!';
                this.style.background = '#10b981';
                this.style.color = 'white';
                this.style.borderColor = '#10b981';
                
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-download"></i> Export Chart';
                    this.style.background = 'white';
                    this.style.color = '#374151';
                    this.style.borderColor = '#e5e7eb';
                    this.disabled = false;
                }, 1500);
            }, 1000);
        });
    }
    
    // Animation for chart load
    function animateChart() {
        const dataPoints = document.querySelectorAll('.data-point');
        const lines = document.querySelectorAll('.line');
        
        // Animate data points
        dataPoints.forEach((point, index) => {
            point.style.opacity = '0';
            point.style.transform = 'translate(-50%, 50%) scale(0)';
            
            setTimeout(() => {
                point.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                point.style.opacity = '1';
                point.style.transform = 'translate(-50%, 50%) scale(1)';
            }, index * 100);
        });
        
        // Animate lines
        lines.forEach((line, index) => {
            const length = line.getTotalLength();
            line.style.strokeDasharray = length;
            line.style.strokeDashoffset = length;
            
            setTimeout(() => {
                line.style.transition = 'stroke-dashoffset 1s ease';
                line.style.strokeDashoffset = '0';
            }, 300 + index * 200);
        });
    }
    
    // Animate on first load
    setTimeout(animateChart, 500);
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            const searchInput = document.querySelector('.search-box input');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Ctrl/Cmd + D to go to dashboard
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            if (!window.location.pathname.includes('dashboard.php')) {
                window.location.href = 'dashboard.php';
            }
        }
        
        // Ctrl/Cmd + C to create campaign
        if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
            e.preventDefault();
            window.location.href = 'create-campaign.php';
        }
    });
    
    // Add CSS animations
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
        
        @keyframes pulse {
            0%, 100% {
                transform: translate(-50%, 50%) scale(1);
            }
            50% {
                transform: translate(-50%, 50%) scale(1.1);
            }
        }
        
        .data-point:hover {
            animation: pulse 1s infinite;
        }
    `;
    document.head.appendChild(style);
});