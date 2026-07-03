(function () {
    "use strict";

    function byId(id) {
        return document.getElementById(id);
    }

    function text(selector) {
        var node = document.querySelector(selector);
        return node ? node.textContent.trim() : "";
    }

    function numFromText(value) {
        if (value == null) return 0;
        var cleaned = String(value).replace(/[^0-9.\-]/g, "");
        var n = Number(cleaned);
        return Number.isFinite(n) ? n : 0;
    }

    function fmtInt(value) {
        return Number(value || 0).toLocaleString();
    }

    function getSidebarPageName() {
        var p = window.location.pathname.replace(/\\/g, "/");
        return p.split("/").pop().toLowerCase();
    }

    function hasNativeAnalytics() {
        return !!document.querySelector(".mn-mini-analytics, .ac-mini-analytics, .module-analytics-strip");
    }

    function mountPoint() {
        return document.querySelector(".main-content .page-content");
    }

    function getFallbackCards() {
        var values = Array.prototype.slice.call(document.querySelectorAll(".stat-value")).slice(0, 4);
        if (!values.length) return null;

        var cards = values.map(function (valueNode, idx) {
            var parent = valueNode.closest(".stat-card, .channel-card, .module-card, .ac-stat, .mn-stat") || valueNode.parentElement;
            var labelNode = parent ? parent.querySelector(".stat-label, .mn-stat-label, .ac-stat-label, h3, h4") : null;
            var label = labelNode ? labelNode.textContent.trim() : ("Metric " + (idx + 1));

            return {
                label: label,
                icon: "fa-chart-line",
                tone: ["tone-a", "tone-b", "tone-c", "tone-d"][idx % 4],
                read: function () { return valueNode.textContent.trim() || "-"; },
                sub: "Live"
            };
        });

        return {
            title: "Module Analytics",
            cards: cards
        };
    }

    function buildConfig(page) {
        var cfg = {
            "dashboard.php": {
                title: "Dashboard Analytics",
                cards: [
                    { label: "Total Subscribers", icon: "fa-users", tone: "tone-a", read: function () { return text("#totalSubscribers") || "0"; }, sub: "Registered citizens" },
                    { label: "Notifications Today", icon: "fa-bell", tone: "tone-b", read: function () { return text("#notificationsToday") || "0"; }, sub: "Alerts sent today" },
                    { label: "Pending Messages", icon: "fa-comments", tone: "tone-c", read: function () { return text("#pendingMessages") || "0"; }, sub: "Need response" },
                    { label: "Success Rate", icon: "fa-chart-line", tone: "tone-d", read: function () { return text("#successRate") || "0%"; }, sub: "Delivery health" }
                ]
            },
            "users.php": {
                title: "User Analytics",
                cards: [
                    { label: "Administrators", icon: "fa-user-shield", tone: "tone-a", read: function () { return text("#totalAdmins") || "0"; }, sub: "Active admins" },
                    { label: "Staff Members", icon: "fa-user-tie", tone: "tone-b", read: function () { return text("#totalStaff") || "0"; }, sub: "Support staff" },
                    { label: "Pending Approval", icon: "fa-clock", tone: "tone-c", read: function () { return text("#totalPending") || "0"; }, sub: "Awaiting review" },
                    { label: "Inactive", icon: "fa-user-slash", tone: "tone-d", read: function () { return text("#totalInactive") || "0"; }, sub: "Disabled accounts" }
                ]
            },
            "citizen-subscriptions.php": {
                title: "Subscriber Analytics",
                cards: [
                    { label: "Total Subscribers", icon: "fa-users", tone: "tone-a", read: function () { return text("#totalSubscribers") || "0"; }, sub: "All records" },
                    { label: "Active", icon: "fa-user-check", tone: "tone-b", read: function () { return text("#activeSubscribers") || "0"; }, sub: "Active subscriptions" },
                    { label: "Weather", icon: "fa-cloud-sun-rain", tone: "tone-c", read: function () { return text("#weatherSubscribers") || "0"; }, sub: "Weather alerts opted-in" },
                    { label: "Earthquake", icon: "fa-mountain", tone: "tone-d", read: function () { return text("#earthquakeSubscribers") || "0"; }, sub: "Earthquake alerts opted-in" }
                ]
            },
            "audit-trail.php": {
                title: "Audit Analytics",
                cards: [
                    { label: "Total Logs", icon: "fa-list", tone: "tone-a", read: function () { return text("#totalNotifications") || "0"; }, sub: "All notifications" },
                    { label: "Successful", icon: "fa-circle-check", tone: "tone-b", read: function () { return text("#successfulNotifications") || "0"; }, sub: "Delivered successfully" },
                    { label: "Failed", icon: "fa-triangle-exclamation", tone: "tone-c", read: function () { return text("#failedNotifications") || "0"; }, sub: "Delivery failures" },
                    { label: "Sent Today", icon: "fa-calendar-day", tone: "tone-d", read: function () { return text("#todayNotifications") || "0"; }, sub: "Today's activity" }
                ]
            },
            "admin-approvals.php": {
                title: "Approval Analytics",
                cards: [
                    { label: "Pending", icon: "fa-hourglass-half", tone: "tone-a", read: function () { return text("#pendingCount") || "0"; }, sub: "Awaiting approval" },
                    { label: "Active", icon: "fa-user-check", tone: "tone-b", read: function () { return text("#activeCount") || "0"; }, sub: "Approved accounts" },
                    { label: "Inactive", icon: "fa-user-slash", tone: "tone-c", read: function () { return text("#inactiveCount") || "0"; }, sub: "Disabled accounts" },
                    {
                        label: "Approval Rate",
                        icon: "fa-chart-line",
                        tone: "tone-d",
                        read: function () {
                            var pending = numFromText(text("#pendingCount"));
                            var active = numFromText(text("#activeCount"));
                            var inactive = numFromText(text("#inactiveCount"));
                            var total = pending + active + inactive;
                            if (!total) return "0%";
                            return Math.round((active / total) * 100) + "%";
                        },
                        sub: "Based on totals"
                    }
                ]
            },
            "two-way-communication.php": {
                title: "Conversation Analytics",
                cards: [
                    {
                        label: "All Conversations",
                        icon: "fa-layer-group",
                        tone: "tone-a",
                        read: function () { return text('[data-dept-count="all"]') || "0"; },
                        sub: "Across departments"
                    },
                    {
                        label: "Open Queue",
                        icon: "fa-inbox",
                        tone: "tone-b",
                        read: function () { return text("#openCount") || "0"; },
                        sub: "Open tab count"
                    },
                    {
                        label: "Active Departments",
                        icon: "fa-building",
                        tone: "tone-c",
                        read: function () {
                            var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-dept-count]'));
                            var active = nodes.filter(function (n) {
                                var key = n.getAttribute("data-dept-count");
                                if (key === "all") return false;
                                return numFromText(n.textContent) > 0;
                            }).length;
                            return String(active);
                        },
                        sub: "With active chats"
                    },
                    {
                        label: "Selected Department",
                        icon: "fa-filter",
                        tone: "tone-d",
                        read: function () {
                            var activeChip = document.querySelector(".dept-nav-chip.active span");
                            return activeChip ? activeChip.textContent.trim() : "All";
                        },
                        sub: "Top navigation filter"
                    }
                ]
            }
        };

        return cfg[page] || getFallbackCards();
    }

    function renderCard(cardCfg) {
        var card = document.createElement("article");
        card.className = "module-analytics-card " + (cardCfg.tone || "tone-a");

        card.innerHTML =
            '<div class="module-analytics-head">' +
                '<span class="module-analytics-label"></span>' +
                '<span class="module-analytics-icon"><i class="fas ' + (cardCfg.icon || "fa-chart-line") + '"></i></span>' +
            "</div>" +
            '<div class="module-analytics-value">-</div>' +
            '<div class="module-analytics-sub">' + (cardCfg.sub || "Live") + "</div>";

        card.querySelector(".module-analytics-label").textContent = cardCfg.label || "Metric";
        return card;
    }

    function init() {
        if (!window.location.pathname.toLowerCase().includes("/admin/sidebar/")) return;
        if (hasNativeAnalytics()) return;

        var target = mountPoint();
        if (!target) return;

        var page = getSidebarPageName();
        var config = buildConfig(page);
        if (!config || !config.cards || !config.cards.length) return;

        var wrapper = document.createElement("section");
        wrapper.className = "module-analytics-strip";
        wrapper.innerHTML =
            '<div class="module-analytics-title">' + (config.title || "Module Analytics") + "</div>" +
            '<div class="module-analytics-grid"></div>';

        var grid = wrapper.querySelector(".module-analytics-grid");
        var bindings = config.cards.slice(0, 4).map(function (cardCfg) {
            var cardEl = renderCard(cardCfg);
            grid.appendChild(cardEl);
            return {
                read: cardCfg.read,
                valueEl: cardEl.querySelector(".module-analytics-value")
            };
        });

        target.insertBefore(wrapper, target.firstChild);

        function update() {
            bindings.forEach(function (b) {
                var raw = typeof b.read === "function" ? b.read() : "-";
                var out = raw == null || raw === "" ? "-" : String(raw);
                b.valueEl.textContent = out;
            });
        }

        update();
        setInterval(update, 1500);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
