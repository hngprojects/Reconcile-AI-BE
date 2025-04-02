<?php

namespace App\Http\Controllers;

use App\Models\BookkeepingLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookkeepingLedgerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/bookkeeping-ledgers",
     *     summary="Get authenticated user's bookkeeping ledgers",
     *     description="Retrieves a list of bookkeeping ledgers for the authenticated user",
     *     tags={"Bookkeeping Ledgers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Ledgers retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Book Keeping Ledger fetch successful."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="user_id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string", example="General Ledger"),
     *                     @OA\Property(property="description", type="string", example="Main business ledger"),
     *                     @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="is_active", type="boolean"),
     *                     @OA\Property(property="is_default", type="boolean"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to fetch bookkeeping ledgers."),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index()
    {
        try {

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status_code' => 401,
                    'message'    => 'Unauthenticated.'
                ], 401);
            }

            $ledgers = BookkeepingLedger::where('user_id', $user->id)->get();

            if ($ledgers->isEmpty()) {
                $defaultLedger = BookkeepingLedger::create([
                    'user_id' => $user->id,
                    'name' => 'General Ledger',
                    'description' => 'Default ledger for all transactions',
                    'is_default' => true,
                    'is_active' => true,
                    'categories' => ['Assets', 'Revenue', 'Liabilities', 'Expenses', 'Equity'],
                ]);
    
                $ledgers = BookkeepingLedger::where('user_id', $user->id)->get();
            }

            return response()->json([
                'status_code' => 200,
                'message'   => 'Book Keeping Ledger fetch successful.',
                'data'      => $ledgers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message'    => 'Failed to fetch bookkeeping ledgers.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/bookkeeping-ledgers",
     *     summary="Create a new bookkeeping ledger",
     *     description="Creates a new bookkeeping ledger for the authenticated user",
     *     tags={"Bookkeeping Ledgers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "categories"},
     *             @OA\Property(property="name", type="string", example="General Ledger"),
     *             @OA\Property(property="description", type="string", example="Main business ledger"),
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 @OA\Items(
     *                     type="string",
     *                     enum={"Assets", "Revenue", "Liabilities", "Expenses", "Equity"}
     *                 ),
     *                 example={"Assets", "Revenue"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ledger created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Ledger created successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="user_id", type="string", format="uuid"),
     *                 @OA\Property(property="name", type="string", example="General Ledger"),
     *                 @OA\Property(property="description", type="string", example="Main business ledger"),
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to create ledger."),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status_code' => 401,
                    'message'    => 'Unauthenticated.'
                ], 401);
            }
            
            $request->validate([
                'name'         => 'required|string|max:255',
                'description'  => 'nullable|string',
                'categories'   => 'required|array|min:1',
                'categories.*' => 'in:Assets,Revenue,Liabilities,Expenses,Equity',
            ]);

            $ledger = BookkeepingLedger::create([
                'user_id'     => Auth::id(),
                'name'        => $request->name,
                'description' => $request->description,
                'categories'  => $request->categories,
            ]);

            return response()->json([
                'status_code' => 200,
                'message'   => 'Ledger created successfully.',
                'data'      => $ledger
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message'    => 'Failed to create ledger.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/bookkeeping-ledgers/{ledger}/toggle",
     *     summary="Toggle ledger active status",
     *     description="Toggle the active status of a specific bookkeeping ledger",
     *     tags={"Bookkeeping Ledgers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ledger",
     *         in="path",
     *         required=true,
     *         description="Ledger ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"is_active"},
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ledger status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Ledger made active"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden action",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Cannot disable the default ledger")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to toggle ledger status."),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function toggle(Request $request, BookkeepingLedger $ledger)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status_code' => 401,
                    'message'    => 'Unauthenticated.'
                ], 401);
            }
            
            $request->validate(['is_active' => 'required|boolean']);

            if ($ledger->is_default && !$request->is_active) {
                return response()->json(['error' => 'Cannot disable the default ledger'], 403);
            }

            $ledger->update(['is_active' => $request->is_active]);

            return response()->json([
                'status_code' => 200,
                'message'    => 'Ledger made active',
                'data'       => $ledger
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message'    => 'Failed to toggle ledger status.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }
}
