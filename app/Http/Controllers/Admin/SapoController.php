<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PushQuoteToSapo;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\SapoVariant;
use App\Services\Sapo\SapoClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

/** Trang quản trị: theo dõi đồng bộ khách hàng / đơn hàng sang Sapo. */
class SapoController extends Controller
{
    public function index(Request $request, SapoClient $sapo): View
    {
        $status = $request->query('status');

        return view('admin.sapo', [
            'enabled' => $sapo->enabled(),
            'store' => $sapo->enabled() ? parse_url($sapo->baseUrl(), PHP_URL_HOST) : null,
            'mode' => config('services.sapo.order_mode'),
            'orders' => QuoteRequest::withCount('items')
                ->when(in_array($status, ['synced', 'pending', 'failed'], true), fn ($q) => $q->where('sapo_status', $status))
                ->when($status === 'none', fn ($q) => $q->whereNull('sapo_status'))
                ->latest()->paginate(30)->withQueryString(),
            'counts' => QuoteRequest::selectRaw('sapo_status, count(*) as n')->groupBy('sapo_status')->pluck('n', 'sapo_status'),
            'variants' => SapoVariant::count(),
            'variantsAt' => SapoVariant::max('updated_at'),
            'webSkus' => Product::count(),
            'matched' => Product::whereIn('sku', SapoVariant::select('sku'))->count(),
            'jobsWaiting' => \Illuminate\Support\Facades\DB::table('jobs')->count(),
        ]);
    }

    public function check(SapoClient $sapo): RedirectResponse
    {
        abort_unless($sapo->enabled(), 404);
        try {
            $r = $sapo->ping();

            return back()->with('ok', "Kết nối Sapo thành công: {$r['customers']} khách hàng, ".($r['products'] ?? '?').' sản phẩm.');
        } catch (\Throwable $e) {
            return back()->withErrors(['sapo' => $e->getMessage()]);
        }
    }

    public function syncVariants(SapoClient $sapo): RedirectResponse
    {
        abort_unless($sapo->enabled(), 404);
        @set_time_limit(300);
        $code = Artisan::call('sapo:run', ['action' => 'sync-variants']);
        $out = trim(Artisan::output());

        return $code === 0 ? back()->with('ok', $out) : back()->withErrors(['sapo' => $out]);
    }

    public function retry(QuoteRequest $quote, SapoClient $sapo): RedirectResponse
    {
        abort_unless($sapo->enabled(), 404);
        if ($quote->sapo_order_id) {
            return back()->with('ok', 'Đơn '.$quote->code().' đã có trên Sapo, không gửi lại.');
        }
        $quote->forceFill(['sapo_status' => 'pending', 'sapo_error' => null])->saveQuietly();
        PushQuoteToSapo::dispatch($quote);

        return back()->with('ok', 'Đã đưa đơn '.$quote->code().' vào hàng đợi gửi lại.');
    }
}
