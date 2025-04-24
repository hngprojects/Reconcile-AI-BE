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
     *                     property="bank_statements[0][period]",
     *                     type="string"
     *                 ),
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
    public function testEmbeddings(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'bank_statements' => 'required|array',
            'ledgers' => 'required|array',
            'mapper' => 'required',
            'mapper.date' => 'required|string',
            'mapper.description' => 'required|string',
            'mapper.amount' => 'required|string',
            'bank_statements.*.file' => 'required|file|mimes:csv|max:2048',
            'bank_statements.*.bank_account' => 'required|uuid|exists:bank_accounts,id',
            'bank_statements.*.period' => 'required|string',
            'ledgers.*' => 'required|string|exists:bookkeeping_ledgers,id',
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
                    'path' => $statementFullPath,
                    'bank_account_id' => $bankAccount,
                    'period' => $period,
                ];
            }

            $reconciliation = $this->reconciliationService->storeReconciliation($statements, $ledgers,  $request->input('title'), $request->user()->id);
            ProcessReconciliation::dispatch($statements, $ledgers, $request->input('mapper'), $request->user(), $reconciliation, $this->reconciliationRepository);

            return response()->json([
                "message" => "Reconciliation initiated successfully",
                "status" => "success",
                "status_code" => 200,
                'data' => [
                    'reconciliation_id' => $reconciliation->id
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


    private function isValidFileFormat(string $filePath): bool
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), ['csv', 'xls', 'xlsx']);
    }
}
