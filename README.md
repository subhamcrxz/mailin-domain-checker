# Mailin Domain Checker

A Laravel-based domain intelligence and email infrastructure checker built as a technical assessment for Mailin.

The application provides:

- DNS record inspection
- DNSBL/RBL blacklist checking
- SPF detection
- DKIM lookup with selector support
- DMARC detection
- A / AAAA / CNAME / NS / PTR records
- Google Workspace detection
- Microsoft 365 detection
- Bulk CSV/TXT processing
- Background queue processing
- Progressive result updates
- Batch progress tracking
- Result filtering
- CSV export
- Retry and backoff handling
- Queue-level rate limiting
- Error handling and per-domain failure tracking

---

## Assessment Requirements Covered

The implementation is designed around the following requirements:

1. Blacklist + DNS Checker
   - Single domain/email input
   - Domain extraction from email addresses
   - DNSBL/RBL blacklist checks
   - MX, SPF/TXT, DKIM, DMARC, A, AAAA, CNAME, NS and PTR checks
   - Detection of blacklist sources
   - DNS error and failure handling

2. Google Workspace / Microsoft 365 Detection
   - Detect Google Workspace
   - Detect Microsoft 365
   - Detect other providers
   - Return DNS/MX evidence for the detection

3. Bulk Processing
   - CSV and TXT uploads
   - Background processing through Redis queues
   - Independent processing for each domain
   - Results displayed as individual checks complete
   - Progress tracking
   - Queued / Checking / Completed / Failed states
   - Result filtering
   - CSV export

4. Reliability
   - Queue timeout
   - Retry attempts
   - Retry backoff
   - Queue-level rate limiting
   - Per-domain failure handling
   - Large-batch processing without running the entire batch synchronously

---

# Architecture

```text
                    ┌──────────────────────────┐
                    │       Laravel UI         │
                    │    Blade + JavaScript    │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │        REST API           │
                    │ DomainCheckController     │
                    └────────────┬─────────────┘
                                 │
                  ┌──────────────┴──────────────┐
                  │                             │
                  ▼                             ▼
        ┌──────────────────┐          ┌──────────────────┐
        │   CheckBatch     │          │   DomainCheck    │
        │   Batch state    │          │   Individual     │
        │   Progress       │          │   results        │
        └────────┬─────────┘          └────────┬─────────┘
                 │                             │
                 └──────────────┬──────────────┘
                                │
                                ▼
                    ┌──────────────────────────┐
                    │          Redis           │
                    │     domain-checks        │
                    │          queue            │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │    ProcessDomainCheck    │
                    │                          │
                    │ Rate limiting             │
                    │ Timeout                   │
                    │ Retry / Backoff           │
                    └────────────┬─────────────┘
                                 │
             ┌───────────────────┼───────────────────┐
             ▼                   ▼                   ▼
      ┌──────────────┐   ┌────────────────┐   ┌─────────────────┐
      │ DNS Checker  │   │ Blacklist      │   │ Mail Provider   │
      │              │   │ Checker        │   │ Detector        │
      └──────┬───────┘   └───────┬────────┘   └────────┬────────┘
             │                   │                     │
             └───────────────────┼─────────────────────┘
                                 ▼
                    ┌──────────────────────────┐
                    │       Database           │
                    │   Persistent results     │
                    └──────────────────────────┘
```

---

# Technology Stack

## Backend

- PHP
- Laravel
- Redis
- SQLite for local development
- MySQL/PostgreSQL compatible database design

## Frontend

- Laravel Blade
- Vanilla JavaScript
- HTML5
- CSS3
- Fetch API

## Infrastructure / Tools

- Composer
- Redis
- Git
- Artisan Queue Worker

---

# Requirements

The application requires:

- PHP 8.2+
- Composer
- Redis
- Laravel
- A supported database

Node.js/npm are only required if the project is extended to use a compiled frontend asset pipeline.

The development environment used for this implementation includes:

- PHP 8.5
- Redis
- SQLite
- Laravel
- Node.js/npm
- Git

