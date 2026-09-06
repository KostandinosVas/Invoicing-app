<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ItemController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Item::class);

        $query = Item::query()->orderBy('name');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return ItemResource::collection($query->paginate(25));
    }

    public function show(Item $item): ItemResource
    {
        $this->authorize('view', $item);

        return new ItemResource($item);
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        $this->authorize('create', Item::class);

        $item = Item::create($request->validated());

        return (new ItemResource($item))
            ->response()
            ->setStatusCode(201);
    }
}
