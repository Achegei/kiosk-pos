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