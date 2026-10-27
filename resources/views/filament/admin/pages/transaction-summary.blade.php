<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filament Form for filters - Use x-filament::card for consistent theming --}}
        <x-filament::card>
            {{ $this->form }}
        </x-filament::card>

        {{-- Display the transaction summary data --}}
        @if (!empty($transactionsSummary))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Data Transactions Overview --}}
                <x-filament::card class="col-span-full">
                    <x-slot:heading>Data Transactions Overview</x-slot:heading>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach(['MTN', 'AIRTEL', 'GLO', '9MOBILE'] as $network)
                            {{-- Added dark:bg-gray-900 for card backgrounds and ensured text colors for dark mode --}}
                            <div class="border rounded-lg p-4 bg-white dark:bg-gray-900 shadow-sm">
                                <h3 class="font-bold text-lg mb-2 text-primary-600 dark:text-primary-400">{{ $network }} Data</h3>
                                {{-- Ensured dark:text-gray-200 or dark:text-white for better visibility --}}
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>Gifting (GB):</strong> {{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_g'] ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>Gifting (Bal):</strong> ₦{{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_g_bal'] ?? '0.00' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>SME (GB):</strong> {{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_sme'] ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>SME (Bal):</strong> ₦{{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_sme_bal'] ?? '0.00' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>Corporate Gifting (GB):</strong> {{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_cg'] ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>Corporate Gifting (Bal):</strong> ₦{{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_cg_bal'] ?? '0.00' }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>

                {{-- Airtime Transactions Overview --}}
                <x-filament::card class="col-span-full">
                    <x-slot:heading>Airtime Transactions Overview</x-slot:heading>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach(['MTN', 'AIRTEL', 'GLO', '9MOBILE'] as $network)
                            <div class="border rounded-lg p-4 bg-white dark:bg-gray-900 shadow-sm">
                                <h3 class="font-bold text-lg mb-2 text-primary-600 dark:text-primary-400">{{ $network }} Airtime</h3>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>VTU Amount:</strong> ₦{{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_vtu'] ?? '0.00' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>VTU Discount:</strong> ₦{{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_vtu_d'] ?? '0.00' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>SNS Amount:</strong> ₦{{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_sns'] ?? '0.00' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>SNS Discount:</strong> ₦{{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_sns_d'] ?? '0.00' }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>

                {{-- Cable Subscriptions --}}
                <x-filament::card>
                    <x-slot:heading>Cable Subscriptions</x-slot:heading>
                    <ul class="space-y-2">
                        @foreach(['DSTV', 'GOTV', 'STARTIME'] as $cable)
                            <li>
                                <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $cable }}:</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200 ml-4">Amount: ₦{{ $transactionsSummary[strtolower($cable)] ?? '0.00' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200 ml-4">Charges: ₦{{ $transactionsSummary[strtolower($cable) . '_c'] ?? '0.00' }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-filament::card>

                {{-- Exam Pins --}}
                <x-filament::card>
                    <x-slot:heading>Exam Pins</x-slot:heading>
                    <ul class="space-y-2">
                        @foreach(['WAEC', 'NECO', 'NABTEB'] as $exam)
                            <li>
                                <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $exam }}:</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200 ml-4">Amount: ₦{{ $transactionsSummary[strtolower($exam)] ?? '0.00' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200 ml-4">Quantity: {{ $transactionsSummary[strtolower($exam) . '_q'] ?? '0' }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-filament::card>

                {{-- Recharge Card Sales --}}
                <x-filament::card class="col-span-full">
                    <x-slot:heading>Recharge Card Sales</x-slot:heading>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach(['MTN', 'AIRTEL', 'GLO', '9MOBILE'] as $network)
                            <div class="border rounded-lg p-4 bg-white dark:bg-gray-900 shadow-sm">
                                <h3 class="font-bold text-lg mb-2 text-primary-600 dark:text-primary-400">{{ $network }} Recharge Cards</h3>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>Amount:</strong> ₦{{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_recharge_amount'] ?? '0.00' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>Quantity:</strong> {{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_recharge_quantity'] ?? '0' }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>

                {{-- Data Card Sales --}}
                <x-filament::card class="col-span-full">
                    <x-slot:heading>Data Card Sales</x-slot:heading>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach(['MTN', 'AIRTEL', 'GLO', '9MOBILE'] as $network)
                            <div class="border rounded-lg p-4 bg-white dark:bg-gray-900 shadow-sm">
                                <h3 class="font-bold text-lg mb-2 text-primary-600 dark:text-primary-400">{{ $network }} Data Cards</h3>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>Amount:</strong> ₦{{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_datacard_amount'] ?? '0.00' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>Quantity:</strong> {{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_datacard_quantity'] ?? '0' }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-200"><strong>Total GB:</strong> {{ $transactionsSummary[strtolower($network == '9MOBILE' ? 'mobile': $network) . '_datacard_gb'] ?? '0.000GB' }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>

                {{-- Other Simple Summaries --}}
                <x-filament::card>
                    <x-slot:heading>Other Transactions</x-slot:heading>
                    <ul class="space-y-2">
                        <li class="text-gray-700 dark:text-gray-200"><strong>Bulk SMS:</strong> ₦{{ $transactionsSummary['bulksms'] ?? '0.00' }}</li>
                        <li class="text-gray-700 dark:text-gray-200"><strong>Bills Payment:</strong> ₦{{ $transactionsSummary['bill'] ?? '0.00' }}</li>
                        <li class="text-gray-700 dark:text-gray-200"><strong>Airtime to Cash (Amount):</strong> ₦{{ $transactionsSummary['cash_amount'] ?? '0.00' }}</li>
                        <li class="text-gray-700 dark:text-gray-200"><strong>Airtime to Cash (Payout):</strong> ₦{{ $transactionsSummary['cash_pay'] ?? '0.00' }}</li>
                    </ul>
                </x-filament::card>

                {{-- Deposit and Spending Summaries --}}
                <x-filament::card>
                    <x-slot:heading>Financial Overview</x-slot:heading>
                    <ul class="space-y-2">
                        <li class="text-gray-700 dark:text-gray-200"><strong>Total Deposits:</strong> ₦{{ $transactionsSummary['deposit_amount'] ?? '0.00' }}</li>
                        <li class="text-gray-700 dark:text-gray-200"><strong>Deposit Charges:</strong> ₦{{ $transactionsSummary['deposit_charges'] ?? '0.00' }}</li>
                        <li class="text-gray-700 dark:text-gray-200"><strong>Total Spent:</strong> ₦{{ $transactionsSummary['spend_amount'] ?? '0.00' }}</li>
                        <li class="text-gray-700 dark:text-gray-200"><strong>Deposit % of Total:</strong> {{ $transactionsSummary['deposit_trans'] ?? '0.0' }}%</li>
                        <li class="text-gray-700 dark:text-gray-200"><strong>Spending % of Total:</strong> {{ $transactionsSummary['spend_trans'] ?? '0.0' }}%</li>
                    </ul>
                </x-filament::card>
            </div>
        @else
            <x-filament::card>
                <p class="text-gray-700 dark:text-gray-200">No transaction data available for the selected filters.</p>
            </x-filament::card>
        @endif
    </div>
</x-filament-panels::page>
