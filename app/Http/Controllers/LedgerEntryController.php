<?php

namespace App\Http\Controllers;

use App\Models\BookkeepingLedger;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LedgerEntryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/ledger/{ledger}/entries",
     *     summary="Get ledger entries",
     *     description="Retrieves all entries for a specific ledger",
     *     tags={"Ledger Entries"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="ledger",
     *         in="path",
     *         required=true,
     *         description="Ledger ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Entries retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Fetched ledger entries successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="ledger_id", type="string", format="uuid"),
     *                     @OA\Property(property="user_id", type="string", format="uuid"),
     *                     @OA\Property(property="account_category", type="string", example="Cash"),
     *                     @OA\Property(property="transaction_type", type="string", enum={"income", "expense"}),
     *                     @OA\Property(property="date", type="string", format="date"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="amount", type="number", format="float"),
     *                     @OA\Property(property="paid_status", type="boolean"),
     *                     @OA\Property(property="bank_account_id", type="string", format="uuid", nullable=true),
     *                     @OA\Property(property="invoice_or_ref_number", type="string", nullable=true),
     *                     @OA\Property(property="attachment", type="string", nullable=true),
     *                     @OA\Property(property="notes", type="string", nullable=true),
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
     *             @OA\Property(property="message", type="string", example="Failed to fetch ledger entries."),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index(BookkeepingLedger $ledger)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status_code' => 401,
                    'message'    => 'Unauthenticated.'
                ], 401);
            }
            
            $entries = LedgerEntry::where('ledger_id', $ledger->id)
                ->where('user_id', Auth::id())
                ->get();

            return response()->json([
                'status_code' => 200,
                'message'    => 'Fetched ledger entries successfully.',
                'data'       => $entries
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message'    => 'Failed to fetch ledger entries.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ledger-entries",
     *     summary="Create a new ledger entry",
     *     description="Creates a new entry in the specified ledger",
     *     tags={"Ledger Entries"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ledger_id", "account_category", "transaction_type", "date", "description", "amount"},
     *             @OA\Property(property="ledger_id", type="string", format="uuid"),
     *             @OA\Property(
     *                 property="account_category",
     *                 type="string",
     *                 enum={"Cash", "Bank", "Accounts Receivable", "Accounts Payable", "Sales Revenue", "Rent Expense", "Utilities Expense", "Salary Expense"}
     *             ),
     *             @OA\Property(property="transaction_type", type="string", enum={"income", "expense"}),
     *             @OA\Property(property="date", type="string", format="date"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="amount", type="number", format="float", minimum=0),
     *             @OA\Property(property="paid_status", type="boolean"),
     *             @OA\Property(property="bank_account_id", type="string", format="uuid", nullable=true),
     *             @OA\Property(property="invoice_or_ref_number", type="string", nullable=true),
     *             @OA\Property(property="notes", type="string", nullable=true)
     *         ),
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="attachment",
     *                     type="string",
     *                     format="binary",
     *                     description="File attachment (PDF, JPG, PNG, max 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Entry created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Ledger entry created successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="ledger_id", type="string", format="uuid"),
     *                 @OA\Property(property="user_id", type="string", format="uuid"),
     *                 @OA\Property(property="account_category", type="string"),
     *                 @OA\Property(property="transaction_type", type="string"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="amount", type="number", format="float"),
     *                 @OA\Property(property="paid_status", type="boolean"),
     *                 @OA\Property(property="bank_account_id", type="string", format="uuid", nullable=true),
     *                 @OA\Property(property="invoice_or_ref_number", type="string", nullable=true),
     *                 @OA\Property(property="attachment", type="string", nullable=true),
     *                 @OA\Property(property="notes", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\AdditionalProperties(
     *                     type="array",
     *                     @OA\Items(type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Failed to create ledger entry."),
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
                'ledger_id'            => 'required|uuid|exists:bookkeeping_ledgers,id',
                'account_category'     => 'required|in:Cash,Bank,Accounts Receivable,Accounts Payable,Sales Revenue,Rent Expense,Utilities Expense,Salary Expense',
                'transaction_type'     => 'required|in:income,expense',
                'date'                 => 'required|date',
                'description'          => 'required|string',
                'amount'               => 'required|numeric|min:0',
                'paid_status'          => 'boolean',
                'bank_account_id'      => 'nullable|uuid|exists:bank_accounts,id',
                'invoice_or_ref_number'=> 'nullable|string|max:255',
                'attachment'           => 'nullable|file|mimes:pdf,jpg,png|max:2048',
                'notes'                => 'nullable|string',
            ]);

            $ledger = BookkeepingLedger::findOrFail($request->ledger_id);
            $attachmentPath = null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentPath = $file->store('ledger-attachments', 'public');
            }

            $entry = LedgerEntry::create([
                'ledger_id'         => $request->ledger_id,
                'user_id'           => Auth::id(),
                'account_category'  => $request->account_category,
                'transaction_type'  => $request->transaction_type,
                'date'              => $request->date,
                'description'       => $request->description,
                'amount'            => $request->amount,
                'paid_status'       => $request->paid_status ?? false,
                'bank_account_id'   => $request->bank_account_id,
                'invoice_or_ref_number' => $request->invoice_or_ref_number,
                'attachment'        => $attachmentPath,
                'notes'             => $request->notes,
            ]);

            return response()->json([
                'status_code' => 201,
                'message'    => 'Ledger entry created successfully.',
                'data'       => $entry
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message'    => 'Failed to create ledger entry.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }
}