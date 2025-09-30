// console.log('flux-data-sections.js loaded');

import { DocumentDetailLookupLinks } from './document-detail-lookup-links.js';

export class DocumentDetailDataSections {
    /**
     * Generates the complete data sections HTML with real data
     * @param {string} sourceImagePath - Path to source system logo
     * @param {string} targetImagePath - Path to target system logo  
     * @param {string} historyImagePath - Path to history section logo
     * @returns {string} HTML string for all data sections
     */
    static generateDataSections(sourceImagePath, targetImagePath, historyImagePath) {
// console.log('🏗️ Generating data sections with placeholder containers');
        
        try {
            return `
                <div class="data-wrapper" style="margin: 20px;">
                    ${this.generateSourceSection(sourceImagePath)}
                    ${this.generateTargetSection(targetImagePath)}
                    ${this.generateHistorySection(historyImagePath)}
                </div>
            `;
        } catch (error) {
            console.error('❌ Error generating data sections:', error);
            return this.generateErrorSection('Failed to generate data sections');
        }
    }

    /**
     * Generates the source data section with logo
     * @param {string} sourceImagePath - Path to the source logo image
     * @returns {string} HTML string for the source section
     */
    static generateSourceSection(sourceImagePath) {
        return `
            <div class="source-section">
                <div class="section-header">
                    <img src="${sourceImagePath}" alt="Source Logo" class="solution-logo source-logo" style="width: 24px; height: 24px; margin-right: 8px;">
                    <h3>Source</h3>
                </div>
                <div id="source-data-body" class="section-body">
                    <p>Loading source data...</p>
                </div>
            </div>
        `;
    }

    /**
     * Generates the target data section with logo
     * @param {string} targetImagePath - Path to the target logo image
     * @returns {string} HTML string for the target section
     */
    static generateTargetSection(targetImagePath) {
        return `
            <div class="target-section">
                <div class="section-header">
                    <img src="${targetImagePath}" alt="Target Logo" class="solution-logo target-logo" style="width: 24px; height: 24px; margin-right: 8px;">
                    <h3>Target</h3>
                </div>
                <div id="target-data-body" class="section-body">
                    <p>Loading target data...</p>
                </div>
            </div>
        `;
    }

    /**
     * Generates the history data section with logo
     * @param {string} historyImagePath - Path to the history logo image
     * @returns {string} HTML string for the history section
     */
    static generateHistorySection(historyImagePath) {
        return `
            <div class="history-section">
                <div class="section-header">
                    <img src="${historyImagePath}" alt="History Logo" class="solution-logo history-logo" style="width: 24px; height: 24px; margin-right: 8px;">
                    <h3>History</h3>
                </div>
                <div id="history-data-body" class="section-body">
                    <p>Loading history data...</p>
                </div>
            </div>
        `;
    }

    /**
     * Renders a full‑width placeholder table under Source/Target/History.
     * @param {Array<Object>} rows
     *   Each row should have: docId, name, ruleId, sourceId, targetId,
     *   modificationDate, type, status
     */
    static generateDocumentHistory(rows = []) {
        if (!rows.length) return ``;

        // Get current document ID from URL
        const currentDocumentId = window.location.pathname.split('/').pop();

        // build each row's <tr>…
        const body = rows
        .map(({ docId, name, ruleId, sourceId, targetId, modificationDate, type, status }) => {
            // turn "Error_transformed" → "error_transformed" for class names
            const statusClass = status.toLowerCase().replace(/[^a-z0-9]+/g, `_`);

            // Check if this is the current document
            const isCurrentDocument = docId === currentDocumentId;

            // Build proper URLs
            const pathParts = window.location.pathname.split('/');
            const publicIndex = pathParts.indexOf('public');
            let baseUrl = window.location.origin;
            if (publicIndex !== -1) {
                const baseParts = pathParts.slice(0, publicIndex + 1);
                baseUrl = window.location.origin + baseParts.join('/');
            } else {
                baseUrl = window.location.origin + "/index.php";
            }

            const documentUrl = `${baseUrl}/rule/flux/modern/${docId}`;
            const ruleUrl = `${baseUrl}/rule/view/${ruleId}`;

            // Determine background color based on status (similar to logs section)
            // Error statuses: all statuses containing 'error' or ending with '_ko' or 'not_found'
            const isErrorStatus = (status.toLowerCase().includes('error') && status.toLowerCase() !== 'error_expected') ||
                                status.toLowerCase().endsWith('_ko') ||
                                status.toLowerCase() === 'not_found' ||
                                status.toLowerCase() === 'create_ko';

            // Cancel statuses: Cancel, Filter, No_send
            const isCancelStatus = ['cancel', 'filter', 'no_send', 'error_expected'].includes(status.toLowerCase());

            const rowStyle = isErrorStatus
                ? ' style="background-color: #ffebee;"'
                : isCancelStatus
                    ? ' style="background-color: #F9EEDF;"'
                    : '';

            return `
            <tr${rowStyle}>
                <td>
                    ${isCurrentDocument ? '<span class="current-document-checkmark">✓</span>' : ''}
                </td>
                <td><a href="${documentUrl}" class="doc-id" style="color: #0F66A9; text-decoration: none;">${docId}</a></td>
                <td><a href="${ruleUrl}" class="doc-name" style="color: #0F66A9; text-decoration: none;">${name}</a></td>
                <td>${this.sanitizeString(sourceId)}</td>
                <td>${this.sanitizeString(targetId)}</td>
                <td>${modificationDate}</td>
                <td>${type}</td>
                <td>
                <span class="status‑badge status‑${statusClass}">
                    ${status}
                </span>
                </td>
            </tr>
            `;
        })
        .join(``);

        const historyHtml = `
        <div class="data-wrapper custom-section">
            <div class="custom-header">
            <h3>Documents history</h3>
            <span class="custom-count">(${rows.length})</span>
            <button class="toggle-btn" aria-expanded="true">-</button>
            </div>

            <div class="custom-content">
            <button type="button" class="btn btn-warning" id="cancel-history-btn" style="margin-bottom: 10px;">
                Cancel History
            </button>
            <table class="custom-table">
            <thead>
                <tr>
                <th></th>
                <th>Doc Id</th>
                <th>Name</th>
                <th>Source id</th>
                <th>Target id</th>
                <th>Modification date</th>
                <th>Type</th>
                <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${body}
            </tbody>
            </table>
        </div>
        </div>
        `;

        // Set up event listener for the cancel history button after a small delay
        setTimeout(() => {
            DocumentDetailDataSections.setupCancelHistoryButton();
        }, 100);

        return historyHtml;
    }

