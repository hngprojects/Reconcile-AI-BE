<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LedgerEntryController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/ledger-entries",
     *     summary="Create a new ledger entry",
     *     description="Creates a new ledger entry for the authenticated user",
     *     tags={"Ledger Entries"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ledger_category","transaction_type","transaction_date","description","amount","paid_status","amount_paid","bank_account_id","account_category"},
     *             @OA\Property(property="ledger_category", type="string", example="Sales"),
     *             @OA\Property(property="transaction_type", type="string", enum={"income","expense"}, example="income"),
     *             @OA\Property(property="transaction_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="description", type="string", example="Monthly service payment"),
     *             @OA\Property(property="amount", type="number", format="float", example=1000.00),
     *             @OA\Property(property="paid_status", type="string", enum={"paid","unpaid","partial"}, example="paid"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-02-15"),
     *             @OA\Property(property="amount_paid", type="number", format="float", example=1000.00),
     *             @OA\Property(property="bank_account_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="account_category", type="string", example="Revenue", description="Must be a valid chart account category name"),
     *             @OA\Property(property="reference", type="string", example="INV-2024-001"),
     *             @OA\Property(property="attachment", type="file")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ledger entry created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Ledger entry created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
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

            $validator = Validator::make($request->all(), [
                'ledger_category' => 'required|string',
                'transaction_type' => 'required|in:income,expense',
                'transaction_date' => 'required|date',
                'description' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'paid_status' => 'required|in:paid,unpaid,partial',
                'due_date' => 'nullable|date',
                'amount_paid' => 'required|numeric|min:0',
                'bank_account_id' => 'required|exists:bank_accounts,id',
                'account_category' => 'nullable|string',
                'reference' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['user_id'] = $user->id;
            $data['id'] = Str::uuid();

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('ledger-attachments', 'public');
                $data['attachment'] = $path;
            }

            $ledgerEntry = LedgerEntry::create($data);

            return response()->json([
                'status_code' => 201,
                'message' => 'Ledger entry created successfully',
                'data' => $ledgerEntry
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to create ledger entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ledger-entries",
     *     summary="Get all ledger entries",
     *     description="Retrieves all ledger entries for the authenticated user",
     *     tags={"Ledger Entries"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Ledger entries retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Ledger entries retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="ledger_category", type="string"),
     *                     @OA\Property(property="transaction_type", type="string"),
     *                     @OA\Property(property="transaction_date", type="string", format="date"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="amount", type="number"),
     *                     @OA\Property(property="paid_status", type="string"),
     *                     @OA\Property(property="due_date", type="string", format="date"),
     *                     @OA\Property(property="amount_paid", type="number"),
     *                     @OA\Property(property="bank_account_id", type="integer"),
     *                     @OA\Property(property="account_category", type="string"),
     *                     @OA\Property(property="reference", type="string"),
     *                     @OA\Property(property="attachment", type="string")
     *                 )
     *             )
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

            $ledgerEntries = LedgerEntry::where('user_id', $user->id)
                ->with('bankAccount')
                ->orderBy('transaction_date', 'desc')
                ->get();

            return response()->json([
                'status_code' => 200,
                'message' => 'Ledger entries retrieved successfully',
                'data' => $ledgerEntries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to retrieve ledger entries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ledger-entries/{id}",
     *     summary="Get a specific ledger entry",
     *     description="Retrieves a specific ledger entry by ID for the authenticated user",
     *     tags={"Ledger Entries"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ledger entry ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ledger entry retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Ledger entry retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ledger entry not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Ledger entry not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status_code' => 401,
                    'message'    => 'Unauthenticated.'
                ], 401);
            }

            $ledgerEntry = LedgerEntry::where('user_id', $user->id)
                ->with('bankAccount')
                ->find($id);

            if (!$ledgerEntry) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Ledger entry not found'
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Ledger entry retrieved successfully',
                'data' => $ledgerEntry
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to retrieve ledger entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/ledger-entries/{id}",
     *     summary="Update a ledger entry",
     *     description="Updates a specific ledger entry by ID for the authenticated user",
     *     tags={"Ledger Entries"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ledger entry ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="ledger_category", type="string", example="Sales"),
     *             @OA\Property(property="transaction_type", type="string", enum={"income","expense"}, example="income"),
     *             @OA\Property(property="transaction_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="description", type="string", example="Monthly service payment"),
     *             @OA\Property(property="amount", type="number", format="float", example=1000.00),
     *             @OA\Property(property="paid_status", type="string", enum={"paid","unpaid","partial"}, example="paid"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-02-15"),
     *             @OA\Property(property="amount_paid", type="number", format="float", example=1000.00),
     *             @OA\Property(property="bank_account_id", type="integer", example=1),
     *             @OA\Property(property="account_category", type="string", example="Revenue"),
     *             @OA\Property(property="reference", type="string", example="INV-2024-001"),
     *             @OA\Property(property="attachment", type="file")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ledger entry updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Ledger entry updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ledger entry not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=404),
     *             @OA\Property(property="message", type="string", example="Ledger entry not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status_code' => 401,
                    'message'    => 'Unauthenticated.'
                ], 401);
            }

            $ledgerEntry = LedgerEntry::where('user_id', $user->id)->find($id);

            if (!$ledgerEntry) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Ledger entry not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'ledger_category' => 'sometimes|required|string',
                'transaction_type' => 'sometimes|required|in:income,expense',
                'transaction_date' => 'sometimes|required|date',
                'description' => 'sometimes|required|string',
                'amount' => 'sometimes|required|numeric|min:0',
                'paid_status' => 'sometimes|required|in:paid,unpaid,partial',
                'due_date' => 'nullable|date',
                'amount_paid' => 'sometimes|required|numeric|min:0',
                'bank_account_id' => 'sometimes|required|exists:bank_accounts,id',
                'account_category' => 'sometimes|required|exists:account_chart_categories,title',
                'reference' => 'nullable|string',
                'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('attachment')) {
                if ($ledgerEntry->attachment) {
                    Storage::disk('public')->delete($ledgerEntry->attachment);
                }
                
                $file = $request->file('attachment');
                $path = $file->store('ledger-attachments', 'public');
                $data['attachment'] = $path;
            }

            $ledgerEntry->update($data);

            return response()->json([
                'status_code' => 200,
                'message' => 'Ledger entry updated successfully',
                'data' => $ledgerEntry->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to update ledger entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}