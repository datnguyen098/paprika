<?php

use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\BranchController as AdminBranchController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DishController as AdminDishController;
use App\Http\Controllers\Admin\DishBulkTimeSlotController as AdminDishBulkTimeSlotController;
use App\Http\Controllers\Admin\DishTimeSlotController as AdminDishTimeSlotController;
use App\Http\Controllers\Admin\DishOptionSettingController as AdminDishOptionSettingController;
use App\Http\Controllers\Admin\GalleryImageController as AdminGalleryImageController;
use App\Http\Controllers\Admin\KitchenController as AdminKitchenController;
use App\Http\Controllers\Admin\NavigationMenuController as AdminNavigationMenuController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\RestaurantTableController as AdminRestaurantTableController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\TranslationController as AdminTranslationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VoucherController as AdminVoucherController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Storefront\BranchController as StorefrontBranchController;
use App\Http\Controllers\Storefront\CartController as StorefrontCartController;
use App\Http\Controllers\Storefront\CheckoutController as StorefrontCheckoutController;
use App\Http\Controllers\Storefront\HomeController as StorefrontHomeController;
use App\Http\Controllers\Storefront\MenuController as StorefrontMenuController;
use App\Http\Controllers\Storefront\ReservationController as StorefrontReservationController;
use App\Http\Controllers\Storefront\OrderTrackingController as StorefrontOrderTrackingController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('admin')
    ->as('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function (): void {
        Route::redirect('/', '/admin/dashboard');
        Route::get('/dashboard', AdminDashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');
        Route::get('/notifications', AdminNotificationController::class)->name('notifications.index');

        Route::get('/settings', [AdminSettingController::class, 'edit'])->middleware('permission:settings.view')->name('settings.edit');
        Route::put('/settings', [AdminSettingController::class, 'update'])->middleware('permission:settings.update')->name('settings.update');
        Route::get('/identity', [AdminSettingController::class, 'identity'])->middleware('permission:identity.view')->name('identity.edit');
        Route::put('/identity', [AdminSettingController::class, 'updateIdentity'])->middleware('permission:identity.update')->name('identity.update');
        Route::get('/seo', [AdminSettingController::class, 'seo'])->middleware('permission:seo.view')->name('seo.edit');
        Route::put('/seo', [AdminSettingController::class, 'updateSeo'])->middleware('permission:seo.update')->name('seo.update');
        Route::get('/translations/settings', [AdminTranslationController::class, 'settings'])->middleware('permission:translations.view')->name('translations.settings');
        Route::put('/translations/settings', [AdminTranslationController::class, 'updateSettings'])->middleware('permission:translations.update')->name('translations.settings.update');
        Route::get('/translations/usage', [AdminTranslationController::class, 'usage'])->middleware('throttle:20,1')->name('translations.usage');
        Route::post('/translations/test', [AdminTranslationController::class, 'test'])->middleware('throttle:10,1')->name('translations.test');
        Route::post('/translations/translate', [AdminTranslationController::class, 'translate'])->middleware(['throttle:20,1', 'permission:translations.auto_translate'])->name('translations.translate');
        Route::get('/translations/deepl/usage', [AdminTranslationController::class, 'usage'])->middleware('throttle:20,1')->name('translations.deepl.usage');
        Route::post('/translations/deepl/test', [AdminTranslationController::class, 'test'])->middleware('throttle:10,1')->name('translations.deepl.test');
        Route::post('/translations/deepl', [AdminTranslationController::class, 'translate'])->middleware(['throttle:20,1', 'permission:translations.auto_translate'])->name('translations.deepl.translate');

        Route::patch('/banners/{banner}/toggle', [AdminBannerController::class, 'toggle'])->middleware('permission:banners.update')->name('banners.toggle');
        Route::resource('branches', AdminBranchController::class)->except('show')
            ->middlewareFor(['index'], 'permission:branches.view')
            ->middlewareFor(['create', 'store'], 'permission:branches.create')
            ->middlewareFor(['edit', 'update'], 'permission:branches.update')
            ->middlewareFor(['destroy'], 'permission:branches.delete');
        Route::resource('restaurant-tables', AdminRestaurantTableController::class)->except('show')
            ->middlewareFor(['index'], 'permission:restaurant_tables.view')
            ->middlewareFor(['create', 'store'], 'permission:restaurant_tables.create')
            ->middlewareFor(['edit', 'update'], 'permission:restaurant_tables.update')
            ->middlewareFor(['destroy'], 'permission:restaurant_tables.delete');
        Route::resource('banners', AdminBannerController::class)->except('show')
            ->middlewareFor(['index'], 'permission:banners.view')
            ->middlewareFor(['create', 'store'], 'permission:banners.create')
            ->middlewareFor(['edit', 'update'], 'permission:banners.update')
            ->middlewareFor(['destroy'], 'permission:banners.delete');
        Route::resource('categories', AdminCategoryController::class)->except('show')
            ->middlewareFor(['index'], 'permission:categories.view')
            ->middlewareFor(['create', 'store'], 'permission:categories.create')
            ->middlewareFor(['edit', 'update'], 'permission:categories.update')
            ->middlewareFor(['destroy'], 'permission:categories.delete');
        Route::get('/dishes/bulk-time-slots', [AdminDishBulkTimeSlotController::class, 'edit'])
            ->middleware('permission:dishes.update')
            ->name('dishes.bulk-time-slots.edit');
        Route::put('/dishes/bulk-time-slots', [AdminDishBulkTimeSlotController::class, 'update'])
            ->middleware('permission:dishes.update')
            ->name('dishes.bulk-time-slots.update');
        Route::resource('dishes', AdminDishController::class)->except('show')
            ->middlewareFor(['index'], 'permission:dishes.view')
            ->middlewareFor(['create', 'store'], 'permission:dishes.create')
            ->middlewareFor(['edit', 'update'], 'permission:dishes.update')
            ->middlewareFor(['destroy'], 'permission:dishes.delete');
        Route::resource('dish-time-slots', AdminDishTimeSlotController::class)->except('show')
            ->middleware('permission:dishes.update');
        Route::get('/dish-option-settings', [AdminDishOptionSettingController::class, 'edit'])->middleware('permission:dishes.update')->name('dish-option-settings.edit');
        Route::put('/dish-option-settings', [AdminDishOptionSettingController::class, 'update'])->middleware('permission:dishes.update')->name('dish-option-settings.update');
        Route::resource('posts', AdminPostController::class)->except('show')
            ->middlewareFor(['index'], 'permission:posts.view')
            ->middlewareFor(['create', 'store'], 'permission:posts.create')
            ->middlewareFor(['edit', 'update'], 'permission:posts.update')
            ->middlewareFor(['destroy'], 'permission:posts.delete');
        Route::resource('pages', AdminPageController::class)->except('show')
            ->middlewareFor(['index'], 'permission:pages.view')
            ->middlewareFor(['create', 'store'], 'permission:pages.create')
            ->middlewareFor(['edit', 'update'], 'permission:pages.update')
            ->middlewareFor(['destroy'], 'permission:pages.delete');
        Route::resource('menus', AdminNavigationMenuController::class)->except('show')
            ->middlewareFor(['index'], 'permission:menus.view')
            ->middlewareFor(['create', 'store'], 'permission:menus.create')
            ->middlewareFor(['edit', 'update'], 'permission:menus.update')
            ->middlewareFor(['destroy'], 'permission:menus.delete');
        Route::resource('testimonials', AdminTestimonialController::class)->except('show')
            ->middlewareFor(['index'], 'permission:testimonials.view')
            ->middlewareFor(['create', 'store'], 'permission:testimonials.create')
            ->middlewareFor(['edit', 'update'], 'permission:testimonials.update')
            ->middlewareFor(['destroy'], 'permission:testimonials.delete');
        Route::resource('promotions', AdminPromotionController::class)->except('show')
            ->middlewareFor(['index'], 'permission:promotions.view')
            ->middlewareFor(['create', 'store'], 'permission:promotions.create')
            ->middlewareFor(['edit', 'update'], 'permission:promotions.update')
            ->middlewareFor(['destroy'], 'permission:promotions.delete');
        Route::resource('vouchers', AdminVoucherController::class)->except('show')
            ->middlewareFor(['index'], 'permission:vouchers.view')
            ->middlewareFor(['create', 'store'], 'permission:vouchers.create')
            ->middlewareFor(['edit', 'update'], 'permission:vouchers.update')
            ->middlewareFor(['destroy'], 'permission:vouchers.delete');
        Route::resource('gallery', AdminGalleryImageController::class)->except('show')
            ->middlewareFor(['index'], 'permission:gallery.view')
            ->middlewareFor(['create', 'store'], 'permission:gallery.create')
            ->middlewareFor(['edit', 'update'], 'permission:gallery.update')
            ->middlewareFor(['destroy'], 'permission:gallery.delete');
        Route::get('/reservations/export', [AdminReservationController::class, 'export'])->name('reservations.export')
            ->middleware('permission:reservations.view');
        Route::resource('reservations', AdminReservationController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy'])
            ->middlewareFor(['index', 'show'], 'permission:reservations.view')
            ->middlewareFor(['create', 'store', 'update'], 'permission:reservations.update')
            ->middlewareFor(['destroy'], 'permission:reservations.delete');
        Route::resource('orders', AdminOrderController::class)->only(['index', 'show', 'update', 'destroy'])
            ->middlewareFor(['index', 'show'], 'permission:orders.view')
            ->middlewareFor(['update'], 'permission:orders.update')
            ->middlewareFor(['destroy'], 'permission:orders.delete');
        Route::post('/orders/bulk-cancel', [AdminOrderController::class, 'bulkCancel'])->name('orders.bulk-cancel')
            ->middleware('permission:orders.update');
        Route::post('/orders/bulk-destroy', [AdminOrderController::class, 'bulkDestroy'])->name('orders.bulk-destroy')
            ->middleware('permission:orders.delete');
        Route::middleware('permission:kitchen.view')->group(function (): void {
            Route::get('/kitchen', [AdminKitchenController::class, 'index'])->name('kitchen.index');
            Route::put('/kitchen/{order}/action', [AdminKitchenController::class, 'update'])->name('kitchen.update');
        });
        Route::resource('contacts', AdminContactController::class)->only(['index', 'show', 'update', 'destroy'])
            ->middlewareFor(['index', 'show'], 'permission:contacts.view')
            ->middlewareFor(['update'], 'permission:contacts.update')
            ->middlewareFor(['destroy'], 'permission:contacts.delete');
        Route::get('/chats', [AdminChatController::class, 'index'])->middleware('permission:chats.view')->name('chats.index');
        Route::get('/chats/{chat}', [AdminChatController::class, 'show'])->middleware('permission:chats.view')->name('chats.show');
        Route::get('/chats/{chat}/messages', [AdminChatController::class, 'messages'])->middleware('permission:chats.view')->name('chats.messages');
        Route::post('/chats/{chat}/reply', [AdminChatController::class, 'reply'])->middleware('permission:chats.reply')->name('chats.reply');
        Route::patch('/chats/{chat}', [AdminChatController::class, 'update'])->middleware('permission:chats.update')->name('chats.update');
        Route::delete('/chats/{chat}', [AdminChatController::class, 'destroy'])->middleware('permission:chats.delete')->name('chats.destroy');
        Route::resource('users', AdminUserController::class)->except('show')
            ->middlewareFor(['index'], 'permission:users.view')
            ->middlewareFor(['create', 'store'], 'permission:users.create')
            ->middlewareFor(['edit', 'update'], 'permission:users.update')
            ->middlewareFor(['destroy'], 'permission:users.delete');
        Route::resource('roles', AdminRoleController::class)->except('show')
            ->middlewareFor(['index'], 'permission:roles.view')
            ->middlewareFor(['create', 'store'], 'permission:roles.create')
            ->middlewareFor(['edit', 'update'], 'permission:roles.update')
            ->middlewareFor(['destroy'], 'permission:roles.delete');
    });

// Root path redirects to localized home based on admin setting
Route::get('/', function () {
    $defaultLocale = setting('default_locale', config('locales.route_default', 'vi'));
    return redirect('/' . $defaultLocale, 302);
})->name('home');

// Public routes without locale prefix (payment callbacks, webhooks, chat)
Route::get('/payments/viva/return', [PaymentController::class, 'vivaReturn'])->name('payments.viva.return');
Route::get('/payments/viva/failure', [PaymentController::class, 'vivaFailure'])->name('payments.viva.failure');
Route::post('/payments/viva/continue/{order:code}', [PaymentController::class, 'vivaContinue'])->name('payments.viva.continue')->middleware('throttle:6,1');
Route::post('/payments/viva/reminder/dismiss', [PaymentController::class, 'dismissVivaReminder'])->name('payments.viva.reminder.dismiss');
Route::get('/payments/viva/webhook', [PaymentController::class, 'vivaWebhookVerification'])->name('payments.viva.webhook.verify');
Route::post('/payments/viva/webhook', [PaymentController::class, 'vivaWebhook'])->name('payments.viva.webhook');

Route::post('/chat/start', [ChatController::class, 'start'])->name('chat.start');
Route::get('/chat/{chatSession}/messages', [ChatController::class, 'messages'])->name('chat.messages');
Route::post('/chat/{chatSession}/messages', [ChatController::class, 'send'])->name('chat.send');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', function () {
    $content = setting('robots_txt_content') ?: "User-agent: *\nAllow: /\nSitemap: ".route('sitemap')."\n";

    return response(
        $content,
        200,
        ['Content-Type' => 'text/plain']
    );
})->name('robots');

