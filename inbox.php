

<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox - ZigTex</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/inbox.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="main-header">
                <div class="header-left">
                    <h1>Inbox</h1>
                    <div class="date-display">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo date('F j, Y'); ?></span>
                    </div>
                </div>
                <div class="header-right">
                    <button class="compose-btn">
                        <i class="fas fa-edit"></i> Compose
                    </button>
                </div>
            </header>

            <!-- Search Container -->
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search emails..." id="searchInput">
                    <button class="clear-search" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="search-filters">
                    <button class="filter-btn active">All</button>
                    <button class="filter-btn">Unread</button>
                    <button class="filter-btn">Starred</button>
                    <button class="filter-btn">Important</button>
                </div>
            </div>

            <!-- Email List -->
            <div class="email-list">
                <!-- Email Item 1 -->
                <div class="email-item unread">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">Ajay Kumar Gupta</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">CV</div>
                        <div class="email-preview">
                            Dear Sir / Ma'am, Greetings for the Day I hope this message finds you well. My name is Er Ajay Kumar Gupta. I have attached my CV for your consideration. I hold ...
                        </div>
                    </div>
                    <div class="email-time">1:02 PM</div>
                </div>

                <!-- Email Item 2 -->
                <div class="email-item unread">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">Hr vConnect</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">Invitation: vConnect iDees - UI Designer Intern - Virtual Round I</div>
                        <div class="email-preview">
                            @ Mon Jan 12, 2026 4:30pm - 5pm (IST) (arpan@vconnectidees.com) vConnect iDees - UI Designer Intern...
                        </div>
                    </div>
                    <div class="email-time">12:36 PM</div>
                </div>

                <!-- Email Item 3 -->
                <div class="email-item unread">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">Hr vConnect</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">Invitation: vConnect iDees - UI Designer Intern - Virtual Round II</div>
                        <div class="email-preview">
                            @ Mon Jan 12, 2026 3:30pm - 4pm (IST) (arpan@vconnectidees.com) vConnect iDees - UI Designer Intern...
                        </div>
                    </div>
                    <div class="email-time">12:36 PM</div>
                </div>

                <!-- Email Item 4 -->
                <div class="email-item">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">Muskan Nigam</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">New Test Case study Stop Unsubscribe</div>
                    </div>
                    <div class="email-time">Jan 11</div>
                </div>

                <!-- Email Item 5 -->
                <div class="email-item">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">Shraddha Thorhate</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">Hello Arpan Chavan</div>
                        <div class="email-preview">
                            Hope you are doing well!.. This is Test Campaign. Vconnect arpan@vconnectidees.com Thank You.
                        </div>
                    </div>
                    <div class="email-time">Jan 10</div>
                </div>

                <!-- Email Item 6 -->
                <div class="email-item">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">Muskan Nigam</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">New Test Case study Stop Unsubscribe</div>
                    </div>
                    <div class="email-time">Jan 10</div>
                </div>

                <!-- Email Item 7 -->
                <div class="email-item">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">Shraddha Thorhate</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">test email - variant A</div>
                        <div class="email-preview">
                            Hey Test User 10, Hope you are doing well! best regards, Shraddha click here to Unsubscribe
                        </div>
                    </div>
                    <div class="email-time">Jan 9</div>
                </div>

                <!-- Email Item 8 -->
                <div class="email-item">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">aastha sahu</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">Application for UI design intern</div>
                        <div class="email-preview">
                            Hi, I hope you're doing well. My name is Aastha Sahu, and I am a UI/UX Designer with 3.5+ years of experience in creating user-friend...
                        </div>
                    </div>
                    <div class="email-time">Jan 9</div>
                </div>

                <!-- Email Item 9 -->
                <div class="email-item">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">"Dinesh Ragavendra. B"</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">Hi Team,</div>
                        <div class="email-preview">
                            I am a Dinesh Ragavendra devops engineer at Nimbilix technology Bangalore as an intern. I am currently looking for the new opportunity kindly let me know if there...
                        </div>
                    </div>
                    <div class="email-time">Jan 9</div>
                </div>

                <!-- Email Item 10 -->
                <div class="email-item">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">"ux.by.vedant (via Google Docs)"</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">Document shared with you: "UI Intern Design Assessment"</div>
                        <div class="email-preview">
                            ux.by.vedant shared a document ux.by.vedant (uxbyvedant222@gmail.com) has invited you to edit the followin...
                        </div>
                    </div>
                    <div class="email-time">Jan 9</div>
                </div>

                <!-- Email Item 11 -->
                <div class="email-item">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">Invitation</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">vConnect iDees - UI Designer Intern - Virtual Round I</div>
                        <div class="email-preview">
                            @ Fri Jan 9, 2026 3pm - 3:30pm (IST) (arpan@vconnectidees.com) vConnect iDees - UI Designer Intern - ...
                        </div>
                    </div>
                    <div class="email-time">Jan 9</div>
                </div>

                <!-- Email Item 12 -->
                <div class="email-item">
                    <div class="email-checkbox">
                        <input type="checkbox">
                    </div>
                    <div class="email-star">
                        <i class="far fa-star"></i>
                    </div>
                    <div class="email-sender">
                        <span class="sender-name">UI UX designer Applicant</span>
                    </div>
                    <div class="email-content">
                        <div class="email-subject">UI UX designer Applicant</div>
                        <div class="email-preview">
                            Hi HR, I'm applying for the UI/UX Designer role and sharing my CV and portfolio. Heyy , akash auti this side your UI UX designer with a dev...
                        </div>
                    </div>
                    <div class="email-time">Jan 9</div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <button class="pagination-btn disabled">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="page-info">Showing 1-12 of 127 emails</span>
                <button class="pagination-btn">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </main>
    </div>

    <script src="js/inbox.js"></script>
    <script src="js/script.js"></script>
</body>
</html>



