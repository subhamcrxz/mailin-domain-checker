<?php

namespace Tests\Feature;

use App\Jobs\ProcessDomainCheck;
use App\Models\CheckBatch;
use App\Models\DomainCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DomainCheckApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_domain_check_is_queued(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/check', [
            'input' => 'user@gmail.com',
            'dkim_selector' => '20230601',
        ]);

        $response
            ->assertStatus(202)
            ->assertJson([
                'message' => 'Domain check queued.',
                'status' => 'queued',
                'domain' => 'gmail.com',
            ]);

        $id = $response->json('id');

        $this->assertDatabaseHas('domain_checks', [
            'id' => $id,
            'input' => 'user@gmail.com',
            'domain' => 'gmail.com',
            'dkim_selector' => '20230601',
            'status' => 'queued',
        ]);

        Queue::assertPushed(
            ProcessDomainCheck::class,
            function ($job) use ($id): bool {
                return $job->domainCheckId === $id;
            }
        );
    }

    public function test_invalid_domain_is_rejected(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/check', [
            'input' => 'not-a-valid-domain',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'message' =>
                    'Unable to determine a valid domain.',
            ]);

        Queue::assertNothingPushed();
    }

    public function test_bulk_csv_creates_batch_and_queues_jobs(): void
    {
        Queue::fake();

        $csv = implode("\n", [
            'domain',
            'gmail.com',
            'microsoft.com',
            'yahoo.com',
            'example.com',
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'domains.csv',
            $csv
        );

        $response = $this->post(
            '/api/bulk-check',
            [
                'file' => $file,
            ]
        );

        $response
            ->assertStatus(202)
            ->assertJson([
                'message' =>
                    'Bulk domain check queued.',
                'total' => 4,
                'queued' => 4,
                'checking' => 0,
                'completed' => 0,
                'failed' => 0,
                'status' => 'queued',
            ]);

        $batchId =
            $response->json('batch_id');

        $this->assertDatabaseHas(
            'check_batches',
            [
                'id' => $batchId,
                'total' => 4,
                'queued' => 4,
                'completed' => 0,
                'failed' => 0,
            ]
        );

        $this->assertDatabaseCount(
            'domain_checks',
            4
        );

        Queue::assertPushed(
            ProcessDomainCheck::class,
            4
        );
    }

    public function test_batch_progress_is_returned(): void
    {
        $batch = CheckBatch::create([
            'type' => 'bulk',
            'filename' => 'test.csv',
            'total' => 3,
            'queued' => 1,
            'checking' => 1,
            'completed' => 1,
            'failed' => 0,
            'status' => 'processing',
        ]);

        $response = $this->getJson(
            "/api/batches/{$batch->id}"
        );

        $response
            ->assertOk()
            ->assertJson([
                'id' => $batch->id,
                'total' => 3,
                'queued' => 1,
                'checking' => 1,
                'completed' => 1,
                'failed' => 0,
                'processed' => 1,
                'progress' => 33.33,
            ]);
    }

    public function test_batch_results_can_be_filtered(): void
    {
        $batch = CheckBatch::create([
            'type' => 'bulk',
            'filename' => 'test.csv',
            'total' => 3,
            'queued' => 0,
            'checking' => 0,
            'completed' => 3,
            'failed' => 0,
            'status' => 'completed',
        ]);

        DomainCheck::create([
            'batch_id' => $batch->id,
            'input' => 'gmail.com',
            'domain' => 'gmail.com',
            'status' => 'completed',
            'blacklist_status' => 'clean',
            'provider' => 'Google Workspace',
            'blacklists' => [],
            'dns_records' => [],
        ]);

        DomainCheck::create([
            'batch_id' => $batch->id,
            'input' => 'microsoft.com',
            'domain' => 'microsoft.com',
            'status' => 'completed',
            'blacklist_status' => 'clean',
            'provider' => 'Microsoft 365',
            'blacklists' => [],
            'dns_records' => [],
        ]);

        DomainCheck::create([
            'batch_id' => $batch->id,
            'input' => 'example.com',
            'domain' => 'example.com',
            'status' => 'completed',
            'blacklist_status' => 'clean',
            'provider' => 'Other',
            'blacklists' => [],
            'dns_records' => [],
        ]);

        $response = $this->getJson(
            "/api/batches/{$batch->id}/results?provider=" .
            urlencode('Google Workspace')
        );

        $response
            ->assertOk()
            ->assertJson([
                'batch_id' => $batch->id,
                'count' => 1,
            ]);

        $this->assertSame(
            'gmail.com',
            $response->json(
                'results.0.domain'
            )
        );
    }

    public function test_batch_results_can_be_exported(): void
    {
        $batch = CheckBatch::create([
            'type' => 'bulk',
            'filename' => 'test.csv',
            'total' => 1,
            'queued' => 0,
            'checking' => 0,
            'completed' => 1,
            'failed' => 0,
            'status' => 'completed',
        ]);

        DomainCheck::create([
            'batch_id' => $batch->id,
            'input' => 'gmail.com',
            'domain' => 'gmail.com',
            'status' => 'completed',
            'blacklist_status' => 'clean',
            'blacklists' => [],
            'provider' => 'Google Workspace',
            'detection_evidence' =>
                'Google MX detected.',
            'dns_records' => [
                'mx' => [
                    [
                        'target' =>
                            'gmail-smtp-in.l.google.com',
                    ],
                ],
            ],
        ]);

        $response = $this->get(
            "/api/batches/{$batch->id}/export"
        );

        $response->assertOk();

        $this->assertStringContainsString(
            'Domain',
            $response->streamedContent()
        );

        $this->assertStringContainsString(
            'gmail.com',
            $response->streamedContent()
        );
    }
}
