(function () {
    'use strict';

    function sanitizeFilename(value) {
        const normalized = String(value || 'report')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        return normalized || 'report';
    }

    function buildTimestamp() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hour = String(now.getHours()).padStart(2, '0');
        const minute = String(now.getMinutes()).padStart(2, '0');
        return `${year}${month}${day}-${hour}${minute}`;
    }

    function getDefaultFilenamePrefix() {
        const titleNode = document.querySelector('.main-content .title h1, .main-content h1, .panel-title');
        if (titleNode && titleNode.textContent) {
            return titleNode.textContent.trim();
        }
        return document.title || 'report';
    }

    function resolveTarget(targetSelector) {
        if (targetSelector) {
            const customTarget = document.querySelector(targetSelector);
            if (customTarget) {
                return customTarget;
            }
        }

        const bodyTargetSelector = document.body ? document.body.getAttribute('data-pdf-target') : '';
        if (bodyTargetSelector) {
            const bodyTarget = document.querySelector(bodyTargetSelector);
            if (bodyTarget) {
                return bodyTarget;
            }
        }

        const candidates = [
            '.main-content .main-container',
            '.main-content .overview-container',
            '.main-content .management-container',
            '.main-content .page-content',
            '.main-content'
        ];

        for (let i = 0; i < candidates.length; i += 1) {
            const node = document.querySelector(candidates[i]);
            if (node) {
                return node;
            }
        }

        return null;
    }

    function setButtonLoading(button, isLoading) {
        if (!button) {
            return;
        }

        if (isLoading) {
            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
            }
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.dataset.loading = 'true';
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Preparing...</span>';
            return;
        }

        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.dataset.loading = 'false';
    }

    function notifyFailure(message) {
        if (typeof window.showAlert === 'function') {
            window.showAlert(message, 'error');
            return;
        }
        window.alert(message);
    }

    async function exportCurrentPage(options) {
        const exportOptions = options || {};
        const triggerButton = exportOptions.triggerButton || null;

        if (triggerButton && triggerButton.dataset.loading === 'true') {
            return false;
        }

        const target = resolveTarget(exportOptions.targetSelector);
        if (!target) {
            notifyFailure('No report content found to export.');
            return false;
        }

        if (typeof window.html2pdf !== 'function') {
            notifyFailure('PDF exporter is not available yet. Please try again.');
            return false;
        }

        const filenamePrefix = exportOptions.filenamePrefix || getDefaultFilenamePrefix();
        const filename = `${sanitizeFilename(filenamePrefix)}-${buildTimestamp()}.pdf`;
        const pdfOptions = {
            margin: [8, 8, 8, 8],
            filename,
            image: { type: 'jpeg', quality: 0.95 },
            html2canvas: {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                scrollX: 0,
                scrollY: 0
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['css', 'legacy'] }
        };

        const finalPdfOptions = Object.assign({}, pdfOptions, exportOptions.pdfOptions || {});

        try {
            setButtonLoading(triggerButton, true);
            await window.html2pdf().set(finalPdfOptions).from(target).save();
            return true;
        } catch (error) {
            console.error('PDF export failed:', error);
            notifyFailure('Unable to generate PDF report.');
            return false;
        } finally {
            setButtonLoading(triggerButton, false);
        }
    }

    function initHeaderPdfButton() {
        const headerButton = document.getElementById('headerPdfExportBtn');
        if (!headerButton) {
            return;
        }

        headerButton.addEventListener('click', function () {
            exportCurrentPage({ triggerButton: headerButton });
        });
    }

    window.AdminReportPdfExporter = {
        exportCurrentPage,
        resolveTarget
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHeaderPdfButton);
    } else {
        initHeaderPdfButton();
    }
})();