    /**
     * Generates Parent documents section
     * @param {Array<Object>} rows - Each row should have: docId, name, ruleId, sourceId, targetId, modificationDate, type, status
     */
    static generateParentDocumentsSection(rows = []) {
        if (!rows.length) {
            return `
            <div class="data-wrapper parent-documents-section" data-section="parent-documents">
                <div class="parent-documents-header">
                    <h3>📄 Parent Documents</h3>
                </div>
                <div class="parent-documents-content">
                    <p>No parent document</p>
                </div>
            </div>
            `;
        }

        const body = rows
        .map(({ docId, name, ruleId, sourceId, targetId, modificationDate, type, status }) => {
            const statusClass = status.toLowerCase().replace(/[^a-z0-9]+/g, `_`);

            // Build proper URLs
            const pathParts = window.location.pathname.split('/');
            const publicIndex = pathParts.indexOf('public');
            let baseUrl = window.location.origin;
            if (publicIndex !== -1) {
                const baseParts = pathParts.slice(0, publicIndex + 1);
                baseUrl = window.location.origin + baseParts.join('/');
            }

            const documentUrl = `${baseUrl}/rule/flux/modern/${docId}`;
            const ruleUrl = `${baseUrl}/rule/view/${ruleId}`;

            // Determine background color based on status (similar to logs section)
            // Error statuses: all statuses containing 'error' or ending with '_ko' or 'not_found'
            const isErrorStatus = (status.toLowerCase().includes('error') && status.toLowerCase() !== 'error_expected') ||
                                status.toLowerCase().endsWith('_ko') ||
                                status.toLowerCase() === 'not_found' ||
                                status.toLowerCase() === 'create_ko';

            // Cancel statuses: Cancel, Filter, No_send
            const isCancelStatus = ['cancel', 'filter', 'no_send', 'error_expected'].includes(status.toLowerCase());

            const rowStyle = isErrorStatus
                ? ' style="background-color: #ffebee;"'
                : isCancelStatus
                    ? ' style="background-color: #F9EEDF;"'
                    : '';

            return `
            <tr${rowStyle}>
                <td><a href="${documentUrl}" class="doc-id" style="color: #0F66A9; text-decoration: none;">${docId}</a></td>
                <td><a href="${ruleUrl}" class="doc-name" style="color: #0F66A9; text-decoration: none;">${name}</a></td>
                <td>${this.sanitizeString(sourceId)}</td>
                <td>${this.sanitizeString(targetId)}</td>
                <td>${modificationDate}</td>
                <td>${type}</td>
                <td>
                <span class="status‑badge status‑${statusClass}">
                    ${status}
                </span>
                </td>
            </tr>
            `;
        })
        .join(``);

        return `
        <div class="data-wrapper parent-documents-section" data-section="parent-documents">
            <div class="parent-documents-header">
            <h3>Parent documents</h3>
            <span class="parent-documents-count">(${rows.length})</span>
            <button class="parent-documents-toggle-btn" aria-expanded="true">-</button>
            </div>

            <div class="parent-documents-content">
            <table class="parent-documents-table">
            <thead>
                <tr>
                <th>Doc Id</th>
                <th>Name</th>
                <th>Source id</th>
                <th>Target id</th>
                <th>Modification date</th>
                <th>Type</th>
                <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${body}
            </tbody>
            </table>
        </div>
        </div>
        `;
    }

