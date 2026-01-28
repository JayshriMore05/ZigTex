<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission for adding new company
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_company') {
    try {
        $pdo = db();
        
        // Sanitize and validate input
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $industry = filter_input(INPUT_POST, 'industry', FILTER_SANITIZE_STRING);
        $website = filter_input(INPUT_POST, 'website', FILTER_SANITIZE_URL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        
        // Validate required fields
        if (empty($name)) {
            $_SESSION['error'] = "Company name is required";
            header("Location: companies.php");
            exit();
        }
        
        // Insert into database
        $insert_query = "INSERT INTO companies (user_id, name, industry, website, phone, address, description, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($insert_query);
        $stmt->execute([$user_id, $name, $industry, $website, $phone, $address, $description]);
        
        $_SESSION['success'] = "Company added successfully!";
        header("Location: companies.php");
        exit();
        
    } catch (PDOException $e) {
        error_log("Error adding company: " . $e->getMessage());
        $_SESSION['error'] = "Failed to add company. Please try again.";
        header("Location: companies.php");
        exit();
    }
}

try {
    $pdo = db();
    
    // Get total companies count
    $total_companies_query = "SELECT COUNT(*) as total FROM companies WHERE user_id = ?";
    $stmt = $pdo->prepare($total_companies_query);
    $stmt->execute([$user_id]);
    $total_companies = $stmt->fetchColumn();
    
    // Get total contacts count
    $total_contacts_query = "SELECT COUNT(*) as total FROM contacts WHERE user_id = ?";
    $stmt = $pdo->prepare($total_contacts_query);
    $stmt->execute([$user_id]);
    $total_contacts = $stmt->fetchColumn();
    
    // Get companies with contact counts
    $companies_query = "
        SELECT 
            c.*,
            (SELECT COUNT(*) FROM contacts WHERE company_id = c.id) as contact_count
        FROM companies c
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ";
    
    $stmt = $pdo->prepare($companies_query);
    $stmt->execute([$user_id]);
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate engagement rates
    foreach ($companies as &$company) {
        // For demo, generate random engagement data
        $company['replied_count'] = rand(0, min($company['contact_count'] * 2, 20));
        $company['total_engagements'] = $company['contact_count'] > 0 ? $company['contact_count'] * rand(2, 5) : 0;
        
        $company['engagement_rate'] = $company['total_engagements'] > 0 
            ? round(($company['replied_count'] / $company['total_engagements']) * 100, 1)
            : 0;
        
        // Add recent activity
        $company['recent_activity'] = [
            'last_contact' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
            'next_followup' => date('Y-m-d H:i:s', strtotime('+' . rand(1, 14) . ' days'))
        ];
    }
    
} catch (PDOException $e) {
    // Log error and use sample data for demo
    error_log("Database error in companies.php: " . $e->getMessage());
    
    // Fallback to sample data
    $total_companies = 6;
    $total_contacts = 6;
    
    $companies = [
        [
            'id' => 1,
            'name' => 'Digital Tech',
            'industry' => 'Technology',
            'website' => 'https://digitaltech.com',
            'phone' => '+1 (555) 123-4567',
            'contact_count' => 1,
            'replied_count' => 2,
            'total_engagements' => 5,
            'engagement_rate' => 40.0,
            'recent_activity' => [
                'last_contact' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'next_followup' => date('Y-m-d H:i:s', strtotime('+5 days'))
            ]
        ],
        [
            'id' => 2,
            'name' => 'Cloud Systems',
            'industry' => 'Technology',
            'website' => 'https://cloudsys.com',
            'phone' => '+1 (555) 234-5678',
            'contact_count' => 1,
            'replied_count' => 0,
            'total_engagements' => 3,
            'engagement_rate' => 0.0,
            'recent_activity' => [
                'last_contact' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'next_followup' => date('Y-m-d H:i:s', strtotime('+14 days'))
            ]
        ],
        [
            'id' => 3,
            'name' => 'Future Tech',
            'industry' => 'Technology',
            'website' => 'https://futuretech.io',
            'phone' => '+1 (555) 345-6789',
            'contact_count' => 1,
            'replied_count' => 1,
            'total_engagements' => 4,
            'engagement_rate' => 25.0,
            'recent_activity' => [
                'last_contact' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'next_followup' => date('Y-m-d H:i:s', strtotime('+7 days'))
            ]
        ],
        [
            'id' => 4,
            'name' => 'WebDev Pro',
            'industry' => 'Technology',
            'website' => 'https://webdev.com',
            'phone' => '+1 (555) 456-7890',
            'contact_count' => 1,
            'replied_count' => 1,
            'total_engagements' => 3,
            'engagement_rate' => 33.3,
            'recent_activity' => [
                'last_contact' => date('Y-m-d H:i:s', strtotime('-3 days')),
                'next_followup' => date('Y-m-d H:i:s', strtotime('+10 days'))
            ]
        ],
        [
            'id' => 5,
            'name' => 'DataScience AI',
            'industry' => 'Technology',
            'website' => 'https://datascience.ai',
            'phone' => '+1 (555) 567-8901',
            'contact_count' => 1,
            'replied_count' => 3,
            'total_engagements' => 6,
            'engagement_rate' => 50.0,
            'recent_activity' => [
                'last_contact' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'next_followup' => date('Y-m-d H:i:s', strtotime('+3 days'))
            ]
        ],
        [
            'id' => 6,
            'name' => 'Mobile Apps Inc.',
            'industry' => 'Technology',
            'website' => 'https://mobileapps.co',
            'phone' => '+1 (555) 678-9012',
            'contact_count' => 1,
            'replied_count' => 2,
            'total_engagements' => 4,
            'engagement_rate' => 50.0,
            'recent_activity' => [
                'last_contact' => date('Y-m-d H:i:s', strtotime('-0 days')),
                'next_followup' => date('Y-m-d H:i:s', strtotime('+2 days'))
            ]
        ]
    ];
}

