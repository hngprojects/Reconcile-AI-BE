<?php

namespace App\Http\Controllers;

use App\Services\NewReconciliation\NewReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use App\Repositories\Reconciliation\ReconciliationRepository;
use App\Models\Reconciliation;
use App\Http\Requests\ManualReconciliationRequest;
use App\Jobs\ProcessReconciliation;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessDraftReconciliation;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Reconciliation",
 *     description="API Endpoints for file reconciliation"
 * )
 */
class ReconciliationController extends Controller
{
    protected $reconciliationService;
    protected $reconciliationRepository;

    public function __construct(NewReconciliationService $reconciliationService, ReconciliationRepository $reconciliationRepository)
    {
        $this->reconciliationService = $reconciliationService;
        $this->reconciliationRepository = $reconciliationRepository;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconciliations",
     *     summary="Create a new reconciliation",
     *     description="Create a new reconciliation with the specified title",
     *     tags={"Reconciliation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"title"},
     *                @OA\Property(
     *                    property="title",
     *                    type="string",
     *                     example="Monthly Reconciliation"
     *                 ),
     *            ) 
     *         )
     *    ),
     *    @OA\Response(
     *        response=200,
     *       description="Reconciliation created successfully",
     *      @OA\JsonContent(
     *          @OA\Property(property="message", type="string", example="Reconciliation created successfully"),
     *          @OA\Property(property="status", type="string", example="success"),
     *          @OA\Property(property="status_code", type="integer", example=200),
     *          @OA\Property(
     *              property="data",
     *              type="object",
     *              @OA\Property(property="reconciliation_id", type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *         )
     *       )
     *    ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid input data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error", 
     *        @OA\JsonContent(
     *            @OA\Property(property="error", type="string", example="The title field is required.")
     *        )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
        ]);

        return response()->json($this->reconciliationService->store($request->all(), $request->user()));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconciliations/{reconciliation}/ledgers",
     *     summary="Add ledgers to reconciliation",
     *     description="Add ledgers to reconciliation",
     *     tags={"Reconciliation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"ledgers"},
     *                 @OA\Property(
     *                     property="ledgers",
     *                     type="array",
     *                     @OA\Items(
     *                         type="string",
     *                         format="uuid",
     *                         example="550e8400-e29b-41d4-a716-446655440000"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reconciliation created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation created successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="reconciliation_id", type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid input data")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error", 
     *        @OA\JsonContent(
     *            @OA\Property(property="error", type="string", example="The title field is required.")
     *        )
     *     )
     * )
     */
    public function createReconWithLedgers(Request $request, Reconciliation $reconciliation): JsonResponse
    {
        $request->validate([
            'ledgers' => 'required|array',
            'ledgers.*' => 'required|string|exists:bookkeeping_ledgers,id',
        ]);

        return response()->json($this->reconciliationService->addLedgersToRecon($reconciliation, $request->all()));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconciliations/{reconciliation}/statements",
     *     summary="Add statements to reconciliation",
     *     description="Upload and add bank statements to an existing reconciliation",
     *     tags={"Reconciliation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="statements[0][file]",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="statements[0][bank_account]",
     *                     type="string",
     *                     format="uuid"
     *                 ),
     *                 @OA\Property(
     *                     property="statements[0][period][from]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="statements[0][period][to]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="statements[0][mapper][date]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="statements[0][mapper][description]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="statements[0][mapper][amount]",
     *                     type="string"
     *                 ),
     *             )
     *         )
     *      ),
     *
     *    @OA\Response(
     *        response=200,
     *       description="Statements added successfully",
     *       @OA\JsonContent(
     *           @OA\Property(property="message", type="string", example="Statements added successfully"),
     *          @OA\Property(property="status", type="string", example="success"),
     *          @OA\Property(property="status_code", type="integer", example=200),
     *          @OA\Property(
     *              property="data",
     *             type="object",
     *            @OA\Property(property="reconciliation_id", type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *           )
     *      )
     *   ),
     *    @OA\Response(
     *       response=422,
     *      description="Validation error",
     *      @OA\JsonContent(
     *          @OA\Property(property="error", type="string", example="The statements field is required.")
     *     )
     *  )
     * )
     */
    public function addStatementsToRecon(Request $request, Reconciliation $reconciliation)
    {
        $request->validate([
            'statements' => 'required|array',
            'statements.*.file' => 'required|file|mimes:csv|max:2048',
            'statements.*.bank_account' => 'required|uuid|exists:bank_accounts,id',
            'statements.*.period' => 'required|array',
            'statements.*.period.from' => 'required|string',
            'statements.*.period.to' => 'required|string',
            'statements.*.mapper' => 'required|array',
            'statements.*.mapper.date' => 'required|string',
            'statements.*.mapper.description' => 'required|string',
            'statements.*.mapper.amount' => 'required|string',
        ]);

        $statements = [];
        foreach ($request->input('statements') as $index => $statementData) {
            $file = $request->file("statements.$index.file");
            $bankAccount = $statementData['bank_account'];
            $period = $statementData['period'];
            $mapper = $statementData['mapper'];
            $originalName = $file->getClientOriginalName();

            if (!$file) {
                return response()->json(['error' => "Missing file for statements[$index]."], 422);
            }

            $statementPath = $file->store('uploads');
            $statementFullPath = Storage::path($statementPath);

            if (!$this->isValidFileFormat($statementFullPath)) {
                Storage::delete([$statementPath]);
                return response()->json(['error' => 'One of the files is not in the correct format.'], 422);
            }

            $statements[] = [
                'name' => $originalName,
                'path' => $statementFullPath,
                'bank_account_id' => $bankAccount,
                'period' => [
                    'start_date' => $period['from'],
                    'end_date' => $period['to'],
                ],
                'mapper' => $mapper,
            ];
        }

        return response()->json($this->reconciliationService->addStatementsToRecon($reconciliation, ['statements' => $statements, 'user_id' => $request->user()->id]), 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconcile",
     *     summary="New Reconciliation Approach - Embeddings",
     *     description="Upload and compare two sets of files using various reconciliation methods",
     *     tags={"Reconciliation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="bank_statements[0][file]",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="bank_statements[0][bank_account]",
     *                     type="string",
     *                     format="uuid"
     *                 ),
     *                 @OA\Property(
     *                     property="bank_statements[0][period][from]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="bank_statements[0][period][to]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="bank_statements[0][mapper][date]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="bank_statements[0][mapper][description]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="bank_statements[0][mapper][amount]",
     *                     type="string"
     *                 ),
     *                 @OA\Property(
     *                     property="ledgers[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="uuid")
     *                 ),
     *                 @OA\Property(
     *                     property="title",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful reconciliation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation successful"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="matches", type="integer", example=5),
     *                 @OA\Property(
     *                     property="unmatched_statements",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Payment for Student: STU1029")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="unmatched_ledgers",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="description", type="string", example="Exam Fee")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid file format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="The bank_statements field is required")
     *         )
     *     )
     * )
     */
    public function testEmbeddings(Request $request, Reconciliation $reconciliation): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'bank_statements' => 'required|array',
            'bank_statements.*.mapper' => 'required|array',
            'bank_statements.*.mapper.date' => 'required|string',
            'bank_statements.*.mapper.description' => 'required|string',
            'bank_statements.*.mapper.amount' => 'required|string',
            'bank_statements.*.file' => 'required|file|mimes:csv|max:2048',
            'bank_statements.*.bank_account' => 'required|uuid|exists:bank_accounts,id',
            'bank_statements.*.period' => 'required|array',
            'bank_statements.*.period.from' => 'required|string',
            'bank_statements.*.period.to' => 'required|string',
            'ledgers' => 'required|array',
            'ledgers.*' => 'required|string|exists:bookkeeping_ledgers,id',
            'statements.*.period' => 'required|array',
            'statements.*.period.from' => 'required|string',
            'statements.*.period.to' => 'required|string',
        ], [
            'bank_statements.*.file.mimes' => 'Bank Statement must be a CSV.',
            'bank_statements.*.file.max' => 'Bank statement must not be larger than 2MB.',
        ]);

        try {
            $statements = [];
            $ledgers = $request->input('ledgers');

            foreach ($request->input('bank_statements') as $index => $statementData) {
                $file = $request->file("bank_statements.$index.file");
                $bankAccount = $statementData['bank_account'];
                $period = $statementData['period'];
                $mapper = $statementData['mapper'];
                $originalName = $file->getClientOriginalName();

                if (!$file) {
                    return response()->json(['error' => "Missing file for bank_statements[$index]."], 422);
                }

                $statementPath = $file->store('uploads');
                $statementFullPath = Storage::path($statementPath);

                if (!$this->isValidFileFormat($statementFullPath)) {
                    Storage::delete([$statementPath]);
                    return response()->json(['error' => 'One of the files is not in the correct format.'], 422);
                }

                $statements[] = [
                    'name' => $originalName,
                    'path' => $statementFullPath,
                    'bank_account_id' => $bankAccount,
                    'period' => [
                        'start_date' => $period['from'],
                        'end_date' => $period['to'],
                    ],
                    'mapper' => $mapper,
                ];
            }

            $reconciliation = $this->reconciliationService->storeReconciliation($statements, $ledgers,  $request->input('title'), $request->user()->id);
            ProcessReconciliation::dispatch($statements, $ledgers, $request->user(), $reconciliation, $this->reconciliationRepository);

            return response()->json([
                "message" => "Reconciliation initiated successfully",
                "status" => "success",
                "status_code" => 200,
                'data' => [
                    'reconciliation_id' => $reconciliation->id,
                    'bank_accounts' => $reconciliation->statementFiles->map(function ($statement) {
                        return [
                            'id' => $statement->bankAccount->id,
                            'name' => $statement->bankAccount->bank_name,
                            'account' => $statement->bankAccount->account_name,
                        ];
                    }),
                    'ledgers' => $reconciliation->ledgers->map(function ($ledger) {
                        return [
                            'id' => $ledger->id,
                            'name' => $ledger->name,
                            'description' => $ledger->description,
                        ];
                    }),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to reconcile: ' . $e->getMessage(), ['trace' => $e->getTrace()]);
            return response()->json([
                "message" => "Failed to reconcile",
                "status" => "error",
                "status_code" => 500,
                'data' => [
                    'error' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/reconciliations/{reconciliation}",
     *     summary="Save reconciliation draft",
     *     description="Save the current state of the reconciliation as a draft",
     *     tags={"Reconciliation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reconciliation saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation saved successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function saveDraft(Request $request, Reconciliation $reconciliation)
    {
        $validated = $request->validate([
            'step' => 'required|integer'
        ]);

        $this->reconciliationService->saveDraft($validated, $reconciliation);
        return response()->json([
            'message' => 'Reconciliation saved successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => [
                ...$reconciliation->toArray()
            ]
        ], 200);
    }
    /**
     * @OA\Put(
     *     path="/api/v1/reconciliations/{reconciliation}/complete",
     *     summary="Save reconciliation draft",
     *     description="Save the current state of the reconciliation as a draft",
     *     tags={"Reconciliation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reconciliation saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation saved successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function complete(Reconciliation $reconciliation)
    {
        $this->reconciliationService->complete($reconciliation);
        return response()->json([
            'message' => 'Reconciliation completed successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => [
                ...$reconciliation->toArray()
            ]
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconciliations/{reconciliation}/export",
     *     summary="Export reconciled data as a CSV file",
     *     description="Generates a CSV file containing matched and unmatched transactions from reconciliation.",
     *     tags={"Reconciliation"},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CSV file generated successfully",
     *         @OA\Header(header="Content-Disposition", description="attachment; filename=reconciled-data.csv", @OA\Schema(type="string"))
     *     ),
     *     @OA\Response(response=500, description="Server error while generating CSV file")
     * )
     */
    public function export(Reconciliation $reconciliation)
    {
        try {
            return $this->reconciliationService->export($reconciliation);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Failed to generate report",
                "status" => "error",
                "status_code" => 500,
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reconciliations/{reconciliation}",
     *     summary="Get reconciled records",
     *     description="Fetch reconciled records for the logged-in user",
     *     tags={"Reconciliation"},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     security={{ "bearerAuth":{} }},
     *     @OA\Response(
     *         response=200,
     *         description="Reconciled records fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciled records fetched successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function getReconciledRecords(Request $request, Reconciliation $reconciliation)
    {
        $user = $request->user();

        if ($reconciliation->user->id != $user->id) {
            return response()->json([
                'message' => 'Failed to authenticate',
                'status' => 'error',
                'status_code' => 401,
                'data' => [
                    'error' => 'Please contact the owner to view this'
                ]
            ], 401);
        }

        $records = $this->reconciliationService->fetchResults($reconciliation, $user);

        return response()->json([
            'message' => 'Reconciled records fetched successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $records
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reconciliations/{reconciliation}/result",
     *     summary="Get reconciliation results",
     *     description="Fetch reconciliation results for the logged-in user",
     *     tags={"Reconciliation"},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Reconciled records fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciled records fetched successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function getReconResults(Request $request, Reconciliation $reconciliation)
    {
        $user = $request->user();

        if ($reconciliation->user->id != $user->id) {
            return response()->json([
                'message' => 'Failed to authenticate',
                'status' => 'error',
                'status_code' => 401,
                'data' => [
                    'error' => 'Please contact the owner to view this'
                ]
            ], 401);
        }

        return $this->reconciliationService->fetchReconResult($reconciliation);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconcile/{reconciliation}",
     *     summary="Reconcile a ledger and a statement",
     *     description="Perform reconciliation between a ledger and a statement based on specified action",
     *     tags={"Reconciliation"},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"matches"},
     *                 @OA\Property(
     *                     property="matches",
     *                     type="array",
     *                     @OA\Items(
     *                          type="object",
     *                          @OA\Property(
     *                              property="ledger",
     *                              type="string",
     *                              format="uuid"
     *                          ),
     *                          @OA\Property(
     *                              property="statement",
     *                              type="string",
     *                              format="uuid"
     *                          ),
     *                          @OA\Property(
     *                              property="score",
     *                              type="string",
     *                              example="80%"
     *                          ),
     *                          @OA\Property(
     *                              property="matched_by",
     *                              type="string",
     *                              enum={"AI", "manual"},
     *                              example="manual | AI",
     *                          ),
     *                          @OA\Property(
     *                              property="action",
     *                              type="string",
     *                              enum={"match", "unmatch"},
     *                              example="match | unmatch",
     *                              description="Reconciliation action to perform"
     *                          )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful reconciliation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation successful"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="matches", type="integer", example=5),
     *                 @OA\Property(property="only_in_ledger", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="only_in_statement", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function matchUnmatch(ManualReconciliationRequest $request, Reconciliation $reconciliation)
    {
        $validated = $request->validated();

        $res = $this->reconciliationService->matchUnmatch($reconciliation, $validated['matches']);

        return response()->json([
            'message' => 'Reconciliation updated successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $res
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reconciliations",
     *     summary="Get reconciliations",
     *     description="Fetch reconciliations for the logged-in user",
     *     tags={"Reconciliation"},
     *     security={{ "bearerAuth":{} }},
     *     @OA\Response(
     *         response=200,
     *         description="Reconciliations fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciled records fetched successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function listUserReconciliations(Request $request)
    {
        return $this->reconciliationService->fetchUserReconciliations($request->user());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reconciliations/{reconciliation}/summary",
     *     summary="Get reconciliation summary",
     *     description="Fetch reconciliation summary for the logged-in user",
     *     tags={"Reconciliation"},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Reconciliation summary fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation summary fetched successfully"),
     *            @OA\Property(property="status", type="string", example="success"),
     *            @OA\Property(property="status_code", type="integer", example=200),
     *            @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="matches", type="integer", example=5),
     *                 @OA\Property(property="only_in_ledger", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="only_in_statement", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function getSummary(Reconciliation $reconciliation)
    {
        return $this->reconciliationService->fetchDetails($reconciliation);
    }

    /**
     * @OA\Post(
     *     path="/api/reconciliations/{reconciliation}/start",
     *     tags={"Reconciliation"},
     *     summary="Start reconciliation process",
     *     description="Dispatches a job to perform AI-powered matching of existing statements and ledgers in a reconciliation",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         description="ID of the reconciliation to process",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Reconciliation process started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation process started successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=202),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="reconciliation_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation must have at least one ledger"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=400)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Authorization error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not own this reconciliation"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=403)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to start reconciliation process"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=500),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="error", type="string", example="Error message details")
     *             )
     *         )
     *     )
     * )
     */
    public function processWithEmbeddings(Reconciliation $reconciliation)
    {
        $user = Auth::user();

        try {
            // Quick validation before dispatching job
            if ($reconciliation->user_id !== $user->id) {
                return response()->json([
                    'message' => 'You do not own this reconciliation',
                    'status' => 'error',
                    'status_code' => 403
                ], 403);
            }

            if ($reconciliation->ledgers()->count() === 0) {
                return response()->json([
                    'message' => 'Reconciliation must have at least one ledger',
                    'status' => 'error',
                    'status_code' => 400
                ], 400);
            }

            if ($reconciliation->statementFiles()->count() === 0) {
                return response()->json([
                    'message' => 'Reconciliation must have at least one statement file',
                    'status' => 'error',
                    'status_code' => 400
                ], 400);
            }

            ProcessDraftReconciliation::dispatch($reconciliation, $user);

            return response()->json([
                'message' => 'Reconciliation process started successfully',
                'status' => 'success',
                'status_code' => 202,
                'data' => [
                    'reconciliation_id' => $reconciliation->id
                ]
            ], 202);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to start reconciliation process',
                'status' => 'error',
                'status_code' => 500,
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }


    private function isValidFileFormat(string $filePath): bool
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), ['csv', 'xls', 'xlsx']);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/reconciliations/{reconciliation}",
     *     summary="Delete a reconciliation",
     *     description="Delete a reconciliation by ID",
     *     tags={"Reconciliation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reconciliation",
     *         in="path",
     *         required=true,
     *         description="Reconciliation ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reconciliation deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation deleted successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="status_code", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="You do not own this reconciliation"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=403)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reconciliation not found"),
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="status_code", type="integer", example=404)
     *         )
     *     )
     * )
     */
    public function destroy(Reconciliation $reconciliation)
    {
        $user = auth()->user();

        if ($reconciliation->user_id !== $user->id) {
            return response()->json([
                'message' => 'You do not own this reconciliation',
                'status' => 'error',
                'status_code' => 403
            ], 403);
        }

        try {
            $reconciliation->delete();

            return response()->json([
                'message' => 'Reconciliation deleted successfully',
                'status' => 'success',
                'status_code' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete reconciliation',
                'status' => 'error',
                'status_code' => 500,
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
