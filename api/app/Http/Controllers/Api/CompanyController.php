<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CompanyController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Company::class);

        return CompanyResource::collection(
            Company::query()->orderBy('name')->paginate(25)
        );
    }

    public function show(Company $company): CompanyResource
    {
        $this->authorize('view', $company);

        return new CompanyResource($company);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $this->authorize('create', Company::class);

        $company = Company::create($request->validated());

        return (new CompanyResource($company))
            ->response()
            ->setStatusCode(201);
    }
}
