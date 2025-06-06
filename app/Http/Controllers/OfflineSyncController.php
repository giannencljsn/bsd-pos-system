<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use Modules\People\Entities\Customer;

class OfflineSyncController extends Controller
{

    public function store(Request $request)
    {

        function sanitizeAmount($value)
        {
            //Remove everything except digits and decimals
            $clean = preg_replace('/[^\d.]/', '', $value);
            return is_numeric($clean) ? (float) $clean : 0;
        }


        foreach ($request->input('records') as $record) {
            $customerName = null;

            if (!empty($record['customer_id'])) {
                $customer = Customer::find($record['customer_id']);
                if ($customer) {
                    $customerName = $customer->customer_name;
                }
            }

            $recordId = $record['id'] ?? null;
            $reference = $recordId ? 'SL-' . $recordId : ($record['reference'] ?? 'SL');

            //Sanitize and convert amounts to integer cents
            $discountAmount = sanitizeAmount($record['discount_amount'] ?? 0) * 100;
            $shippingAmount = sanitizeAmount($record['shipping_amount'] ?? 0) * 100;
            $totalAmount = sanitizeAmount($record['total_amount'] ?? 0) * 100;
            $paidAmount = sanitizeAmount($record['paid_amount'] ?? 0) * 100;



            //Apply the payment status and overall status logic
            if (($totalAmount - $paidAmount) == 0) {
                $paymentStatus = "Paid";
                $status = "Completed";
                $dueAmount = 0;
            } else {
                $dueAmount = ($totalAmount - $paidAmount) / 100; // convert back to original currency
                $paymentStatus = "Partial";
                $status = "Pending";
            }


            //Store the records
            Sale::updateOrCreate(
                ['id' => $recordId],
                [
                    'date' => $record['date'] ?? now(),
                    'reference' => $reference,
                    'customer_id' => $record['customer_id'] ?? null,
                    'customer_name' => $customerName ?? '',
                    'tax_percentage' => $record['tax_percentage'] ?? 0,
                    'tax_amount' => $record['tax_amount'] ?? 0,
                    'discount_percentage' => $record['discount_percentage'] ?? 0,
                    'discount_amount' => $discountAmount,
                    'shipping_amount' => $shippingAmount,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'due_amount' => $dueAmount,
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $record['payment_method'] ?? 0,
                    'note' => $record['note'] ?? '',
                    'synced' => true
                ]
            );
        }
    }

}
