<?php
/**
 * Two-Way Communication Interface Page
 * Manage interactive communication between administrators and citizens
 */

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Two-Way Communication Interface';
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/global.css?v=<?php echo filemtime(__DIR__ . '/css/global.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css?v=<?php echo filemtime(__DIR__ . '/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="css/admin-header.css">
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/forms.css">
    <link rel="stylesheet" href="css/hero.css">
    <link rel="stylesheet" href="css/sidebar-footer.css">
    <link rel="stylesheet" href="css/modules.css">
        <link rel="stylesheet" href="css/module-two-way-communication.css?v=<?php echo filemtime(__DIR__ . '/css/module-two-way-communication.css'); ?>">
</head>
<body class="twc-page">
    <!-- Include Sidebar Component -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Include Admin Header Component -->
    <?php include 'includes/admin-header.php'; ?>
    
    <!-- ===================================
       MAIN CONTENT - Two-Way Communication
       =================================== -->
    <div class="main-content">
        <div class="main-container">
            <div class="title">
                <nav aria-label="Breadcrumb">
                    <ol class="breadcrumb-list">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php" class="breadcrumb-link">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Two-Way Communication</li>
                    </ol>
                </nav>
                <h1><i class="fas fa-comments" style="color: var(--primary-color-1); margin-right: 0.5rem;"></i> Two-Way Communication Interface</h1>
                <p>Interactive communication platform allowing administrators and citizens to exchange messages in real-time.</p>
            </div>

            <div class="department-top-nav" id="departmentTopNav" aria-label="Department Navigation">
                <button type="button" class="dept-nav-chip active" data-dept="all">
                    <i class="fas fa-layer-group"></i>
                    <span>All Conversations</span>
                    <span class="dept-nav-count" data-dept-count="all">0</span>
                </button>
                <button type="button" class="dept-nav-chip" data-dept="incident_nlp">
                    <i class="fas fa-microscope"></i>
                    <span>Incident &amp; NLP</span>
                    <span class="dept-nav-count" data-dept-count="incident_nlp">0</span>
                </button>
                <button type="button" class="dept-nav-chip" data-dept="traffic_transport">
                    <i class="fas fa-traffic-light"></i>
                    <span>Traffic &amp; Transport</span>
                    <span class="dept-nav-count" data-dept-count="traffic_transport">0</span>
                </button>
                <button type="button" class="dept-nav-chip" data-dept="emergency_response">
                    <i class="fas fa-ambulance"></i>
                    <span>Emergency Response</span>
                    <span class="dept-nav-count" data-dept-count="emergency_response">0</span>
                </button>
                <button type="button" class="dept-nav-chip" data-dept="community_policing">
                    <i class="fas fa-shield-alt"></i>
                    <span>Community Policing</span>
                    <span class="dept-nav-count" data-dept-count="community_policing">0</span>
                </button>
                <button type="button" class="dept-nav-chip" data-dept="crime_analytics">
                    <i class="fas fa-chart-line"></i>
                    <span>Crime Analytics</span>
                    <span class="dept-nav-count" data-dept-count="crime_analytics">0</span>
                </button>
                <button type="button" class="dept-nav-chip" data-dept="public_safety_campaign">
                    <i class="fas fa-bullhorn"></i>
                    <span>Public Safety</span>
                    <span class="dept-nav-count" data-dept-count="public_safety_campaign">0</span>
                </button>
                <button type="button" class="dept-nav-chip" data-dept="health_inspection">
                    <i class="fas fa-notes-medical"></i>
                    <span>Health &amp; Safety</span>
                    <span class="dept-nav-count" data-dept-count="health_inspection">0</span>
                </button>
                <button type="button" class="dept-nav-chip" data-dept="disaster_preparedness">
                    <i class="fas fa-hard-hat"></i>
                    <span>Disaster Preparedness</span>
                    <span class="dept-nav-count" data-dept-count="disaster_preparedness">0</span>
                </button>
                <button type="button" class="dept-nav-chip" data-dept="emergency_comm">
                    <i class="fas fa-broadcast-tower"></i>
                    <span>Emergency Comms</span>
                    <span class="dept-nav-count" data-dept-count="emergency_comm">0</span>
                </button>
            </div>
            
            <div class="sub-container">
                <div class="page-content">
                    <div class="communication-container" id="communicationContainer">
                        <!-- Conversations List Container -->
                        <div class="conversations-list-container">
                            <div class="chat-tabs">
                                <div class="chat-tab active" onclick="switchTab('open')">
                                    <i class="fas fa-inbox"></i> Open <span id="openCount" class="badge"></span>
                                </div>
                                <div class="chat-tab" onclick="switchTab('assigned')">
                                    <i class="fas fa-user-check"></i> Assigned
                                </div>
                                <div class="chat-tab" onclick="switchTab('closed')">
                                    <i class="fas fa-check-circle"></i> Closed
                                </div>
                            </div>
                            <div class="chat-filters">
                                <label for="deptFilter">Department</label>
                                <select id="deptFilter">
                                    <option value="all">All Departments</option>
                                    <option value="incident_nlp">Incident & NLP Investigation</option>
                                    <option value="traffic_transport">Traffic & Transport Management</option>
                                    <option value="emergency_response">Emergency Response & Recovery</option>
                                    <option value="community_policing">Community Policing & Surveillance</option>
                                    <option value="crime_analytics">Crime Data Analytics</option>
                                    <option value="public_safety_campaign">Public Safety Campaign</option>
                                    <option value="health_inspection">Health & Safety Inspection</option>
                                    <option value="disaster_preparedness">Disaster Preparedness Training</option>
                                    <option value="emergency_comm">Emergency Communication</option>
                                </select>
                                <label for="topicFilter">Topic</label>
                                <select id="topicFilter">
                                    <option value="all">All Topics</option>
                                </select>
                                <label for="priorityFilter">Priority</label>
                                <select id="priorityFilter">
                                    <option value="all">All Priorities</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="normal">Normal</option>
                                </select>
                            </div>
                            <div class="conversations-list" id="scrollableList">
                                <div id="incomingEmergencyCallRow" style="display:none;"></div>
                                <div id="conversationsList">
                                    <!-- Conversations will be loaded here -->
                                </div>
                                <div id="loadMoreContainer" class="load-more-container" style="display: none;">
                                    <button class="btn-load-more" onclick="loadMoreConversations()">
                                        Load More
                                    </button>
                                </div>
                                <div id="loadingSpinner" style="text-align: center; padding: 1rem; display: none;">
                                    <i class="fas fa-spinner fa-spin" style="color: var(--primary-color-1);"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Window -->
                        <div class="chat-window">
                            <div class="chat-header">
                                <div style="display: flex; align-items: center; overflow: hidden;">
                                    <button class="mobile-back-btn" onclick="closeMobileChat()">
                                        <i class="fas fa-arrow-left"></i>
                                    </button>
                                    <div class="chat-header-info">
                                        <h3 id="chatUserName">Select a conversation</h3>
                                        <small id="chatUserStatus"></small>
                                    </div>
                                </div>
                                <div class="chat-actions">
                                    <button class="btn btn-sm btn-secondary" id="toggleStatusBtn" style="display: none;">
                                        <i class="fas fa-check"></i> Close Chat
                                    </button>
                                </div>
                            </div>
                            <div class="chat-messages" id="chatMessages">
                                <div style="text-align: center; color: var(--text-secondary-1); padding: 3rem; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                                    <div style="font-size: 3rem; opacity: 0.2; margin-bottom: 1rem;"><i class="fas fa-comments"></i></div>
                                    <p>Select a conversation from the list to start messaging</p>
                                </div>
                            </div>
                            <div class="chat-input">
                                <input type="text" id="messageInput" placeholder="Type a message..." disabled>
                                <button class="btn btn-primary" id="sendButton" disabled style="padding: 0.8rem 1rem; border-radius: 50%;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MySQL Chat System -->
    <script>
        const API_BASE = '../api/';
        const ADMIN_USERNAME = <?php echo json_encode($adminUsername); ?>;
        const ADMIN_AVATAR = `https://ui-avatars.com/api/?name=${encodeURIComponent(ADMIN_USERNAME)}&background=4c8a89&color=fff&size=128`;
        
        // State Management
        let currentStatus = 'open';
        let currentConversationId = null;
        let lastMessageId = 0;
        let currentPage = 1;
        const pageLimit = 20; // Load 20 at a time for speed
        let isLoading = false;
        let hasMore = true;
        let lastDisplayedDate = null; // Track the last date shown in the chat
        let currentDept = 'all';
        let currentTopic = 'all';
        let lastUnreadCount = 0;
        let hasUnreadBaseline = false;
        let topicSet = new Set();
        let currentPriority = 'all';
        const DEPARTMENT_KEYS = [
            'incident_nlp',
            'traffic_transport',
            'emergency_response',
            'community_policing',
            'crime_analytics',
            'public_safety_campaign',
            'health_inspection',
            'disaster_preparedness',
            'emergency_comm'
        ];
        
        // Polling Intervals
        let pollInterval = null;
        let messageInterval = null;

        // --- View Management ---
        
        function switchTab(status) {
            if (currentStatus === status) return;
            currentStatus = status;
            
            // UI Update
            document.querySelectorAll('.chat-tab').forEach(tab => {
                tab.classList.toggle('active', tab.textContent.trim().toLowerCase().includes(status));
            });

            // Reset List
            currentPage = 1;
            hasMore = true;
            document.getElementById('conversationsList').innerHTML = '';
            document.getElementById('loadMoreContainer').style.display = 'none';
            
            loadConversations(true);
        }
        
        function closeMobileChat() {
            document.getElementById('communicationContainer').classList.remove('chat-active');
            // Allow polling to refresh list again if needed, but keep current ID active in background
        }

        // --- Data Loading ---

        function normalizeDeptKey(value) {
            return String(value || '').trim().toLowerCase();
        }

        function mapConversationDept(conv) {
            if (conv.department) return normalizeDeptKey(conv.department);
            const concern = normalizeDeptKey(conv.userConcern);
            const msg = normalizeDeptKey(conv.lastMessage);
            const hay = `${concern} ${msg}`;

            if (/(incident|investigation|case|nlp)/.test(hay)) return 'incident_nlp';
            if (/(traffic|transport|violation|road)/.test(hay)) return 'traffic_transport';
            if (/(emergency response|response|recovery|incident logging|resource)/.test(hay)) return 'emergency_response';
            if (/(police|policing|surveillance|cctv)/.test(hay)) return 'community_policing';
            if (/(crime|hotspot|geospatial|analytics)/.test(hay)) return 'crime_analytics';
            if (/(public safety|campaign|awareness)/.test(hay)) return 'public_safety_campaign';
            if (/(health|inspection|safety|compliance)/.test(hay)) return 'health_inspection';
            if (/(disaster|preparedness|training|simulation)/.test(hay)) return 'disaster_preparedness';
            if (/(alert|warning|multilingual|communication)/.test(hay)) return 'emergency_comm';
            return '';
        }

        function mapConversationTopic(conv) {
            if (conv.topic) return normalizeDeptKey(conv.topic);
            if (conv.userConcern) return normalizeDeptKey(conv.userConcern);
            return '';
        }

        function topicLabel(key) {
            if (!key) return '';
            return key.replace(/[_-]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        }

        function updateTopicFilterOptions() {
            const topicFilter = document.getElementById('topicFilter');
            if (!topicFilter) return;
            const current = topicFilter.value || 'all';
            const options = Array.from(topicSet).sort();
            topicFilter.innerHTML = '<option value="all">All Topics</option>' +
                options.map(t => `<option value="${t}">${topicLabel(t)}</option>`).join('');
            if (options.includes(current)) topicFilter.value = current;
        }

        function deptLabel(key) {
            const map = {
                incident_nlp: 'Incident & NLP',
                traffic_transport: 'Traffic & Transport',
                emergency_response: 'Emergency Response',
                community_policing: 'Policing & CCTV',
                crime_analytics: 'Crime Analytics',
                public_safety_campaign: 'Public Safety',
                health_inspection: 'Health Inspection',
                disaster_preparedness: 'Disaster Training',
                emergency_comm: 'Emergency Comms',
                unassigned: 'Unassigned'
            };
            return map[key] || '';
        }

        function deptOrder() {
            return [
                'incident_nlp',
                'traffic_transport',
                'emergency_response',
                'community_policing',
                'crime_analytics',
                'public_safety_campaign',
                'health_inspection',
                'disaster_preparedness',
                'emergency_comm',
                'unassigned'
            ];
        }

        function setActiveDepartmentNav(key) {
            const normalizedKey = normalizeDeptKey(key || 'all') || 'all';
            document.querySelectorAll('.dept-nav-chip').forEach(chip => {
                chip.classList.toggle('active', chip.getAttribute('data-dept') === normalizedKey);
            });
        }

        function updateDepartmentNavCounts(conversations) {
            const counts = { all: conversations.length };
            DEPARTMENT_KEYS.forEach(key => {
                counts[key] = 0;
            });

            conversations.forEach(conv => {
                const key = mapConversationDept(conv);
                if (key && Object.prototype.hasOwnProperty.call(counts, key)) {
                    counts[key] += 1;
                }
            });

            document.querySelectorAll('.dept-nav-count').forEach(node => {
                const key = node.getAttribute('data-dept-count') || 'all';
                const value = counts[key] || 0;
                node.textContent = String(value);
                node.style.display = value > 0 || key === 'all' ? 'inline-flex' : 'none';
            });
        }

        function updateDepartmentQueryParam(dept) {
            const url = new URL(window.location.href);
            const normalizedDept = normalizeDeptKey(dept || 'all');

            if (normalizedDept && normalizedDept !== 'all') {
                url.searchParams.set('dept', normalizedDept);
            } else {
                url.searchParams.delete('dept');
            }

            window.history.replaceState({}, '', url.toString());
        }

        function resetConversationsAndReload() {
            currentPage = 1;
            hasMore = true;
            document.getElementById('conversationsList').innerHTML = '';
            document.getElementById('loadMoreContainer').style.display = 'none';
            loadConversations(true);
        }

        function getConversationTimestamp(conv) {
            const raw = conv.lastMessageTime ?? conv.lastMessageAt ?? conv.updatedAt ?? conv.createdAt ?? 0;
            const ts = Number(raw);
            return Number.isFinite(ts) ? ts : 0;
        }

        function sortConversationsNewest(conversations) {
            return [...conversations].sort((a, b) => {
                const diff = getConversationTimestamp(b) - getConversationTimestamp(a);
                if (diff !== 0) return diff;
                return Number(b.id || 0) - Number(a.id || 0);
            });
        }

        function orderedDeptKeysByRecency(grouped) {
            const fallbackOrder = deptOrder();
            return Object.keys(grouped).sort((a, b) => {
                const aTopTs = grouped[a]?.[0] ? getConversationTimestamp(grouped[a][0]) : 0;
                const bTopTs = grouped[b]?.[0] ? getConversationTimestamp(grouped[b][0]) : 0;
                if (aTopTs !== bTopTs) return bTopTs - aTopTs;

                const aIdx = fallbackOrder.indexOf(a);
                const bIdx = fallbackOrder.indexOf(b);
                return (aIdx === -1 ? 999 : aIdx) - (bIdx === -1 ? 999 : bIdx);
            });
        }

        function ensureDeptSection(listContainer, key) {
            const id = `dept-${key}`;
            let section = document.getElementById(id);
            if (section) return section;

            section = document.createElement('div');
            section.className = 'dept-section';
            section.id = id;
            section.innerHTML = `
                <div class="dept-section-title">
                    <span class="dept-toggle"><i class="fas fa-chevron-down dept-caret"></i> ${deptLabel(key) || 'Unassigned'}</span>
                    <span id="${id}-count">0</span>
                </div>
                <div class="dept-section-list"></div>
            `;
            section.querySelector('.dept-section-title').addEventListener('click', () => {
                section.classList.toggle('collapsed');
            });
            listContainer.appendChild(section);
            return section;
        }

        function renderGroupedConversations(conversations, append) {
            const listContainer = document.getElementById('conversationsList');
            if (!listContainer) return;

            if (!append) listContainer.innerHTML = '';

            const grouped = {};
            sortConversationsNewest(conversations).forEach(conv => {
                const key = mapConversationDept(conv) || 'unassigned';
                if (!grouped[key]) grouped[key] = [];
                grouped[key].push(conv);
            });

            Object.keys(grouped).forEach(key => {
                grouped[key] = sortConversationsNewest(grouped[key]);
            });

            orderedDeptKeysByRecency(grouped).forEach(key => {
                if (!grouped[key] || grouped[key].length === 0) return;
                const section = ensureDeptSection(listContainer, key);
                const list = section.querySelector('.dept-section-list');
                const existingIds = new Set(
                    Array.from(list.querySelectorAll('.conversation-item')).map(node => String(node.getAttribute('data-conversation-id')))
                );
                grouped[key].forEach(conv => {
                    const convId = String(conv.id);
                    if (existingIds.has(convId)) return;
                    list.appendChild(createConversationElement(conv));
                    existingIds.add(convId);
                });
                const count = section.querySelector(`#dept-${key}-count`);
                if (count) count.textContent = String(list.children.length);
            });
        }

        async function loadConversations(isInitial = false, append = false, silent = false) {
            if (isLoading) return;
            isLoading = true;
            
            const listContainer = document.getElementById('conversationsList');
            const spinner = document.getElementById('loadingSpinner');
            const loadMoreBtn = document.getElementById('loadMoreContainer');
            
            if (isInitial && !append && !silent) {
                spinner.style.display = 'block';
                listContainer.innerHTML = ''; // Clear for initial load
            } else if (append) {
                spinner.style.display = 'block';
                loadMoreBtn.style.display = 'none';
            }
            
            try {
                const params = new URLSearchParams({
                    status: currentStatus,
                    page: currentPage,
                    limit: pageLimit
                });
                if (currentDept !== 'all') {
                    params.set('category', currentDept);
                }
                if (currentPriority !== 'all') {
                    params.set('priority', currentPriority);
                }
                
                const response = await fetch(`${API_BASE}chat-get-conversations.php?${params}`);
                const data = await response.json();
                
                if (!silent) {
                    spinner.style.display = 'none';
                }
                
                if (!data.success) throw new Error(data.message);
                
                let conversations = data.conversations || [];
                updateDepartmentNavCounts(conversations);
                const openBadge = document.getElementById('openCount');
                if (openBadge && (currentStatus === 'open' || currentStatus === 'active')) {
                    const totalOpen = (data.pagination && typeof data.pagination.total === 'number')
                        ? data.pagination.total
                        : conversations.length;
                    openBadge.textContent = totalOpen > 0 ? String(totalOpen) : '';
                    openBadge.style.display = totalOpen > 0 ? 'inline-block' : 'none';
                }
                conversations.forEach(c => {
                    const t = mapConversationTopic(c);
                    if (t) topicSet.add(t);
                });
                updateTopicFilterOptions();
                if (currentDept !== 'all') {
                    conversations = conversations.filter(conv => mapConversationDept(conv) === currentDept);
                }
                if (currentTopic !== 'all') {
                    conversations = conversations.filter(conv => mapConversationTopic(conv) === currentTopic);
                }
                
                // Handle Empty State
                if (conversations.length === 0) {
                    hasMore = false;
                    if (isInitial && !append) {
                        const suffix = currentDept === 'all' ? '' : ' for this department';
                        const topicSuffix = currentTopic === 'all' ? '' : ' for this topic';
                        listContainer.innerHTML = `<p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">No ${currentStatus} conversations${suffix}${topicSuffix}</p>`;
                    }
                    return;
                }
                
                // Check if we have more pages
                if (conversations.length < pageLimit) {
                    hasMore = false;
                } else {
                    loadMoreBtn.style.display = 'block';
                }
                
                // Render Items (grouped by department)
                renderGroupedConversations(conversations, append);
                
            } catch (error) {
                console.error('Error loading conversations:', error);
                if (isInitial && !silent) listContainer.innerHTML = '<p style="color: #e74c3c; text-align: center; padding: 1rem;">Failed to load data</p>';
            } finally {
                if (!silent) {
                    spinner.style.display = 'none';
                }
                isLoading = false;
            }
        }
        
        function loadMoreConversations() {
            if (hasMore && !isLoading) {
                currentPage++;
                loadConversations(false, true);
            }
        }
        
        // --- Real-time Polling ---
        
        function ensureToastContainer() {
            let container = document.querySelector('.tw-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'tw-toast-container';
                document.body.appendChild(container);
            }
            return container;
        }

        function showToast(title, message) {
            const container = ensureToastContainer();
            const toast = document.createElement('div');
            toast.className = 'tw-toast';
            toast.innerHTML = `
                <i class="fas fa-comment-dots"></i>
                <div>
                    <strong>${title}</strong><br/>
                    <small>${message}</small>
                </div>
            `;
            container.appendChild(toast);
            setTimeout(() => { toast.remove(); }, 3500);
        }

        async function pollUpdates() {
            // 1. Update Badge & Unread Count
            try {
                const response = await fetch(API_BASE + 'chat-get-unread-count.php');
                const data = await response.json();
                if (data.success) {
                    const count = data.unreadCount;
                    if (!hasUnreadBaseline) {
                        lastUnreadCount = count;
                        hasUnreadBaseline = true;
                    } else if (count > lastUnreadCount) {
                        const diff = count - lastUnreadCount;
                        showToast('New message', diff === 1 ? '1 new conversation update' : `${diff} new conversation updates`);
                        lastUnreadCount = count;
                    } else if (count < lastUnreadCount) {
                        lastUnreadCount = count;
                    }
                    // Sidebar Badge
                    const sidebarLinks = document.querySelectorAll('.sidebar-menu li a');
                    sidebarLinks.forEach(link => {
                        if (link.href.includes('two-way-communication.php')) {
                            let badge = link.querySelector('.sidebar-badge');
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'sidebar-badge';
                                badge.style.cssText = 'background: #ff5252; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.7rem; margin-left: auto; display: none;';
                                link.appendChild(badge);
                                link.style.display = 'flex';
                                link.style.alignItems = 'center';
                            }
                            badge.textContent = count;
                            badge.style.display = count > 0 ? 'inline-block' : 'none';
                        }
                    });
                }
            } catch (e) {}

            // 2. Silent list refresh for first page only (stable re-render, no manual DOM shuffling)
            const listEl = document.getElementById('scrollableList');
            if (!isLoading && currentPage === 1 && listEl && listEl.scrollTop < 50 && (currentStatus === 'open' || currentStatus === 'active')) {
                await loadConversations(false, false, true);
            }
        }

        // --- DOM Helpers ---

        function createConversationElement(conv) {
            const item = document.createElement('div');
            item.className = 'conversation-item';
            if (currentStatus === 'closed') item.classList.add('closed');
            if (String(conv.id) === String(currentConversationId)) item.classList.add('active');
            
            item.setAttribute('data-conversation-id', conv.id);
            item._conversationData = conv;
            
            item.innerHTML = getConversationHTML(conv);
            
            item.addEventListener('click', function() {
                openConversation(conv.id, this._conversationData, this);
            });
            
            return item;
        }
        
        function getConversationHTML(conv) {
            const guestBadge = conv.isGuest ? '<span class="list-chip list-chip-guest">GUEST</span>' : '';
            const concernBadge = conv.userConcern ? `<span class="list-chip list-chip-concern">${conv.userConcern}</span>` : '';
            const callBadge = conv.hasCall ? '<span class="list-chip list-chip-call"><i class="fas fa-phone"></i>Call</span>' : '';
            const unreadBadge = conv.unreadCount > 0 ? `<span class="list-chip list-chip-unread">${conv.unreadCount}</span>` : '';
            const deptKey = mapConversationDept(conv);
            const deptTag = deptKey ? `<span class="dept-badge">${deptLabel(deptKey)}</span>` : '';
            const topicKey = mapConversationTopic(conv);
            const topicTag = topicKey ? `<span class="topic-badge">${topicLabel(topicKey)}</span>` : '';
            const workflowRaw = (conv.workflowStatus || '').toLowerCase();
            const workflowLabelMap = {
                open: 'Open',
                active: 'Open',
                in_progress: 'In Progress',
                waiting_user: 'Waiting User',
                resolved: 'Resolved',
                closed: 'Closed'
            };
            const workflowLabel = workflowLabelMap[workflowRaw] || 'Open';
            const workflowClassMap = {
                open: 'workflow-open',
                active: 'workflow-open',
                in_progress: 'workflow-progress',
                waiting_user: 'workflow-waiting',
                resolved: 'workflow-resolved',
                closed: 'workflow-closed'
            };
            const workflowClass = workflowClassMap[workflowRaw] || 'workflow-open';
            const statusBadge = `<span class="workflow-pill ${workflowClass}">${workflowLabel}</span>`;
            const statusDot = `<span class="status-dot"></span>`;

            const timestamp = getConversationTimestamp(conv);
            const displayTime = timestamp
                ? `<small style="opacity:0.75;font-size:0.72rem;white-space:nowrap;display:block;text-align:right;line-height:1.25;">
                        ${new Date(timestamp).toLocaleDateString([], { month: 'short', day: '2-digit', year: 'numeric' })}
                        ${new Date(timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                   </small>`
                : '';

            // User Info Line
            const userInfo = [];
            // Prioritize User Name/Email/Phone in bold
            // Secondary info in small text
            
            if (conv.userPhone) userInfo.push(`<i class="fas fa-phone"></i>`);
            if (conv.userLocation) userInfo.push(`<i class="fas fa-map-marker-alt"></i>`);
            if (conv.ipAddress) userInfo.push(`<i class="fas fa-network-wired"></i>`);
            
            return `
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.35rem;">
                    <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 0.5rem; font-size: 0.95rem;">
                        ${statusDot}
                        <strong>${conv.userName || 'Unknown'}</strong>${guestBadge}${concernBadge}${callBadge}${unreadBadge}
                    </div>
                    ${displayTime}
                </div>
                <p style="margin: 0; font-size: 0.85rem; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${conv.lastMessage || 'No messages'}
                </p>
                <div style="margin-top: 0.5rem; font-size: 0.75rem; opacity: 0.6;">
                    ${userInfo.join(' &nbsp; ')} &nbsp; ${conv.userLocation || ''}
                </div>
                <div style="margin-top: 0.45rem; display: flex; gap: 0.35rem; flex-wrap: wrap;">
                    ${statusBadge} ${deptTag} ${topicTag}
                </div>
            `;
        }

        // --- Chat Interaction ---

        function openConversation(id, data, element) {
            currentConversationId = id;
            lastMessageId = 0;
            
            // UI Selection
            document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
            if (element) element.classList.add('active');
            else {
                // Try finding it if element not passed
                const found = document.querySelector(`.conversation-item[data-conversation-id="${id}"]`);
                if (found) found.classList.add('active');
            }
            
            // Mobile View Toggle
            document.getElementById('communicationContainer').classList.add('chat-active');
            
            // Header Info
            const nameEl = document.getElementById('chatUserName');
            const statusEl = document.getElementById('chatUserStatus');
            
            const guestBadge = data.isGuest ? ' <span class="list-chip list-chip-guest">GUEST</span>' : '';
            nameEl.innerHTML = (data.userName || 'Unknown') + guestBadge;
            
            // Detailed Info for Status Bar
            const details = [];
            if (data.userPhone) details.push(data.userPhone);
            if (data.userLocation) details.push(data.userLocation);
            if (data.ipAddress) details.push(data.ipAddress);
            
            // Device Info Parsing
            let devStr = '';
            if (data.deviceInfo) {
               let d = data.deviceInfo;
               if (typeof d === 'string') try { d = JSON.parse(d); } catch(e){}
               if (d && typeof d === 'object') {
                   const parts = [d.device_type, d.os, d.browser].filter(Boolean);
                   if (parts.length) devStr = parts.join(' • ');
               }
            }
            if (devStr) details.push(devStr);
            
            statusEl.textContent = details.join(' | ') || 'Online';
            
            // Input/Button State
            const isClosed = (data.status === 'closed');
            setupInputState(isClosed);
            setupCloseButton(isClosed);

            // Load Messages
            loadMessages(id, true);
        }
        
        function setupInputState(isClosed) {
            const input = document.getElementById('messageInput');
            const btn = document.getElementById('sendButton');
            
            if (input) {
                input.disabled = isClosed;
                input.placeholder = isClosed ? 'Conversation closed' : 'Type a message...';
                input.style.cursor = isClosed ? 'not-allowed' : 'text';
            }
            if (btn) {
                btn.disabled = isClosed;
            }
        }
        
        function setupCloseButton(isClosed) {
            const btn = document.getElementById('toggleStatusBtn');
            if (!btn) return;
            
            btn.style.display = 'inline-flex';
            btn.className = isClosed ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-success';
            btn.innerHTML = isClosed ? '<i class="fas fa-undo"></i> Re-open' : '<i class="fas fa-check"></i> Close';
            
            // Clean listener
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            const freshBtn = document.getElementById('toggleStatusBtn');
            
            freshBtn.onclick = async () => {
                if (!confirm(isClosed ? 'Re-open this chat?' : 'Close this chat?')) return;
                
                freshBtn.disabled = true;
                try {
                    const newStatus = isClosed ? 'active' : 'closed';
                    const res = await fetch(API_BASE + 'chat-update-status.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ conversationId: currentConversationId, status: newStatus })
                    });
                    const d = await res.json();
                    
                    if (d.success) {
                        // Remove item from current list immediately
                        const item = document.querySelector(`.conversation-item[data-conversation-id="${currentConversationId}"]`);
                        if (item) {
                            item.style.opacity = '0';
                            item.style.height = '0';
                            item.style.margin = '0';
                            item.style.padding = '0';
                            
                            // Remove after animation
                            setTimeout(() => {
                                item.remove();
                                // Handle empty list state
                                const list = document.getElementById('conversationsList');
                                if (list && list.children.length === 0) {
                                    list.innerHTML = `<p style="text-align: center; color: var(--text-secondary-1); padding: 2rem;">No ${currentStatus} conversations</p>`;
                                }
                            }, 300);
                        }

                        // Clear chat window and reset state
                        document.getElementById('chatMessages').innerHTML = '<div style="text-align: center; color: var(--text-secondary-1); padding: 3rem; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;"><div style="font-size: 3rem; opacity: 0.2; margin-bottom: 1rem;"><i class="fas fa-comments"></i></div><p>Select a conversation from the list to start messaging</p></div>';
                        document.getElementById('chatUserName').textContent = 'Select a conversation';
                        document.getElementById('chatUserStatus').textContent = '';
                        document.getElementById('messageInput').disabled = true;
                        document.getElementById('messageInput').placeholder = 'Type a message...';
                        document.getElementById('sendButton').disabled = true;
                        freshBtn.style.display = 'none';
                        
                        // Clear active ID
                        currentConversationId = null;

                        // Close mobile chat view if open
                        closeMobileChat();
                    } else {
                        alert('Error updating status');
                    }
                } catch(e) {
                    console.error(e);
                    alert('Network error');
                } finally {
                    freshBtn.disabled = false;
                }
            };
        }
        
        async function loadMessages(id, initial = false) {
            const container = document.getElementById('chatMessages');
            if (initial) {
                container.innerHTML = '<div style="display:flex; justify-content:center; padding:2rem;"><i class="fas fa-spinner fa-spin"></i></div>';
                lastDisplayedDate = null; // Reset date tracking when opening new conversation
            }
            
            // Clear polling
            if (messageInterval) clearInterval(messageInterval);
            
            const fetchMsgs = async (isFirstLoad) => {
                if (currentConversationId !== id) return;
                try {
                    const res = await fetch(`${API_BASE}chat-get-messages.php?conversationId=${id}&lastMessageId=${lastMessageId}`);
                    const data = await res.json();
                    
                    if (data.success && Array.isArray(data.messages)) {
                        // Remove spinner on first load
                        if (container.querySelector('.fa-spinner')) container.innerHTML = '';
                        
                        // Fix: Only show "No messages yet" if this is the FIRST load and the list is truly empty
                        // This prevents polling from overwriting existing messages with "No messages yet"
                        if (isFirstLoad && data.messages.length === 0) {
                            container.innerHTML = '<p style="text-align:center; color:#999; padding:2rem;">No messages yet.</p>';
                        }
                        
                        let added = false;
                        const existingIds = new Set(Array.from(container.querySelectorAll('.message')).map(el => parseInt(el.dataset.id)));
                        
                        data.messages.forEach(msg => {
                            if (msg.id > lastMessageId && !existingIds.has(msg.id)) {
                                appendMessage(msg);
                                lastMessageId = Math.max(lastMessageId, msg.id);
                                added = true;
                            }
                        });
                        
                        if (added) scrollToBottom();
                    }
                } catch (e) { console.error(e); }
            };
            
            await fetchMsgs(initial); // Initial call with passed state
            messageInterval = setInterval(() => fetchMsgs(false), 3000); // Poll with false
        }
        
        function appendMessage(msg) {
            const container = document.getElementById('chatMessages');
            // Remove placeholders
            const p = container.querySelector('p');
            if (p) p.remove();
            // Remove center container placeholders if any
            if (container.children.length === 1 && container.children[0].style.textAlign === 'center') {
                container.innerHTML = '';
            }

            const msgDate = new Date(msg.timestamp);
            const dateStr = msgDate.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });

            // Show date separator if the date has changed
            if (lastDisplayedDate !== dateStr) {
                const separator = document.createElement('div');
                separator.className = 'date-separator';
                separator.textContent = dateStr;
                container.appendChild(separator);
                lastDisplayedDate = dateStr;
            }
            
            // Check if this is a system message (like "Call ended")
            const isSystemMessage = msg.senderType === 'system' || (msg.text && msg.text.startsWith('[CALL_ENDED]'));
            
            if (isSystemMessage) {
                // Render as system message (like Messenger's call ended style)
                const div = document.createElement('div');
                div.className = 'message system-message';
                div.dataset.id = msg.id;
                
                // Extract the actual message text (remove [CALL_ENDED] prefix)
                let messageText = msg.text || '';
                let isCallEnded = false;
                if (messageText.startsWith('[CALL_ENDED]')) {
                    messageText = messageText.replace('[CALL_ENDED]', '').trim();
                    isCallEnded = true;
                }
                
                // Determine the display text
                let displayText = 'Call ended';
                if (isCallEnded) {
                    // Extract duration if present
                    const durationMatch = messageText.match(/Duration:\s*([^\s]+)/);
                    if (durationMatch) {
                        displayText = `Call ended • ${durationMatch[1]}`;
                    } else {
                        displayText = 'Call ended';
                    }
                } else {
                    displayText = messageText || 'System message';
                }
                
                const timeStr = msgDate.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit', 
                    hour12: true 
                });
                
                div.innerHTML = `
                    <div class="system-message-content">
                        <div class="system-message-header">
                            <div class="system-message-icon">
                                <i class="fas fa-phone-slash"></i>
                            </div>
                            <span class="system-message-text">${escapeHtml(displayText)}</span>
                        </div>
                        <div class="system-message-meta">${timeStr}</div>
                    </div>
                `;
                container.appendChild(div);
                return;
            }
            
            const div = document.createElement('div');
            const type = (msg.senderType === 'admin' || msg.senderType === 'sent') ? 'admin' : 'user';
            div.className = `message ${type}`;
            div.dataset.id = msg.id;
            
            const name = type === 'admin' ? ADMIN_USERNAME : (msg.senderName || 'User');
            const avatar = type === 'admin' ? ADMIN_AVATAR : `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6c757d&color=fff&size=64`;
            
            const timeStr = msgDate.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            const fullStamp = `${msgDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} • ${timeStr}`;
            const attachmentUrl = sanitizeAttachmentUrl(msg.imageUrl || msg.attachmentUrl || null);
            const normalizedText = (msg.text || '').toString().trim();
            const attachmentMimeRaw = (msg.attachmentMime || msg.attachment_mime || '').toString().trim().toLowerCase();
            const attachmentMime = attachmentMimeRaw || null;
            const isImageAttachment = !!(attachmentUrl && (
                (attachmentMime && attachmentMime.indexOf('image/') === 0) ||
                (!attachmentMime && /\.(png|jpe?g|gif|webp)(\?|$)/i.test(attachmentUrl))
            ));
            const isVideoAttachment = !!(attachmentUrl && (
                (attachmentMime && attachmentMime.indexOf('video/') === 0) ||
                (!attachmentMime && /\.(mp4|webm|ogv|mov|avi|mkv)(\?|$)/i.test(attachmentUrl))
            ));
            const isEmailAttachment = !!(attachmentUrl && (
                attachmentMime === 'message/rfc822' ||
                attachmentMime === 'application/eml' ||
                /\.eml(\?|$)/i.test(attachmentUrl)
            ));
            const hideAttachmentPlaceholder = attachmentUrl && /^\[(photo|video|email|attachment)\]/i.test(normalizedText);

            let bodyHtml = '';
            if (normalizedText && !hideAttachmentPlaceholder) {
                bodyHtml += `<div class="message-text">${escapeHtml(normalizedText)}</div>`;
            }
            if (attachmentUrl) {
                if (isVideoAttachment) {
                    bodyHtml += `
                        <a href="${attachmentUrl}" target="_blank" rel="noopener noreferrer" class="message-attachment-link">
                            <video class="message-attachment-image" controls preload="metadata">
                                <source src="${attachmentUrl}"${attachmentMime ? ` type="${attachmentMime}"` : ''}>
                                Your browser does not support video playback.
                            </video>
                        </a>
                    `;
                } else if (isImageAttachment) {
                    bodyHtml += `
                        <a href="${attachmentUrl}" target="_blank" rel="noopener noreferrer" class="message-attachment-link">
                            <img src="${attachmentUrl}" alt="Incident attachment" class="message-attachment-image">
                        </a>
                    `;
                } else {
                    const fileLabel = isEmailAttachment ? 'Open email attachment (.eml)' : 'Open attachment';
                    const fileIcon = isEmailAttachment ? 'fa-envelope-open-text' : 'fa-paperclip';
                    bodyHtml += `
                        <a href="${attachmentUrl}" target="_blank" rel="noopener noreferrer" class="message-attachment-link">
                            <span class="message-attachment-file"><i class="fas ${fileIcon}"></i> ${fileLabel}</span>
                        </a>
                    `;
                }
            }
            if (!bodyHtml) {
                bodyHtml = `<div class="message-text">${escapeHtml(normalizedText || 'Attachment')}</div>`;
            }
            
            div.innerHTML = `
                <img src="${avatar}" class="message-avatar" alt="">
                <div class="message-content">
                    ${bodyHtml}
                    <div class="message-meta">
                        ${fullStamp}
                    </div>
                </div>
            `;
            container.appendChild(div);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function sanitizeAttachmentUrl(url) {
            if (!url) return null;
            const raw = String(url).trim();
            if (!raw) return null;
            if (raw.startsWith('/')) return raw;
            try {
                const parsed = new URL(raw, window.location.origin);
                if (parsed.protocol === 'http:' || parsed.protocol === 'https:') {
                    return parsed.href;
                }
            } catch (e) {
                return null;
            }
            return null;
        }
        
        function scrollToBottom() {
            const c = document.getElementById('chatMessages');
            c.scrollTop = c.scrollHeight;
        }

        // --- Sending ---
        
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const text = input.value.trim();
            if (!text || !currentConversationId) return;
            
            input.value = '';
            input.focus();
            
            // Optimistic UI
            const tempId = Date.now(); // Temp ID
            appendMessage({
                id: tempId,
                text: text,
                senderType: 'admin',
                timestamp: Date.now(),
                senderName: ADMIN_USERNAME
            });
            scrollToBottom();
            
            try {
                const fd = new FormData();
                fd.append('text', text);
                fd.append('conversationId', currentConversationId);
                
                const res = await fetch(API_BASE + 'chat-send.php', { method: 'POST', body: fd });
                const d = await res.json();
                
                if (d.success) {
                    // Update temp message with real ID if needed, or just let polling handle sync
                    if (d.messageId) lastMessageId = Math.max(lastMessageId, d.messageId);
                } else {
                    alert('Failed to send');
                }
            } catch (e) {
                alert('Send error');
            }
        }
        
        // Listeners
        document.getElementById('sendButton').onclick = sendMessage;
        document.getElementById('messageInput').onkeypress = e => { if(e.key === 'Enter') sendMessage(); };
        
        // Init
        document.addEventListener('DOMContentLoaded', () => {
            const deptFilter = document.getElementById('deptFilter');
            const deptTopNav = document.getElementById('departmentTopNav');
            if (deptFilter) {
                const urlDept = new URLSearchParams(window.location.search).get('dept');
                if (urlDept && Array.from(deptFilter.options).some(o => o.value === urlDept)) {
                    deptFilter.value = urlDept;
                    currentDept = urlDept;
                }
                deptFilter.addEventListener('change', () => {
                    currentDept = deptFilter.value || 'all';
                    setActiveDepartmentNav(currentDept);
                    updateDepartmentQueryParam(currentDept);
                    resetConversationsAndReload();
                });
            }
            if (deptTopNav) {
                deptTopNav.addEventListener('click', (event) => {
                    const chip = event.target.closest('.dept-nav-chip');
                    if (!chip) return;

                    const selectedDept = chip.getAttribute('data-dept') || 'all';
                    if (normalizeDeptKey(selectedDept) === normalizeDeptKey(currentDept)) return;

                    currentDept = selectedDept;
                    if (deptFilter) deptFilter.value = selectedDept;
                    setActiveDepartmentNav(currentDept);
                    updateDepartmentQueryParam(currentDept);
                    resetConversationsAndReload();
                });
            }
            setActiveDepartmentNav(currentDept);
            const topicFilter = document.getElementById('topicFilter');
            if (topicFilter) {
                topicFilter.addEventListener('change', () => {
                    currentTopic = topicFilter.value || 'all';
                    resetConversationsAndReload();
                });
            }
            const priorityFilter = document.getElementById('priorityFilter');
            if (priorityFilter) {
                priorityFilter.addEventListener('change', () => {
                    currentPriority = priorityFilter.value || 'all';
                    resetConversationsAndReload();
                });
            }

            loadConversations(true);
            pollInterval = setInterval(pollUpdates, 5000);
        });
        
    </script>

    <div id="incomingCallModal" style="display:none; position:fixed; right:18px; top:18px; z-index:100001; width:min(420px, 92vw); background:#0f172a; border:1px solid rgba(220,38,38,0.55); border-radius:16px; padding:16px; color:#fff; box-shadow:0 20px 60px rgba(0,0,0,0.55);">
        <div style="display:flex; align-items:flex-start; gap:12px;">
            <div style="width:44px; height:44px; border-radius:12px; background:rgba(220,38,38,0.18); border:1px solid rgba(220,38,38,0.45); display:flex; align-items:center; justify-content:center; flex:0 0 auto;">
                <i class="fas fa-phone-alt" style="color:#fecaca;"></i>
            </div>
            <div style="flex:1; min-width:0;">
                <div style="font-weight:900; letter-spacing:0.6px; text-transform:uppercase; color:#fecaca;">Incoming Emergency Call</div>
                <div id="incomingCallText" style="opacity:0.9; font-size:13px; margin-top:4px;">Someone is calling for emergency assistance.</div>
            </div>
        </div>
        <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:12px;">
            <button id="incomingDeclineBtn" class="btn btn-secondary">Decline</button>
            <button id="incomingAnswerBtn" class="btn btn-primary">Answer</button>
        </div>
    </div>

    <div id="callOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:100000;">
        <div style="position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:min(1400px, 98vw); height:min(900px, 95vh); background:#0f172a; border:1px solid rgba(255,255,255,0.12); border-radius:18px; padding:24px; color:#fff; box-shadow:0 20px 60px rgba(0,0,0,0.5); display:flex; flex-direction:column;">
            <div id="callActiveBanner" style="display:none; margin:-6px 0 12px; padding:8px 12px; border-radius:12px; background:rgba(220,38,38,0.18); border:1px solid rgba(220,38,38,0.45); color:#fecaca; font-weight:800; letter-spacing:0.6px; text-transform:uppercase; text-align:center;">CALL ON ACTIVE</div>

            <div style="display:flex; gap:20px; flex:1; min-height:0;">
                <div style="width:420px; max-width:40%; min-width:380px; border:1px solid rgba(255,255,255,0.10); border-radius:14px; padding:18px; background:rgba(0,0,0,0.18); display:flex; flex-direction:column; gap:14px;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:44px; height:44px; border-radius:12px; background:rgba(58, 118, 117,0.2); display:flex; align-items:center; justify-content:center; flex:0 0 auto;">
                            <i class="fas fa-user" style="color:#3a7675;"></i>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:900; letter-spacing:0.4px;">Caller Details</div>
                            <div style="opacity:0.75; font-size:12px;">Account + location info</div>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:110px 1fr; gap:8px 10px; font-size:13px;">
                        <div style="opacity:0.7;">Name</div>
                        <div id="callerName" style="font-weight:700;">—</div>

                        <div style="opacity:0.7;">Phone</div>
                        <div id="callerPhone" style="font-weight:700;">—</div>

                        <div style="opacity:0.7;">Address</div>
                        <div id="callerAddress" style="font-weight:600; opacity:0.95;">—</div>

                        <div style="opacity:0.7;">Location</div>
                        <div id="callerCoords" style="font-weight:600; opacity:0.95;">—</div>
                    </div>

                    <div style="border-top:1px solid rgba(255,255,255,0.10); padding-top:12px; display:flex; flex-direction:column; gap:10px;">
                        <label style="font-size:12px; opacity:0.8; margin:0;">Emergency Type</label>
                        <select id="emergencyTypeSelect" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.14); background:rgba(255,255,255,0.08); color:#fff; outline:none;">
                            <option value="" selected>Choose type…</option>
                            <option value="fire">Fire</option>
                            <option value="flood">Flood</option>
                            <option value="rescue">Rescue Assistance</option>
                            <option value="police">Police</option>
                            <option value="medical">Medical</option>
                            <option value="earthquake">Earthquake</option>
                            <option value="other">Other</option>
                        </select>

                        <div style="display:flex; gap:10px;">
                            <button id="dispatchRespondentBtn" class="btn btn-primary" style="flex:1; padding:10px 12px;">Send Respondent</button>
                            <button id="transferCallBtn" class="btn btn-secondary" style="flex:1; padding:10px 12px;">Transfer Call</button>
                        </div>

                        <div id="dispatchStatus" style="font-size:12px; opacity:0.85; min-height:18px;"></div>
                    </div>
                </div>

                <div style="flex:1; min-width:0; display:flex; flex-direction:column;">
                    <!-- Call Header -->
                    <div style="display:flex; align-items:center; gap:12px; flex-shrink:0;">
                        <div style="width:44px; height:44px; border-radius:12px; background:rgba(58, 118, 117,0.2); display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-headset" style="color:#3a7675;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:700; font-size:16px;">Emergency Call</div>
                            <div id="callStatus" style="opacity:0.85; font-size:13px;">Connecting…</div>
                        </div>
                        <div id="callTimer" style="font-variant-numeric:tabular-nums; font-weight:700;">00:00</div>
                    </div>

                    <!-- Messages Area -->
                    <div id="callMessages" style="flex:1; margin-top:16px; overflow-y:auto; border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px; background:rgba(0,0,0,0.2); min-height:200px;">
                        <div style="text-align:center; opacity:0.6; font-size:12px;">Messages will appear here</div>
                    </div>

                    <!-- Message Input -->
                    <div style="margin-top:12px; display:flex; gap:10px; flex-shrink:0; align-items:center;">
                        <input type="text" id="callMessageInput" placeholder="Type a message..." style="flex:1; padding:10px 12px; border:1px solid rgba(255,255,255,0.18); border-radius:10px; background:rgba(255,255,255,0.08); color:#fff; outline:none;">
                        <button id="callSendMessageBtn" class="btn btn-primary" style="padding:10px 16px; min-height:44px;">Send</button>
                    </div>

                    <!-- Call Controls -->
                    <div style="margin-top:14px; display:flex; gap:10px; justify-content:flex-end; flex-shrink:0;">
                        <button id="endCallBtn" class="btn btn-secondary" disabled style="opacity:0.6; pointer-events:none; min-height:44px;">End Call</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <audio id="remote" autoplay></audio>

    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script>
    const IS_LOCAL = ['localhost', '127.0.0.1'].includes(window.location.hostname);
    const SOCKET_IO_PATH = '/socket.io';
    const LOCAL_SOCKET_PORT = 3000;
    const SIGNALING_HOST = window.location.hostname === 'localhost' ? '127.0.0.1' : window.location.hostname;
    const SIGNALING_URL = IS_LOCAL ? `${window.location.protocol}//${SIGNALING_HOST}` + ':' + LOCAL_SOCKET_PORT : null;
    const SOCKET_HEALTH_URL = IS_LOCAL ? `${window.location.protocol}//${SIGNALING_HOST}:${LOCAL_SOCKET_PORT}/health` : null;
    const room = "emergency-room";

    let socket = null;
    let socketBound = false;
    let notificationSound = 'siren';
    let socketRetryCount = 0;
    const MAX_SOCKET_RETRIES = 5;
    let socketServerChecked = !IS_LOCAL;
    let socketServerAvailable = !IS_LOCAL;
    let socketServerCheckPromise = null;
    let socketServerLastCheckAt = 0;
    let socketUnavailableNoticeShown = false;

    let _soundCtx = null;
    let _soundOsc = null;
    let _soundGain = null;
    let _soundTimer = null;

    (function primeAudioContext() {
        let primed = false;
        const prime = () => {
            if (primed) return;
            primed = true;
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                if (!_soundCtx) _soundCtx = new AudioContext();
                if (_soundCtx && _soundCtx.state === 'suspended') {
                    _soundCtx.resume();
                }
            } catch (e) {}
        };
        document.addEventListener('click', prime, { once: true });
        document.addEventListener('keydown', prime, { once: true });
        document.addEventListener('touchstart', prime, { once: true });
    })();

    async function checkSocketServerAvailability(force = false) {
        if (!IS_LOCAL) return true;

        const now = Date.now();
        if (!force && socketServerChecked && socketServerAvailable) {
            return true;
        }
        if (!force && socketServerChecked && !socketServerAvailable && (now - socketServerLastCheckAt) < 10000) {
            return false;
        }
        if (socketServerCheckPromise) {
            return socketServerCheckPromise;
        }

        socketServerCheckPromise = (async () => {
            let reachable = false;
            try {
                const controller = new AbortController();
                const timer = setTimeout(() => controller.abort(), 1800);
                const response = await fetch(SOCKET_HEALTH_URL, {
                    method: 'GET',
                    cache: 'no-store',
                    signal: controller.signal
                });
                clearTimeout(timer);
                reachable = response.ok;
            } catch (e) {
                reachable = false;
            } finally {
                socketServerChecked = true;
                socketServerLastCheckAt = Date.now();
            }

            socketServerAvailable = reachable;
            if (!socketServerAvailable) {
                if (!socketUnavailableNoticeShown) {
                    socketUnavailableNoticeShown = true;
                    console.warn('[socket] Signaling server is unavailable at', SOCKET_HEALTH_URL);
                }
            } else {
                socketUnavailableNoticeShown = false;
            }

            return socketServerAvailable;
        })();

        try {
            return await socketServerCheckPromise;
        } finally {
            socketServerCheckPromise = null;
        }
    }

    function ensureSocket() {
        if (socket && socket.connected) return socket;
        if (typeof window.io !== 'function') {
            console.error('[socket] Socket.IO library not loaded');
            return null;
        }
        if (IS_LOCAL && !socketServerAvailable) {
            // Probe in the background and avoid noisy websocket errors while server is down.
            checkSocketServerAvailability();
            return null;
        }
        
        // Reset socket if it exists but is disconnected
        if (socket && !socket.connected) {
            socket.disconnect();
            socket = null;
            socketBound = false;
        }
        
        const socketOptions = {
            path: SOCKET_IO_PATH,
            transports: ['websocket', 'polling'],
            // In local mode we retry manually after health checks to avoid noisy browser errors.
            reconnection: !IS_LOCAL,
            reconnectionAttempts: IS_LOCAL ? 0 : MAX_SOCKET_RETRIES,
            reconnectionDelayMax: IS_LOCAL ? 0 : 2000,
            timeout: 8000
        };

        socket = IS_LOCAL
            ? window.io(SIGNALING_URL, socketOptions)
            : window.io(socketOptions);
        
        bindSocketHandlers();
        return socket;
    }

    function bindSocketHandlers() {
        if (!socket || socketBound) return;
        socketBound = true;

        socket.on('connect', () => {
            console.log('[socket] Connected to signaling server');
            socket.emit('join', room);
            socketRetryCount = 0; // Reset retry count on successful connection
        });

        socket.on('disconnect', (reason) => {
            console.warn('[socket] Disconnected:', reason);
            if (callId) {
                setStatus('Connection lost. Attempting to reconnect…');
            }
        });

        socket.on('connect_error', (error) => {
            console.error('[socket] Connection error:', error);
            if (IS_LOCAL) {
                socketServerAvailable = false;
                socketServerChecked = true;
                socketServerLastCheckAt = Date.now();

                if (socket) {
                    socket.disconnect();
                    socket = null;
                    socketBound = false;
                    callSocketListenersBoundFor = null;
                }

                if (callId) {
                    setStatus('Call signaling unavailable (socket server offline).');
                }
                return;
            }

            socketRetryCount++;
            if (socketRetryCount >= MAX_SOCKET_RETRIES) {
                console.error('[socket] Max retries reached. Giving up.');
                if (callId) {
                    setStatus('Connection failed. Please refresh the page.');
                    setEndEnabled(true);
                }
            } else {
                console.log(`[socket] Retry ${socketRetryCount}/${MAX_SOCKET_RETRIES}`);
                if (callId) {
                    setStatus(`Connecting... (attempt ${socketRetryCount}/${MAX_SOCKET_RETRIES})`);
                }
            }
        });

        (async function initNotificationSoundPref() {
            try {
                const res = await fetch('../api/profile.php?action=notification_sound_get');
                const data = await res.json();
                if (data && data.success && data.notification_sound) {
                    notificationSound = data.notification_sound;
                }
            } catch (e) {}
        })();
    }

    function _stopAlertSound() {
        try {
            if (_soundTimer) clearInterval(_soundTimer);
            _soundTimer = null;
            if (_soundGain) _soundGain.gain.value = 0;
            if (_soundOsc) {
                try { _soundOsc.stop(); } catch (e) {}
                _soundOsc.disconnect();
            }
        } catch (e) {}
        _soundOsc = null;
        _soundGain = null;
    }

    function _startAlertSound(type) {
        if (type === 'silent') return;
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        if (!_soundCtx) _soundCtx = new AudioContext();
        const ctx = _soundCtx;

        try {
            if (ctx && ctx.state === 'suspended') ctx.resume();
        } catch (e) {}

        _stopAlertSound();

        const gain = ctx.createGain();
        gain.gain.value = 0;
        gain.connect(ctx.destination);

        const osc = ctx.createOscillator();
        osc.type = 'sine';
        osc.frequency.value = 800;
        osc.connect(gain);
        osc.start();

        _soundOsc = osc;
        _soundGain = gain;

        const setOn = (on) => {
            if (!_soundGain) return;
            _soundGain.gain.value = on ? 0.22 : 0;
        };

        if (type === 'beep') {
            let on = false;
            _soundTimer = setInterval(() => {
                on = !on;
                osc.frequency.value = 880;
                setOn(on);
            }, 260);
            setOn(true);
            return;
        }

        if (type === 'pulse') {
            let step = 0;
            _soundTimer = setInterval(() => {
                step++;
                const on = step % 6 === 0;
                osc.frequency.value = 950;
                setOn(on);
            }, 130);
            return;
        }

        if (type === 'siren') {
            let high = false;
            _soundTimer = setInterval(() => {
                high = !high;
                osc.frequency.value = high ? 1100 : 700;
                setOn(true);
            }, 260);
            setOn(true);
            return;
        }

        setOn(true);
    }

    let pc = null;
    let localStream = null;
    let callId = null;
    let callConversationId = null;
    let callerInfo = null;
    let callerLocation = null;
    let callConnectedAt = null;
    let timerInterval = null;
    let locationData = null;
    let messages = [];

    let pendingOffer = null;
    let pendingCallId = null;
    let pendingCandidates = [];

    // Messaging functions for admin
    function addMessage(text, sender = 'admin', timestamp = Date.now()) {
        const messagesContainer = document.getElementById('callMessages');
        if (!messagesContainer) return;
        
        // Clear placeholder text if this is the first message
        if (messages.length === 0) {
            messagesContainer.innerHTML = '';
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
            margin-bottom: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            background: ${sender === 'admin'
                ? 'color-mix(in srgb, var(--primary-color-1) 22%, transparent)'
                : 'color-mix(in srgb, var(--secondary-color-1) 18%, transparent)'};
            border-left: 3px solid ${sender === 'admin' ? 'var(--primary-color-1)' : 'var(--secondary-color-1)'};
            font-size: 13px;
            line-height: 1.4;
        `;
        
        const time = new Date(timestamp).toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true 
        });
        
        const senderName = sender === 'admin' ? 
            'Emergency Services' : 
            'User';
        
        messageDiv.innerHTML = `
            <div style="font-weight: 600; margin-bottom: 2px; font-size: 11px; opacity: 0.8;">
                ${senderName} • ${time}
            </div>
            <div>${text}</div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        messages.push({ text, sender, timestamp, callId });
    }

    async function sendCallMessage() {
        const input = document.getElementById('callMessageInput');
        const text = input.value.trim();
        if (!text || !callId) return;
        
        input.value = '';
        
        // Add to local UI immediately
        addMessage(text, 'admin');
        
        // Send via socket
        const s = ensureSocket();
        if (s) {
            s.emit('call-message', {
                text,
                callId,
                sender: 'admin',
                senderName: 'Emergency Services',
                timestamp: Date.now()
            }, room);
        }
        
        // Log to database using existing chat-send structure
        try {
            const formData = new FormData();
            formData.append('text', text);
            if (callConversationId) formData.append('conversationId', callConversationId);
            
            const response = await fetch('../api/chat-send.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                console.error('Failed to log message to database');
            }
        } catch (e) {
            console.error('Failed to log message:', e);
        }
    }

    function formatTime(totalSeconds) {
        const m = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
        const s = String(totalSeconds % 60).padStart(2, '0');
        return `${m}:${s}`;
    }

    function setOverlayVisible(visible) {
        document.getElementById('callOverlay').style.display = visible ? 'block' : 'none';
    }

    async function renderCallerDetails() {
        const nameEl = document.getElementById('callerName');
        const phoneEl = document.getElementById('callerPhone');
        const addrEl = document.getElementById('callerAddress');
        const coordsEl = document.getElementById('callerCoords');

        if (nameEl) nameEl.textContent = callerInfo?.name || '—';
        if (phoneEl) phoneEl.textContent = callerInfo?.phone || '—';

        // Fetch address from database if we have user_id or phone
        let address = callerInfo?.address || '';
        if (!address && (callerInfo?.user_id || callerInfo?.phone)) {
            try {
                const userId = callerInfo?.user_id || null;
                const phone = callerInfo?.phone || null;
                
                if (userId || phone) {
                    const params = new URLSearchParams();
                    if (userId) params.append('user_id', userId);
                    if (phone) params.append('phone', phone);
                    
                    const response = await fetch(`${API_BASE}get-caller-address.php?${params.toString()}`);
                    const data = await response.json();
                    
                    if (data.success && data.address) {
                        address = data.address;
                        // Update callerInfo for future use
                        if (callerInfo) callerInfo.address = address;
                    }
                }
            } catch (e) {
                console.error('Error fetching address:', e);
            }
        }
        
        // Fallback: build address from components if still empty
        if (!address) {
            const parts = [];
            if (callerInfo?.house_number) parts.push(callerInfo.house_number);
            if (callerInfo?.street) parts.push(callerInfo.street);
            if (callerInfo?.barangay) parts.push(callerInfo.barangay);
            if (callerInfo?.district) parts.push(callerInfo.district);
            const fallback = parts.filter(Boolean).join(', ');
            if (fallback) address = fallback;
        }
        
        if (addrEl) addrEl.textContent = address || '—';

        const lat = callerLocation?.lat;
        const lng = callerLocation?.lng;
        if (coordsEl) coordsEl.textContent = (lat != null && lng != null) ? `${lat}, ${lng}` : '—';
    }

    function setCallActiveBannerVisible(visible) {
        const el = document.getElementById('callActiveBanner');
        if (!el) return;
        el.style.display = visible ? 'block' : 'none';
    }

    function setIncomingCallModalVisible(visible) {
        const el = document.getElementById('incomingCallModal');
        if (!el) return;
        el.style.display = visible ? 'block' : 'none';
    }

    function setIncomingCallModalText(text) {
        const el = document.getElementById('incomingCallText');
        if (el) el.textContent = text;
    }

    function setIncomingEmergencyCallRowVisible(visible) {
        const el = document.getElementById('incomingEmergencyCallRow');
        if (!el) return;
        el.style.display = visible ? 'block' : 'none';
    }

    function renderIncomingEmergencyCallRow() {
        const host = document.getElementById('incomingEmergencyCallRow');
        if (!host) return;

        if (!pendingOffer || !pendingCallId) {
            host.innerHTML = '';
            setIncomingEmergencyCallRowVisible(false);
            return;
        }

        host.innerHTML = `
            <div class="conversation-item" data-conversation-id="emergency-call" style="border:1px solid rgba(220,38,38,0.45); background: rgba(220,38,38,0.06);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:10px; background:rgba(220,38,38,0.18); border:1px solid rgba(220,38,38,0.35); display:flex; align-items:center; justify-content:center; flex:0 0 auto;">
                        <i class="fas fa-phone-alt" style="color:#dc2626;"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:900; letter-spacing:0.4px;">Emergency Call</div>
                        <div style="font-size:12px; opacity:0.9;">Incoming call request</div>
                    </div>
                    <div style="display:flex; gap:8px; flex:0 0 auto;">
                        <button id="emergencyCallDeclineBtn" class="btn btn-sm btn-secondary" style="padding:0.4rem 0.65rem;">Decline</button>
                        <button id="emergencyCallAcceptBtn" class="btn btn-sm btn-primary" style="padding:0.4rem 0.65rem;">Accept</button>
                    </div>
                </div>
            </div>
        `;

        setIncomingEmergencyCallRowVisible(true);

        const acceptBtn = document.getElementById('emergencyCallAcceptBtn');
        if (acceptBtn) acceptBtn.onclick = () => {
            if (typeof window.acceptIncomingEmergencyCall === 'function') window.acceptIncomingEmergencyCall();
        };

        const declineBtn = document.getElementById('emergencyCallDeclineBtn');
        if (declineBtn) declineBtn.onclick = () => {
            if (typeof window.declineIncomingEmergencyCall === 'function') window.declineIncomingEmergencyCall();
        };
    }

    function setStatus(text) {
        const el = document.getElementById('callStatus');
        if (el) el.textContent = text;
    }

    function setTimer(seconds) {
        const el = document.getElementById('callTimer');
        if (el) el.textContent = formatTime(seconds);
    }

    function setEndEnabled(enabled) {
        const btn = document.getElementById('endCallBtn');
        if (!btn) return;
        btn.disabled = !enabled;
        btn.style.opacity = enabled ? '1' : '0.6';
        btn.style.pointerEvents = enabled ? 'auto' : 'none';
    }

    function startTimer() {
        if (!callConnectedAt) return;
        stopTimer();
        timerInterval = setInterval(() => {
            const seconds = Math.max(0, Math.floor((Date.now() - callConnectedAt) / 1000));
            setTimer(seconds);
        }, 1000);
    }

    function stopTimer() {
        if (timerInterval) clearInterval(timerInterval);
        timerInterval = null;
    }

    async function tryGetLocation() {
        return new Promise(resolve => {
            if (!navigator.geolocation) return resolve(null);
            navigator.geolocation.getCurrentPosition(
                p => resolve({
                    lat: p.coords.latitude,
                    lng: p.coords.longitude,
                    accuracy: p.coords.accuracy
                }),
                () => resolve(null),
                { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
            );
        });
    }

    async function logCall(event, extra = {}) {
        try {
            // Only log if we have a callId
            if (!callId) {
                console.warn('Cannot log call event: callId is missing');
                return;
            }
            
            const payload = {
                callId: callId,
                room: room || null,
                role: 'admin',
                event: event,
                location: locationData || null,
                ...extra
            };
            
            const response = await fetch('../api/call-log.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                console.warn('Call log response not OK:', response.status, errorText);
            }
        } catch (e) {
            // Log call errors are non-critical, just log a warning
            console.warn('Failed to log call event:', e);
        }
    }

    function cleanupCall() {
        stopTimer();
        setEndEnabled(false);
        setCallActiveBannerVisible(false);
        setIncomingCallModalVisible(false);

        messages = [];
        const messagesContainer = document.getElementById('callMessages');
        if (messagesContainer) {
            messagesContainer.innerHTML = '<div style="text-align:center; opacity:0.6; font-size:12px;">Messages will appear here</div>';
        }

        const messageInput = document.getElementById('callMessageInput');
        if (messageInput) messageInput.value = '';

        pendingOffer = null;
        pendingCallId = null;
        pendingCandidates = [];
        callConversationId = null;
        callerInfo = null;
        callerLocation = null;
        renderCallerDetails();
        renderIncomingEmergencyCallRow();

        _stopAlertSound();

        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
            localStream = null;
        }
        if (pc) {
            try { pc.close(); } catch (e) {}
            pc = null;
        }
        callConnectedAt = null;
        callId = null;
        locationData = null;
        setTimer(0);
    }

    document.getElementById('endCallBtn').onclick = () => endCall(true);
    document.getElementById('callSendMessageBtn').onclick = () => sendCallMessage();
    document.getElementById('callMessageInput').onkeypress = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendCallMessage();
        }
    };
    document.getElementById('incomingAnswerBtn').onclick = () => acceptIncomingEmergencyCall();
    document.getElementById('incomingDeclineBtn').onclick = () => declineIncomingEmergencyCall();

    document.getElementById('dispatchRespondentBtn').onclick = async () => {
        const statusEl = document.getElementById('dispatchStatus');
        if (statusEl) statusEl.textContent = '';
        if (!callId) {
            if (statusEl) statusEl.textContent = 'No active call.';
            return;
        }
        const type = document.getElementById('emergencyTypeSelect')?.value || '';
        if (!type) {
            if (statusEl) statusEl.textContent = 'Please choose an emergency type.';
            return;
        }
        try {
            if (statusEl) statusEl.textContent = 'Sending dispatch request…';
            const res = await fetch('../api/dispatch-respondent.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    callId,
                    emergencyType: type,
                    caller: callerInfo,
                    location: callerLocation,
                    conversationId: callConversationId
                })
            });
            const data = await res.json().catch(() => ({}));
            if (data && data.success) {
                if (statusEl) statusEl.textContent = 'Dispatch request queued (placeholder endpoint).';
            } else {
                if (statusEl) statusEl.textContent = data.message || 'Dispatch request failed.';
            }
        } catch (e) {
            if (statusEl) statusEl.textContent = 'Dispatch request failed.';
        }
    };

    document.getElementById('transferCallBtn').onclick = async () => {
        const statusEl = document.getElementById('dispatchStatus');
        if (statusEl) statusEl.textContent = '';
        if (!callId) {
            if (statusEl) statusEl.textContent = 'No active call.';
            return;
        }
        try {
            if (statusEl) statusEl.textContent = 'Starting transfer…';
            const res = await fetch('../api/transfer-call.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    callId,
                    emergencyType: document.getElementById('emergencyTypeSelect')?.value || '',
                    caller: callerInfo,
                    location: callerLocation,
                    conversationId: callConversationId
                })
            });
            const data = await res.json().catch(() => ({}));
            if (data && data.success) {
                if (statusEl) statusEl.textContent = 'Transfer initiated (placeholder endpoint).';
            } else {
                if (statusEl) statusEl.textContent = data.message || 'Transfer failed.';
            }
        } catch (e) {
            if (statusEl) statusEl.textContent = 'Transfer failed.';
        }
    };

    async function endCall(notifyPeer = true) {
        const durationSec = callConnectedAt ? Math.floor((Date.now() - callConnectedAt) / 1000) : 0;
        
        // Log call end event (non-blocking)
        try {
            await logCall('ended', { durationSec });
        } catch (e) {
            console.warn('Failed to log call end event:', e);
        }

        // Save call to conversation with proper user information
        if (callId) {
            try {
                // Get user information from callerInfo or use defaults
                const userId = callerInfo?.user_id || callerInfo?.id || null;
                const userName = callerInfo?.name || 'Emergency Call User';
                const userPhone = callerInfo?.phone || null;
                
                const saveResponse = await fetch('../api/save-completed-call.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        callId: callId,
                        userId: userId,
                        userName: userName,
                        userPhone: userPhone,
                        duration: durationSec || 0,
                        endedAt: Math.floor(Date.now() / 1000), // Unix timestamp in seconds
                        conversationId: callConversationId || null // Pass existing conversation ID if available
                    })
                });
                
                if (!saveResponse.ok) {
                    const errorText = await saveResponse.text();
                    console.error('Save call response not OK:', saveResponse.status, errorText);
                    throw new Error(`HTTP ${saveResponse.status}: ${errorText}`);
                }
                
                const saveData = await saveResponse.json();
                if (saveData.success) {
                    if (saveData.conversationId) {
                        // Update callConversationId if we got a new one
                        callConversationId = saveData.conversationId;
                        console.log('Call saved to conversation:', saveData.conversationId);
                    }
                    
                    // Refresh conversations list to show the new/updated conversation
                    // Switch to closed tab since call ended conversations are closed
                    if (typeof switchTab === 'function') {
                        switchTab('closed');
                    }
                    if (typeof loadConversations === 'function') {
                        setTimeout(() => {
                            loadConversations(true);
                        }, 1000);
                    }
                } else {
                    console.error('Failed to save call:', saveData.message || saveData.error || 'Unknown error');
                }
            } catch (e) {
                console.error('Error saving call:', e);
                // Don't block the call end process if saving fails
            }
        } else {
            console.warn('Cannot save call: callId is missing');
        }

        if (notifyPeer && callId) {
            const s = ensureSocket();
            if (s) s.emit('hangup', { callId }, room);
        }

        setStatus('Call ended');
        setTimeout(() => {
            setOverlayVisible(false);
            cleanupCall();
        }, 800);
    }

    function initPeer() {
        pc = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:global.stun.twilio.com:3478' }
            ]
        });

        pc.ontrack = e => {
            const remote = document.getElementById('remote');
            if (remote) remote.srcObject = e.streams[0];
        };

        pc.onicecandidate = e => {
            if (!e.candidate) return;
            const s = ensureSocket();
            if (s && callId) s.emit('candidate', { candidate: e.candidate, callId }, room);
        };

        pc.onconnectionstatechange = () => {
            if (!pc) return;
            if (pc.connectionState === 'connected' && !callConnectedAt) {
                callConnectedAt = Date.now();
                setStatus('Connected');
                setEndEnabled(true);
                startTimer();
                logCall('connected');
                _stopAlertSound();
                setIncomingCallModalVisible(false);
            }
            if (['disconnected', 'failed', 'closed'].includes(pc.connectionState)) {
                if (callId) endCall(false);
            }
        };
    }

    async function acceptIncomingEmergencyCall() {
        if (!pendingOffer || !pendingCallId) return;
        if (callId && pendingCallId !== callId) return;

        callId = pendingCallId;
        try {
            await logCall('accepted', {
                adminUsername: (typeof ADMIN_USERNAME !== 'undefined' ? ADMIN_USERNAME : null)
            });
        } catch (e) {}
        setIncomingEmergencyCallRowVisible(false);
        setOverlayVisible(true);
        setCallActiveBannerVisible(true);
        setStatus('Connecting…');
        setTimer(0);
        setEndEnabled(false);

        try {
            if (!pc) initPeer();
            await pc.setRemoteDescription(pendingOffer);

            localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
            localStream.getTracks().forEach(track => pc.addTrack(track, localStream));

            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            const s = ensureSocket();
            if (s) s.emit('answer', { sdp: answer, callId }, room);

            if (Array.isArray(pendingCandidates) && pendingCandidates.length) {
                for (const cand of pendingCandidates) {
                    try { if (pc && cand) await pc.addIceCandidate(cand); } catch (e) {}
                }
            }
        } catch (e) {
            setStatus('Call failed');
            setEndEnabled(true);
            endCall(true);
        } finally {
            pendingOffer = null;
            pendingCandidates = [];
            renderIncomingEmergencyCallRow();
        }
    }

    async function declineIncomingEmergencyCall() {
        if (!pendingCallId) {
            setIncomingEmergencyCallRowVisible(false);
            _stopAlertSound();
            return;
        }

        try {
            await logCall('declined', { callId: pendingCallId });
        } catch (e) {}

        const s = ensureSocket();
        if (s) s.emit('hangup', { callId: pendingCallId }, room);
        pendingOffer = null;
        pendingCallId = null;
        pendingCandidates = [];
        renderIncomingEmergencyCallRow();
        _stopAlertSound();
    }

    window.acceptIncomingEmergencyCall = acceptIncomingEmergencyCall;
    window.declineIncomingEmergencyCall = declineIncomingEmergencyCall;

    let callSocketListenersBoundFor = null;
    function bindCallSocketListeners() {
        const s = ensureSocket();
        if (!s) return;
        if (callSocketListenersBoundFor === s) return;
        callSocketListenersBoundFor = s;

        s.on('offer', async payload => {
            const sdp = payload && payload.sdp ? payload.sdp : payload;
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (!incomingCallId) return;
            if (callId && incomingCallId !== callId) return;

            callConversationId = payload && payload.conversationId ? payload.conversationId : null;
            callerInfo = payload && payload.caller ? payload.caller : null;
            callerLocation = payload && payload.location ? payload.location : null;
            renderCallerDetails(); // Now async, will fetch address from database

            // If no conversation ID, try to find or create one for this user
            // Note: This will be done when the call is accepted, not here
            // We'll create/find the conversation when saving the completed call

            pendingCallId = incomingCallId;
            pendingOffer = sdp;
            pendingCandidates = [];

            try {
                if (typeof switchTab === 'function') switchTab('open');
            } catch (e) {}

            _startAlertSound(notificationSound);
            locationData = await tryGetLocation();
            await logCall('incoming');
            renderIncomingEmergencyCallRow();
        });

        s.on('candidate', payload => {
            const cand = payload && payload.candidate ? payload.candidate : payload;
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (incomingCallId && callId && incomingCallId !== callId) return;
            if (incomingCallId && pendingCallId && incomingCallId !== pendingCallId) return;

            if (!pc || !callId) {
                if (cand) pendingCandidates.push(cand);
                return;
            }

            if (pc && cand) pc.addIceCandidate(cand);
        });

        s.on('hangup', payload => {
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (incomingCallId && callId && incomingCallId !== callId) return;
            if (incomingCallId && pendingCallId && incomingCallId !== pendingCallId) return;

            if (pendingCallId && incomingCallId === pendingCallId && !callId) {
                pendingOffer = null;
                pendingCallId = null;
                pendingCandidates = [];
                renderIncomingEmergencyCallRow();
                _stopAlertSound();
                return;
            }

            if (callId) endCall(false);
        });

        s.on('call-message', payload => {
            const incomingCallId = payload && payload.callId ? payload.callId : null;
            if (incomingCallId && callId && incomingCallId !== callId) return;
            if (incomingCallId && pendingCallId && incomingCallId !== pendingCallId) return;
            if (payload.text && payload.sender !== 'admin') {
                addMessage(payload.text, payload.sender || 'user', payload.timestamp);
            }
        });
    }

    if (IS_LOCAL) {
        checkSocketServerAvailability(true).then((available) => {
            if (available) {
                bindCallSocketListeners();
            } else {
                setStatus('Call signaling unavailable (socket server offline).');
            }
        });

        // Keep trying quietly so page can recover if socket server starts later.
        setInterval(() => {
            if (socket && socket.connected) return;
            checkSocketServerAvailability();
            bindCallSocketListeners();
        }, 7000);
    } else {
        bindCallSocketListeners();
    }
</script>

</body>
</html>