    /**
     * Generates Child documents section
     * @param {Array<Object>} rows - Each row should have: docId, name, ruleId, sourceId, targetId, modificationDate, type, status
     */
    static generateChildDocumentsSection(rows = []) {
        if (!rows.length) {
            return `
            <div class="data-wrapper child-documents-section" data-section="child-documents">
                <div class="child-documents-header">
                    <h3>📄 Child Documents</h3>
                </div>
                <div class="child-documents-content">
                    <p>No child document</p>
                </div>
            </div>
            `;
        }

        const body = rows
        .map(({ docId, name, ruleId, sourceId, targetId, modificationDate, type, status }) => {
            const statusClass = status.toLowerCase().replace(/[^a-z0-9]+/g, `_`);

            // Build proper URLs
            const pathParts = window.location.pathname.split('/');
            const publicIndex = pathParts.indexOf('public');
            let baseUrl = window.location.origin;
            if (publicIndex !== -1) {
                const baseParts = pathParts.slice(0, publicIndex + 1);
                baseUrl = window.location.origin + baseParts.join('/');
            } else {
                baseUrl = window.location.origin + "/index.php";
            }

            const documentUrl = `${baseUrl}/rule/flux/modern/${docId}`;
            const ruleUrl = `${baseUrl}/rule/view/${ruleId}`;

            // Determine background color based on status (similar to logs section)
            // Error statuses: all statuses containing 'error' or ending with '_ko' or 'not_found'
            const isErrorStatus = (status.toLowerCase().includes('error') && status.toLowerCase() !== 'error_expected') ||
                                status.toLowerCase().endsWith('_ko') ||
                                status.toLowerCase() === 'not_found' ||
                                status.toLowerCase() === 'create_ko';

            // Cancel statuses: Cancel, Filter, No_send
            const isCancelStatus = ['cancel', 'filter', 'no_send', 'error_expected'].includes(status.toLowerCase());

            const rowStyle = isErrorStatus
                ? ' style="background-color: #ffebee;"'
                : isCancelStatus
                    ? ' style="background-color: #F9EEDF;"'
                    : '';

            return `
            <tr${rowStyle}>
                <td><a href="${documentUrl}" class="doc-id" style="color: #0F66A9; text-decoration: none;">${docId}</a></td>
                <td><a href="${ruleUrl}" class="doc-name" style="color: #0F66A9; text-decoration: none;">${name}</a></td>
                <td>${this.sanitizeString(sourceId)}</td>
                <td>${this.sanitizeString(targetId)}</td>
                <td>${modificationDate}</td>
                <td>${type}</td>
                <td>
                <span class="status‑badge status‑${statusClass}">
                    ${status}
                </span>
                </td>
            </tr>
            `;
        })
        .join(``);

        return `
        <div class="data-wrapper child-documents-section" data-section="child-documents">
            <div class="child-documents-header">
            <h3>Child documents</h3>
            <span class="child-documents-count">(${rows.length})</span>
            <button class="child-documents-toggle-btn" aria-expanded="true">-</button>
            </div>

            <div class="child-documents-content">
            <table class="child-documents-table">
            <thead>
                <tr>
                <th>Doc Id</th>
                <th>Name</th>
                <th>Source id</th>
                <th>Target id</th>
                <th>Modification date</th>
                <th>Type</th>
                <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${body}
            </tbody>
            </table>
        </div>
        </div>
        `;
    }

    /**
     * Generates Post Documents section
     * @param {Array<Object>} rows - Post documents data
     */
    static generatePostDocumentsSection(rows = []) {
        if (!rows.length) {
            return `
            <div class="data-wrapper post-documents-section" data-section="post-documents">
                <div class="post-documents-header">
                    <h3>📄 Post Documents</h3>
                </div>
                <div class="post-documents-content">
                    <p>No post document</p>
                </div>
            </div>
            `;
        }

        const body = rows
        .map(({ docId, name, ruleId, sourceId, targetId, modificationDate, type, status }) => {
            const statusClass = status.toLowerCase().replace(/[^a-z0-9]+/g, `_`);

            // Build proper URLs
            const pathParts = window.location.pathname.split('/');
            const publicIndex = pathParts.indexOf('public');
            let baseUrl = window.location.origin;
            if (publicIndex !== -1) {
                const baseParts = pathParts.slice(0, publicIndex + 1);
                baseUrl = window.location.origin + baseParts.join('/');
            } else {
                baseUrl = window.location.origin + "/index.php";
            }

            const documentUrl = `${baseUrl}/rule/flux/modern/${docId}`;
            const ruleUrl = `${baseUrl}/rule/view/${ruleId}`;

            // Determine background color based on status
            const isErrorStatus = (status.toLowerCase().includes('error') && status.toLowerCase() !== 'error_expected') ||
                                status.toLowerCase().endsWith('_ko') ||
                                status.toLowerCase() === 'not_found' ||
                                status.toLowerCase() === 'create_ko';

            const isCancelStatus = ['cancel', 'filter', 'no_send', 'error_expected'].includes(status.toLowerCase());

            const rowStyle = isErrorStatus
                ? ' style="background-color: #ffebee;"'
                : isCancelStatus
                    ? ' style="background-color: #F9EEDF;"'
                    : '';

            return `
            <tr${rowStyle}>
                <td><a href="${documentUrl}" class="doc-id" style="color: #0F66A9; text-decoration: none;">${docId}</a></td>
                <td><a href="${ruleUrl}" class="doc-name" style="color: #0F66A9; text-decoration: none;">${name}</a></td>
                <td>${this.sanitizeString(sourceId)}</td>
                <td>${this.sanitizeString(targetId)}</td>
                <td>${modificationDate}</td>
                <td>${type}</td>
                <td>
                <span class="status‑badge status‑${statusClass}">
                    ${status}
                </span>
                </td>
            </tr>
            `;
        })
        .join(``);

        return `
        <div class="data-wrapper post-documents-section" data-section="post-documents">
            <div class="post-documents-header">
            <h3>Post documents</h3>
            <span class="post-documents-count">(${rows.length})</span>
            <button class="post-documents-toggle-btn" aria-expanded="true">-</button>
            </div>

            <div class="post-documents-content">
            <table class="post-documents-table">
            <thead>
                <tr>
                <th>Doc Id</th>
                <th>Name</th>
                <th>Source id</th>
                <th>Target id</th>
                <th>Modification date</th>
                <th>Type</th>
                <th>Status</th>
                </tr>
            </thead>
            <tbody>
                ${body}
            </tbody>
            </table>
        </div>
        </div>
        `;
    }