---

# Installation

## 1. Clone the repository

```bash
git clone <repository-url>
cd mailin-domain-checker
```

## 2. Install PHP dependencies

```bash
composer install
```

## 3. Create the environment file

```bash
cp .env.example .env
```

## 4. Generate the application key

```bash
php artisan key:generate
```

## 5. Configure the database

For local development, SQLite can be used.

Create the SQLite database:

```bash
touch database/database.sqlite
```

Then configure:

```env
DB_CONNECTION=sqlite
```

Run migrations:

```bash
php artisan migrate
```

---

# Redis Configuration

The application uses Redis for background queue processing.

Example `.env` configuration:

```env
QUEUE_CONNECTION=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Verify Redis:

```bash
redis-cli ping
```

Expected:

```text
PONG
```

---

# Queue Configuration

Domain checks are processed using a dedicated Redis queue:

```text
domain-checks
```

The `ProcessDomainCheck` job is explicitly assigned to this queue.

Start the worker:

```bash
php artisan queue:work redis --queue=domain-checks --verbose
```

The job configuration includes:

```text
Attempts: 3
Timeout: 30 seconds
Backoff: 2 seconds, then 5 seconds
```

This provides retry handling for transient failures.

---

# Rate Limiting

Domain-check jobs use Laravel's queue rate limiter.

The rate limit is configurable through `.env`:

```env
DOMAIN_CHECK_RATE_LIMIT=60
```

Default:

```text
60 jobs per minute
```

The purpose of the limit is to prevent a large bulk upload from generating excessive external DNS and blacklist traffic.

The rate can be adjusted depending on the production DNS provider, blacklist services and infrastructure capacity.

---

# Running the Application

## Start Redis

Make sure Redis is running.

Verify:

```bash
redis-cli ping
```

Expected:

```text
PONG
```

## Start Laravel

```bash
php artisan serve
```

The application will normally be available at:

```text
http://127.0.0.1:8000
```

## Start the queue worker

Open another terminal:

```bash
php artisan queue:work redis --queue=domain-checks --verbose
```

## Open the application

```text
http://127.0.0.1:8000/domain-checker
```

---

# Application Features

## 1. Blacklist + DNS Checker

The first interface allows a user to enter:

- A domain
- An email address
- An optional DKIM selector

Examples:

```text
gmail.com
```

or:

```text
user@gmail.com
```

If an email address is supplied, the application extracts the domain automatically.

For example:

```text
user@gmail.com
```

becomes:

```text
gmail.com
```

The result includes:

- Domain
- Processing status
- Blacklist status
- Detected blacklist sources
- Provider
- Provider evidence
- DNS records

---

# DNS Records

The DNS checker supports:

```text
A
AAAA
MX
TXT
SPF
DKIM
DMARC
CNAME
NS
PTR
ALL
```

The application stores the DNS response in the `dns_records` field for the individual check.

---

# SPF Detection

SPF is detected from TXT records.

Example:

```text
v=spf1 ...
```

SPF records are returned separately in the result.

---

# DKIM Detection

DKIM requires a selector.

Example:

```text
Domain:
gmail.com

