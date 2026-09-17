<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CancelInvoice;
use App\Actions\CreateCreditNote;
use App\Actions\CreateInvoice;
use App\Actions\IssueInvoice;
use App\Actions\SendInvoiceEmail;
use App\Actions\SubmitInvoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendInvoiceEmailRequest;
use App\Http\Requests\StoreCreditNoteRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoicePdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class InvoiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        $query = Invoice::query()->latest('issue_date');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return InvoiceResource::collection($query->paginate(25));
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        $this->authorize('view', $invoice);

        return new InvoiceResource($invoice->load(['lines', 'submissions', 'corrections']));
    }

    public function store(StoreInvoiceRequest $request, CreateInvoice $action): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $action->handle($request->validated());

        return (new InvoiceResource($invoice->load(['lines', 'submissions', 'corrections'])))
            ->response()
            ->setStatusCode(201);
    }

    public function issue(Invoice $invoice, IssueInvoice $action): InvoiceResource
    {
        $this->authorize('issue', $invoice);

        $action->handle($invoice);

        return new InvoiceResource($invoice->load(['lines', 'submissions', 'corrections']));
    }

    public function submit(Invoice $invoice, SubmitInvoice $action): InvoiceResource
    {
        $this->authorize('submit', $invoice);

        $action->handle($invoice);

        return new InvoiceResource($invoice->load(['lines', 'submissions', 'corrections']));
    }

    public function storeCreditNote(
        StoreCreditNoteRequest $request,
        Invoice $invoice,
        CreateCreditNote $action,
    ): JsonResponse {
        $this->authorize('createCreditNote', $invoice);

        $creditNote = $action->handle($invoice, $request->validated());

        return (new InvoiceResource($creditNote->load(['lines', 'submissions', 'corrections'])))
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(Invoice $invoice, CancelInvoice $action): InvoiceResource
    {
        $this->authorize('cancel', $invoice);

        $action->handle($invoice);

        return new InvoiceResource(
            $invoice->fresh()?->load(['lines', 'submissions', 'corrections'])
        );
    }

    public function pdf(Invoice $invoice, InvoicePdf $pdf): Response
    {
        $this->authorize('view', $invoice);

        return $pdf->render($invoice)->download($pdf->filename($invoice));
    }

    public function email(
        SendInvoiceEmailRequest $request,
        Invoice $invoice,
        SendInvoiceEmail $action,
    ): JsonResponse {
        $this->authorize('email', $invoice);

        $action->handle($invoice, $request->string('email')->toString());

        return response()->json(['message' => 'Το παραστατικό στάλθηκε.']);
    }

    public function activity(Invoice $invoice): AnonymousResourceCollection
    {
        $this->authorize('view', $invoice);

        return ActivityResource::collection(
            $invoice->activitiesAsSubject()->with('causer')->latest()->get()
        );
    }
}