    /**
     * Generates Workflow Logs section
     * @param {Array<Object>} rows - Workflow logs data with id, workflowName, jobName, actionName, status, dateCreated, message
     */
    static generateWorkflowLogsSection(rows = []) {
        if (!rows.length) {
            return `
            <div class="data-wrapper workflow-logs-section" data-section="workflow-logs">
                <div class="workflow-logs-header">
                    <h3>Workflow Logs</h3>
                    <span class="workflow-logs-count">(0)</span>
                    <button class="workflow-logs-toggle-btn" aria-expanded="true">-</button>
                </div>

                <div class="workflow-logs-content">
                    <p>No workflow logs available</p>
                </div>
            </div>
            `;
        }

        // console.log('🔍 generateWorkflowLogsSection: Processing', rows.length, 'workflow log rows');
        // console.log('🔍 Sample workflow log data:', rows[0]);
        // console.log('🔍 All available fields in first row:', Object.keys(rows[0] || {}));

        const body = rows
        .map(({ id, workflowName, jobName, triggerDocument, generateDocument, createdBy, actionName, actionType, status, dateCreated, message, workflowId, jobId, actionId }, index) => {
            // console.log(`🔍 Row ${index}:`, { id, workflowName, jobName, triggerDocument, generateDocument, createdBy, actionName, actionType, workflowId, jobId, actionId });
            
            // Determine color based on status
            let statusColor = '#28a745'; // default green for success
            if (status && status.toLowerCase().includes('error')) {
                statusColor = '#dc3545'; // red for errors
            } else if (status && status.toLowerCase().includes('warning')) {
                statusColor = '#ffc107'; // yellow for warnings
            }

            // Build proper URLs
            const pathParts = window.location.pathname.split('/');
            const publicIndex = pathParts.indexOf('public');
            let baseUrl = window.location.origin;
            if (publicIndex !== -1) {
                const baseParts = pathParts.slice(0, publicIndex + 1);
                baseUrl = window.location.origin + baseParts.join('/');
            } else {
                baseUrl = window.location.origin + "/index.php";
            }

            // console.log('🔍 Base URL:', baseUrl);

            // Create clickable links for workflow, job, and action if IDs are available
            let workflowLink = this.sanitizeString(workflowName);
            if (workflowId) {
                const workflowUrl = `${baseUrl}/workflow/show/${workflowId}`;
                workflowLink = `<a href="${workflowUrl}" class="workflow-link single-line-detected">${this.sanitizeString(workflowName)}</a>`;
                // console.log('🔗 Created workflow link:', workflowLink);
            } else {
                // console.log('❌ No workflowId for workflow:', workflowName);
            }

            let jobLink = this.sanitizeString(jobName);
            if (jobId) {
                const jobUrl = `${baseUrl}/rule/task/view/${jobId}/log`;
                jobLink = `<a href="${jobUrl}" class="job-link single-line-detected">${this.sanitizeString(jobName)}</a>`;
                // console.log('🔗 Created job link:', jobLink);
            } else {
                // console.log('❌ No jobId for job:', jobName);
            }

            // Create clickable link for trigger document if it exists
            let triggerDocumentLink = this.sanitizeString(triggerDocument || '');
            if (triggerDocument) {
                const triggerDocUrl = `${baseUrl}/rule/flux/modern/${triggerDocument}`;
                triggerDocumentLink = `<a href="${triggerDocUrl}" class="trigger-doc-link single-line-detected">${this.sanitizeString(triggerDocument)}</a>`;
            }

            // Create clickable link for generate document if it exists
            let generateDocumentLink = this.sanitizeString(generateDocument || '');
            if (generateDocument) {
                const generateDocUrl = `${baseUrl}/rule/flux/modern/${generateDocument}`;
                generateDocumentLink = `<a href="${generateDocUrl}" class="generate-doc-link single-line-detected">${this.sanitizeString(generateDocument)}</a>`;
            }

            let actionLink = this.sanitizeString(actionName);
            if (actionId) {
                const actionUrl = `${baseUrl}/workflowAction/showAction/${actionId}`;
                actionLink = `<a href="${actionUrl}" class="action-link single-line-detected">${this.sanitizeString(actionName)}</a>`;
                // console.log('🔗 Created action link:', actionLink);
            } else {
                // console.log('❌ No actionId for action:', actionName);
            }

            return `
            <tr>
                <td>${id}</td>
                <td>${workflowLink}</td>
                <td>${jobLink}</td>
                <td>${triggerDocumentLink}</td>
                <td>${generateDocumentLink}</td>
                <td>${this.sanitizeString(createdBy || '')}</td>
                <td><span style="color: ${statusColor}; font-weight: bold;">${this.sanitizeString(status)}</span></td>
                <td>${dateCreated}</td>
                <td>${this.sanitizeString(message)}</td>
                <td>${actionLink}</td>
                <td>${this.sanitizeString(actionType || '')}</td>
            </tr>
            `;
        })
        .join(``);

        return `
        <div class="data-wrapper workflow-logs-section" data-section="workflow-logs">
            <div class="workflow-logs-header">
            <h3>Workflow Logs</h3>
            <span class="workflow-logs-count">(${rows.length})</span>
            <button class="workflow-logs-toggle-btn" aria-expanded="true">-</button>
            </div>

            <div class="workflow-logs-content">
            <table class="workflow-logs-table">
            <thead>
                <tr>
                <th>Id</th>
                <th>Workflow</th>
                <th>Job</th>
                <th>Trigger Document</th>
                <th>Generate Document</th>
                <th>Created By</th>
                <th>Status</th>
                <th>Date Created</th>
                <th>Message</th>
                <th>Action Name</th>
                <th>Action Type</th>
                </tr>
            </thead>
            <tbody>
                ${body}
            </tbody>
            </table>
        </div>
        </div>
        `;
    }

