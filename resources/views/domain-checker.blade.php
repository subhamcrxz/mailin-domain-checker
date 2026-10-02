<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Mailin Task Domain Checker</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            background: #f5f7fb;
            color: #172033;
        }

        .container {
            width: min(1400px, calc(100% - 40px));
            margin: 0 auto;
        }

        header {
            background: #111827;
            color: white;
            padding: 22px 0;
        }

        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            font-size: 22px;
            font-weight: 800;
        }

        .brand span {
            color: #60a5fa;
        }

        .subtitle {
            color: #cbd5e1;
            font-size: 13px;
            margin-top: 4px;
        }

        main {
            padding: 30px 0 60px;
        }

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .tab {
            border: 1px solid #d8dee9;
            background: white;
            padding: 11px 18px;
            border-radius: 9px;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
        }

        .tab.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.04);
        }

        .card h2 {
            margin: 0 0 7px;
            font-size: 18px;
        }

        .description {
            margin: 0 0 20px;
            color: #64748b;
            font-size: 14px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 220px auto;
            gap: 12px;
        }

        input[type="text"],
        input[type="file"] {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            padding: 12px 13px;
            font-size: 14px;
            background: white;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        button {
            border: 0;
            border-radius: 9px;
            padding: 12px 17px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn-success {
            background: #16a34a;
            color: white;
        }

        .btn-danger {
            background: #dc2626;
            color: white;
        }

        button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .help {
            font-size: 12px;
            color: #64748b;
            margin-top: 7px;
        }

        .hidden {
            display: none !important;
        }

        .single-result {
            margin-top: 20px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .summary-item {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            background: #f8fafc;
        }

        .summary-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 15px;
            font-weight: 700;
            word-break: break-word;
        }

        .clean {
            color: #15803d;
        }

        .listed {
            color: #dc2626;
        }

        .neutral {
            color: #475569;
        }

        .progress-wrapper {
            margin-top: 20px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .progress {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            width: 0;
            background: #2563eb;
            transition: width .25s ease;
        }

        .batch-stats {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .stat {
            padding: 8px 12px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 13px;
        }

        .filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .filter {
            border: 1px solid #cbd5e1;
            background: white;
            color: #475569;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .filter.active {
            background: #172033;
            border-color: #172033;
            color: white;
        }

        .table-wrapper {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }

        th {
            background: #f8fafc;
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: .04em;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-green {
            background: #dcfce7;
            color: #166534;
        }

        .badge-red {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-blue {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-gray {
            background: #e2e8f0;
            color: #475569;
        }

        .error {
            color: #b91c1c;
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 12px;
            border-radius: 9px;
            margin-top: 14px;
            font-size: 13px;
        }

        .success {
            color: #166534;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 12px;
            border-radius: 9px;
            margin-top: 14px;
            font-size: 13px;
        }

        .loading {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-size: 13px;
        }

        .spinner {
            width: 15px;
            height: 15px;
            border: 2px solid #cbd5e1;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .dns-section {
            margin-top: 20px;
        }

        .dns-section h3 {
            margin: 0 0 10px;
            font-size: 15px;
        }

        .record-box {
            background: #0f172a;
            color: #e2e8f0;
            padding: 14px;
            border-radius: 9px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .table-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        @media (max-width: 900px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .summary-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .container {
                width: min(
                    100% - 24px,
                    1400px
                );
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .header-inner {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<header>
    <div class="container header-inner">
        <div>
            <div class="brand">
                Mailin<span> Task</span>
            </div>

            <div class="subtitle">
                Domain Intelligence & Email Infrastructure Checker
            </div>
        </div>
    </div>
</header>

<main>
    <div class="container">

        <div class="tabs">
            <button
                class="tab active"
                data-tab="dns"
                type="button"
            >
                Blacklist + DNS
            </button>

            <button
                class="tab"
                data-tab="provider"
                type="button"
            >
                Email Provider Detection
            </button>
        </div>

        <!-- SINGLE CHECK -->
        <section class="card">
            <h2 id="singleTitle">
                Blacklist + DNS Checker
            </h2>

            <p class="description" id="singleDescription">
                Check a domain or email address for DNS records,
                blacklist status, SPF, DKIM and DMARC.
            </p>

            <form id="singleForm">

                <div class="form-row">

                    <input
                        type="text"
                        id="domainInput"
                        name="input"
                        placeholder="example.com or user@example.com"
                        required
                    >

                    <input
                        type="text"
                        id="dkimSelector"
                        name="dkim_selector"
                        placeholder="DKIM selector (optional)"
                    >

                    <button
                        type="submit"
                        class="btn-primary"
                        id="singleButton"
                    >
                        Check Domain
                    </button>

                </div>

                <div
                    class="help"
                    id="dkimHelp"
                >
                    Enter a DKIM selector such as
                    <strong>selector1</strong> or
                    <strong>20230601</strong>
                    if you want to check DKIM.
                </div>

            </form>

            <div
                id="singleMessage"
                class="hidden"
            ></div>

            <div
                id="singleLoading"
                class="loading hidden"
                style="margin-top: 18px;"
            >
                <span class="spinner"></span>
                <span id="singleLoadingText">
                    Processing...
                </span>
            </div>

            <div
                id="singleResult"
                class="single-result hidden"
            ></div>
        </section>

        <!-- BULK CHECK -->
        <section class="card">
            <h2>
                Bulk Domain Check
            </h2>

            <p class="description">
                Upload a CSV or TXT file. Each domain is processed
                independently and results appear as soon as they complete.
            </p>

            <form id="bulkForm">

                <div class="form-row">

                    <input
                        type="file"
                        id="bulkFile"
                        name="file"
                        accept=".csv,.txt"
                        required
                    >

                    <div></div>

                    <button
                        type="submit"
                        class="btn-primary"
                        id="bulkButton"
                    >
                        Start Bulk Check
                    </button>

                </div>

                <div class="help">
                    CSV example:
                    <strong>domain,gmail.com</strong>
                    is not required.
                    A simple first-column format is supported:
                    domain, gmail.com, microsoft.com.
                </div>

            </form>

            <div
                id="bulkMessage"
                class="hidden"
            ></div>
        </section>

        <!-- BATCH PROGRESS -->
        <section
            class="card hidden"
            id="batchCard"
        >

            <div class="table-actions">

                <div>
                    <h2>
                        Bulk Processing
                    </h2>

                    <div
                        class="description"
                        id="batchFilename"
                        style="margin-bottom: 0;"
                    ></div>
                </div>

                <button
                    type="button"
                    class="btn-success"
                    id="exportButton"
                    disabled
                >
                    Export CSV
                </button>

            </div>

            <div class="progress-wrapper">

                <div class="progress-header">
                    <span id="progressText">
                        0 / 0
                    </span>

                    <strong id="progressPercent">
                        0%
                    </strong>
                </div>

                <div class="progress">
                    <div
                        class="progress-bar"
                        id="progressBar"
                    ></div>
                </div>

            </div>

            <div
                class="batch-stats"
                id="batchStats"
            ></div>

        </section>

        <!-- RESULTS -->
        <section
            class="card hidden"
            id="resultsCard"
        >

            <div class="table-actions">

                <div>
                    <h2>
                        Results
                    </h2>

                    <div
                        class="description"
                        id="resultCount"
                        style="margin-bottom: 0;"
                    ></div>
                </div>

            </div>

            <div class="filters">

                <button
                    class="filter active"
                    data-filter="all"
                    type="button"
                >
                    All
                </button>

                <button
                    class="filter"
                    data-filter="clean"
                    type="button"
                >
                    Clean
                </button>

                <button
                    class="filter"
                    data-filter="blacklisted"
                    type="button"
                >
                    Blacklisted
                </button>

                <button
                    class="filter"
                    data-filter="google"
                    type="button"
                >
                    Google
                </button>

                <button
                    class="filter"
                    data-filter="microsoft"
                    type="button"
                >
                    Microsoft
                </button>

                <button
                    class="filter"
                    data-filter="other"
                    type="button"
                >
                    Other
                </button>

                <button
                    class="filter"
                    data-filter="failed"
                    type="button"
                >
                    Failed
                </button>

            </div>

            <div class="table-wrapper">

                <table>
                    <thead>
                    <tr>
                        <th>Input</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Blacklist</th>
                        <th>Provider</th>
                        <th>Details</th>
                    </tr>
                    </thead>

                    <tbody id="resultsBody">
                    </tbody>
                </table>

            </div>

        </section>

    </div>
</main>

<script>
    const state = {
        mode: 'dns',
        batchId: null,
        batchTimer: null,
        resultsTimer: null,
        results: [],
        activeFilter: 'all'
    };

    const $ = (selector) =>
        document.querySelector(selector);

    const $$ = (selector) =>
        document.querySelectorAll(selector);

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    async function api(url, options = {}) {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                ...(options.body instanceof FormData
                    ? {}
                    : {
                        'Content-Type':
                            'application/json'
                    })
            },
            ...options
        });

        let data = {};

        try {
            data = await response.json();
        } catch (error) {
            data = {};
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                'Request failed.'
            );
        }

        return data;
    }

    function showMessage(
        element,
        message,
        type = 'error'
    ) {
        element.className =
            type === 'success'
                ? 'success'
                : 'error';

        element.textContent = message;
    }

    function hideMessage(element) {
        element.className = 'hidden';
        element.textContent = '';
    }

    function badge(
        text,
        type = 'gray'
    ) {
        return `
            <span class="badge badge-${type}">
                ${escapeHtml(text)}
            </span>
        `;
    }

    function blacklistBadge(status) {
        if (status === 'clean') {
            return badge(
                'Clean',
                'green'
            );
        }

        if (
            status === 'listed' ||
            status === 'blacklisted'
        ) {
            return badge(
                'Blacklisted',
                'red'
            );
        }

        return badge(
            status || 'Unknown',
            'gray'
        );
    }

    function statusBadge(status) {
        if (status === 'completed') {
            return badge(
                'Completed',
                'green'
            );
        }

        if (status === 'failed') {
            return badge(
                'Failed',
                'red'
            );
        }

        if (status === 'checking') {
            return badge(
                'Checking',
                'blue'
            );
        }

        return badge(
            'Queued',
            'gray'
        );
    }

    function providerBadge(provider) {
        if (provider === 'Google Workspace') {
            return badge(
                'Google Workspace',
                'blue'
            );
        }

        if (provider === 'Microsoft 365') {
            return badge(
                'Microsoft 365',
                'blue'
            );
        }

        if (provider === 'Other') {
            return badge(
                'Other',
                'gray'
            );
        }

        return badge(
            'Not Detected',
            'gray'
        );
    }

    function renderSingleResult(result) {
        const container = $('#singleResult');

        const dns =
            result.dns_records || {};

        const blacklists =
            result.blacklists || [];

        const blacklistText =
            blacklists.length > 0
                ? blacklists.join(', ')
                : 'None detected';

        const provider =
            result.provider ||
            'Not Detected';

        container.innerHTML = `
            <div class="summary-grid">

                <div class="summary-item">
                    <div class="summary-label">
                        Domain
                    </div>

                    <div class="summary-value">
                        ${escapeHtml(result.domain)}
                    </div>
                </div>

                <div class="summary-item">
                    <div class="summary-label">
                        Status
                    </div>

                    <div class="summary-value">
                        ${statusBadge(result.status)}
                    </div>
                </div>

                <div class="summary-item">
                    <div class="summary-label">
                        Blacklist
                    </div>

                    <div class="summary-value">
                        ${blacklistBadge(
                            result.blacklist_status
                        )}
                    </div>
                </div>

                <div class="summary-item">
                    <div class="summary-label">
                        Provider
                    </div>

                    <div class="summary-value">
                        ${providerBadge(provider)}
                    </div>
                </div>

            </div>

            <div class="dns-section">
                <h3>Blacklist Detection</h3>

                <div class="record-box">
                    ${escapeHtml(blacklistText)}
                </div>
            </div>

            <div class="dns-section">
                <h3>Provider Evidence</h3>

                <div class="record-box">
                    ${escapeHtml(
                        result.detection_evidence ||
                        'No provider-specific evidence detected.'
                    )}
                </div>
            </div>

            <div class="dns-section">
                <h3>DNS Records</h3>

                <div class="record-box">
${escapeHtml(
    JSON.stringify(
        dns,
        null,
        2
    )
)}
                </div>
            </div>

            ${
                result.error
                    ? `
                    <div class="error">
                        ${escapeHtml(result.error)}
                    </div>
                    `
                    : ''
            }
        `;

        container.classList.remove(
            'hidden'
        );
    }

    async function pollSingleCheck(id) {
        $('#singleLoading')
            .classList
            .remove('hidden');

        while (true) {
            try {
                const result = await api(
                    `/api/checks/${id}`
                );

                if (
                    result.status === 'completed' ||
                    result.status === 'failed'
                ) {
                    $('#singleLoading')
                        .classList
                        .add('hidden');

                    renderSingleResult(
                        result
                    );

                    $('#singleButton')
                        .disabled = false;

                    return;
                }

                $('#singleLoadingText')
                    .textContent =
                    result.status === 'checking'
                        ? 'Checking DNS and blacklists...'
                        : 'Queued...';

            } catch (error) {
                $('#singleLoading')
                    .classList
                    .add('hidden');

                $('#singleButton')
                    .disabled = false;

                showMessage(
                    $('#singleMessage'),
                    error.message
                );

                return;
            }

            await sleep(1000);
        }
    }

    $('#singleForm')
        .addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                hideMessage(
                    $('#singleMessage')
                );

                $('#singleResult')
                    .classList
                    .add('hidden');

                const input =
                    $('#domainInput')
                        .value
                        .trim();

                const selector =
                    $('#dkimSelector')
                        .value
                        .trim();

                if (!input) {
                    showMessage(
                        $('#singleMessage'),
                        'Enter a domain or email address.'
                    );

                    return;
                }

                $('#singleButton')
                    .disabled = true;

                try {
                    const payload = {
                        input
                    };

                    if (
                        state.mode === 'dns' &&
                        selector
                    ) {
                        payload.dkim_selector =
                            selector;
                    }

                    const response = await api(
                        '/api/check',
                        {
                            method: 'POST',
                            body: JSON.stringify(
                                payload
                            )
                        }
                    );

                    await pollSingleCheck(
                        response.id
                    );

                } catch (error) {
                    $('#singleButton')
                        .disabled = false;

                    showMessage(
                        $('#singleMessage'),
                        error.message
                    );
                }
            }
        );

    $('#bulkForm')
        .addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                hideMessage(
                    $('#bulkMessage')
                );

                const file =
                    $('#bulkFile')
                        .files[0];

                if (!file) {
                    showMessage(
                        $('#bulkMessage'),
                        'Please select a CSV or TXT file.'
                    );

                    return;
                }

                $('#bulkButton')
                    .disabled = true;

                const formData =
                    new FormData();

                formData.append(
                    'file',
                    file
                );

                try {
                    const response =
                        await api(
                            '/api/bulk-check',
                            {
                                method: 'POST',
                                body: formData
                            }
                        );

                    state.batchId =
                        response.batch_id;

                    $('#batchCard')
                        .classList
                        .remove('hidden');

                    $('#resultsCard')
                        .classList
                        .remove('hidden');

                    $('#batchFilename')
                        .textContent =
                        `${response.filename || file.name} — Batch #${response.batch_id}`;

                    $('#exportButton')
                        .disabled = true;

                    state.results = [];

                    renderResults();

                    startBatchPolling();

                    showMessage(
                        $('#bulkMessage'),
                        `Batch #${response.batch_id} started.`,
                        'success'
                    );

                } catch (error) {
                    showMessage(
                        $('#bulkMessage'),
                        error.message
                    );
                } finally {
                    $('#bulkButton')
                        .disabled = false;
                }
            }
        );

    function startBatchPolling() {
        if (state.batchTimer) {
            clearInterval(
                state.batchTimer
            );
        }

        if (state.resultsTimer) {
            clearInterval(
                state.resultsTimer
            );
        }

        pollBatch();

        state.batchTimer =
            setInterval(
                pollBatch,
                1500
            );

        state.resultsTimer =
            setInterval(
                pollResults,
                1500
            );
    }

    async function pollBatch() {
        if (!state.batchId) {
            return;
        }

        try {
            const batch =
                await api(
                    `/api/batches/${state.batchId}`
                );

            const percent =
                Number(batch.progress || 0);

            $('#progressBar')
                .style
                .width =
                `${percent}%`;

            $('#progressText')
                .textContent =
                `${batch.processed} / ${batch.total}`;

            $('#progressPercent')
                .textContent =
                `${percent}%`;

            $('#batchStats')
                .innerHTML = `
                    <div class="stat">
                        Queued:
                        <strong>
                            ${batch.queued}
                        </strong>
                    </div>

                    <div class="stat">
                        Checking:
                        <strong>
                            ${batch.checking}
                        </strong>
                    </div>

                    <div class="stat">
                        Completed:
                        <strong>
                            ${batch.completed}
                        </strong>
                    </div>

                    <div class="stat">
                        Failed:
                        <strong>
                            ${batch.failed}
                        </strong>
                    </div>
                `;

            if (
                batch.status === 'completed' ||
                batch.status === 'completed_with_errors'
            ) {
                $('#exportButton')
                    .disabled = false;

                if (state.batchTimer) {
                    clearInterval(
                        state.batchTimer
                    );

                    state.batchTimer = null;
                }

                await pollResults();

                if (state.resultsTimer) {
                    clearInterval(
                        state.resultsTimer
                    );

                    state.resultsTimer = null;
                }
            }

        } catch (error) {
            console.error(
                'Batch polling error:',
                error
            );
        }
    }

    async function pollResults() {
        if (!state.batchId) {
            return;
        }

        try {
            const response =
                await api(
                    `/api/batches/${state.batchId}/results`
                );

            state.results =
                response.results || [];

            renderResults();

        } catch (error) {
            console.error(
                'Results polling error:',
                error
            );
        }
    }

    function matchesFilter(result) {
        const filter =
            state.activeFilter;

        if (filter === 'all') {
            return true;
        }

        if (filter === 'clean') {
            return result.blacklist_status ===
                'clean';
        }

        if (filter === 'blacklisted') {
            return (
                result.blacklist_status ===
                    'listed' ||
                result.blacklist_status ===
                    'blacklisted'
            );
        }

        if (filter === 'google') {
            return result.provider ===
                'Google Workspace';
        }

        if (filter === 'microsoft') {
            return result.provider ===
                'Microsoft 365';
        }

        if (filter === 'other') {
            return result.provider ===
                'Other';
        }

        if (filter === 'failed') {
            return result.status ===
                'failed';
        }

        return true;
    }

    function renderResults() {
        const body =
            $('#resultsBody');

        const filtered =
            state.results.filter(
                matchesFilter
            );

        $('#resultCount')
            .textContent =
            `${filtered.length} of ${state.results.length} results`;

        if (filtered.length === 0) {
            body.innerHTML = `
                <tr>
                    <td
                        colspan="6"
                        style="
                            text-align:center;
                            padding:30px;
                            color:#64748b;
                        "
                    >
                        No results match this filter.
                    </td>
                </tr>
            `;

            return;
        }

        body.innerHTML =
            filtered
                .map(result => `
                    <tr>
                        <td>
                            ${escapeHtml(
                                result.input
                            )}
                        </td>

                        <td>
                            <strong>
                                ${escapeHtml(
                                    result.domain
                                )}
                            </strong>
                        </td>

                        <td>
                            ${statusBadge(
                                result.status
                            )}
                        </td>

                        <td>
                            ${blacklistBadge(
                                result.blacklist_status
                            )}
                        </td>

                        <td>
                            ${providerBadge(
                                result.provider
                            )}
                        </td>

                        <td>
                            ${
                                result.error
                                    ? `
                                    <span
                                        style="
                                            color:#b91c1c;
                                        "
                                    >
                                        ${escapeHtml(
                                            result.error
                                        )}
                                    </span>
                                    `
                                    : `
                                    <details>
                                        <summary
                                            style="
                                                cursor:pointer;
                                                color:#2563eb;
                                            "
                                        >
                                            View
                                        </summary>

                                        <pre
                                            style="
                                                max-width:500px;
                                                white-space:pre-wrap;
                                                font-size:11px;
                                                margin-top:8px;
                                            "
                                        >${escapeHtml(
                                            JSON.stringify(
                                                {
                                                    blacklist:
                                                        result.blacklists,
                                                    provider:
                                                        result.detection_evidence,
                                                    dns:
                                                        result.dns_records
                                                },
                                                null,
                                                2
                                            )
                                        )}</pre>
                                    </details>
                                    `
                            }
                        </td>
                    </tr>
                `)
                .join('');
    }

    $$('.filter')
        .forEach(button => {
            button.addEventListener(
                'click',
                () => {
                    $$('.filter')
                        .forEach(
                            item =>
                                item.classList
                                    .remove('active')
                        );

                    button.classList
                        .add('active');

                    state.activeFilter =
                        button.dataset.filter;

                    renderResults();
                }
            );
        });

    $('#exportButton')
        .addEventListener(
            'click',
            () => {
                if (!state.batchId) {
                    return;
                }

                window.location.href =
                    `/api/batches/${state.batchId}/export`;
            }
        );

    $$('.tab')
        .forEach(tab => {
            tab.addEventListener(
                'click',
                () => {
                    $$('.tab')
                        .forEach(
                            item =>
                                item.classList
                                    .remove('active')
                        );

                    tab.classList
                        .add('active');

                    state.mode =
                        tab.dataset.tab;

                    if (state.mode === 'provider') {
                        $('#singleTitle')
                            .textContent =
                            'Email Provider Detection';

                        $('#singleDescription')
                            .textContent =
                            'Detect whether the domain uses Google Workspace, Microsoft 365, or another email provider using public DNS indicators.';

                        $('#dkimSelector')
                            .classList
                            .add('hidden');

                        $('#dkimHelp')
                            .classList
                            .add('hidden');

                        $('#singleButton')
                            .textContent =
                            'Detect Provider';

                    } else {
                        $('#singleTitle')
                            .textContent =
                            'Blacklist + DNS Checker';

                        $('#singleDescription')
                            .textContent =
                            'Check a domain or email address for DNS records, blacklist status, SPF, DKIM and DMARC.';

                        $('#dkimSelector')
                            .classList
                            .remove('hidden');

                        $('#dkimHelp')
                            .classList
                            .remove('hidden');

                        $('#singleButton')
                            .textContent =
                            'Check Domain';
                    }
                }
            );
        });

    function sleep(milliseconds) {
        return new Promise(
            resolve =>
                setTimeout(
                    resolve,
                    milliseconds
                )
        );
    }
</script>

</body>
</html>