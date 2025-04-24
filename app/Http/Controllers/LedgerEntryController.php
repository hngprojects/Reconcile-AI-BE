<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\Ledger;
use App\Models\LedgerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Services\NewReconciliation\NewReconciliationService;
use Illuminate\Validation\ValidationException;
use App\Repositories\MatchingTransaction\MatchingTransactionRepository;

class LedgerEntryController extends Controller
{
    protected $reconciliationService;
    protected MatchingTransactionRepository $matchedRepository;

    public function __construct(
        NewReconciliationService $reconciliationService,
        MatchingTransactionRepository $matchedRepository
    )
    {
        $this->reconciliationService = $reconciliationService;
        $this->matchedRepository = $matchedRepository;
    }
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
     *             required={"bookkeeping_ledger_id","transaction_type","transaction_date","description","amount","paid_status","amount_paid","bank_account_id","account_chart_id"},
     *             @OA\Property(property="bookkeeping_ledger_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="statement_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="transaction_type", type="string", enum={"Income","Expense","Payable","Receivable"}, example="Income"),
     *             @OA\Property(property="transaction_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="description", type="string", example="Monthly service payment"),
     *             @OA\Property(property="amount", type="number", format="float", example=1000.00),
     *             @OA\Property(property="paid_status", type="string", enum={"paid","unpaid","partial"}, example="paid"),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-02-15"),
     *             @OA\Property(property="amount_paid", type="number", format="float", example=1000.00),
     *             @OA\Property(property="bank_account_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="account_chart_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="reference", type="string", example="INV-2024-001"),
     *             @OA\Property(property="attachment", type="file", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ledger entry created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Ledger entry created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="ledger", type="object"),
     *                 @OA\Property(property="payment", type="object")
     *             )
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
                'bookkeeping_ledger_id' => 'required|uuid|exists:bookkeeping_ledgers,id',
                'statement_id' => 'nullable|uuid|exists:statements,id',
                'transaction_type' => 'required|in:Income,Expense,Payable,Receivable',
                'transaction_date' => 'required|date',
                'description' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'paid_status' => 'required|in:paid,unpaid,partial',
                'due_date' => 'nullable|date',
                'amount_paid' => 'required|numeric|min:0',
                'bank_account_id' => 'required|uuid|exists:bank_accounts,id',
                'account_chart_id' => 'required|uuid|exists:account_charts,id',
                'reference' => 'nullable|string|max:255',
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

            $ledger = Ledger::create([
                'bookkeeping_ledger_id' => $data['bookkeeping_ledger_id'],
                'transaction_type' => $data['transaction_type'],
                'date' => $data['transaction_date'],
                'person' => $data['description'],
                'amount' => $data['amount'],
            ]);