// Helper function for time ago
function time_ago($date) {
    $timestamp = strtotime($date);
    $current_time = time();
    $diff = $current_time - $timestamp;
    
    $intervals = array(
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );
    
    foreach ($intervals as $seconds => $label) {
        $div = $diff / $seconds;
        if ($div >= 1) {
            $rounded = floor($div);
            return $rounded . ' ' . $label . ($rounded > 1 ? 's' : '') . ' ago';
        }
    }
    
    return 'just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Companies - ZigTex</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        /* Companies Page Styles */
        .companies-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .companies-header h1 {
            font-size: 24px;
            color: #1f2937;
            font-weight: 700;
            margin: 0;
        }
        
        .create-company-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .create-company-btn:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        
        /* Success/Error Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #10b981;
            color: white;
            border-left: 4px solid #059669;
        }
        
        .alert-error {
            background: #ef4444;
            color: white;
            border-left: 4px solid #dc2626;
        }
        
        .alert-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background 0.2s;
        }
        
        .alert-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Quick Stats */
        .quick-stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .quick-stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .quick-stat-card:hover {
            border-color: #d1d5db;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .quick-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .quick-stat-label {
            font-size: 14px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .quick-stat-label i {
            font-size: 12px;
        }
        
        /* Companies Table */
        .companies-table-section {
            background: white;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .companies-table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .companies-table-header h2 {
            font-size: 18px;
            color: #1f2937;
            font-weight: 600;
            margin: 0;
        }
        
        .companies-search-box {
            position: relative;
            width: 300px;
        }
        
        .companies-search-box input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #374151;
            background: white;
            transition: border-color 0.2s;
        }
        
        .companies-search-box input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .companies-search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 14px;
        }
        
        /* Table Styles */
        .companies-table-container {
            overflow-x: auto;
        }
        
        .companies-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        .companies-table thead {
            background: #f9fafb;
        }
        
        .companies-table th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 500;
            color: #6b7280;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .companies-table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #374151;
            transition: background-color 0.2s;
        }
        
        .companies-table tbody tr {
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .companies-table tbody tr:hover {
            background: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .company-info-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .company-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .company-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .company-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 15px;
        }
        
        .company-industry {
            font-size: 12px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .contact-count-cell {
            font-weight: 500;
            color: #1f2937;
        }
        
        .engagement-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .engagement-value {
            min-width: 40px;
            font-weight: 500;
        }
        
        .engagement-indicator {
            flex: 1;
            max-width: 80px;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .engagement-fill {
            height: 100%;
            background: #10b981;
            transition: width 0.3s ease;
        }
        
        .replied-cell {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #3b82f6;
            font-weight: 500;
        }
        
        .recent-activity-cell {
            font-size: 13px;
            color: #6b7280;
            white-space: nowrap;
        }
        
        .company-actions-cell {
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .companies-table tbody tr:hover .company-actions-cell {
            opacity: 1;
        }
        
        .company-action-btn {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
            background: white;
            color: #6b7280;
        }
        
        .company-action-btn:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            transform: translateY(-1px);
        }
        
        /* Empty State */
        .companies-empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .companies-empty-state i {
            font-size: 48px;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        
        .companies-empty-state h3 {
            font-size: 18px;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .companies-empty-state p {
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        /* FIXED MODAL STYLES - Updated for proper display */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            box-sizing: border-box;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
            position: relative;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #1f2937;
            font-size: 24px;
            font-weight: 600;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
            transition: all 0.2s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #f3f4f6;
        }
        
        .close-btn:hover {
            background: #e5e7eb;
            color: #ef4444;
        }
        
        /* Enhanced Modal Form Styles */
        .form-container {
            padding: 24px;
        }
        
        .form-grid {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row.two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-row .full-width {
            width: 100%;
        }
        
        .modal-form-group {
            margin-bottom: 0;
        }
        
        .modal-form-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
            font-weight: 500;
            color: #1f2937;
            font-size: 14px;
        }
        
        .required-asterisk {
            color: #ef4444;
            font-weight: bold;
        }
        
        .optional-text {
            color: #6b7280;
            font-size: 12px;
            font-weight: normal;
        }
        
        .input-with-icon {
            position: relative;
            width: 100%;
        }
        
        .input-with-icon i,
        .select-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
            z-index: 1;
        }
        
        .input-with-icon input,
        .input-with-icon textarea,
        .select-wrapper select {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            color: #1f2937;
            background: white;
            transition: all 0.2s;
            font-family: inherit;
            box-sizing: border-box;
        }
        
        .input-with-icon input:focus,
        .input-with-icon textarea:focus,
        .select-wrapper select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        
        .input-with-icon input.error,
        .input-with-icon textarea.error,
        .select-wrapper select.error {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }
        
        .input-with-icon textarea {
            min-height: 80px;
            resize: vertical;
            line-height: 1.5;
            padding-top: 14px;
        }
        
        .select-wrapper {
            position: relative;
            width: 100%;
        }
        
        .select-wrapper .select-arrow {
            left: auto;
            right: 16px;
            pointer-events: none;
        }
        
        .select-wrapper select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            padding-right: 48px;
            background: white;
        }
        
        .field-hint {
            font-size: 13px;
            color: #6b7280;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .char-count {
            font-size: 13px;
            color: #6b7280;
            text-align: right;
            margin-top: 8px;
            font-weight: 500;
        }
        
        .char-count.warning {
            color: #f59e0b;
        }
        
        .char-count.error {
            color: #ef4444;
        }
        
        .error-message {
            color: #ef4444;
            font-size: 13px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        
        /* Improved Form Actions */
        .form-actions {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 2px solid #f3f4f6;
            position: sticky;
            bottom: 0;
            background: white;
            padding-bottom: 8px;
            border-radius: 0 0 12px 12px;
        }
        
        .modal-btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 140px;
            justify-content: center;
            letter-spacing: 0.3px;
        }
        
        .modal-btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        
        .modal-btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }
        
        .modal-btn-secondary {
            background: white;
            color: #374151;
            border: 2px solid #d1d5db;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .modal-btn-secondary:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Loading Overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9998;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Pagination */
        .pagination-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .pagination-info {
            font-size: 14px;
            color: #6b7280;
        }
        
        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }
        
        .pagination-btn:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
        }
        
        .pagination-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .page-numbers {
            display: flex;
            gap: 4px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .quick-stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .companies-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .companies-table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
            
            .companies-search-box {
                width: 100%;
            }
            
            .quick-stats-row {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 100%;
                max-height: 85vh;
                margin: 0;
                border-radius: 0;
            }
            
            .modal {
                padding: 0;
            }
            
            .form-row.two-columns {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 12px;
                position: static;
                padding: 16px 0 0 0;
                border-top: 2px solid #f3f4f6;
            }
            
            .modal-btn {
                width: 100%;
                min-width: auto;
                padding: 16px;
            }
            
            .modal-header {
                padding: 20px;
            }
            
            .form-container {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .modal-header h2 {
                font-size: 20px;
            }
            
            .modal-btn {
                padding: 14px;
                font-size: 14px;
            }
            
            .input-with-icon input,
            .input-with-icon textarea,
            .select-wrapper select {
                padding: 12px 16px 12px 44px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="app-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <span><?php echo $_SESSION['success']; ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <span><?php echo $_SESSION['error']; ?></span>
                <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
            <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Companies Header -->
            <div class="companies-header">
                <h1>Companies</h1>
                <button class="create-company-btn" onclick="showAddCompanyModal()">
                    <i class="fas fa-plus"></i> Add Company
                </button>
            </div>

            <!-- Quick Stats -->
            <div class="quick-stats-row">
                <div class="quick-stat-card">
                    <div class="quick-stat-value"><?php echo $total_companies; ?></div>
                    <div class="quick-stat-label">
                        <i class="fas fa-building"></i>
                        <span>Total Companies</span>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="quick-stat-value"><?php echo $total_contacts; ?></div>
                    <div class="quick-stat-label">
                        <i class="fas fa-users"></i>
                        <span>Total Contacts</span>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="quick-stat-value">
                        <?php 
                        $avg_contacts = $total_companies > 0 ? round($total_contacts / $total_companies, 1) : 0;
                        echo $avg_contacts;
                        ?>
                    </div>
                    <div class="quick-stat-label">
                        <i class="fas fa-user-friends"></i>
                        <span>Avg. Contacts/Company</span>
                    </div>
                </div>
                <div class="quick-stat-card">
                    <div class="quick-stat-value">
                        <?php 
                        $total_replies = array_sum(array_column($companies, 'replied_count'));
                        echo $total_replies;
                        ?>
                    </div>
                    <div class="quick-stat-label">
                        <i class="fas fa-reply"></i>
                        <span>Total Replies</span>
                    </div>
                </div>
            </div>

            <!-- Companies Table Section -->
            <section class="companies-table-section">
                <div class="companies-table-header">
                    <h2>Search companies</h2>
                    <div class="companies-search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="companiesSearch" placeholder="Search companies by name or industry..." autocomplete="off">
                    </div>
                </div>

                <div class="companies-table-container">
                    <table class="companies-table">
                        <thead>
                            <tr>
                                <th>COMPANY</th>
                                <th>CONTACTS</th>
                                <th>ENGAGEMENT RATE</th>
                                <th>REPLIES</th>
                                <th>RECENT ACTIVITY</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="companiesTableBody">
                            <?php if (empty($companies)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="companies-empty-state">
                                        <i class="fas fa-building"></i>
                                        <h3>No companies found</h3>
                                        <p>Add your first company to start tracking relationships</p>
                                        <button class="create-company-btn" onclick="showAddCompanyModal()" style="display: inline-flex;">
                                            <i class="fas fa-plus"></i> Add Company
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($companies as $company): ?>
                                <tr class="company-row" 
                                    data-company-id="<?php echo $company['id']; ?>"
                                    onclick="viewCompanyDetails('<?php echo $company['id']; ?>')">
                                    <td>
                                        <div class="company-info-cell">
                                            <div class="company-logo">
                                                <?php echo strtoupper(substr($company['name'], 0, 2)); ?>
                                            </div>
                                            <div class="company-details">
                                                <div class="company-name">
                                                    <?php echo htmlspecialchars($company['name']); ?>
                                                </div>
                                                <div class="company-industry">
                                                    <i class="fas fa-industry"></i>
                                                    <?php echo htmlspecialchars($company['industry'] ?? 'N/A'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="contact-count-cell">
                                        <?php echo $company['contact_count']; ?> contacts
                                    </td>
                                    <td>
                                        <div class="engagement-cell">
                                            <span class="engagement-value"><?php echo $company['engagement_rate']; ?>%</span>
                                            <div class="engagement-indicator">
                                                <div class="engagement-fill" style="width: <?php echo min($company['engagement_rate'], 100); ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="replied-cell">
                                        <i class="fas fa-reply"></i>
                                        <?php echo $company['replied_count']; ?> replies
                                    </td>
                                    <td class="recent-activity-cell">
                                        Last: <?php echo time_ago($company['recent_activity']['last_contact']); ?><br>
                                        Next: <?php echo date('M d', strtotime($company['recent_activity']['next_followup'])); ?>
                                    </td>
                                    <td>
                                        <div class="company-actions-cell">
                                            <button class="company-action-btn" onclick="event.stopPropagation(); editCompany('<?php echo $company['id']; ?>')" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="company-action-btn" onclick="event.stopPropagation(); viewCompanyContacts('<?php echo $company['id']; ?>')" title="View Contacts">
                                                <i class="fas fa-users"></i>
                                            </button>
                                            <button class="company-action-btn" onclick="event.stopPropagation(); deleteCompany('<?php echo $company['id']; ?>', '<?php echo htmlspecialchars($company['name']); ?>')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (!empty($companies)): ?>
                <div class="pagination-section">
                    <div class="pagination-info">
                        Showing 1-<?php echo count($companies); ?> of <?php echo count($companies); ?>
                    </div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" onclick="window.location.href='companies.php?page=1'">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <div class="page-numbers">
                            <button class="pagination-btn active">1</button>
                            <?php if (count($companies) >= 10): ?>
                            <button class="pagination-btn" onclick="window.location.href='companies.php?page=2'">2</button>
                            <?php endif; ?>
                            <?php if (count($companies) >= 20): ?>
                            <button class="pagination-btn" onclick="window.location.href='companies.php?page=3'">3</button>
                            <?php endif; ?>
                        </div>
                        <?php if (count($companies) >= 10): ?>
                        <button class="pagination-btn" onclick="window.location.href='companies.php?page=2'">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Add Company Modal -->
    <div class="modal" id="addCompanyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Company</h2>
                <button class="close-btn" type="button" onclick="closeModal('addCompanyModal')">&times;</button>
            </div>
            
            <div class="form-container">
                <form id="addCompanyForm" method="POST" action="companies.php">
                    <input type="hidden" name="action" value="add_company">
                    
                    <div class="form-grid">
                        <div class="form-row">
                            <div class="modal-form-group full-width">
                                <label for="companyName">
                                    Company Name 
                                    <span class="required-asterisk">*</span>
                                </label>
                                <div class="input-with-icon">
                                    <i class="fas fa-building"></i>
                                    <input type="text" id="companyName" name="name" required 
                                           placeholder="Enter company name" autocomplete="off">
                                </div>
                                <div class="field-hint">Required field</div>
                            </div>
                        </div>
                        
                        <div class="form-row two-columns">
                            <div class="modal-form-group">
                                <label for="companyIndustry">Industry</label>
                                <div class="select-wrapper">
                                    <i class="fas fa-industry"></i>
                                    <select id="companyIndustry" name="industry">
                                        <option value="">Select Industry</option>
                                        <option value="Technology">Technology</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Healthcare">Healthcare</option>
                                        <option value="Retail">Retail</option>
                                        <option value="Manufacturing">Manufacturing</option>
                                        <option value="Education">Education</option>
                                        <option value="Real Estate">Real Estate</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Consulting">Consulting</option>
                                        <option value="Hospitality">Hospitality</option>
                                        <option value="Transportation">Transportation</option>
                                        <option value="Energy">Energy</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <i class="fas fa-chevron-down select-arrow"></i>
                                </div>
                            </div>
                            
                            <div class="modal-form-group">
                                <label for="companyPhone">Phone Number</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" id="companyPhone" name="phone" 
                                           placeholder="+1 (555) 123-4567" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="modal-form-group full-width">
                                <label for="companyWebsite">Website URL</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-globe"></i>
                                    <input type="url" id="companyWebsite" name="website" 
                                           placeholder="https://example.com" autocomplete="off">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="modal-form-group full-width">
                                <label for="companyAddress">Address</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <textarea id="companyAddress" name="address" rows="2" 
                                              placeholder="Street, City, State, Zip Code, Country"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="modal-form-group full-width">
                                <label for="companyDescription">
                                    Description
                                    <span class="optional-text">(Optional)</span>
                                </label>
                                <textarea id="companyDescription" name="description" rows="4" 
                                          placeholder="Brief description about the company, services, products, etc."></textarea>
                                <div class="char-count">
                                    <span id="descCharCount">0</span>/500 characters
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('addCompanyModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="modal-btn modal-btn-primary">
                            <i class="fas fa-plus"></i> Add Company
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script>
        // Search functionality
        let searchTimeout;
        document.getElementById('companiesSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.toLowerCase().trim();
            
            searchTimeout = setTimeout(() => {
                const rows = document.querySelectorAll('.company-row');
                let visibleCount = 0;
                
                rows.forEach(row => {
                    const companyName = row.querySelector('.company-name').textContent.toLowerCase();
                    const companyIndustry = row.querySelector('.company-industry').textContent.toLowerCase();
                    
                    if (companyName.includes(searchTerm) || companyIndustry.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Update pagination info
                const infoElement = document.querySelector('.pagination-info');
                if (infoElement) {
                    infoElement.textContent = `Showing 1-${visibleCount} of ${visibleCount}`;
                }
            }, 300);
        });
        
        // Modal functions
        function showAddCompanyModal() {
            document.getElementById('addCompanyModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            document.getElementById('companyName').focus();
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                
                // Reset form
                const form = document.getElementById('addCompanyForm');
                if (form) {
                    form.reset();
                    // Reset character count
                    const charCount = document.getElementById('descCharCount');
                    if (charCount) {
                        charCount.textContent = '0';
                        charCount.parentElement.classList.remove('warning', 'error');
                    }
                    // Clear validation errors
                    const errorElements = form.querySelectorAll('.error-message');
                    errorElements.forEach(el => el.remove());
                    const inputElements = form.querySelectorAll('input, select, textarea');
                    inputElements.forEach(el => {
                        el.classList.remove('error');
                        el.style.borderColor = '';
                        el.style.boxShadow = '';
                    });
                }
            }
        }
        
        // Loading functions
        function showLoading(show) {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (show) {
                loadingOverlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            } else {
                loadingOverlay.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
        
        // Form validation
        function validateForm(form) {
            let isValid = true;
            const companyName = form.querySelector('#companyName');
            const errorElements = form.querySelectorAll('.error-message');
            
            // Clear previous errors
            errorElements.forEach(el => el.remove());
            const inputElements = form.querySelectorAll('input, select, textarea');
            inputElements.forEach(el => {
                el.classList.remove('error');
                el.style.borderColor = '';
                el.style.boxShadow = '';
            });
            
            // Validate required fields
            if (!companyName.value.trim()) {
                showFieldError(companyName, 'Company name is required');
                isValid = false;
            }
            
            // Validate website format if provided
            const website = form.querySelector('#companyWebsite');
            if (website.value && !isValidUrl(website.value)) {
                showFieldError(website, 'Please enter a valid URL');
                isValid = false;
            }
            
            return isValid;
        }
        
        function showFieldError(input, message) {
            input.classList.add('error');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.style.color = '#ef4444';
            errorDiv.style.fontSize = '13px';
            errorDiv.style.marginTop = '8px';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            input.parentNode.appendChild(errorDiv);
            
            // Add red border
            input.style.borderColor = '#ef4444';
            input.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
        }
        
        function isValidUrl(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        }
        
        // Form submission
        document.getElementById('addCompanyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm(this)) {
                showNotification('Please fix the errors in the form', 'error');
                return;
            }
            
            showLoading(true);
            
            try {
                const formData = new FormData(this);
                
                // Submit form
                const response = await fetch('companies.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Check if response is a redirect
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    // Try to parse as JSON
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.includes("application/json")) {
                        const result = await response.json();
                        if (result.success) {
                            showNotification(result.message || 'Company added successfully!', 'success');
                            closeModal('addCompanyModal');
                            // Reload page after 1 second
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showNotification(result.error || 'Failed to add company', 'error');
                        }
                    } else {
                        // If not JSON, reload page
                        window.location.reload();
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            } finally {
                showLoading(false);
            }
        });
        
        // Company actions
        function viewCompanyDetails(companyId) {
            window.location.href = `company_details.php?id=${companyId}`;
        }
        
        function editCompany(companyId) {
            // In a real app, this would load company data into modal
            showAddCompanyModal();
            showNotification('Edit functionality would load company data here', 'info');
        }
        
        function viewCompanyContacts(companyId) {
            window.location.href = `prospects.php?company_id=${companyId}`;
        }
        
        function deleteCompany(companyId, companyName) {
            if (confirm(`Are you sure you want to delete "${companyName}"?\n\nThis will also delete all associated contacts.`)) {
                showLoading(true);
                
                // Simulate API call
                setTimeout(() => {
                    showNotification(`Company "${companyName}" deleted successfully!`, 'success');
                    showLoading(false);
                    
                    // Reload page after 1 second
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }, 500);
            }
        }
        
        // Notification function
        function showNotification(message, type = 'success') {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            
            if (typeof Toastify !== 'undefined') {
                Toastify({
                    text: message,
                    duration: 3000,
                    close: true,
                    gravity: "bottom",
                    position: "right",
                    backgroundColor: colors[type] || colors.info,
                    stopOnFocus: true,
                    style: {
                        borderRadius: '10px',
                        fontFamily: 'inherit',
                        fontSize: '14px',
                        padding: '16px 20px',
                        fontWeight: '500'
                    }
                }).showToast();
            } else {
                alert(message);
            }
        }
        
        // Character counter for description
        const descriptionTextarea = document.getElementById('companyDescription');
        const charCount = document.getElementById('descCharCount');
        
        if (descriptionTextarea && charCount) {
            descriptionTextarea.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = length;
                
                // Update character count styling
                if (length > 450) {
                    charCount.parentElement.classList.add('warning');
                    charCount.parentElement.classList.remove('error');
                } else if (length >= 500) {
                    charCount.parentElement.classList.add('error');
                    charCount.parentElement.classList.remove('warning');
                    // Truncate if over limit
                    if (length > 500) {
                        this.value = this.value.substring(0, 500);
                        charCount.textContent = '500';
                    }
                } else {
                    charCount.parentElement.classList.remove('warning', 'error');
                }
            });
        }
        
        // Phone number formatting
        const phoneInput = document.getElementById('companyPhone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length > 0) {
                    if (!value.startsWith('+')) {
                        value = '+' + value;
                    }
                    
                    // Format as user types
                    if (value.length > 3) {
                        value = value.replace(/(\+\d{1,3})(\d{3})/, '$1 ($2) ');
                    }
                    if (value.length > 9) {
                        value = value.replace(/(\+\d{1,3}\s\(\d{3}\)\s)(\d{3})/, '$1$2-');
                    }
                    if (value.length > 14) {
                        value = value.substring(0, 14);
                    }
                }
                
                e.target.value = value;
            });
        }
        
        // Close modal when clicking outside or pressing ESC
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('addCompanyModal');
            if (e.target === modal) {
                closeModal('addCompanyModal');
            }
        });
        
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('addCompanyModal');
            }
        });
        
        // Auto-close alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>