Selector:
20230601
```

The application performs the equivalent lookup:

```text
20230601._domainkey.gmail.com
```

The selector can be provided through the single-check interface.

CSV uploads can also optionally provide a second column for the DKIM selector:

```csv
domain,dkim_selector
gmail.com,20230601
microsoft.com,
example.com,
```

A simple one-column CSV remains supported:

```csv
domain
gmail.com
microsoft.com
yahoo.com
example.com
```

---

# DMARC Detection

DMARC records are queried using:

```text
_dmarc.<domain>
```

For example:

```text
_dmarc.gmail.com
```

TXT records are returned as the DMARC result.

---

# PTR Detection

PTR information is checked for discovered IPv4 A records.

For each detected IP address, the application attempts reverse DNS resolution.

---

# Blacklist Detection

The blacklist checker performs DNSBL/RBL checks.

The result can be:

```text
Clean
Listed
```

If a domain/IP is listed, the response includes the detected blacklist source(s).

The UI displays:

```text
Clean
```

or:

```text
Blacklisted
```

along with the detected blacklist information.

---

# Email Provider Detection

The provider detector uses public DNS/MX information to identify common email infrastructure.

Supported classifications:

```text
Google Workspace
Microsoft 365
Other
Not Detected
```

Examples:

```text
gmail.com
→ Google Workspace
```

```text
microsoft.com
→ Microsoft 365
```

The application also returns evidence explaining the detection.

For example:

```text
MX record alt3.gmail-smtp-in.l.google.com matches Google mail infrastructure.
```

or:

```text
MX record microsoft-com.mail.protection.outlook.com matches Microsoft 365 mail infrastructure.
```

---

# Single Domain Processing

The single-domain API immediately creates a queued check.

Endpoint:

```http
POST /api/check
```

Request:

```json
{
    "input": "user@gmail.com",
    "dkim_selector": "20230601"
}
```

Response:

```json
{
    "message": "Domain check queued.",
    "id": 1,
    "status": "queued",
    "domain": "gmail.com"
}
```

The frontend then polls the check endpoint until processing completes.

---

# Single Check Result

Endpoint:

```http
GET /api/checks/{id}
```

Possible statuses:

```text
queued
checking
completed
failed
```

Example completed response:

```json
{
    "id": 1,
    "input": "gmail.com",
    "domain": "gmail.com",
    "status": "completed",
    "blacklist_status": "clean",
    "blacklists": [],
    "provider": "Google Workspace",
    "detection_evidence": "Google MX infrastructure detected.",
    "error": null
}
```

---

# Bulk Processing

Bulk processing supports:

```text
CSV
TXT
```

The maximum upload size is controlled by the Laravel request validation.

The application creates:

1. A `CheckBatch`
2. Individual `DomainCheck` records
3. A queue job for every domain

Example:

```text
50 domains
    ↓
1 batch
    ↓
50 DomainCheck records
    ↓
50 queue jobs
```

Each domain is processed independently.

---

# Progressive Processing

The system does not wait for the entire batch before displaying results.

For example, a batch can show:

```text
Total:      50
Queued:     26
Checking:    1
Completed:  23
Failed:      0
Progress:   46%
```

Individual rows transition through:

```text
Queued
   ↓
Checking
   ↓
Completed
```

or:

```text
Queued
   ↓
Checking
   ↓
Failed
```

This allows the UI to display completed results while the remaining domains are still being processed.

---

# Batch Progress API

Endpoint:

```http
GET /api/batches/{batch}
```

Example:

```json
{
    "id": 3,
    "type": "bulk",
    "filename": "domains.csv",
    "status": "processing",
    "total": 50,
    "queued": 26,
    "checking": 1,
    "completed": 23,
    "failed": 0,
    "processed": 23,
    "progress": 46
}
```

When every domain has either completed or permanently failed, the batch becomes:

```text
completed
```

or:

```text
completed_with_errors
```

---

# Batch Results API

Endpoint:

```http
GET /api/batches/{batch}/results
```

The endpoint returns individual results for the batch.

Optional filters are supported.

## Status

```text
?status=completed
```

```text
?status=failed
```

## Blacklist

```text
?blacklist=clean
```

```text
?blacklist=listed
```

## Provider

```text
?provider=Google%20Workspace
```

```text
?provider=Microsoft%20365
```

```text
?provider=Other
```

The frontend also provides convenient filtering buttons:

```text
All
Clean
Blacklisted
Google
Microsoft
Other
Failed
```

---

# CSV Export

Endpoint:

```http
GET /api/batches/{batch}/export
```

The export is streamed directly to the browser.

The CSV contains:

```text
ID
Input
Domain
Status
Blacklist Status
Blacklists
Provider
Detection Evidence
MX
SPF
DMARC
DKIM
A
AAAA
CNAME
NS
PTR
Error
Started At
Completed At
```

The export processes database records in chunks rather than loading the complete result set into memory at once.

---

# Error Handling

Each domain has its own error field.

A failure in one domain does not stop the rest of the batch.

Example:

```text
Domain A → Completed
Domain B → Completed
Domain C → Failed
Domain D → Completed
```

The batch continues processing.

The failed result contains an error message that can be displayed in the UI and included in the CSV export.

---

# Retry Strategy

`ProcessDomainCheck` is configured with:

```text
tries = 3
timeout = 30 seconds
```

Backoff:

```text
Attempt 1 failure
      ↓
