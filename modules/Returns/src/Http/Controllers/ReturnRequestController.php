<?php

namespace Modules\Returns\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\Orders\Models\Order;
use Modules\Returns\Actions\SubmitReturnRequest;
use Modules\Returns\ComplaintResolution;
use Modules\Returns\ReturnType;

/**
 * Formulár na odstúpenie od zmluvy alebo reklamáciu (zo stránky objednávky, bez prihlásenia).
 */
class ReturnRequestController extends Controller
{
    public function create(Request $request, string $token): View
    {
        $order = $this->order($token);

        return view('theme::templates.return-request', [
            'order' => $order,
            'type' => ReturnType::tryFrom((string) $request->query('typ')) ?? ReturnType::Withdrawal,
        ]);
    }

    public function store(Request $request, string $token, SubmitReturnRequest $submit): RedirectResponse
    {
        $order = $this->order($token);
        $isComplaint = $request->input('type') === ReturnType::Complaint->value;

        $data = $request->validate([
            'type' => ['required', Rule::enum(ReturnType::class)],
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'reason' => [Rule::requiredIf($isComplaint), 'nullable', 'string', 'max:2000'],
            'preferred_resolution' => [Rule::requiredIf($isComplaint), 'nullable', Rule::enum(ComplaintResolution::class)],
            'refund_account' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z]{2}[0-9A-Za-z ]{10,40}$/'],
        ]);

        if (array_sum(array_map('intval', $data['quantities'])) < 1) {
            return back()->withInput()->withErrors(['quantities' => __('returns::returns.no_items')]);
        }

        $returnRequest = $submit->handle(
            $order,
            ReturnType::from($data['type']),
            array_map('intval', $data['quantities']),
            $data['reason'] ?? null,
            isset($data['preferred_resolution']) ? ComplaintResolution::from($data['preferred_resolution']) : null,
            $data['refund_account'] ?? null,
            $request->ip(),
        );

        return redirect()->to($order->publicUrl())->with('success', __('returns::returns.submitted', [
            'type' => $returnRequest->type->getLabel(),
            'number' => $returnRequest->number,
            'email' => $order->email,
        ]));
    }

    private function order(string $token): Order
    {
        $order = Order::query()->where('token', $token)->with('items')->firstOrFail();
        abort_if($order->isCancelled(), 404);

        return $order;
    }
}
