<?php

namespace App\Http\Controllers\Admin;

use App\Events\OrderStatusEvent;
use App\Http\Controllers\Controller;
use App\Interfaces\TransactionInterface;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private $transaction;

    public function __construct(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return datatables()
                ->of($this->transaction->getAll())
                ->addColumn('transaction_code', function ($data) {
                    return $data->transaction_code;
                })
                ->addColumn('payment_method', function ($data) {
                    return $data->payment_method == 1 ? 'E Wallet' : 'COD (Bayar di tempat)';
                })
                ->addColumn('customer', function ($data) {
                    return $data->user->name;
                })
                ->addColumn('receiver', function ($data) {
                    return $data->receiver_name;
                })
                ->addColumn('phone', function ($data) {
                    return $data->receiver_phone;
                })
                ->addColumn('proof_of_payment', function ($data) {
                    return view('admin.order.column.proof_of_payment', ['data' => $data]);
                })
                ->addColumn('status', function ($data) {
                    return view('admin.order.column.status', ['data' => $data]);
                })
                ->addColumn('shipping_cost', function ($data) {
                    return 'Rp. ' . number_format($data->shipping_price, 0, ',', '.');
                })
                ->addColumn('total_payment', function ($data) {
                    return 'Rp. ' . number_format($data->total_payment, 0, ',', '.');
                })
                ->addColumn('address', function ($data) {
                    return $data->receiver_address ?? '-';
                })
                ->addColumn('created_at', function ($data) {
                    return $data->created_at->format('d-m-Y H:i') . ' WIB';
                })
                ->addColumn('detail_transaction', function ($data) {
                    return view('admin.order.column.detail_transaction', ['data' => $data]);
                })
                ->addColumn('action', function ($data) {
                    return view('admin.order.column.action', ['data' => $data]);
                })
                ->addIndexColumn()
                ->make(true);
        }
        return view('admin.order.index', [
            'months' => [
                '01' => 'Januari',
                '02' => 'Februari',
                '03' => 'Maret',
                '04' => 'April',
                '05' => 'Mei',
                '06' => 'Juni',
                '07' => 'Juli',
                '08' => 'Agustus',
                '09' => 'September',
                '10' => 'Oktober',
                '11' => 'November',
                '12' => 'Desember',
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
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
        //
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
        //
    }

    public function changeStatus(Request $request)
    {
        try {
            $this->transaction->changeStatus($request->id, $request->status);
            $transaction = $this->transaction->getById($request->id);
            event(new OrderStatusEvent($transaction->transaction_code, $this->translateStaus($request->status), $transaction->user_id));
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengubah status pesanan ke ' . $request->status
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah status pesanan'
            ]);
        }
    }

    public function translateStaus($status)
    {
        switch ($status) {
            case 'pending':
                return 'Menunggu Pembayaran';
                break;
            case 'paid':
                return 'Dibayar';
                break;
            case 'confirmed':
                return 'Dikonfirmasi';
                break;
            case 'failed':
                return 'Gagal';
                break;
            case 'delivered':
                return 'Dikirim';
                break;
            case 'success':
                return 'Selesai';
                break;
            default:
                return 'Menunggu Pembayaran';
                break;
        }
    }

    public function listDetail(Request $request)
    {
        if ($request->ajax()) {
            return datatables()
                ->of($this->transaction->listDetail())
                ->addColumn('invoice_code', function ($data) {
                    return $data->transaction->transaction_code;
                })
                ->addColumn('customer_name', function ($data) {
                    return $data->transaction->user->name;
                })
                ->addColumn('receiver_name', function ($data) {
                    return $data->transaction->receiver_name;
                })
                ->addColumn('product_name', function ($data) {
                    return $data->product->name;
                })
                ->addColumn('qty', function ($data) {
                    return $data->qty;
                })
                ->addColumn('price', function ($data) {
                    return 'Rp. ' . number_format($data->price, 0, ',', '.');
                })
                ->addColumn('subtotal', function ($data) {
                    return 'Rp. ' . number_format($data->total_price, 0, ',', '.');
                })
                ->addColumn('created_at', function ($data) {
                    return $data->created_at->format('d-m-Y H:i') . ' WIB';
                })
                ->addIndexColumn()
                ->make(true);
        }
        return view('admin.order.list-detail');
    }

    public function uploadPaymentCod(Request $request)
    {
        try {
            $this->transaction->uploadPaymentCod($request->all());
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil mengupload bukti pembayaran'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage()
            ]);
        }
    }

    public function filterMonthly(Request $request)
    {
        $transaction = $this->transaction->filterMonthly($request->month);
        $data = $transaction->map(function ($item) {
            return [
                'DT_RowIndex' => $item->id,
                'transaction_code' => $item->transaction_code,
                'payment_method' => $item->payment_method == 1 ? 'E Wallet' : 'COD (Bayar di tempat)',
                'customer' => $item->user->name,
                'receiver' => $item->receiver_name,
                'phone' => $item->receiver_phone,
                'proof_of_payment' => view('admin.order.column.proof_of_payment', ['data' => $item])->render(),
                'status' => view('admin.order.column.status', ['data' => $item])->render(),
                'shipping_cost' => 'Rp. ' . number_format($item->shipping_price, 0, ',', '.'),
                'total_payment' => 'Rp. ' . number_format($item->total_payment, 0, ',', '.'),
                'address' => $item->receiver_address ?? '-',
                'created_at' => $item->created_at->format('d-m-Y H:i') . ' WIB',
                'detail_transaction' => view('admin.order.column.detail_transaction', ['data' => $item])->render(),
                'action' => view('admin.order.column.action', ['data' => $item])->render(),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    public function filterYearly(Request $request)
    {
        $transaction = $this->transaction->filterYearly($request->year);
        $data = $transaction->map(function ($item) {
            return [
                'DT_RowIndex' => $item->id,
                'transaction_code' => $item->transaction_code,
                'payment_method' => $item->payment_method == 1 ? 'E Wallet' : 'COD (Bayar di tempat)',
                'customer' => $item->user->name,
                'receiver' => $item->receiver_name,
                'phone' => $item->receiver_phone,
                'proof_of_payment' => view('admin.order.column.proof_of_payment', ['data' => $item])->render(),
                'status' => view('admin.order.column.status', ['data' => $item])->render(),
                'shipping_cost' => 'Rp. ' . number_format($item->shipping_price, 0, ',', '.'),
                'total_payment' => 'Rp. ' . number_format($item->total_payment, 0, ',', '.'),
                'address' => $item->receiver_address ?? '-',
                'created_at' => $item->created_at->format('d-m-Y H:i') . ' WIB',
                'detail_transaction' => view('admin.order.column.detail_transaction', ['data' => $item])->render(),
                'action' => view('admin.order.column.action', ['data' => $item])->render(),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    public function filterPeriodic(Request $request)
    {
        $transaction = $this->transaction->filterPeriodic($request->month, $request->year);
        $data = $transaction->map(function ($item) {
            return [
                'DT_RowIndex' => $item->id,
                'transaction_code' => $item->transaction_code,
                'payment_method' => $item->payment_method == 1 ? 'E Wallet' : 'COD (Bayar di tempat)',
                'customer' => $item->user->name,
                'receiver' => $item->receiver_name,
                'phone' => $item->receiver_phone,
                'proof_of_payment' => view('admin.order.column.proof_of_payment', ['data' => $item])->render(),
                'status' => view('admin.order.column.status', ['data' => $item])->render(),
                'shipping_cost' => 'Rp. ' . number_format($item->shipping_price, 0, ',', '.'),
                'total_payment' => 'Rp. ' . number_format($item->total_payment, 0, ',', '.'),
                'address' => $item->receiver_address ?? '-',
                'created_at' => $item->created_at->format('d-m-Y H:i') . ' WIB',
                'detail_transaction' => view('admin.order.column.detail_transaction', ['data' => $item])->render(),
                'action' => view('admin.order.column.action', ['data' => $item])->render(),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