            if($data['statement_id']){
                $this->matchedRepository->storeByIds($ledger->id, $data['statement_id'], '100%', 'manual');
            }

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentPath = $file->store('ledger-attachments', 'public');
            }

            $ledgerPayment = LedgerPayment::create([
                'ledger_id' => $ledger->id,
                'payment_status' => $data['paid_status'],
                'due_date' => $data['due_date'],
                'amount_paid' => $data['amount_paid'],
                'bank_account_id' => $data['bank_account_id'],
                'account_chart_id' => $data['account_chart_id'],
                'reference' => $data['reference'],
                'attachment' => $attachmentPath
            ]);

            return response()->json([
                'status_code' => 201,
                'message' => 'Ledger entry created successfully',
                'data' => [
                    'ledger' => $ledger,
                    'payment' => $ledgerPayment
                ]
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
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="bookkeeping_ledger_id", type="string", format="uuid"),
     *                     @OA\Property(property="transaction_type", type="string", enum={"Income","Expense","Payable","Receivable"}),
     *                     @OA\Property(property="date", type="string", format="date"),
     *                     @OA\Property(property="person", type="string"),
     *                     @OA\Property(property="amount", type="number", format="float"),
     *                     @OA\Property(property="reconciliation_id", type="string", format="uuid"),
     *                     @OA\Property(
     *                         property="payment",
     *                         type="object",
     *                         @OA\Property(property="payment_status", type="string", enum={"paid","unpaid","partial"}),
     *                         @OA\Property(property="due_date", type="string", format="date"),
     *                         @OA\Property(property="amount_paid", type="number", format="float"),
     *                         @OA\Property(property="bank_account_id", type="string", format="uuid"),
     *                         @OA\Property(property="account_chart_id", type="string", format="uuid"),
     *                         @OA\Property(property="reference", type="string"),
     *                         @OA\Property(property="attachment", type="string")
     *                     )
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

            $ledgers = Ledger::with(['payment', 'payment.account', 'ledgerType'])
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'status_code' => 200,
                'message' => 'Ledger entries retrieved successfully',
                'data' => $ledgers
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
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ledger entry retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Ledger entry retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="bookkeeping_ledger_id", type="string", format="uuid"),
     *                 @OA\Property(property="transaction_type", type="string", enum={"Income","Expense","Payable","Receivable"}),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="person", type="string"),
     *                 @OA\Property(property="amount", type="number", format="float"),
     *                 @OA\Property(property="reconciliation_id", type="string", format="uuid"),
     *                 @OA\Property(
     *                     property="payment",
     *                     type="object",
     *                     @OA\Property(property="payment_status", type="string", enum={"paid","unpaid","partial"}),
     *                     @OA\Property(property="due_date", type="string", format="date"),
     *                     @OA\Property(property="amount_paid", type="number", format="float"),
     *                     @OA\Property(property="bank_account_id", type="string", format="uuid"),
     *                     @OA\Property(property="account_chart_id", type="string", format="uuid"),
     *                     @OA\Property(property="reference", type="string"),
     *                     @OA\Property(property="attachment", type="string")
     *                 )
     *             )
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

            $ledger = Ledger::with(['payment', 'payment.account', 'ledgerType'])
                ->find($id);

            if (!$ledger) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Ledger entry not found'
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'message' => 'Ledger entry retrieved successfully',
                'data' => $ledger
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
     *     description="Updates a specific ledger entry and its payment details",
     *     tags={"Ledger Entries"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Ledger ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="bookkeeping_ledger_id", type="string", format="uuid"),
     *             @OA\Property(property="transaction_type", type="string", enum={"Income","Expense","Payable","Receivable"}),
     *             @OA\Property(property="transaction_date", type="string", format="date"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="paid_status", type="string", enum={"paid","unpaid","partial"}),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="amount_paid", type="number", format="float"),
     *             @OA\Property(property="bank_account_id", type="string", format="uuid"),
     *             @OA\Property(property="account_chart_id", type="string", format="uuid"),
     *             @OA\Property(property="reference", type="string"),
     *             @OA\Property(property="attachment", type="file", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ledger entry updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Ledger entry updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="ledger", type="object"),
     *                 @OA\Property(property="payment", type="object")
     *             )
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

            $ledger = Ledger::with('payment')->find($id);
            if (!$ledger) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Ledger entry not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'bookkeeping_ledger_id' => 'sometimes|required|uuid|exists:bookkeeping_ledgers,id',
                'transaction_type' => 'sometimes|required|in:Income,Expense,Payable,Receivable',
                'transaction_date' => 'sometimes|required|date',
                'description' => 'sometimes|required|string',
                'amount' => 'sometimes|required|numeric|min:0',
                'paid_status' => 'sometimes|required|in:paid,unpaid,partial',
                'due_date' => 'nullable|date',
                'amount_paid' => 'sometimes|required|numeric|min:0',
                'bank_account_id' => 'sometimes|required|uuid|exists:bank_accounts,id',
                'account_chart_id' => 'sometimes|required|uuid|exists:account_charts,id',
                'reference' => 'nullable|string|max:255',
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

            if (isset($data['bookkeeping_ledger_id'])) $ledger->bookkeeping_ledger_id = $data['bookkeeping_ledger_id'];
            if (isset($data['transaction_type'])) $ledger->transaction_type = $data['transaction_type'];
            if (isset($data['transaction_date'])) $ledger->date = $data['transaction_date'];
            if (isset($data['description'])) $ledger->person = $data['description'];
            if (isset($data['amount'])) $ledger->amount = $data['amount'];
            $ledger->save();

            $payment = $ledger->payment;
            if (isset($data['paid_status'])) $payment->payment_status = $data['paid_status'];
            if (isset($data['due_date'])) $payment->due_date = $data['due_date'];
            if (isset($data['amount_paid'])) $payment->amount_paid = $data['amount_paid'];
            if (isset($data['bank_account_id'])) $payment->bank_account_id = $data['bank_account_id'];
            if (isset($data['account_chart_id'])) $payment->account_chart_id = $data['account_chart_id'];
            if (isset($data['reference'])) $payment->reference = $data['reference'];

            if ($request->hasFile('attachment')) {
                if ($payment->attachment) {
                    Storage::disk('public')->delete($payment->attachment);
                }
                $file = $request->file('attachment');
                $payment->attachment = $file->store('ledger-attachments', 'public');
            }
            $payment->save();

            return response()->json([
                'status_code' => 200,
                'message' => 'Ledger entry updated successfully',
                'data' => [
                    'ledger' => $ledger->fresh(),
                    'payment' => $payment->fresh()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'Failed to update ledger entry',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload ledger CSV
     *
     * @OA\Post(
     *     path="/api/v1/ledger-entries/upload",
     *     summary="Upload ledger CSV",
     *     description="Uploads the ledger and saves the entries",
     *     tags={"Ledger Entries"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"ledger", "ledger_file", "mapper"},
     *                 @OA\Property(property="ledger", type="string", format="uuid"),
     *                 @OA\Property(property="ledger_file", type="string", format="binary"),
     *                 @OA\Property(property="transaction_type", type="string"),
     *                 @OA\Property(
     *                     property="mapper[date]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="mapper[description]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="mapper[amount]",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ledger CSV successfully uploaded",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Ledger CSV successfully uploaded and saved."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object"
     *             )
     *         )
     *     )
     * )
     */
    public function uploadLedger(Request $request){
        try {
            $validated = $request->validate([
                'ledger_file' => 'required|file|mimes:csv|max:2048',
                'ledger' => 'required|uuid|exists:bookkeeping_ledgers,id',
                'transaction_type' => 'required|string',
                'mapper' => 'required',
                'mapper.date' => 'required|string',
                'mapper.description' => 'required|string',
                'mapper.amount' => 'required|string',
            ]);
            return $this->reconciliationService->uploadLedger($validated);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'status' => 'error',
                'status_code' => 422,
                'data' => [
                    'errors' => $e->errors()
                ]
            ], 422);

        } catch(\Exception $e) {
            return response()->json([
                "message" => "Failed to upload ledger",
                "status" => "error",
                "status_code" => 500,
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
