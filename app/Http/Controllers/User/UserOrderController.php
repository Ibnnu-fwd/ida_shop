<?php

namespace App\Http\Controllers\User;

use App\Events\OrderStatusEvent;
use App\Http\Controllers\Controller;
use App\Interfaces\ReviewInterface;
use App\Interfaces\TransactionInterface;
use App\Models\ConfigurationStore;
use Illuminate\Http\Request;

class UserOrderController extends Controller
{
    private $transaction;
    private $review;

    public function __construct(TransactionInterface $transaction, ReviewInterface $review)
    {
        $this->transaction = $transaction;
        $this->review = $review;
    }

    public function index()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        return view('customer.order', [
            'storeConfiguration' => ConfigurationStore::first(),
            'orders' => $this->transaction->getTransactionByUserId(auth()->user()->id),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json($this->transaction->getById($id));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->transaction->destroy($id);
        return response()->json([
            'message' => 'Transaksi berhasil dihapus'
        ]);
    }

    public function confirm(Request $request, string $id)
    {
        $this->transaction->confirm($id, $request->all());
        $transaction = $this->transaction->getById($id);
        event(new OrderStatusEvent($transaction->transaction_code, 'Pesanan dibayar', $transaction->user_id));
        return redirect()->back()->with('success', 'Transaksi berhasil dibayar');
    }

    public function review(Request $request, string $id)
    {
        try {
            $this->review->store($request->all(), $id);
            return response()->json([
                'status' => 'success',
                'message' => 'Review berhasil ditambahkan'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ]);
        }
    }

    public function filterStatus(Request $request)
    {
        $orders = $this->transaction->getTransactionByUserId(auth()->user()->id);

        return view('customer.detail_order', [
            'orders' => $orders->where('status', $request->status),
            'storeConfiguration' => ConfigurationStore::first(),
        ])->render();
    }

    public function print(string $id)
    {
        return view('customer.print-order', [
            'order' => $this->transaction->getById($id),
            'configuration' => ConfigurationStore::first(),
        ]);
    }
}