    /**
     * Generates Logs section
     * @param {Array<Object>} rows - Logs data with id, reference, job, creationDate, type, message
     */
    static generateLogsSection(rows = []) {
        if (!rows.length) {
            // Always create the logs section container, even when empty
            return `
            <div class="data-wrapper logs-section">
                <div class="logs-header">
                <h3>Logs</h3>
                <span class="logs-count">(0)</span>
                <button class="logs-toggle-btn" aria-expanded="true">-</button>
                </div>

                <div class="logs-content">
                    <p>No logs available</p>
                </div>
            </div>
            `;
        }

        const body = rows
        .map(({ id, reference, job, creationDate, type, message }) => {
            // Determine color based on type
            let typeColor = '#28a745'; // default green for 'S ✓'
            if (type.startsWith('W')) {
                typeColor = '#ffc107'; // yellow for 'W x' types
            } else if (type.startsWith('E')) {
                typeColor = '#dc3545'; // red for 'E' types
            }

            // Build proper URL for reference link if reference exists
            let referenceLink = reference;
            if (reference && reference !== '' && reference.trim() !== '') {
                // Build proper URLs
                const pathParts = window.location.pathname.split('/');
                const publicIndex = pathParts.indexOf('public');
                let baseUrl = window.location.origin;
                if (publicIndex !== -1) {
                    const baseParts = pathParts.slice(0, publicIndex + 1);
                    baseUrl = window.location.origin + baseParts.join('/');
                } else {
                    baseUrl = window.location.origin + "/index.php";
                }
                
                const referenceUrl = `${baseUrl}/rule/flux/modern/${reference}`;
                referenceLink = `<a href="${referenceUrl}" class="log-reference" style="color: #0F66A9; text-decoration: none;">${reference}</a>`;
            }

            // Build proper URL for job link if job exists
            let jobLink = job;
            if (job && job !== '' && job.trim() !== '') {
                // Build proper URLs
                const pathParts = window.location.pathname.split('/');
                const publicIndex = pathParts.indexOf('public');
                let baseUrl = window.location.origin;
                if (publicIndex !== -1) {
                    const baseParts = pathParts.slice(0, publicIndex + 1);
                    baseUrl = window.location.origin + baseParts.join('/');
                } else {
                    baseUrl = window.location.origin + "/index.php";
                }
                
                const jobUrl = `${baseUrl}/rule/task/view/${job}/log`;
                jobLink = `<a href="${jobUrl}" class="log-job" style="color: #0F66A9; text-decoration: none;">${job}</a>`;
            }

            const rowStyle = type.startsWith('E')
                ? ' style="background-color: #ffebee;"'
                : type.startsWith('W')
                    ? ' style="background-color: #F9EEDF;"'
                    : '';

            
            return `
            <tr${rowStyle}>
                <td>${id}</td>
                <td>${referenceLink}</td>
                <td>${jobLink}</td>
                <td>${creationDate}</td>
                <td><span style="color: ${typeColor}; font-weight: bold;">${type}</span></td>
                <td>${message}</td>
            </tr>
            `;
        })
        .join(``);

        return `
        <div class="data-wrapper logs-section">
            <div class="logs-header">
            <h3>Logs</h3>
            <span class="logs-count">(${rows.length})</span>
            <button class="logs-toggle-btn" aria-expanded="true">-</button>
            </div>

            <div class="logs-content">
            <table class="logs-table">
            <thead>
                <tr>
                <th>Id</th>
                <th>Reference</th>
                <th>Job</th>
                <th>Creation date</th>
                <th>Type</th>
                <th>Message</th>
                </tr>
            </thead>
            <tbody>
                ${body}
            </tbody>
            </table>
        </div>
        </div>
        `;
    }

