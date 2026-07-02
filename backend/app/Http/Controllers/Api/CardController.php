<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Card::class);
        $cards = Card::with(['customer.user', 'cardProduct', 'account'])
            ->whereNull('deleted_at')
            ->when($request->customer_id,  fn($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->card_status,  fn($q) => $q->where('card_status', $request->card_status))
            ->when($request->card_type,    fn($q) => $q->where('card_type', $request->card_type))
            ->latest()->paginate($request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $cards]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Card::class);
        $data = $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'account_id'       => 'required|exists:accounts,id',
            'card_product_id'  => 'required|exists:card_products,id',
            'card_holder_name' => 'required|string|max:255',
            'card_type'        => 'required|in:debit,credit,prepaid,virtual',
            'card_network'     => 'required|in:visa,mastercard,rupay,amex',
        ]);

        $card = Card::create(array_merge($data, [
            'card_number'     => $this->generateCardNumber($data['card_network']),
            'card_status'     => 'inactive',
            'issue_date'      => now()->toDateString(),
            'expiry_date'     => now()->addYears(5)->toDateString(),
            'credit_limit'    => 0,
            'available_limit' => 0,
            'outstanding_amount' => 0,
            'reward_points'   => 0,
            'international_enabled' => false,
            'online_enabled'        => true,
            'contactless_enabled'   => true,
            'pin_change_required'   => true,
        ]));

        return response()->json(['success' => true, 'message' => 'Card issued.', 'data' => $card->load(['customer', 'cardProduct'])], 201);
    }

    public function show(Card $card): JsonResponse
    {
        $this->authorize('view', $card);
        $card->load(['customer.user', 'cardProduct', 'account']);
        return response()->json(['success' => true, 'data' => array_merge($card->toArray(), ['card_number' => $card->masked_card_number])]);
    }

    public function activate(Request $request, Card $card): JsonResponse
    {
        $this->authorize('update', $card);
        $card->update(['card_status' => 'active', 'activation_date' => now()->toDateString()]);
        return response()->json(['success' => true, 'message' => 'Card activated.', 'data' => $card]);
    }

    public function block(Request $request, Card $card): JsonResponse
    {
        $this->authorize('update', $card);
        $data = $request->validate(['reason' => 'required|string|max:500', 'block_type' => 'required|in:temporary,permanent']);
        $card->update([
            'card_status'    => $data['block_type'] === 'permanent' ? 'cancelled' : 'blocked',
            'blocked_reason' => $data['reason'],
            'blocked_at'     => now(),
        ]);
        return response()->json(['success' => true, 'message' => 'Card blocked.', 'data' => $card]);
    }

    public function updateLimits(Request $request, Card $card): JsonResponse
    {
        $this->authorize('update', $card);
        $data = $request->validate([
            'daily_limit'          => 'nullable|numeric|min:0',
            'per_transaction_limit'=> 'nullable|numeric|min:0',
            'international_enabled'=> 'nullable|boolean',
            'online_enabled'       => 'nullable|boolean',
            'contactless_enabled'  => 'nullable|boolean',
        ]);
        $card->update($data);
        return response()->json(['success' => true, 'message' => 'Card limits updated.', 'data' => $card]);
    }

    private function generateCardNumber(string $network): string
    {
        $prefixes = ['visa' => '4', 'mastercard' => '5', 'rupay' => '6', 'amex' => '3'];
        $prefix   = $prefixes[$network] ?? '4';
        return $prefix . str_pad(random_int(0, 999999999999999), 15, '0', STR_PAD_LEFT);
    }
}