2 second delay
      ↓
Attempt 2
      ↓
5 second delay
      ↓
Attempt 3
```

After the final failure, the job is marked as permanently failed and the associated `DomainCheck` is updated accordingly.

---

# Queue and Concurrency Design

The system uses Redis queues to prevent bulk requests from running synchronously inside the HTTP request.

Architecture:

```text
HTTP Request
     ↓
Create Batch
     ↓
Create Domain Checks
     ↓
Dispatch Jobs
     ↓
Redis
     ↓
Queue Worker(s)
     ↓
Process Domains
```

Multiple workers can be used in production to increase throughput while maintaining the configured rate limit.

Example:

```bash
php artisan queue:work redis --queue=domain-checks --verbose
```

For production, multiple workers can be managed using Laravel Horizon, Supervisor or another process manager.

---

# API Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/check` | Queue a single domain/email check |
| GET | `/api/checks/{id}` | Get a single check result |
| POST | `/api/bulk-check` | Upload and queue CSV/TXT batch |
| GET | `/api/batches/{id}` | Get batch progress |
| GET | `/api/batches/{id}/results` | Get/filter batch results |
| GET | `/api/batches/{id}/export` | Export batch results as CSV |

---

# Project Structure

```text
app/
├── Exceptions/
│   └── DnsLookupException.php
│
├── Http/
│   └── Controllers/
│       └── Api/
│           └── DomainCheckController.php
│
├── Jobs/
│   └── ProcessDomainCheck.php
│
├── Models/
│   ├── CheckBatch.php
│   └── DomainCheck.php
│
├── Providers/
│   └── AppServiceProvider.php
│
└── Services/
    ├── BlacklistCheckerService.php
    ├── DnsCheckerService.php
    ├── DomainCheckService.php
    └── MailProviderDetector.php

config/
└── domain-check.php

database/
├── database.sqlite
└── migrations/

resources/
└── views/
    └── domain-checker.blade.php

routes/
├── api.php
└── web.php

tests/
└── Feature/
    └── DomainCheckApiTest.php
```

---

# Database Design

## check_batches

Stores the state of every bulk upload.

Important fields:

```text
id
type
filename
total
queued
checking
completed
failed
status
created_at
updated_at
```

## domain_checks

Stores every individual domain check.

Important fields:

```text
id
batch_id
input
domain
dkim_selector
status
blacklist_status
blacklists
dns_records
provider
detection_evidence
error
started_at
completed_at
created_at
updated_at
```

The `batch_id` creates the relationship between an individual domain check and its bulk batch.

---

# Testing

Run the complete test suite:

```bash
php artisan test
```

The feature tests cover:

- Single domain queueing
- Email-to-domain extraction
- Invalid input handling
- Bulk CSV processing
- Batch creation
- Queue dispatching
- Batch progress
- Result filtering
- CSV export

Additional manual tests can be performed through:

```text
http://127.0.0.1:8000/domain-checker
```

---

# Manual Demo Scenarios

## Google Workspace

Input:

```text
gmail.com
```

Expected:

```text
Provider: Google Workspace
```

## Microsoft 365

Input:

```text
microsoft.com
```

Expected:

```text
Provider: Microsoft 365
```

## Email Input

Input:

```text
someone@gmail.com
```

Expected domain:

```text
gmail.com
```

## DKIM

Domain:

```text
gmail.com
```

Selector:

```text
20230601
```

Expected lookup:

