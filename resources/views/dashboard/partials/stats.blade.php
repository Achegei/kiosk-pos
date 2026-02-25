<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
    <div class="bg-white shadow-md rounded-xl p-5 hover:shadow-xl transition duration-300">
        <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wide">Daily Sales</h3>
        <div class="mt-2 overflow-x-auto">
            <p class="text-lg sm:text-xl font-bold text-green-600 font-mono whitespace-nowrap">
                KES {{ number_format($dailySales, 2) }}
            </p>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-xl p-5 hover:shadow-xl transition duration-300">
        <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wide">Daily Credit Sales</h3>
        <div class="mt-2 overflow-x-auto">
            <p class="text-lg sm:text-xl font-bold text-yellow-500 font-mono whitespace-nowrap">
                KES {{ number_format($dailyCreditSales, 2) }}
            </p>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-xl p-5 hover:shadow-xl transition duration-300">
        <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wide">Total Revenue</h3>
        <div class="mt-2 overflow-x-auto">
            <p class="text-lg sm:text-xl font-bold text-blue-600 font-mono whitespace-nowrap">
                KES {{ number_format($totalRevenue, 2) }}
            </p>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-xl p-5 hover:shadow-xl transition duration-300">
        <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wide">Active Customers</h3>
        <div class="mt-2 overflow-x-auto">
            <p class="text-lg sm:text-xl font-bold text-purple-600 font-mono whitespace-nowrap">
                {{ $activeCustomers }}
            </p>
        </div>
    </div>

    <div class="bg-white shadow-md rounded-xl p-5 hover:shadow-xl transition duration-300">
        <h3 class="text-xs font-medium text-gray-400 uppercase tracking-wide">Money In / Out</h3>
        <div class="mt-2 overflow-x-auto">
            <p class="text-lg sm:text-xl font-bold text-indigo-600 font-mono whitespace-nowrap">
                KES {{ number_format($moneyIn, 2) }} / KES {{ number_format($moneyOut, 2) }}
            </p>
        </div>
    </div>
</div>

{{-- ================= DEBUG PANEL ================= --}}
<div class="mt-6 bg-gray-50 border border-gray-200 p-4 rounded-xl text-sm sm:text-base font-mono">
    <h3 class="text-gray-700 font-semibold mb-2">Debug Info</h3>
    <p><span class="font-medium">Open Register IDs:</span> {{ $openRegister ? $openRegister->id : 'None' }}</p>
    <p><span class="font-medium">All Open Register IDs:</span> {{ implode(', ', $openRegisters->pluck('id')->toArray()) ?: 'None' }}</p>
    <p><span class="font-medium">Cash Movements:</span></p>
    <ul class="ml-4 list-disc">
        <li>Drops: KES {{ number_format($drops ?? 0, 2) }}</li>
        <li>Expenses: KES {{ number_format($expenses ?? 0, 2) }}</li>
        <li>Payouts: KES {{ number_format($payouts ?? 0, 2) }}</li>
        <li>Deposits: KES {{ number_format($deposits ?? 0, 2) }}</li>
        <li>Adjustments: KES {{ number_format($adjustments ?? 0, 2) }}</li>
    </ul>
    <p>
        <span class="font-medium">Money In:</span> KES {{ number_format($moneyIn ?? 0, 2) }} | 
        <span class="font-medium">Money Out:</span> KES {{ number_format($moneyOut ?? 0, 2) }}
    </p>
</div>