// ============================================
// VIETNAMESE LOCALIZED ROUTES (/vi/*)
// ============================================
Route::prefix('vi')
    ->as('localized.vi.')
    ->middleware('locale:vi')
    ->group(function (): void {
        Route::get('/', StorefrontHomeController::class)->name('home');
        Route::get('/gioi-thieu', fn () => view('storefront.about', [
            'seo' => \App\Services\SeoService::page(
                __('site.about.fallback_title'),
                __('site.about.fallback_description'),
                'paprika, vietnamese, greek, about, patras',
                route('localized.vi.about')
            ),
        ]))->name('about');
        Route::get('/khong-gian', GalleryController::class)->name('gallery.index');
        Route::get('/trang/{page:slug}', [PageController::class, 'show'])->name('pages.show');

        Route::get('/thuc-don', [StorefrontMenuController::class, 'index'])->name('menu.index');
        Route::get('/mon-an/{slug}', [StorefrontMenuController::class, 'show'])->name('menu.show');

        Route::post('/chi-nhanh', [StorefrontBranchController::class, 'set'])->name('branch.set');

        Route::get('/gio-hang', [StorefrontCartController::class, 'index'])->name('cart.index');
        Route::post('/gio-hang/{dish:slug}', [CartController::class, 'add'])->name('cart.add');
        Route::patch('/gio-hang', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/gio-hang/item/{lineKey}', [CartController::class, 'remove'])->name('cart.remove');
        Route::get('/dat-hang', [StorefrontCheckoutController::class, 'create'])->name('checkout.create');
        Route::post('/dat-hang/phi-ship', [StorefrontCheckoutController::class, 'deliveryQuote'])->name('checkout.delivery-quote');
        Route::post('/dat-hang/goi-y-dia-chi', [StorefrontCheckoutController::class, 'addressSuggest'])->name('checkout.address-suggest');
        Route::post('/dat-hang/dia-chi-tu-toa-do', [StorefrontCheckoutController::class, 'addressReverse'])->name('checkout.address-reverse');
        Route::post('/dat-hang/kiem-tra-khung-gio', [StorefrontCheckoutController::class, 'availability'])->name('checkout.availability');
        Route::post('/dat-hang/voucher', [StorefrontCheckoutController::class, 'voucherPreview'])->name('checkout.voucher-preview');
        Route::delete('/dat-hang/voucher', [StorefrontCheckoutController::class, 'clearVoucher'])->name('checkout.voucher-clear');
        Route::post('/dat-hang', [StorefrontCheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/dat-hang/thanh-cong/{order:code}', [StorefrontCheckoutController::class, 'success'])->name('checkout.success');
        Route::get('/tra-cuu-don-hang', [StorefrontOrderTrackingController::class, 'index'])->name('order.lookup')->middleware('throttle:10,1');
        Route::get('/tra-cuu-don-hang/ket-qua', [StorefrontOrderTrackingController::class, 'lookup'])->name('order.lookup.results')->middleware('throttle:10,1');
        Route::get('/don-hang/{order:code}', [StorefrontOrderTrackingController::class, 'show'])->name('order.track');
        Route::post('/don-hang/{order:code}/thanh-toan-lai', [StorefrontOrderTrackingController::class, 'retryPayment'])->name('order.retry-payment')->middleware('throttle:6,1');

        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

        Route::get('/dat-ban', [StorefrontReservationController::class, 'create'])->name('reservations.create');
        Route::get('/dat-ban/ban-trong', [StorefrontReservationController::class, 'availability'])->name('reservations.availability');
        Route::post('/dat-ban', [ReservationController::class, 'store'])->name('reservations.store');

        Route::get('/lien-he', [ContactController::class, 'create'])->name('contact');
        Route::post('/lien-he', [ContactController::class, 'store'])->name('contact.store');
    });

// ============================================
// ENGLISH LOCALIZED ROUTES (/en/*)
// ============================================
Route::prefix('en')
    ->as('localized.en.')
    ->middleware('locale:en')
    ->group(function (): void {
        Route::get('/', StorefrontHomeController::class)->name('home');
        Route::get('/about', fn () => view('storefront.about', [
            'seo' => \App\Services\SeoService::page(
                __('site.about.fallback_title'),
                __('site.about.fallback_description'),
                'paprika, vietnamese, greek, about, patras',
                route('localized.en.about')
            ),
        ]))->name('about');
        Route::get('/space', GalleryController::class)->name('gallery.index');
        Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');

        Route::get('/menu', [StorefrontMenuController::class, 'index'])->name('menu.index');
        Route::get('/dishes/{slug}', [StorefrontMenuController::class, 'show'])->name('menu.show');

        Route::post('/branch', [StorefrontBranchController::class, 'set'])->name('branch.set');

        Route::get('/cart', [StorefrontCartController::class, 'index'])->name('cart.index');
        Route::post('/cart/{dish:slug}', [CartController::class, 'add'])->name('cart.add');
        Route::patch('/cart', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/item/{lineKey}', [CartController::class, 'remove'])->name('cart.remove');
        Route::get('/checkout', [StorefrontCheckoutController::class, 'create'])->name('checkout.create');
        Route::post('/checkout/delivery-quote', [StorefrontCheckoutController::class, 'deliveryQuote'])->name('checkout.delivery-quote');
        Route::post('/checkout/address-suggest', [StorefrontCheckoutController::class, 'addressSuggest'])->name('checkout.address-suggest');
        Route::post('/checkout/address-reverse', [StorefrontCheckoutController::class, 'addressReverse'])->name('checkout.address-reverse');
        Route::post('/checkout/availability', [StorefrontCheckoutController::class, 'availability'])->name('checkout.availability');
        Route::post('/checkout/voucher', [StorefrontCheckoutController::class, 'voucherPreview'])->name('checkout.voucher-preview');
        Route::delete('/checkout/voucher', [StorefrontCheckoutController::class, 'clearVoucher'])->name('checkout.voucher-clear');
        Route::post('/checkout', [StorefrontCheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/checkout/success/{order:code}', [StorefrontCheckoutController::class, 'success'])->name('checkout.success');
        Route::get('/order-lookup', [StorefrontOrderTrackingController::class, 'index'])->name('order.lookup')->middleware('throttle:10,1');
        Route::get('/order-lookup/results', [StorefrontOrderTrackingController::class, 'lookup'])->name('order.lookup.results')->middleware('throttle:10,1');
        Route::get('/order/{order:code}', [StorefrontOrderTrackingController::class, 'show'])->name('order.track');
        Route::post('/order/{order:code}/retry-payment', [StorefrontOrderTrackingController::class, 'retryPayment'])->name('order.retry-payment')->middleware('throttle:6,1');

        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

        Route::get('/reservation', [StorefrontReservationController::class, 'create'])->name('reservations.create');
        Route::get('/reservation/availability', [StorefrontReservationController::class, 'availability'])->name('reservations.availability');
        Route::post('/reservation', [ReservationController::class, 'store'])->name('reservations.store');

        Route::get('/contact', [ContactController::class, 'create'])->name('contact');
        Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
    });

// ============================================
// GREEK LOCALIZED ROUTES (/el/*)
// ============================================
Route::prefix('el')
    ->as('localized.el.')
    ->middleware('locale:el')
    ->group(function (): void {
        Route::get('/', StorefrontHomeController::class)->name('home');
        Route::get('/schetikos', fn () => view('storefront.about', [
            'seo' => \App\Services\SeoService::page(
                __('site.about.fallback_title'),
                __('site.about.fallback_description'),
                'paprika, vietnamese, greek, about, patras',
                route('localized.el.about')
            ),
        ]))->name('about');
        Route::get('/choros', GalleryController::class)->name('gallery.index');
        Route::get('/selides/{page:slug}', [PageController::class, 'show'])->name('pages.show');

        Route::get('/menou', [StorefrontMenuController::class, 'index'])->name('menu.index');
        Route::get('/piata/{slug}', [StorefrontMenuController::class, 'show'])->name('menu.show');

        Route::post('/katastima', [StorefrontBranchController::class, 'set'])->name('branch.set');

        Route::get('/kalaithi', [StorefrontCartController::class, 'index'])->name('cart.index');
        Route::post('/kalaithi/{dish:slug}', [CartController::class, 'add'])->name('cart.add');
        Route::patch('/kalaithi', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/kalaithi/item/{lineKey}', [CartController::class, 'remove'])->name('cart.remove');
        Route::get('/tameio', [StorefrontCheckoutController::class, 'create'])->name('checkout.create');
        Route::post('/tameio/ypologismos-apostolis', [StorefrontCheckoutController::class, 'deliveryQuote'])->name('checkout.delivery-quote');
        Route::post('/tameio/protaseis-dieythynsis', [StorefrontCheckoutController::class, 'addressSuggest'])->name('checkout.address-suggest');
        Route::post('/tameio/dieythynsi-apo-syntetagmenes', [StorefrontCheckoutController::class, 'addressReverse'])->name('checkout.address-reverse');
        Route::post('/tameio/diathesimotita', [StorefrontCheckoutController::class, 'availability'])->name('checkout.availability');
        Route::post('/tameio/kouponi', [StorefrontCheckoutController::class, 'voucherPreview'])->name('checkout.voucher-preview');
        Route::delete('/tameio/kouponi', [StorefrontCheckoutController::class, 'clearVoucher'])->name('checkout.voucher-clear');
        Route::post('/tameio', [StorefrontCheckoutController::class, 'store'])->name('checkout.store');
        Route::get('/tameio/epitychia/{order:code}', [StorefrontCheckoutController::class, 'success'])->name('checkout.success');
        Route::get('/anazitisi-parangelias', [StorefrontOrderTrackingController::class, 'index'])->name('order.lookup')->middleware('throttle:10,1');
        Route::get('/anazitisi-parangelias/apotelesmata', [StorefrontOrderTrackingController::class, 'lookup'])->name('order.lookup.results')->middleware('throttle:10,1');
        Route::get('/parangelia/{order:code}', [StorefrontOrderTrackingController::class, 'show'])->name('order.track');
        Route::post('/parangelia/{order:code}/epanapliromi', [StorefrontOrderTrackingController::class, 'retryPayment'])->name('order.retry-payment')->middleware('throttle:6,1');

        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

        Route::get('/kratise', [StorefrontReservationController::class, 'create'])->name('reservations.create');
        Route::get('/kratise/diathesima-trapezia', [StorefrontReservationController::class, 'availability'])->name('reservations.availability');
        Route::post('/kratise', [ReservationController::class, 'store'])->name('reservations.store');

        Route::get('/epikoinonia', [ContactController::class, 'create'])->name('contact');
        Route::post('/epikoinonia', [ContactController::class, 'store'])->name('contact.store');
    });
