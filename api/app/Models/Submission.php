<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property SubmissionStatus $status
 * @property int $attempt
 * @property string $idempotency_key
 * @property string $request_payload
 * @property string|null $response_payload
 * @property string|null $mydata_mark
 * @property list<string>|null $errors
 * @property Carbon|null $sent_at
 * @property Carbon|null $completed_at
 */
final class Submission extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'invoice_id',
        'idempotency_key',
        'status',
        'attempt',
        'request_payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'attempt' => 'integer',
            'errors' => 'array',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