```text
20230601._domainkey.gmail.com
```

## Bulk

Example:

```csv
domain
gmail.com
microsoft.com
yahoo.com
outlook.com
example.com
openai.com
google.com
apple.com
```

The UI should show the progress changing while jobs are still running.

---

# Production Considerations

The current implementation is designed to be clear and reliable for the assessment and local development.

For a production deployment, additional infrastructure can be introduced:

- Laravel Horizon for Redis queue monitoring
- Supervisor/systemd for persistent queue workers
- PostgreSQL/MySQL for production persistence
- Authentication and authorization
- API authentication
- API-level rate limiting
- WebSocket/SSE updates instead of polling
- Dedicated DNS resolver infrastructure
- Per-DNS-query timeout configuration
- Structured application logging
- Monitoring and alerting
- Queue metrics
- Distributed workers
- Pagination/incremental result synchronization for very large batches
- Object storage for very large input files
- Database indexes optimized for large batch queries

---

# Security Considerations

The application validates:

- Uploaded file type
- Uploaded file size
- Input length
- Domain normalization
- Bulk row count

Bulk processing is performed asynchronously to avoid holding HTTP requests open for large jobs.

For production, authentication and authorization should be added to the API endpoints.

---

# Performance Considerations

The application separates:

```text
HTTP request processing
```

from:

```text
DNS/blacklist processing
```

using Redis queues.

This prevents large batches from blocking the web process.

Batch export uses database chunking to avoid loading all records into memory simultaneously.

The rate limiter helps control external DNS/blacklist traffic.

---

# Example Bulk Processing Flow

```text
User uploads 1,000 domains
              │
              ▼
       Validate upload
              │
              ▼
       Create batch
              │
              ▼
    Create 1,000 checks
              │
              ▼
       Dispatch jobs
              │
              ▼
       Redis queue
              │
       ┌──────┴──────┐
       ▼             ▼
    Worker 1      Worker 2
       │             │
       └──────┬──────┘
              ▼
       Rate limiter
              │
              ▼
       Domain checks
              │
       ┌──────┼───────────┐
       ▼      ▼           ▼
      DNS  Blacklists  Provider
       │      │           │
       └──────┼───────────┘
              ▼
        Save result
              │
              ▼
       Frontend polling
              │
              ▼
       Live result rows
```

---

# Development Commands

Clear Laravel caches:

```bash
php artisan optimize:clear
```

Run migrations:

```bash
php artisan migrate
```

Start Laravel:

```bash
php artisan serve
```

Start queue:

```bash
php artisan queue:work redis --queue=domain-checks --verbose
```

Run tests:

```bash
php artisan test
```

View routes:

```bash
php artisan route:list --path=api
```

Check Laravel environment:

```bash
php artisan about
```

---

# Final Verification Checklist

Before submission:

```text
[ ] Laravel application starts
[ ] Redis responds with PONG
[ ] Database migrations complete
[ ] Queue worker starts
[ ] Dedicated domain-checks queue is used
[ ] Single domain check works
[ ] Email input extracts domain
[ ] DNS records are returned
[ ] SPF is detected
[ ] DMARC is detected
[ ] DKIM selector lookup works
[ ] Blacklist check works
[ ] Google Workspace detection works
[ ] Microsoft 365 detection works
[ ] Bulk CSV upload works
[ ] Bulk TXT upload works
[ ] Results appear progressively
[ ] Queue / checking / completed / failed states work
[ ] Batch progress works
[ ] Filters work
[ ] CSV export works
[ ] Retry/backoff is configured
[ ] Rate limiting is configured
[ ] Automated tests pass
[ ] README is included
[ ] Git working tree is clean
```

---

# Submission

Recommended final commands:

```bash
php artisan optimize:clear
php artisan test
git status
```

If all tests pass and the working tree contains the expected changes:

```bash
git add .
git commit -m "Complete Mailin.ai domain checker assessment"
git push
```

---

# Author

Built as a technical assessment implementation for Mailin.ai.
