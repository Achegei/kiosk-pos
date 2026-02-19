<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
    <div class="bg-white shadow rounded-lg p-4">
        <h3 class="text-sm text-gray-500">Daily Sales</h3>
        <p class="text-2xl font-semibold text-green-600">${{ number_format($dailySales, 2) }}</p>
    </div>
    <div class="bg-white shadow rounded-lg p-4">
        <h3 class="text-sm text-gray-500">Daily Credit Sales</h3>
        <p class="text-2xl font-semibold text-yellow-500">${{ number_format($dailyCreditSales, 2) }}</p>
    </div>
    <div class="bg-white shadow rounded-lg p-4">
        <h3 class="text-sm text-gray-500">Total Revenue</h3>
        <p class="text-2xl font-semibold text-blue-600">${{ number_format($totalRevenue, 2) }}</p>
    </div>
    <div class="bg-white shadow rounded-lg p-4">
        <h3 class="text-sm text-gray-500">Active Customers</h3>
        <p class="text-2xl font-semibold text-purple-600">{{ $activeCustomers }}</p>
    </div>
    <div class="bg-white shadow rounded-lg p-4">
        <h3 class="text-sm text-gray-500">Money In / Out</h3>
        <p class="text-2xl font-semibold text-indigo-600">${{ number_format($moneyIn, 2) }} / ${{ number_format($moneyOut, 2) }}</p>
    </div>
</div>
