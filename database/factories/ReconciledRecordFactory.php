<?php

namespace Database\Factories;

use App\Models\ReconciledRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReconciledRecordFactory extends Factory
{
    protected $model = ReconciledRecord::class;

    public function definition()
    {
        return [
            'reconciliation_id' => \App\Models\Reconciliation::factory(), // Creates a related User if not provided
            'data' => [
                "reconciliation_id" => "7cb9206c-0b6e-461e-b22a-8767b614cd59",
                "matches" => [
                    [
                        "statements" => [
                            [
                                "statement" => [
                                    "id" => "3b9a0a8d-46bf-4656-91b5-12103f25b82e",
                                    "Date" => "2024-12-11",
                                    "Description" => "Subscription",
                                    "Amount" => "63155"
                                ],
                                "score" => "96%"
                            ]
                        ],
                        "ledgers" => [
                            [
                                "ledger" => [
                                    "id" => "66ac8e0f-fa08-4c63-a025-97bcb21eda46",
                                    "Date" => "2024-12-11",
                                    "Description" => "Subscription Provider",
                                    "Amount" => "42223"
                                ],
                                "score" => "96%"
                            ]
                        ]
                    ],
                    [
                        "statements" => [
                            [
                                "statement" => [
                                    "id" => "5c6c7dae-5a21-4601-8df3-8dbcd5d8a993",
                                    "Date" => "2024-12-10",
                                    "Description" => "Office Supplies",
                                    "Amount" => "57279"
                                ],
                                "score" => "87%"
                            ]
                        ],
                        "ledgers" => [
                            [
                                "ledger" => [
                                    "id" => "d7f04a20-6dd2-442a-af3e-bba271864f6f",
                                    "Date" => "2024-12-06",
                                    "Description" => "Invoice Recipient",
                                    "Amount" => "12459"
                                ],
                                "score" => "87%"
                            ]
                        ]
                    ],
                    [
                        "statements" => [
                            [
                                "statement" => [
                                    "id" => "6117dd97-3b9d-4d5f-8bdd-55abe08ade5a",
                                    "Date" => "2024-12-02",
                                    "Description" => "Office Supplies",
                                    "Amount" => "93785"
                                ],
                                "score" => "88%"
                            ]
                        ],
                        "ledgers" => [
                            [
                                "ledger" => [
                                    "id" => "e688bbeb-7815-4f0f-81b8-df08e465726b",
                                    "Date" => "2024-12-03",
                                    "Description" => "Company Employee",
                                    "Amount" => "91009"
                                ],
                                "score" => "88%"
                            ]
                        ]
                    ],
                    [
                        "statements" => [
                            [
                                "statement" => [
                                    "id" => "8045ce70-2f40-48f5-a0f5-cfb33a0ca82a",
                                    "Date" => "2024-12-10",
                                    "Description" => "Invoice Payment",
                                    "Amount" => "61326"
                                ],
                                "score" => "97%"
                            ]
                        ],
                        "ledgers" => [
                            [
                                "ledger" => [
                                    "id" => "7175d808-5000-4c4b-961b-a1a71bce4178",
                                    "Date" => "2024-12-10",
                                    "Description" => "Invoice Recipient",
                                    "Amount" => "61326"
                                ],
                                "score" => "97%"
                            ]
                        ]
                    ],
                    [
                        "statements" => [
                            [
                                "statement" => [
                                    "id" => "85304245-07aa-403a-8514-719c419a6c25",
                                    "Date" => "2024-12-11",
                                    "Description" => "Invoice Payment",
                                    "Amount" => "60320"
                                ],
                                "score" => "96%"
                            ]
                        ],
                        "ledgers" => [
                            [
                                "ledger" => [
                                    "id" => "7175d808-5000-4c4b-961b-a1a71bce4178",
                                    "Date" => "2024-12-10",
                                    "Description" => "Invoice Recipient",
                                    "Amount" => "61326"
                                ],
                                "score" => "96%"
                            ]
                        ]
                    ],
                    [
                        "statements" => [
                            [
                                "statement" => [
                                    "id" => "96634cfb-34a6-413f-a2e4-421df2283d89",
                                    "Date" => "2024-12-10",
                                    "Description" => "Subscription",
                                    "Amount" => "39693"
                                ],
                                "score" => "96%"
                            ]
                        ],
                        "ledgers" => [
                            [
                                "ledger" => [
                                    "id" => "66ac8e0f-fa08-4c63-a025-97bcb21eda46",
                                    "Date" => "2024-12-11",
                                    "Description" => "Subscription Provider",
                                    "Amount" => "42223"
                                ],
                                "score" => "96%"
                            ]
                        ]
                    ],
                    [
                        "statements" => [
                            [
                                "statement" => [
                                    "id" => "d71bb9e0-8c23-4d08-af15-1309fa19fe0b",
                                    "Date" => "2024-12-08",
                                    "Description" => "Invoice Payment",
                                    "Amount" => "15922"
                                ],
                                "score" => "96%"
                            ]
                        ],
                        "ledgers" => [
                            [
                                "ledger" => [
                                    "id" => "d7f04a20-6dd2-442a-af3e-bba271864f6f",
                                    "Date" => "2024-12-06",
                                    "Description" => "Invoice Recipient",
                                    "Amount" => "12459"
                                ],
                                "score" => "96%"
                            ]
                        ]
                    ]
                ],
                "unmatched_ledgers" => [
                    "1" => [
                        "id" => "43b1727b-82a0-4661-a975-2052745f4f21",
                        "Date" => "2024-12-03",
                        "Description" => "Company Employee",
                        "Amount" => "91009"
                    ],
                    "2" => [
                        "id" => "009c8055-c199-4ba8-9171-a834aeee0a9d",
                        "Date" => "2024-12-08",
                        "Description" => "Invoice Recipient",
                        "Amount" => "89887"
                    ],
                    "4" => [
                        "id" => "cf0268d0-2752-48ac-8b4f-1fb9ff8ecf46",
                        "Date" => "2024-12-03",
                        "Description" => "Company Employee",
                        "Amount" => "91009"
                    ],
                    "6" => [
                        "id" => "6ea69134-7d4b-47a4-9384-e3a3e550d789",
                        "Date" => "2024-12-10",
                        "Description" => "Consultant",
                        "Amount" => "77628"
                    ]
                ],
                "unmatched_statements" => [],
                "summary" => [
                    "totalMatched" => 7,
                    "totalUnmatched" => 4,
                    "total" => 11
                ]
            ]
        ];
    }
}
