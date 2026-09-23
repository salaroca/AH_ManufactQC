<?php

namespace App\Http\Controllers\Operari;

use App\Http\Controllers\Controller;
use App\Models\OrderFabrication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderFabricationController extends Controller
{
    private const LIST_LIMIT = 50;

    public function index(Request $request): JsonResponse
    {
        $orderFabrications = OrderFabrication::query()
            ->with(['project.family', 'project.sections'])
            ->withCount([
                'equipment',
                'equipment as pending_equipment_count' => fn ($query) => $query->whereNull('checked_at'),
                // Un equip està "començat" si ja té alguna resposta o ja s'ha finalitzat.
                'equipment as started_equipment_count' => fn ($query) => $query->where(
                    fn ($query) => $query->whereNotNull('checked_at')->orWhereHas('answers')
                ),
            ])
            ->when($request->filled('q'), fn ($query) => $query->where('number', 'like', '%'.$request->string('q').'%'))
            // Primer les OF amb equips per revisar, després la resta (acabades o sense equips).
            ->orderByRaw('pending_equipment_count > 0 desc')
            ->orderBy('number')
            ->limit(self::LIST_LIMIT)
            ->get();

        return response()->json($orderFabrications);
    }
}
