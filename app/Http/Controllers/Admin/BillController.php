<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Services\BillPayment;
use App\Services\MembershipSettings;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $query = Bill::with(['user', 'tracking'])->latest();
        if (in_array($request->query('status'), array_keys(Bill::STATUS_LABELS), true)) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('q')) {
            $search = '%'.mb_substr((string) $request->query('q'), 0, 100).'%';
            $query->where(fn ($builder) => $builder->whereLike('reference', $search)->orWhereLike('title', $search)->orWhereLike('customer_name', $search)->orWhereLike('customer_email', $search)->orWhereHas('user', fn ($member) => $member->whereLike('name', $search)));
        }

        return view('admin.bills.index', ['bills' => $query->paginate(15)->withQueryString(), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function create()
    {
        return redirect()->route('admin.tracking.create');
    }

    public function edit(Bill $bill)
    {
        return redirect()->route('admin.bills.show', $bill);
    }

    public function show(Bill $bill)
    {
        return view('shared.bill', ['bill' => $bill->load(['user', 'service', 'tracking', 'transaction.pointEntry']), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function store()
    {
        abort(403, 'Bill otomatis dibuat dari pesanan baru.');
    }

    public function update()
    {
        abort(403, 'Bill yang diterbitkan tidak dapat diubah.');
    }

    public function pay(Request $request, Bill $bill, BillPayment $payments)
    {
        $values = $request->validate(['payment' => ['required', 'string', 'max:100']]);
        $payments->pay($bill, $request->user(), $values['payment']);

        return redirect()->route('admin.bills.show', $bill)->with('success', 'Bill lunas. Transaksi dan poin dicatat satu kali.');
    }

    public function cancel(Bill $bill, BillPayment $payments)
    {
        $payments->cancel($bill);

        return redirect()->route('admin.bills.show', $bill)->with('success', 'Bill dibatalkan.');
    }
}
