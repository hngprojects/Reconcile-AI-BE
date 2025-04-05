<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *    title="Reconcile AI Apis",
 *    version="1.0.0",
 * )
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="bearerAuth",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
/**
 * @OA\Schema(
 *     schema="AccountSetupRequest",
 *     required={"business_name", "business_type", "currency", "reporting_year", "bank_name", "account_name", "account_number", "opening_balance", "ledger_types"},
 *     @OA\Property(property="business_name", type="string", example="My Business"),
 *     @OA\Property(property="business_type", type="string", example="Retail"),
 *     @OA\Property(property="currency", type="string", example="NGN"),
 *     @OA\Property(property="reporting_year", type="string", enum={"January - December", "April - March", "July - June"}, example="January - December"),
 *     @OA\Property(property="bank_name", type="string", example="GT Bank"),
 *     @OA\Property(property="account_name", type="string", example="Business Account"),
 *     @OA\Property(property="account_number", type="string", example="1234567890"),
 *     @OA\Property(property="opening_balance", type="number", format="float", example=50000),
 *     @OA\Property(
 *         property="ledger_types",
 *         type="array",
 *         @OA\Items(type="string", enum={"general", "vendor", "customer"}, example="general")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="AccountSetupResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Account setup completed successfully"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="business_info", ref="#/components/schemas/BusinessInfo"),
 *         @OA\Property(property="bank_account", ref="#/components/schemas/BankAccount"),
 *         @OA\Property(
 *             property="ledgers",
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/BookkeepingLedger")
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="BusinessInfo",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="type", type="string"),
 *     @OA\Property(property="reporting_year", type="string"),
 *     @OA\Property(property="currency", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="BankAccount",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="bank_name", type="string"),
 *     @OA\Property(property="account_name", type="string"),
 *     @OA\Property(property="account_number", type="string"),
 *     @OA\Property(property="opening_balance", type="number", format="float")
 * )
 *
 * @OA\Schema(
 *     schema="BookkeepingLedger",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="type", type="string", enum={"general", "vendor", "customer"}),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(property="is_default", type="boolean")
 * )
 */
abstract class Controller
{
    //
}
