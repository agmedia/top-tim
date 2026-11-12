<?php

namespace App\Http\Controllers\Front;

use App\Helpers\Helper;
use App\Helpers\Metatags;
use App\Helpers\Recaptcha;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontBaseController;
use App\Mail\ContactFormMessage;
use App\Models\Back\Marketing\Review;
use App\Models\Back\Orders\Order;
use App\Models\ContractTermination;
use App\Models\Front\Loyalty;
use App\Models\Front\Page;
use App\Models\Front\Newsletter;
use App\Models\Sitemap;
use App\Services\ContractTerminationNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class HomeController extends FrontBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page = Page::whereHas('translation', function ($query) {
            $query->where('slug', 'homepage');
        })->first();

        $page->translation->description = Helper::setDescription(
            isset($page->translation->description) ? $page->translation->description : '',
            isset($page->id) ? $page->id : 0
        );

        $og_schema = Metatags::indexSchema();

        return view('front.page', compact('page', 'og_schema'));
    }


    /**
     * @param Page $page
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function page(Page $page)
    {
        return view('front.page', compact('page'));
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function contact(Request $request)
    {
        return view('front.contact');
    }


    /**
     * Show the unilateral contract termination form.
     */
    public function contractTermination()
    {
        return view('front.contract-termination');
    }


    /**
     * Store the consumer's statement and send receipt notifications.
     */
    public function sendContractTermination(
        Request $request,
        ContractTerminationNotificationService $notifications
    ) {
        $receivedDateRules = ['bail', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'];
        if ($request->filled('order_date')) {
            $receivedDateRules[] = 'after_or_equal:order_date';
        }

        $validated = $request->validate([
            'full_name' => ['bail', 'required', 'string', 'max:150'],
            'email' => ['bail', 'required', 'string', 'email:rfc', 'max:190'],
            'phone' => ['bail', 'nullable', 'string', 'max:50', $this->phoneValidationRule()],
            'address' => ['bail', 'required', 'string', 'max:190'],
            'postal_code' => ['bail', 'required', 'string', 'max:20'],
            'city' => ['bail', 'required', 'string', 'max:100'],
            'country' => ['bail', 'required', 'string', 'max:80'],
            'order_number' => ['bail', 'required', 'string', 'max:80'],
            'order_date' => ['bail', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'received_date' => $receivedDateRules,
            'items' => ['bail', 'required', 'string', 'max:3000'],
            'iban' => ['bail', 'nullable', 'string', 'max:50', $this->ibanValidationRule()],
            'statement' => ['bail', 'accepted'],
            'website' => ['bail', 'nullable', 'string', 'max:0'],
            'recaptcha' => ['bail', 'nullable', 'string', 'max:4096'],
        ], [
            'full_name.required' => 'Upišite ime i prezime.',
            'full_name.string' => 'Ime i prezime moraju biti tekst.',
            'full_name.max' => 'Ime i prezime mogu sadržavati najviše 150 znakova.',
            'email.required' => 'Upišite e-mail adresu.',
            'email.string' => 'E-mail adresa mora biti tekst.',
            'email.email' => 'Upišite ispravnu e-mail adresu.',
            'email.max' => 'E-mail adresa može sadržavati najviše 190 znakova.',
            'phone.string' => 'Broj telefona mora biti tekst.',
            'phone.max' => 'Broj telefona može sadržavati najviše 50 znakova.',
            'address.required' => 'Upišite ulicu i kućni broj.',
            'address.string' => 'Ulica i kućni broj moraju biti tekst.',
            'address.max' => 'Ulica i kućni broj mogu sadržavati najviše 190 znakova.',
            'postal_code.required' => 'Upišite poštanski broj.',
            'postal_code.string' => 'Poštanski broj mora biti tekst.',
            'postal_code.max' => 'Poštanski broj može sadržavati najviše 20 znakova.',
            'city.required' => 'Upišite mjesto.',
            'city.string' => 'Mjesto mora biti tekst.',
            'city.max' => 'Mjesto može sadržavati najviše 100 znakova.',
            'country.required' => 'Upišite državu.',
            'country.string' => 'Država mora biti tekst.',
            'country.max' => 'Država može sadržavati najviše 80 znakova.',
            'order_number.required' => 'Upišite broj narudžbe ili računa.',
            'order_number.string' => 'Broj narudžbe ili računa mora biti tekst.',
            'order_number.max' => 'Broj narudžbe ili računa može sadržavati najviše 80 znakova.',
            'order_date.date_format' => 'Upišite ispravan datum narudžbe.',
            'order_date.before_or_equal' => 'Datum narudžbe ne može biti u budućnosti.',
            'received_date.date_format' => 'Upišite ispravan datum primitka robe.',
            'received_date.before_or_equal' => 'Datum primitka robe ne može biti u budućnosti.',
            'received_date.after_or_equal' => 'Datum primitka robe ne može biti prije datuma narudžbe.',
            'items.required' => 'Navedite artikle na koje se raskid odnosi.',
            'items.string' => 'Popis artikala mora biti tekst.',
            'items.max' => 'Popis artikala može sadržavati najviše 3000 znakova.',
            'iban.string' => 'IBAN mora biti tekst.',
            'iban.max' => 'IBAN može sadržavati najviše 50 znakova.',
            'statement.accepted' => 'Za slanje je potrebno potvrditi izjavu o raskidu ugovora.',
            'website.max' => 'Obrazac nije moguće poslati.',
            'recaptcha.string' => 'Sigurnosna provjera nije valjana. Osvježite stranicu i pokušajte ponovno.',
            'recaptcha.max' => 'Sigurnosna provjera nije valjana. Osvježite stranicu i pokušajte ponovno.',
        ]);

        $recaptcha = (new Recaptcha())->check([
            'recaptcha' => $validated['recaptcha'] ?? null,
        ], 'contract_termination');
        if (! $recaptcha->ok()) {
            return back()
                ->withErrors(['recaptcha' => 'Sigurnosna provjera nije uspjela. Osvježite stranicu i pokušajte ponovno.'])
                ->withInput($request->except(['_token', 'recaptcha', 'website']));
        }

        $submittedAt = now();
        $orderNumber = ltrim(trim((string) $validated['order_number']), '#');
        $order = ctype_digit($orderNumber)
            ? Order::query()->whereKey((int) $orderNumber)
                ->where(function ($query) use ($validated) {
                    $query->where('payment_email', $validated['email'])
                        ->orWhere('shipping_email', $validated['email']);
                })->first()
            : null;

        do {
            $reference = 'JR-' . $submittedAt->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (ContractTermination::query()->where('reference', $reference)->exists());

        $termination = ContractTermination::query()->create([
            'reference' => $reference,
            'user_id' => optional($request->user())->id,
            'order_id' => optional($order)->id,
            'order_number' => trim((string) $validated['order_number']),
            'full_name' => trim((string) $validated['full_name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'phone' => trim((string) ($validated['phone'] ?? '')) ?: null,
            'address' => trim((string) $validated['address']),
            'postal_code' => trim((string) $validated['postal_code']),
            'city' => trim((string) $validated['city']),
            'country' => strtoupper(trim((string) $validated['country'])),
            'order_date' => $validated['order_date'] ?? null,
            'received_date' => $validated['received_date'] ?? null,
            'items' => trim((string) $validated['items']),
            'iban' => trim((string) ($validated['iban'] ?? '')) ?: null,
            'statement' => true,
            'status' => ContractTermination::STATUS_RECEIVED,
            'submitted_at' => $submittedAt,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 512, ''),
        ]);

        $notifications->send($termination);
        $termination->refresh();

        $redirect = redirect()->route('contract-termination')->with(
            'success',
            'Izjava o jednostranom raskidu zaprimljena je pod oznakom ' . $reference . '.'
        );

        if (! $termination->consumer_notified_at) {
            $redirect->with('warning', 'Izjava je spremljena, ali potvrdu trenutačno nije bilo moguće poslati e-mailom.');
        }

        return $redirect;
    }


    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendProductComment(Request $request)
    {
        $review = new Review();

        $created_review = $review->validateFrontRequest($request)->create();

        if ($created_review) {
            if ($request->user()) {
                DB::transaction(function () use ($request, $created_review) {
                    $user = $request->user()->newQuery()->whereKey($request->user()->id)->lockForUpdate()->first();
                    if ($user && ! Loyalty::query()->where('user_id', $user->id)
                        ->where('reference_id', $created_review->product_id)->where('target', 'product_review')->exists()) {
                        Loyalty::addPoints(config('settings.loyalty.product_review'), $created_review->product_id, 'product_review');
                    }
                });
            }

            return back()->with(['success' => 'Komentar je uspješno poslan']);
        }

        return back()->with(['error' => 'Whoops..! Greška kod snimanja komentara']);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function newsletter(Request $request)
    {
        $newsletter = new Newsletter();

        $created_newsletter = $newsletter->validateRequest($request)->create();

        if ($created_newsletter) {
            if (!app(\App\Services\BrevoNewsletter::class)->subscribe($created_newsletter->email)) {
                return back()->withErrors(['email' => 'Prijava trenutno nije dostupna. Pokušajte ponovno.']);
            }

            return back()->with(['success' => 'Uspješna prijava', 'newsletter_subscribed' => true]);
        }

        return back()->with(['error' => 'Whoops..! Upišite ispravan email']);
    }



    /**
     * @param Request $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function sendContactMessage(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'message' => 'required',
        ]);

        // Recaptcha
        $recaptcha = (new Recaptcha())->check($request->toArray());

        if ( ! $recaptcha->ok()) {
            return back()->withErrors(['error' => 'ReCaptcha Error! Kontaktirajte administratora!']);
        }

        $message = $request->toArray();

        dispatch(function () use ($request) {
            Mail::to(config('mail.admin'))->send(new ContactFormMessage($request->toArray()));
        })->afterResponse();

        return redirect()->route('kontakt')->with(['success' => 'Vaša poruka je uspješno poslana.! Odgovoriti ćemo vam uskoro.']);
    }


    private function phoneValidationRule(): \Closure
    {
        return static function ($attribute, $value, $fail) {
            $phone = trim((string) $value);
            $digits = preg_replace('/\D+/', '', $phone) ?? '';

            if (! preg_match('/^\+?[0-9() .\/-]+$/u', $phone)
                || strlen($digits) < 6
                || strlen($digits) > 15) {
                $fail('Upišite ispravan broj telefona (6–15 znamenki).');
            }
        };
    }


    private function ibanValidationRule(): \Closure
    {
        return static function ($attribute, $value, $fail) {
            $iban = strtoupper((string) preg_replace('/\s+/', '', trim((string) $value)));

            if (! preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/', $iban)
                || ! self::ibanChecksumIsValid($iban)) {
                $fail('Upišite ispravan IBAN.');
            }
        };
    }


    private static function ibanChecksumIsValid(string $iban): bool
    {
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $remainder = 0;

        foreach (str_split($rearranged) as $character) {
            $numeric = ctype_alpha($character)
                ? (string) (ord($character) - 55)
                : $character;

            foreach (str_split($numeric) as $digit) {
                $remainder = (($remainder * 10) + (int) $digit) % 97;
            }
        }

        return $remainder === 1;
    }


    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function imageCache(Request $request)
    {
        return app(\App\Services\SafeMediaImage::class)->response($request);
    }


    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function thumbCache(Request $request)
    {
        return app(\App\Services\SafeMediaImage::class)->response($request, true);
    }


    /**
     * @param Request $request
     * @param null    $sitemap
     *
     * @return \Illuminate\Http\Response
     */
    public function sitemapXML(Request $request, $sitemap = null)
    {
        if ( ! $sitemap) {
            $items = config('settings.sitemap');

            return response()->view('front.layouts.partials.sitemap-index', [
                'items' => $items
            ])->header('Content-Type', 'text/xml');
        }

        $sm = new Sitemap($sitemap);

        abort_if($sm->getSitemap() === null, 404);

        if (in_array($sitemap, ['images', 'img'], true)) {
            return response()->view('front.layouts.partials.sitemap-image', [
                'items' => $sm->getResponse()
            ])->header('Content-Type', 'text/xml');
        }

        return response()->view('front.layouts.partials.sitemap', [
            'items' => $sm->getSitemap()
        ])->header('Content-Type', 'text/xml');
    }


    /**
     * @return \Illuminate\Http\Response
     */
    public function sitemapImageXML()
    {
        $sm = new Sitemap('images');

        return response()->view('front.layouts.partials.sitemap-image', [
            'items' => $sm->getResponse()
        ])->header('Content-Type', 'text/xml');
    }

}
