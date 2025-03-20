<?php

namespace App\Http\Controllers;

use App\Services\ReconciliationService;
use App\Services\NewReconciliation\NewReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use App\Models\ReconciledRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Reconciliation;
use App\Http\Requests\ManualReconciliationRequest;
use App\Jobs\ProcessReconciliation;
use App\Jobs\ProcessRecoxReconciliation;

use App\Jobs\ReconcileJob;
use App\Models\ReconciliationProject;
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
    protected $testService;

    public function __construct(ReconciliationService $reconciliationService, NewReconciliationService $testService)
    {
        $this->reconciliationService = $reconciliationService;
        $this->testService = $testService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reconcile",
     *     summary="Reconcile two CSV or Excel files",
     *     description="Upload and compare two files using various reconciliation methods",
     *     tags={"Reconciliation"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file1", "file2"},
     *                 @OA\Property(
     *                     property="file1",
     *                     type="string",
     *                     format="binary",
     *                     description="First CSV or Excel file"
     *                 ),
     *                 @OA\Property(
     *                     property="file2",
     *                     type="string",
     *                     format="binary",
     *                     description="Second CSV or Excel file"
     *                 ),
     *                 @OA\Property(
     *                     property="reconcile_option",
     *                     type="string",
     *                     enum={
     *                         "reconcile_with_Gemini",
     *                         "reconcile_with_recox_ai",
     *                         "reconcile_with_openAI",
     *                         "reconcile_with_deepSeek"
     *                     },
     *                     description="Reconciliation method to use"
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
     *                 @OA\Property(property="only_in_file1", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="only_in_file2", type="array", @OA\Items(type="object"))
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
    public function reconcile(Request $request): JsonResponse
    {
        $request->validate([
            'file1' => 'required|file|mimes:csv|max:2048',
            'file2' => 'required|file|mimes:csv|max:2048',
            'reconcile_option' => 'nullable|in:reconcile_with_recox_ai,reconcile_with_openAI,reconcile_with_deepSeek,reconcile_with_Gemini',
        ], [
            'file1.mimes' => 'File 1 must be a CSV.',
            'file2.mimes' => 'File 2 must be a CSV.',
            'file1.max' => 'File 1 must not be larger than 2MB.',
            'file2.max' => 'File 2 must not be larger than 2MB.',
        ]);

        try {
            $file1Path = $request->file('file1')->store('uploads');
            $file2Path = $request->file('file2')->store('uploads');

            $file1FullPath = Storage::path($file1Path);
            $file2FullPath = Storage::path($file2Path);

            if (!$this->isValidFileFormat($file1FullPath) || !$this->isValidFileFormat($file2FullPath)) {
                Storage::delete([$file1Path, $file2Path]);
                return response()->json(['error' => 'One or both files are not in the correct format.'], 422);
            }

            $reconcileOption = $request->input('reconcile_option', 'reconcile_with_Gemini');

            $result = match ($reconcileOption) {
                'reconcile_with_recox_ai' => $this->reconciliationService->reconcileWithRecox($file1FullPath, $file2FullPath),
                'reconcile_with_openAI' => $this->reconciliationService->reconcileWithOpenAI($file1FullPath, $file2FullPath),
                'reconcile_with_deepSeek' => $this->reconciliationService->reconcileWithDeepSeek($file1FullPath, $file2FullPath),
                default => $this->reconciliationService->reconcileWithGemini($file1FullPath, $file2FullPath),
            };

            $reconciliation = $this->reconciliationService->store([
                'user' => Auth::id(),
                'statement' => $file1FullPath,
                'ledger' => $file2FullPath,
                'ai' => $reconcileOption,
                'response' => $result
            ]);

            return response()->json([
                "message" => "Reconciliation successful",
                "status" => "success",
                "status_code" => 200,
                'data' => [
                    'reconciliation_id' => $reconciliation->id,
                    ...$result
                ]
            ], 200);
        } catch (\Exception $e) {
            if (isset($file1Path, $file2Path)) {
                Storage::delete([$file1Path, $file2Path]);
            }
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'statement_file' => 'required|file|mimes:csv|max:2048',
            'ledger_file' => 'required|file|mimes:csv|max:2048',
            'ai_option' => 'required|string',
        ], [
            'statement_file.mimes' => 'Statement file must be a CSV.',
            'ledger_file.mimes' => 'Ledger file must be a CSV.',
            'statement_file.max' => 'Statement file must not be larger than 2MB.',
            'ledger_file.max' => 'Ledger file must not be larger than 2MB.',
        ]);
    
        try {
            $statementPath = $request->file('statement_file')->store('uploads');
            $ledgerPath = $request->file('ledger_file')->store('uploads');
    
            $statementFullPath = Storage::path($statementPath);
            $ledgerFullPath = Storage::path($ledgerPath);
    
            Log::info('Files stored successfully', [
                'statement_path' => $statementFullPath,
                'ledger_path' => $ledgerFullPath,
            ]);
    
            $project = ReconciliationProject::create([
                'user_id' => 1,
                'status' => 'pending',
                'progress' => 0,
                'statement_file' => $statementPath,
                'ledger_file' => $ledgerPath,
                'ai_option' => $request->ai_option,
            ]);
    
            ReconcileJob::dispatch($project);
    
            return response()->json([
                'message' => 'Reconciliation started',
                'project_id' => $project->id
            ], 200);
        } catch (\Exception $e) {
            if (isset($statementPath, $ledgerPath)) {
                Storage::delete([$statementPath, $ledgerPath]);
            }
            Log::error('Failed to store files or process job', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show(ReconciliationProject $project)
    {
        return response()->json($project->load('statementRecords', 'ledgerRecords'));
    }

    /**
 * @OA\Post(
 *     path="/api/v1/reconcile/export",
 *     summary="Export reconciled data as a CSV file",
 *     description="Generates a CSV file containing matched and unmatched transactions from reconciliation.",
 *     tags={"Reconciliation"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"data"},
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(
 *                     property="matches",
 *                     type="array",
 *                     @OA\Items(
 *                         type="object",
 *                         @OA\Property(property="file1_transaction", type="object",
 *                             @OA\Property(property="Date", type="string", example="12/4/2023"),
 *                             @OA\Property(property="Description", type="string", example="Test"),
 *                             @OA\Property(property="Amount", type="number", example=650)
 *                         ),
 *                         @OA\Property(property="status", type="string", example="Matched"),
 *                         @OA\Property(property="file2_transaction", type="object",
 *                             @OA\Property(property="Date", type="string", example="12/4/2023"),
 *                             @OA\Property(property="Description", type="string", example="Test"),
 *                             @OA\Property(property="Amount", type="number", example=650)
 *                         )
 *                     )
 *                 ),
 *                 @OA\Property(
 *                     property="unmatched",
 *                     type="object",
 *                     @OA\Property(property="unmatched_file1", type="array", @OA\Items(type="object")),
 *                     @OA\Property(property="unmatched_file2", type="array", @OA\Items(type="object"))
 *                 ),
 *                 @OA\Property(property="only_in_file1", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="only_in_file2", type="array", @OA\Items(type="object"))
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="CSV file generated successfully",
 *         @OA\Header(header="Content-Disposition", description="attachment; filename=reconciled-data.csv", @OA\Schema(type="string"))
 *     ),
 *     @OA\Response(response=400, description="Invalid request data"),
 *     @OA\Response(response=500, description="Server error while generating CSV file")
 * )
 */
    public function export(Request $request){
        try {
            $request->validate([
                'data' => 'required|array',
                'data.matches' => 'array',
                'data.unmatched' => 'array',
                'data.unmatched.unmatched_file1' => 'array',
                'data.unmatched.unmatched_file2' => 'array',
                'data.matches.*.file1_transaction' => 'array',
                'data.matches.*.file2_transaction' => 'array',
                'data.matches.*.status' => 'string',
            ]);

            return $this->reconciliationService->generateExport($request->input('data'));
        } catch(\Exception $e) {
            if(get_class($e) == "Illuminate\Validation\ValidationException"){
                 return response()->json([
                    "message" => "Failed to generate report",
                    "status" => "error",
                    "status_code" => 422,
                    'data' => [
                        'error' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : $e->getMessage()
                    ]
                ], 422);
            }
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
        $records = ReconciledRecord::where('reconciliation_id', $reconciliation->id)->get();

        return response()->json([
            'message' => 'Reconciled records fetched successfully',
            'status' => 'success',
            'status_code' => 200,
            'data' => $records,
        ], 200);
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
     *                 required={"ledger", "statement", "action"},
     *                 @OA\Property(
     *                     property="ledger",
     *                     type="object",
     *                     @OA\Property(property="Date", type="string", description="Date of the ledger entry"),
     *                     @OA\Property(property="Person", type="string", description="Description of the ledger entry"),
     *                     @OA\Property(property="Amount", type="integer", description="Amount in the ledger entry")
     *                 ),
     *                 @OA\Property(
     *                     property="statement",
     *                     type="object",
     *                     @OA\Property(property="Date", type="string", description="Date of the statement entry"),
     *                     @OA\Property(property="Person", type="string", description="Description of the statement entry"),
     *                     @OA\Property(property="Amount", type="integer", description="Amount in the statement entry")
     *                 ),
     *                 @OA\Property(
     *                     property="action",
     *                     type="string",
     *                     enum={"match", "unmatch"},
     *                     description="Reconciliation action to perform"
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
    public function matchUnmatch(ManualReconciliationRequest $request, Reconciliation $reconciliation){
        $validated = $request->validated();

        return $this->reconciliationService->matchUnmatch($validated, $reconciliation);
    }


    /**
 * @OA\Post(
 *     path="/api/v1/reconcile-test",
 *     summary="New Reconciliation Approach - Recox",
 *     description="Upload and compare two files using various reconciliation methods",
 *     tags={"Reconciliation"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"bank_statement", "ledger"},
 *                 @OA\Property(
 *                     property="bank_statement",
 *                     type="string",
 *                     format="binary",
 *                     description="Bank Statement CSV file"
 *                 ),
 *                 @OA\Property(
 *                     property="ledger",
 *                     type="string",
 *                     format="binary",
 *                     description="Ledger CSV file"
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
 *                 @OA\Property(property="unmatched_statements", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="unmatched_ledgers", type="array", @OA\Items(type="object"))
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
    public function test(Request $request): JsonResponse
    {
        $request->validate([
            'bank_statement' => 'required|file|mimes:csv|max:2048',
            'ledger' => 'required|file|mimes:csv|max:2048',
        ], [
            'bank_statement.mimes' => 'File 1 must be a CSV.',
            'ledger.mimes' => 'File 2 must be a CSV.',
            'bank_statement.max' => 'File 1 must not be larger than 2MB.',
            'ledger.max' => 'File 2 must not be larger than 2MB.',
        ]);

        try {
            $statementPath = $request->file('bank_statement')->store('uploads');
            $ledgerPath = $request->file('ledger')->store('uploads');

            $statementFullPath = Storage::path($statementPath);
            $ledgerFullPath = Storage::path($ledgerPath);

            if (!$this->isValidFileFormat($statementFullPath) || !$this->isValidFileFormat($ledgerFullPath)) {
                Storage::delete([$statementPath, $ledgerPath]);
                return response()->json(['error' => 'One or both files are not in the correct format.'], 422);
            }

            $result = ProcessRecoxReconciliation::dispatch($statementFullPath, $ledgerFullPath);


            return response()->json([
                "message" => "Reconciliation successful",
                "status" => "success",
                "status_code" => 200,
                'data' => [
                    ...$result
                ]
            ], 200);
        } catch (\Exception $e) {
            if (isset($statementPath, $ledgerPath)) {
                Storage::delete([$statementPath, $ledgerPath]);
            }
            \Log::error('Failed to reconcile ' . $e);
            return response()->json([
                "message" => "Failed to reconcile",
                "status" => "success",
                "status_code" => 500,
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 400);
        }
    }

   /**
 * @OA\Post(
 *     path="/api/v1/reconcile-embeddings",
 *     summary="New Reconciliation Approach - Embeddings",
 *     description="Upload and compare two files using various reconciliation methods",
 *     tags={"Reconciliation"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={"bank_statement", "ledger"},
 *                 @OA\Property(
 *                     property="bank_statement",
 *                     type="string",
 *                     format="binary",
 *                     description="Bank Statement CSV file"
 *                 ),
 *                 @OA\Property(
 *                     property="ledger",
 *                     type="string",
 *                     format="binary",
 *                     description="Ledger CSV file"
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
 *                 @OA\Property(property="unmatched_statements", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="unmatched_ledgers", type="array", @OA\Items(type="object"))
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
    public function testEmbeddings(Request $request): JsonResponse
    {
        $request->validate([
            'bank_statement' => 'required|file|mimes:csv|max:2048',
            'ledger' => 'required|file|mimes:csv|max:2048',
        ], [
            'bank_statement.mimes' => 'File 1 must be a CSV.',
            'ledger.mimes' => 'File 2 must be a CSV.',
            'bank_statement.max' => 'File 1 must not be larger than 2MB.',
            'ledger.max' => 'File 2 must not be larger than 2MB.',
        ]);

        try {
            $statementPath = $request->file('bank_statement')->store('uploads');
            $ledgerPath = $request->file('ledger')->store('uploads');

            $statementFullPath = Storage::path($statementPath);
            $ledgerFullPath = Storage::path($ledgerPath);

            if (!$this->isValidFileFormat($statementFullPath) || !$this->isValidFileFormat($ledgerFullPath)) {
                Storage::delete([$statementPath, $ledgerPath]);
                return response()->json(['error' => 'One or both files are not in the correct format.'], 422);
            }

            $user = $request->user();
            $result = ProcessReconciliation::dispatch($statementFullPath, $ledgerFullPath, $user);

            return response()->json([
                "message" => "Reconciliation successful",
                "status" => "success",
                "status_code" => 200,
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            if (isset($statementPath, $ledgerPath)) {
                Storage::delete([$statementPath, $ledgerPath]);
            }
            \Log::error('Failed to reconcile ' . $e);
            return response()->json([
                "message" => "Failed to reconcile",
                "status" => "success",
                "status_code" => 500,
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 400);
        }
    }



    private function isValidFileFormat(string $filePath): bool
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return in_array(strtolower($extension), ['csv', 'xls', 'xlsx']);
    }

}
