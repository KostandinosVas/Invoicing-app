<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeriesRequest;
use App\Http\Resources\SeriesResource;
use App\Models\Series;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SeriesController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Series::class);

        $query = Series::query()->orderBy('code');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->string('document_type')->toString());
        }

        return SeriesResource::collection($query->paginate(25));
    }

    public function show(Series $series): SeriesResource
    {
        $this->authorize('view', $series);

        return new SeriesResource($series);
    }

    public function store(StoreSeriesRequest $request): JsonResponse
    {
        $this->authorize('create', Series::class);

        $series = Series::create($request->validated());

        return (new SeriesResource($series))
            ->response()
            ->setStatusCode(201);
    }
}
