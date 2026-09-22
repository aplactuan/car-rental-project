<?php

namespace App\Http\Controllers\V1\Audits;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\ListAuditsRequest;
use App\Http\Resources\V1\AuditResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OwenIt\Auditing\Models\Audit;

class ListAuditsController extends Controller
{
    public function __invoke(ListAuditsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->filters();

        $audits = Audit::query()
            ->with('user')
            ->whereIn('auditable_type', array_values(ListAuditsRequest::AUDITABLE_TYPES))
            ->when(
                isset($filters['customer_id']),
                fn (Builder $query) => $query->where('customer_id', $filters['customer_id'])
            )
            ->when(
                isset($filters['auditable_type']),
                fn (Builder $query) => $query->where('auditable_type', $filters['auditable_type'])
            )
            ->when(
                isset($filters['auditable_id']),
                fn (Builder $query) => $query->where('auditable_id', $filters['auditable_id'])
            )
            ->when(
                isset($filters['event']),
                fn (Builder $query) => $query->where('event', $filters['event'])
            )
            ->when(
                isset($filters['user_id']),
                fn (Builder $query) => $query->where('user_id', $filters['user_id'])
            )
            ->when(
                isset($filters['date_from']),
                fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from'])
            )
            ->when(
                isset($filters['date_to']),
                fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to'])
            )
            ->latest('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return AuditResource::collection($audits);
    }
}