    /**
     * Generates the source data section HTML
     * @param {string} logoPath - Path to source logo image
     * @returns {string} HTML string for source section
     */
    static generateSourceSection(logoPath) {
        return this.generateDataSection('source', 'Source', logoPath);
    }

    /**
     * Generates the target data section HTML
     * @param {string} logoPath - Path to target logo image
     * @returns {string} HTML string for target section
     */
    static generateTargetSection(logoPath) {
        return this.generateDataSection('target', 'Target', logoPath);
    }

    /**
     * Generates the history data section HTML
     * @param {string} logoPath - Path to history logo image
     * @returns {string} HTML string for history section
     */
    static generateHistorySection(logoPath) {
        return this.generateDataSection('history', 'History', logoPath);
    }

    /**
     * Generates a generic data section template
     * @param {string} sectionType - Type of section (source, target, history)
     * @param {string} sectionTitle - Display title for the section
     * @param {string} logoPath - Path to logo image
     * @returns {string} HTML string for the section
     */
    static generateDataSection(sectionType, sectionTitle, logoPath) {
        const sectionId = `${sectionType}-data-body`;
        
        return `
            <div class="${sectionType}-data">
                <div class="${sectionType}-data-content">
                    <div class="${sectionType}-data-content-header">
                        <div class="${sectionType}-logo-container">
                            <img class="logo-small-size" src="${logoPath}" alt="${sectionTitle} Logo">
                        </div>
                        <h3>${sectionTitle}</h3>
                    </div>
                    <div class="${sectionType}-data-content-body" id="${sectionId}">
                        <div class="loading-message">Loading ${sectionTitle.toLowerCase()} data...</div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Updates source section with real data
     * @param {Object|null} sourceData - Source data object from API
     */
    static updateSourceData(sourceData) {
// console.log('📊 Updating source data section');
        this.updateDataSection('source', sourceData, 'Source');
    }

    /**
     * Updates target section with real data
     * @param {Object|null} targetData - Target data object from API
     */
    static updateTargetData(targetData) {
// console.log('🎯 Updating target data section');
        this.updateDataSection('target', targetData, 'Target');
    }

    /**
     * Updates history section with real data
     * @param {Object|null} historyData - History data object from API
     */
    static updateHistoryData(historyData) {
// console.log('📜 Updating history data section');
        this.updateDataSection('history', historyData, 'History');
    }

    /**
     * Generic method to update any data section
     * @param {string} sectionType - Type of section (source, target, history)
     * @param {Object|null} sectionData - Data object from API
     * @param {string} sectionName - Display name for logging
     */
    static updateDataSection(sectionType, sectionData, sectionName) {
        const sectionBodyId = `${sectionType}-data-body`;
        const sectionElement = document.getElementById(sectionBodyId);

        if (!sectionElement) {
            console.error(`❌ ${sectionName} section element not found:`, sectionBodyId);
            return;
        }

        try {
            if (!sectionData || Object.keys(sectionData).length === 0) {
                sectionElement.innerHTML = this.generateEmptyDataMessage(sectionName);
                // console.warn(`⚠️ No ${sectionName.toLowerCase()} data available`);
                return;
            }

            const fieldsHtml = this.generateDataFields(sectionData, sectionType);
            sectionElement.innerHTML = fieldsHtml;

            // Add ID field for non-SuiteCRM solutions if not already present
            this.ensureIdFieldExists(sectionElement, sectionType, sectionData);

            // Add click handlers for field expansion
            this.addFieldClickHandlers(sectionElement);

// console.log(`✅ ${sectionName} data updated successfully`);

        } catch (error) {
            console.error(`❌ Error updating ${sectionName.toLowerCase()} data:`, error);
            sectionElement.innerHTML = this.generateErrorMessage(`Failed to load ${sectionName.toLowerCase()} data`);
        }
    }

    /**
     * Generates HTML for data fields
     * @param {Object} fieldData - Object containing field key-value pairs
     * @param {string} sectionType - Type of section for CSS classes
     * @returns {string} HTML string for all fields
     */
    static generateDataFields(fieldData, sectionType) {
        if (!fieldData || typeof fieldData !== 'object') {
            // console.warn('⚠️ Invalid field data provided:', fieldData);
            return this.generateEmptyDataMessage('data');
        }

        const fieldEntries = Object.entries(fieldData);

        if (fieldEntries.length === 0) {
            return this.generateEmptyDataMessage('fields');
        }

        return fieldEntries
            .filter(([fieldName, fieldValue]) => {
                // Skip 'id' field if there will be a direct link (SuiteCRM/Airtable)
                if (fieldName.toLowerCase() === 'id') {
                    const hasDirectLink = this.willHaveDirectLink(sectionType);
                    if (hasDirectLink) {
                        // console.log(`✅ Filtering out redundant '${fieldName}' field for ${sectionType} (direct link available)`);
                        return false;
                    }
                }
                return true;
            })
            .map(([fieldName, fieldValue]) => this.generateSingleField(fieldName, fieldValue, sectionType))
            .join('');
    }

    /**
     * Generates HTML for a single field
     * @param {string} fieldName - Name/label of the field
     * @param {any} fieldValue - Value of the field
     * @param {string} sectionType - Type of section for CSS classes
     * @returns {string} HTML string for the field
     */
    static generateSingleField(fieldName, fieldValue, sectionType) {
        const sanitizedFieldName = this.sanitizeString(fieldName);
        const sanitizedFieldValue = this.sanitizeString(fieldValue);
        const fieldId = `field-${sectionType}-${this.generateFieldId(fieldName)}`;

        // Use lookup link utility to wrap value with link if conditions are met
        const fieldValueWithLookupLink = DocumentDetailLookupLinks.wrapWithLookupLinkIfNeeded(fieldName, fieldValue);

        return `
            <div class="field-row" data-field-type="${sectionType}">
                <div class="field-label" title="${sanitizedFieldName}">${sanitizedFieldName}</div>
                <div class="field-separator"></div>
                <div class="field-value" 
                     id="${fieldId}"
                     title="${sanitizedFieldValue}" 
                     data-full-value="${sanitizedFieldValue}">
                    ${fieldValueWithLookupLink}
                </div>
            </div>
        `;
    }

    /**
     * Generates a unique field ID from field name
     * @param {string} fieldName - Original field name
     * @returns {string} Sanitized field ID
     */
    static generateFieldId(fieldName) {
        return fieldName
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    /**
     * Sanitizes string values for safe HTML display
     * @param {any} value - Value to sanitize
     * @returns {string} Sanitized string
     */
    static sanitizeString(value) {
        if (value === null || value === undefined) {
            return '';
        }
        
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /**
     * Adds click handlers for field expansion - REMOVED: Using existing FluxFieldExpander system
     * @param {HTMLElement} sectionElement - Section container element
     */
    static addFieldClickHandlers(sectionElement) {
        // Notify the existing FluxFieldExpander system that new content is available
        this.notifyFieldExpanderOfNewContent();
    }

    /**
     * Ensures that an ID field exists for the section, adding one if missing
     * @param {HTMLElement} sectionElement - The section container element
     * @param {string} sectionType - Type of section (source, target, history)
     * @param {Object} sectionData - Data object from API
     */
    static ensureIdFieldExists(sectionElement, sectionType, sectionData) {
        // Check if there's already a direct link or ID field present
        const existingDirectLink = sectionElement.querySelector('.direct-link-document');
        const existingIdField = sectionElement.querySelector(`[id^="field-${sectionType}-id"]`);

        if (existingDirectLink || existingIdField) {
            // ID field already exists (either as direct link or regular field)
            return;
        }

        // Get the document ID based on section type
        let idValue = null;
        if (sectionType === 'source') {
            // Try to get source_id from the document data (passed from API)
            idValue = this.getDocumentIdFromAPI(sectionType);
        } else if (sectionType === 'target') {
            // Try to get target_id from the document data (passed from API)
            idValue = this.getDocumentIdFromAPI(sectionType);
        }

        // If we couldn't get the ID from API data, skip adding the field
        if (!idValue) {
            // console.log(`ℹ️ No ${sectionType} ID available to display`);
            return;
        }

        // Generate ID field HTML
        const idFieldHtml = this.generateSingleField('id', idValue, sectionType);

        // Insert the ID field at the beginning of the section
        sectionElement.insertAdjacentHTML('afterbegin', idFieldHtml);

        // console.log(`✅ Added ID field to ${sectionType} section:`, idValue);
    }

    /**
     * Gets the document ID from the API data that was stored globally
     * @param {string} sectionType - Type of section (source, target)
     * @returns {string|null} The ID value or null if not found
     */
    static getDocumentIdFromAPI(sectionType) {
        try {
            // Check if we have access to the document data that was loaded earlier
            // We'll store this on window temporarily during the update process
            if (window.currentDocumentData) {
                if (sectionType === 'source') {
                    return window.currentDocumentData.source_id || null;
                } else if (sectionType === 'target') {
                    return window.currentDocumentData.target_id || null;
                }
            }
            return null;
        } catch (error) {
            // console.warn(`⚠️ Error getting ${sectionType} ID from API data:`, error);
            return null;
        }
    }

    /**
     * Checks if the section will have a direct link (SuiteCRM/Airtable solutions)
     * @param {string} sectionType - Type of section (source, target)
     * @returns {boolean} True if the section will have a direct link
     */
    static willHaveDirectLink(sectionType) {
        try {
            if (window.currentDocumentData) {
                if (sectionType === 'source') {
                    return !!window.currentDocumentData.source_direct_link;
                } else if (sectionType === 'target') {
                    return !!window.currentDocumentData.target_direct_link;
                }
            }
            return false;
        } catch (error) {
            // console.warn(`⚠️ Error checking direct link for ${sectionType}:`, error);
            return false;
        }
    }

    /**
     * Notifies the existing FluxFieldExpander system that new content has been loaded
     */
    static notifyFieldExpanderOfNewContent() {
        // Dispatch a custom event to let FluxFieldExpander know it should re-initialize
        const event = new CustomEvent('fluxDataUpdated', {
            detail: {
                source: 'FluxDataSections',
                timestamp: new Date().toISOString()
            }
        });
        document.dispatchEvent(event);
// console.log('📢 Notified FluxFieldExpanner of new content');
    }

    /**
     * Generates empty data message
     * @param {string} dataType - Type of data that's empty
     * @returns {string} HTML for empty message
     */
    static generateEmptyDataMessage(dataType) {
        return `
            <div class="empty-data-message">
                <p>No ${dataType} available</p>
            </div>
        `;
    }

    /**
     * Generates error message HTML
     * @param {string} errorMessage - Error message to display
     * @returns {string} HTML for error message
     */
    static generateErrorMessage(errorMessage) {
        return `
            <div class="error-data-message">
                <p style="color: #dc3545;">⚠️ ${errorMessage}</p>
            </div>
        `;
    }

    /**
     * Generates error section HTML
     * @param {string} errorMessage - Error message to display
     * @returns {string} HTML for error section
     */
    static generateErrorSection(errorMessage) {
        return `
            <div class="data-wrapper error-wrapper" style="margin: 20px;">
                <div class="error-message">
                    <p style="color: #dc3545; text-align: center; padding: 20px;">
                        ⚠️ ${errorMessage}
                    </p>
                </div>
            </div>
        `;
    }

    /**
     * Sets up the event listener for the cancel history button
     */
    static setupCancelHistoryButton() {
        const cancelButton = document.getElementById('cancel-history-btn');
        if (cancelButton && !cancelButton.hasAttribute('data-listener-attached')) {
            cancelButton.addEventListener('click', () => {
                DocumentDetailDataSections.cancelHistoryDocuments();
            });
            cancelButton.setAttribute('data-listener-attached', 'true');
            // console.log('✅ Cancel history button event listener attached');
        }
    }

    /**
     * Cancels all documents in the history table using mass action
     */
    static cancelHistoryDocuments() {
        try {
            // Get all document IDs from the history table
            const historyTable = document.querySelector('.custom-table tbody');
            if (!historyTable) {
                console.error('❌ History table not found');
                return;
            }

            const documentIds = [];
            const rows = historyTable.querySelectorAll('tr');

            rows.forEach(row => {
                const docIdLink = row.querySelector('td:nth-child(2) a.doc-id');
                if (docIdLink) {
                    const docId = docIdLink.textContent.trim();
                    if (docId) {
                        documentIds.push(docId);
                    }
                }
            });

            if (documentIds.length === 0) {
                console.warn('⚠️ No document IDs found in history table');
                return;
            }

            console.log('📋 Found document IDs:', documentIds);

            // Get base URL for the API call
            const pathParts = window.location.pathname.split('/');
            const publicIndex = pathParts.indexOf('public');
            let baseUrl = window.location.origin;
            if (publicIndex !== -1) {
                const baseParts = pathParts.slice(0, publicIndex + 1);
                baseUrl = window.location.origin + baseParts.join('/');
            } else {
                baseUrl = window.location.origin + "/index.php";
            }

            const apiUrl = `${baseUrl}/rule/flux/masscancel`;

            // Prepare the payload for mass cancel (form data format)
            const formData = new FormData();
            documentIds.forEach(id => {
                formData.append('ids[]', id);
            });

            console.log('🚀 Calling mass cancel API:', apiUrl, 'with', documentIds.length, 'document IDs');

            // Make the API call
            fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                // The mass cancel endpoint doesn't return JSON, just success if we get here
                console.log('✅ Mass cancel completed successfully');
                alert(`Mass cancel action initiated for ${documentIds.length} documents. Check the task list for progress.`);
            })
            .catch(error => {
                console.error('❌ Error calling mass action API:', error);
                alert('Error initiating mass cancel action. Please check the console for details.');
            });

        } catch (error) {
            console.error('❌ Error in cancelHistoryDocuments:', error);
            alert('Error initiating mass cancel action. Please check the console for details.');
        }
    }
}

// Make the class available globally
window.DocumentDetailDataSections = DocumentDetailDataSections;