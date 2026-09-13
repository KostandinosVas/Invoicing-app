<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateInvoice;
use App\Actions\IssueInvoice;
use App\Actions\SubmitInvoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

        return new InvoiceResource($invoice->load('lines'));
    }

    public function store(StoreInvoiceRequest $request, CreateInvoice $action): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $invoice = $action->handle($request->validated());

        return (new InvoiceResource($invoice->load('lines')))
            ->response()
            ->setStatusCode(201);
    }

    public function issue(Invoice $invoice, IssueInvoice $action): InvoiceResource
    {
        $this->authorize('issue', $invoice);

        $action->handle($invoice);

        return new InvoiceResource($invoice->load('lines'));
    }

    public function submit(Invoice $invoice, SubmitInvoice $action): InvoiceResource
    {
        $this->authorize('submit', $invoice);

        $action->handle($invoice);

        return new InvoiceResource($invoice->load('lines'));
    }
}
