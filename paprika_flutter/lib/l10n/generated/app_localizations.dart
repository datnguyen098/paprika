import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_el.dart';
import 'app_localizations_en.dart';
import 'app_localizations_vi.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'generated/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
      : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
    delegate,
    GlobalMaterialLocalizations.delegate,
    GlobalCupertinoLocalizations.delegate,
    GlobalWidgetsLocalizations.delegate,
  ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('el'),
    Locale('en'),
    Locale('vi')
  ];

  /// App display name
  ///
  /// In en, this message translates to:
  /// **'Paprika Patras'**
  String get appTitle;

  /// No description provided for @languageSwitcherTooltip.
  ///
  /// In en, this message translates to:
  /// **'Language / Ngôn ngữ / Γλώσσα'**
  String get languageSwitcherTooltip;

  /// No description provided for @commonRetry.
  ///
  /// In en, this message translates to:
  /// **'Retry'**
  String get commonRetry;

  /// No description provided for @commonClose.
  ///
  /// In en, this message translates to:
  /// **'Close'**
  String get commonClose;

  /// No description provided for @commonSubmit.
  ///
  /// In en, this message translates to:
  /// **'Submit'**
  String get commonSubmit;

  /// No description provided for @commonCancel.
  ///
  /// In en, this message translates to:
  /// **'Cancel'**
  String get commonCancel;

  /// No description provided for @commonLoading.
  ///
  /// In en, this message translates to:
  /// **'Loading...'**
  String get commonLoading;

  /// No description provided for @commonSending.
  ///
  /// In en, this message translates to:
  /// **'Sending...'**
  String get commonSending;

  /// No description provided for @commonSeeMore.
  ///
  /// In en, this message translates to:
  /// **'See more'**
  String get commonSeeMore;

  /// No description provided for @commonErrorGeneric.
  ///
  /// In en, this message translates to:
  /// **'Something went wrong. Please try again.'**
  String get commonErrorGeneric;

  /// No description provided for @navHome.
  ///
  /// In en, this message translates to:
  /// **'Home'**
  String get navHome;

  /// No description provided for @navMenu.
  ///
  /// In en, this message translates to:
  /// **'Menu'**
  String get navMenu;

  /// No description provided for @navAbout.
  ///
  /// In en, this message translates to:
  /// **'About'**
  String get navAbout;

  /// No description provided for @navBranches.
  ///
  /// In en, this message translates to:
  /// **'Branches'**
  String get navBranches;

  /// No description provided for @navContact.
  ///
  /// In en, this message translates to:
  /// **'Contact'**
  String get navContact;

  /// No description provided for @navReservation.
  ///
  /// In en, this message translates to:
  /// **'Reservation'**
  String get navReservation;

  /// No description provided for @navCart.
  ///
  /// In en, this message translates to:
  /// **'Cart'**
  String get navCart;

  /// No description provided for @navProfile.
  ///
  /// In en, this message translates to:
  /// **'Profile'**
  String get navProfile;

  /// No description provided for @navSearch.
  ///
  /// In en, this message translates to:
  /// **'Search'**
  String get navSearch;

  /// No description provided for @navOrders.
  ///
  /// In en, this message translates to:
  /// **'Orders'**
  String get navOrders;

  /// No description provided for @navNotifications.
  ///
  /// In en, this message translates to:
  /// **'Notifications'**
  String get navNotifications;

  /// No description provided for @navReservations.
  ///
  /// In en, this message translates to:
  /// **'My reservations'**
  String get navReservations;

  /// No description provided for @bottomNavHome.
  ///
  /// In en, this message translates to:
  /// **'HOME'**
  String get bottomNavHome;

  /// No description provided for @bottomNavMenu.
  ///
  /// In en, this message translates to:
  /// **'MENU'**
  String get bottomNavMenu;

  /// No description provided for @bottomNavReservation.
  ///
  /// In en, this message translates to:
  /// **'RESERVE'**
  String get bottomNavReservation;

  /// No description provided for @bottomNavCart.
  ///
  /// In en, this message translates to:
  /// **'CART'**
  String get bottomNavCart;

  /// No description provided for @heroBadge.
  ///
  /// In en, this message translates to:
  /// **'Order online'**
  String get heroBadge;

  /// No description provided for @heroTitleFallback.
  ///
  /// In en, this message translates to:
  /// **'Paprika - Vietnamese Cuisine'**
  String get heroTitleFallback;

  /// No description provided for @heroSubtitleFallback.
  ///
  /// In en, this message translates to:
  /// **'Pho, banh mi, spring rolls and grilled dishes in Patras'**
  String get heroSubtitleFallback;

  /// No description provided for @heroCta.
  ///
  /// In en, this message translates to:
  /// **'ORDER NOW'**
  String get heroCta;

  /// No description provided for @statFreshValue.
  ///
  /// In en, this message translates to:
  /// **'100%'**
  String get statFreshValue;

  /// No description provided for @statFreshLabel.
  ///
  /// In en, this message translates to:
  /// **'Fresh'**
  String get statFreshLabel;

  /// No description provided for @statFastValue.
  ///
  /// In en, this message translates to:
  /// **'Fast'**
  String get statFastValue;

  /// No description provided for @statFastLabel.
  ///
  /// In en, this message translates to:
  /// **'Pickup'**
  String get statFastLabel;

  /// No description provided for @statEasyValue.
  ///
  /// In en, this message translates to:
  /// **'Easy'**
  String get statEasyValue;

  /// No description provided for @statEasyLabel.
  ///
  /// In en, this message translates to:
  /// **'Payment'**
  String get statEasyLabel;

  /// No description provided for @sectionDiscover.
  ///
  /// In en, this message translates to:
  /// **'Discover'**
  String get sectionDiscover;

  /// No description provided for @sectionBestSellers.
  ///
  /// In en, this message translates to:
  /// **'Best Sellers'**
  String get sectionBestSellers;

  /// No description provided for @sectionBestSellersSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Our most loved dishes at Paprika Patras'**
  String get sectionBestSellersSubtitle;

  /// No description provided for @sectionGallery.
  ///
  /// In en, this message translates to:
  /// **'Inside\nPaprika Patras'**
  String get sectionGallery;

  /// No description provided for @sectionGallerySubtitle.
  ///
  /// In en, this message translates to:
  /// **'A warm space, open kitchen and a VIP room — a touch of Vietnam in the heart of Patras.'**
  String get sectionGallerySubtitle;

  /// No description provided for @sectionServices.
  ///
  /// In en, this message translates to:
  /// **'Services'**
  String get sectionServices;

  /// No description provided for @sectionServicesTitle.
  ///
  /// In en, this message translates to:
  /// **'Choose how to enjoy'**
  String get sectionServicesTitle;

  /// No description provided for @sectionServicesSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Three ways to enjoy your favourite dishes'**
  String get sectionServicesSubtitle;

  /// No description provided for @sectionTestimonials.
  ///
  /// In en, this message translates to:
  /// **'What guests say'**
  String get sectionTestimonials;

  /// No description provided for @sectionAbout.
  ///
  /// In en, this message translates to:
  /// **'About us'**
  String get sectionAbout;

  /// No description provided for @sectionPromotions.
  ///
  /// In en, this message translates to:
  /// **'Special offers'**
  String get sectionPromotions;

  /// No description provided for @sectionPromotionsTitle.
  ///
  /// In en, this message translates to:
  /// **'Popular dishes right now'**
  String get sectionPromotionsTitle;

  /// No description provided for @promotionsBadgeFallback.
  ///
  /// In en, this message translates to:
  /// **'NEW OFFER'**
  String get promotionsBadgeFallback;

  /// No description provided for @promotionsViewMore.
  ///
  /// In en, this message translates to:
  /// **'SEE MORE PHOTOS'**
  String get promotionsViewMore;

  /// No description provided for @galleryBadge.
  ///
  /// In en, this message translates to:
  /// **'GALLERY'**
  String get galleryBadge;

  /// No description provided for @serviceDeliveryTitle.
  ///
  /// In en, this message translates to:
  /// **'Delivery'**
  String get serviceDeliveryTitle;

  /// No description provided for @serviceDeliverySubtitle.
  ///
  /// In en, this message translates to:
  /// **'Order online, delivered within 30 minutes in Patras.'**
  String get serviceDeliverySubtitle;

  /// No description provided for @serviceDeliveryCta.
  ///
  /// In en, this message translates to:
  /// **'Order now'**
  String get serviceDeliveryCta;

  /// No description provided for @servicePickupTitle.
  ///
  /// In en, this message translates to:
  /// **'Pickup'**
  String get servicePickupTitle;

  /// No description provided for @servicePickupSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Pre-order and skip the queue.'**
  String get servicePickupSubtitle;

  /// No description provided for @servicePickupCta.
  ///
  /// In en, this message translates to:
  /// **'Order now'**
  String get servicePickupCta;

  /// No description provided for @serviceDineInTitle.
  ///
  /// In en, this message translates to:
  /// **'Dine-in'**
  String get serviceDineInTitle;

  /// No description provided for @serviceDineInSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Reserve a table to get the best seats.'**
  String get serviceDineInSubtitle;

  /// No description provided for @serviceDineInCta.
  ///
  /// In en, this message translates to:
  /// **'Reserve a table'**
  String get serviceDineInCta;

  /// No description provided for @serviceSeeDetails.
  ///
  /// In en, this message translates to:
  /// **'See details'**
  String get serviceSeeDetails;

  /// No description provided for @dishTagNew.
  ///
  /// In en, this message translates to:
  /// **'NEW'**
  String get dishTagNew;

  /// No description provided for @discountPercent.
  ///
  /// In en, this message translates to:
  /// **'-{percent}%'**
  String discountPercent(int percent);

  /// No description provided for @testimonialsEmpty.
  ///
  /// In en, this message translates to:
  /// **'No reviews yet'**
  String get testimonialsEmpty;

  /// No description provided for @bestSellerBadge.
  ///
  /// In en, this message translates to:
  /// **'BEST SELLER'**
  String get bestSellerBadge;

  /// No description provided for @aboutCardBrand.
  ///
  /// In en, this message translates to:
  /// **'PAPRIKA PATRAS'**
  String get aboutCardBrand;

  /// No description provided for @aboutCardTagline.
  ///
  /// In en, this message translates to:
  /// **'Vietnamese & Greek Restaurant · Patras, GR'**
  String get aboutCardTagline;

  /// No description provided for @aboutCardBody.
  ///
  /// In en, this message translates to:
  /// **'Since 2019, Paprika Patras has offered a unique fusion menu between two cuisines: pho, bun cha, Vietnamese banh mi paired with gyros, moussaka and souvlaki. All ingredients are sourced fresh weekly and cooked by hand daily.'**
  String get aboutCardBody;

  /// No description provided for @aboutPillHours.
  ///
  /// In en, this message translates to:
  /// **'Open 11:30 - 23:00'**
  String get aboutPillHours;

  /// No description provided for @aboutPillLocation.
  ///
  /// In en, this message translates to:
  /// **'Patras, Greece'**
  String get aboutPillLocation;

  /// No description provided for @aboutPillRating.
  ///
  /// In en, this message translates to:
  /// **'4.7 / 5 on Google'**
  String get aboutPillRating;

  /// No description provided for @fabCallTooltip.
  ///
  /// In en, this message translates to:
  /// **'Call us'**
  String get fabCallTooltip;

  /// No description provided for @fabChatTooltip.
  ///
  /// In en, this message translates to:
  /// **'Live chat'**
  String get fabChatTooltip;

  /// No description provided for @fabCallFeature.
  ///
  /// In en, this message translates to:
  /// **'Phone call'**
  String get fabCallFeature;

  /// No description provided for @fabChatFeature.
  ///
  /// In en, this message translates to:
  /// **'Live chat'**
  String get fabChatFeature;

  /// No description provided for @footerHotline.
  ///
  /// In en, this message translates to:
  /// **'Hotline'**
  String get footerHotline;

  /// No description provided for @footerHotlineNumber.
  ///
  /// In en, this message translates to:
  /// **'+30 2610 123 456'**
  String get footerHotlineNumber;

  /// No description provided for @footerTagline.
  ///
  /// In en, this message translates to:
  /// **'Vietnamese flavours · Greek craft. Reserve a table or get it delivered.'**
  String get footerTagline;

  /// No description provided for @footerExplore.
  ///
  /// In en, this message translates to:
  /// **'Explore'**
  String get footerExplore;

  /// No description provided for @footerServices.
  ///
  /// In en, this message translates to:
  /// **'Services'**
  String get footerServices;

  /// No description provided for @footerNewsletter.
  ///
  /// In en, this message translates to:
  /// **'Newsletter'**
  String get footerNewsletter;

  /// No description provided for @footerNewsletterBody.
  ///
  /// In en, this message translates to:
  /// **'Subscribe to receive special offers and updates from Paprika Patras.'**
  String get footerNewsletterBody;

  /// No description provided for @footerNewsletterPlaceholder.
  ///
  /// In en, this message translates to:
  /// **'Your email...'**
  String get footerNewsletterPlaceholder;

  /// No description provided for @footerNewsletterFeature.
  ///
  /// In en, this message translates to:
  /// **'Newsletter signup'**
  String get footerNewsletterFeature;

  /// No description provided for @footerHours.
  ///
  /// In en, this message translates to:
  /// **'Mon-Fri: 11:30 - 23:00\nSat-Sun: 11:00 - 01:00'**
  String get footerHours;

  /// No description provided for @footerAddress.
  ///
  /// In en, this message translates to:
  /// **'Patras, Greece'**
  String get footerAddress;

  /// No description provided for @footerAddressTitle.
  ///
  /// In en, this message translates to:
  /// **'Address'**
  String get footerAddressTitle;

  /// No description provided for @footerHoursTitle.
  ///
  /// In en, this message translates to:
  /// **'Opening hours'**
  String get footerHoursTitle;

  /// No description provided for @footerCopyright.
  ///
  /// In en, this message translates to:
  /// **'© 2026 Paprika Patras. All rights reserved.'**
  String get footerCopyright;

  /// No description provided for @footerLinkContact.
  ///
  /// In en, this message translates to:
  /// **'Contact'**
  String get footerLinkContact;

  /// No description provided for @footerLinkOrderLookup.
  ///
  /// In en, this message translates to:
  /// **'Track order'**
  String get footerLinkOrderLookup;

  /// No description provided for @footerContactFeature.
  ///
  /// In en, this message translates to:
  /// **'Contact'**
  String get footerContactFeature;

  /// No description provided for @footerMenuFeature.
  ///
  /// In en, this message translates to:
  /// **'Menu'**
  String get footerMenuFeature;

  /// No description provided for @footerAboutFeature.
  ///
  /// In en, this message translates to:
  /// **'About'**
  String get footerAboutFeature;

  /// No description provided for @footerReservationFeature.
  ///
  /// In en, this message translates to:
  /// **'Reservation'**
  String get footerReservationFeature;

  /// No description provided for @comingSoonDefault.
  ///
  /// In en, this message translates to:
  /// **'This feature is coming soon'**
  String get comingSoonDefault;

  /// No description provided for @comingSoonFeature.
  ///
  /// In en, this message translates to:
  /// **'\"{feature}\" is coming soon'**
  String comingSoonFeature(String feature);

  /// No description provided for @comingSoonHint.
  ///
  /// In en, this message translates to:
  /// **'Team FE is finalising it. Please come back later.'**
  String get comingSoonHint;

  /// No description provided for @featureMenu.
  ///
  /// In en, this message translates to:
  /// **'Menu'**
  String get featureMenu;

  /// No description provided for @featureCart.
  ///
  /// In en, this message translates to:
  /// **'Cart'**
  String get featureCart;

  /// No description provided for @featureReservation.
  ///
  /// In en, this message translates to:
  /// **'Reservation'**
  String get featureReservation;

  /// No description provided for @featureOrders.
  ///
  /// In en, this message translates to:
  /// **'Orders'**
  String get featureOrders;

  /// No description provided for @featureReservations.
  ///
  /// In en, this message translates to:
  /// **'My reservations'**
  String get featureReservations;

  /// No description provided for @featureSearch.
  ///
  /// In en, this message translates to:
  /// **'Search dishes'**
  String get featureSearch;

  /// No description provided for @featureProfile.
  ///
  /// In en, this message translates to:
  /// **'Profile'**
  String get featureProfile;

  /// No description provided for @featureNotifications.
  ///
  /// In en, this message translates to:
  /// **'Notifications'**
  String get featureNotifications;

  /// No description provided for @aboutPageBadge.
  ///
  /// In en, this message translates to:
  /// **'ABOUT PAPRIKA'**
  String get aboutPageBadge;

  /// No description provided for @aboutPageBodyTitle.
  ///
  /// In en, this message translates to:
  /// **'Our Story'**
  String get aboutPageBodyTitle;

  /// No description provided for @aboutCtaTitle.
  ///
  /// In en, this message translates to:
  /// **'Ready to\nexplore your taste buds?'**
  String get aboutCtaTitle;

  /// No description provided for @aboutCtaBody.
  ///
  /// In en, this message translates to:
  /// **'Order online for delivery perks, or reserve a table at our warm Vietnamese-Greek space today!'**
  String get aboutCtaBody;

  /// No description provided for @aboutErrorTitle.
  ///
  /// In en, this message translates to:
  /// **'Couldn\'t load About page'**
  String get aboutErrorTitle;

  /// No description provided for @aboutRetry.
  ///
  /// In en, this message translates to:
  /// **'Retry'**
  String get aboutRetry;

  /// No description provided for @branchListBadge.
  ///
  /// In en, this message translates to:
  /// **'BRANCHES'**
  String get branchListBadge;

  /// No description provided for @branchListTitle.
  ///
  /// In en, this message translates to:
  /// **'ALL BRANCHES'**
  String get branchListTitle;

  /// No description provided for @branchListSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Pick the nearest branch to reserve or order.'**
  String get branchListSubtitle;

  /// No description provided for @branchEmpty.
  ///
  /// In en, this message translates to:
  /// **'No branches yet'**
  String get branchEmpty;

  /// No description provided for @branchErrorTitle.
  ///
  /// In en, this message translates to:
  /// **'Couldn\'t load branches'**
  String get branchErrorTitle;

  /// No description provided for @branchOpenBadge.
  ///
  /// In en, this message translates to:
  /// **'OPEN'**
  String get branchOpenBadge;

  /// No description provided for @branchButtonDetails.
  ///
  /// In en, this message translates to:
  /// **'Details'**
  String get branchButtonDetails;

  /// No description provided for @branchButtonCall.
  ///
  /// In en, this message translates to:
  /// **'Call'**
  String get branchButtonCall;

  /// No description provided for @branchDetailFallbackName.
  ///
  /// In en, this message translates to:
  /// **'Branch'**
  String get branchDetailFallbackName;

  /// No description provided for @branchDetailCall.
  ///
  /// In en, this message translates to:
  /// **'Call now'**
  String get branchDetailCall;

  /// No description provided for @branchDetailDirections.
  ///
  /// In en, this message translates to:
  /// **'Directions'**
  String get branchDetailDirections;

  /// No description provided for @branchDetailServiceModes.
  ///
  /// In en, this message translates to:
  /// **'Service modes'**
  String get branchDetailServiceModes;

  /// No description provided for @branchDetailModeOnline.
  ///
  /// In en, this message translates to:
  /// **'Online order'**
  String get branchDetailModeOnline;

  /// No description provided for @branchDetailModePickup.
  ///
  /// In en, this message translates to:
  /// **'Pickup'**
  String get branchDetailModePickup;

  /// No description provided for @branchDetailModeDelivery.
  ///
  /// In en, this message translates to:
  /// **'Delivery'**
  String get branchDetailModeDelivery;

  /// No description provided for @branchDetailDeliveryInfo.
  ///
  /// In en, this message translates to:
  /// **'Delivery information'**
  String get branchDetailDeliveryInfo;

  /// No description provided for @branchDetailMinOrder.
  ///
  /// In en, this message translates to:
  /// **'Minimum order'**
  String get branchDetailMinOrder;

  /// No description provided for @branchDetailFreeDelivery.
  ///
  /// In en, this message translates to:
  /// **'Free delivery'**
  String get branchDetailFreeDelivery;

  /// No description provided for @branchDetailFreeDeliveryFrom.
  ///
  /// In en, this message translates to:
  /// **'from €{amount}'**
  String branchDetailFreeDeliveryFrom(String amount);

  /// No description provided for @branchDetailRadius.
  ///
  /// In en, this message translates to:
  /// **'Delivery radius'**
  String get branchDetailRadius;

  /// No description provided for @branchDetailRadiusValue.
  ///
  /// In en, this message translates to:
  /// **'{km} km'**
  String branchDetailRadiusValue(int km);

  /// No description provided for @branchDetailMap.
  ///
  /// In en, this message translates to:
  /// **'Map'**
  String get branchDetailMap;

  /// No description provided for @branchDetailMapUnavailable.
  ///
  /// In en, this message translates to:
  /// **'Map unavailable'**
  String get branchDetailMapUnavailable;

  /// No description provided for @branchDetailMapOpen.
  ///
  /// In en, this message translates to:
  /// **'Open map'**
  String get branchDetailMapOpen;

  /// No description provided for @branchDetailErrorTitle.
  ///
  /// In en, this message translates to:
  /// **'Couldn\'t load branch details'**
  String get branchDetailErrorTitle;

  /// No description provided for @contactBadge.
  ///
  /// In en, this message translates to:
  /// **'CONTACT'**
  String get contactBadge;

  /// No description provided for @contactTitle.
  ///
  /// In en, this message translates to:
  /// **'GET IN TOUCH'**
  String get contactTitle;

  /// No description provided for @contactSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Send us a message and we\'ll reply by email.'**
  String get contactSubtitle;

  /// No description provided for @contactFieldName.
  ///
  /// In en, this message translates to:
  /// **'Full name'**
  String get contactFieldName;

  /// No description provided for @contactFieldNameHint.
  ///
  /// In en, this message translates to:
  /// **'John Doe'**
  String get contactFieldNameHint;

  /// No description provided for @contactFieldEmail.
  ///
  /// In en, this message translates to:
  /// **'Email'**
  String get contactFieldEmail;

  /// No description provided for @contactFieldPhone.
  ///
  /// In en, this message translates to:
  /// **'Phone'**
  String get contactFieldPhone;

  /// No description provided for @contactFieldPhoneHint.
  ///
  /// In en, this message translates to:
  /// **'+30 2610 123 456'**
  String get contactFieldPhoneHint;

  /// No description provided for @contactFieldSubject.
  ///
  /// In en, this message translates to:
  /// **'Subject'**
  String get contactFieldSubject;

  /// No description provided for @contactFieldSubjectHint.
  ///
  /// In en, this message translates to:
  /// **'Message subject (optional)'**
  String get contactFieldSubjectHint;

  /// No description provided for @contactFieldMessage.
  ///
  /// In en, this message translates to:
  /// **'Message'**
  String get contactFieldMessage;

  /// No description provided for @contactFieldMessageHint.
  ///
  /// In en, this message translates to:
  /// **'Write your message...'**
  String get contactFieldMessageHint;

  /// No description provided for @contactSubmit.
  ///
  /// In en, this message translates to:
  /// **'SEND MESSAGE'**
  String get contactSubmit;

  /// No description provided for @contactReplyHint.
  ///
  /// In en, this message translates to:
  /// **'We\'ll get back to you within 24 hours.'**
  String get contactReplyHint;

  /// No description provided for @contactSuccessTitle.
  ///
  /// In en, this message translates to:
  /// **'Message sent successfully!'**
  String get contactSuccessTitle;

  /// No description provided for @contactSuccessBody.
  ///
  /// In en, this message translates to:
  /// **'We\'ll reply by email as soon as possible.'**
  String get contactSuccessBody;

  /// No description provided for @requiredAsterisk.
  ///
  /// In en, this message translates to:
  /// **' *'**
  String get requiredAsterisk;

  /// No description provided for @menuTitle.
  ///
  /// In en, this message translates to:
  /// **'Paprika menu'**
  String get menuTitle;

  /// No description provided for @menuSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Slow-simmered pho, charcoal-grilled nem, authentic Athens gyros pita — pick your favourite.'**
  String get menuSubtitle;

  /// No description provided for @menuSearchHint.
  ///
  /// In en, this message translates to:
  /// **'Search dishes...'**
  String get menuSearchHint;

  /// No description provided for @menuCategoryAll.
  ///
  /// In en, this message translates to:
  /// **'All'**
  String get menuCategoryAll;

  /// No description provided for @menuEmpty.
  ///
  /// In en, this message translates to:
  /// **'No dishes in this category yet'**
  String get menuEmpty;

  /// No description provided for @menuLoadMore.
  ///
  /// In en, this message translates to:
  /// **'Load more'**
  String get menuLoadMore;

  /// No description provided for @menuFeaturedFilter.
  ///
  /// In en, this message translates to:
  /// **'Featured only'**
  String get menuFeaturedFilter;

  /// No description provided for @categoryLoadError.
  ///
  /// In en, this message translates to:
  /// **'Couldn\'t load categories'**
  String get categoryLoadError;

  /// No description provided for @searchClearTooltip.
  ///
  /// In en, this message translates to:
  /// **'Clear'**
  String get searchClearTooltip;

  /// No description provided for @dishSoldOut.
  ///
  /// In en, this message translates to:
  /// **'SOLD OUT'**
  String get dishSoldOut;

  /// No description provided for @menuErrorTitle.
  ///
  /// In en, this message translates to:
  /// **'Couldn\'t load menu'**
  String get menuErrorTitle;

  /// No description provided for @menuErrorRetry.
  ///
  /// In en, this message translates to:
  /// **'Retry'**
  String get menuErrorRetry;

  /// No description provided for @paginationPrev.
  ///
  /// In en, this message translates to:
  /// **'Previous page'**
  String get paginationPrev;

  /// No description provided for @paginationNext.
  ///
  /// In en, this message translates to:
  /// **'Next page'**
  String get paginationNext;

  /// No description provided for @reservationBadge.
  ///
  /// In en, this message translates to:
  /// **'RESERVATION'**
  String get reservationBadge;

  /// No description provided for @reservationTitle.
  ///
  /// In en, this message translates to:
  /// **'RESERVE A TABLE AT PAPRIKA'**
  String get reservationTitle;

  /// No description provided for @reservationSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Pick the branch, date, time and number of guests. We\'ll confirm by phone.'**
  String get reservationSubtitle;

  /// No description provided for @reservationFieldName.
  ///
  /// In en, this message translates to:
  /// **'Full name'**
  String get reservationFieldName;

  /// No description provided for @reservationFieldPhone.
  ///
  /// In en, this message translates to:
  /// **'Phone'**
  String get reservationFieldPhone;

  /// No description provided for @reservationFieldEmail.
  ///
  /// In en, this message translates to:
  /// **'Email'**
  String get reservationFieldEmail;

  /// No description provided for @reservationFieldDate.
  ///
  /// In en, this message translates to:
  /// **'Date'**
  String get reservationFieldDate;

  /// No description provided for @reservationFieldTime.
  ///
  /// In en, this message translates to:
  /// **'Time'**
  String get reservationFieldTime;

  /// No description provided for @reservationFieldGuests.
  ///
  /// In en, this message translates to:
  /// **'Guests'**
  String get reservationFieldGuests;

  /// No description provided for @reservationFieldBranch.
  ///
  /// In en, this message translates to:
  /// **'Branch'**
  String get reservationFieldBranch;

  /// No description provided for @reservationFieldNotes.
  ///
  /// In en, this message translates to:
  /// **'Notes'**
  String get reservationFieldNotes;

  /// No description provided for @reservationSubmit.
  ///
  /// In en, this message translates to:
  /// **'HOLD THE TABLE'**
  String get reservationSubmit;

  /// No description provided for @reservationReplyHint.
  ///
  /// In en, this message translates to:
  /// **'We\'ll call to confirm your booking within 30 minutes.'**
  String get reservationReplyHint;

  /// No description provided for @reservationSuccessTitle.
  ///
  /// In en, this message translates to:
  /// **'Request sent'**
  String get reservationSuccessTitle;

  /// No description provided for @reservationSuccessBody.
  ///
  /// In en, this message translates to:
  /// **'The restaurant will call to confirm.'**
  String get reservationSuccessBody;

  /// No description provided for @reservationHeroTitle.
  ///
  /// In en, this message translates to:
  /// **'RESERVE A TABLE AT PAPRIKA'**
  String get reservationHeroTitle;

  /// No description provided for @quickActionDateLabel.
  ///
  /// In en, this message translates to:
  /// **'DATE'**
  String get quickActionDateLabel;

  /// No description provided for @quickActionDateHint.
  ///
  /// In en, this message translates to:
  /// **'PICK'**
  String get quickActionDateHint;

  /// No description provided for @quickActionTimeLabel.
  ///
  /// In en, this message translates to:
  /// **'TIME'**
  String get quickActionTimeLabel;

  /// No description provided for @quickActionTimeHint.
  ///
  /// In en, this message translates to:
  /// **'open'**
  String get quickActionTimeHint;

  /// No description provided for @quickActionTimeHintValue.
  ///
  /// In en, this message translates to:
  /// **'12:00 - 22:30'**
  String get quickActionTimeHintValue;

  /// No description provided for @quickActionGuestsLabel.
  ///
  /// In en, this message translates to:
  /// **'GUESTS'**
  String get quickActionGuestsLabel;

  /// No description provided for @quickActionGuestsHint.
  ///
  /// In en, this message translates to:
  /// **'TABLES AVAILABLE'**
  String get quickActionGuestsHint;

  /// No description provided for @guestsPickerTitle.
  ///
  /// In en, this message translates to:
  /// **'GUESTS'**
  String get guestsPickerTitle;

  /// No description provided for @guestsPickerItem.
  ///
  /// In en, this message translates to:
  /// **'{n} guests'**
  String guestsPickerItem(int n);

  /// No description provided for @reservationCallTooltip.
  ///
  /// In en, this message translates to:
  /// **'Call hotline'**
  String get reservationCallTooltip;

  /// No description provided for @reservationChatTooltip.
  ///
  /// In en, this message translates to:
  /// **'Chat with restaurant'**
  String get reservationChatTooltip;

  /// No description provided for @reservationChatFeature.
  ///
  /// In en, this message translates to:
  /// **'Chat with restaurant'**
  String get reservationChatFeature;

  /// No description provided for @validationNameRequired.
  ///
  /// In en, this message translates to:
  /// **'Please enter your name'**
  String get validationNameRequired;

  /// No description provided for @validationPhoneRequired.
  ///
  /// In en, this message translates to:
  /// **'Please enter your phone number'**
  String get validationPhoneRequired;

  /// No description provided for @validationPhoneInvalid.
  ///
  /// In en, this message translates to:
  /// **'Invalid phone number'**
  String get validationPhoneInvalid;

  /// No description provided for @validationEmailInvalid.
  ///
  /// In en, this message translates to:
  /// **'Invalid email'**
  String get validationEmailInvalid;

  /// No description provided for @validationBranchRequired.
  ///
  /// In en, this message translates to:
  /// **'Please pick a branch'**
  String get validationBranchRequired;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['el', 'en', 'vi'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'el':
      return AppLocalizationsEl();
    case 'en':
      return AppLocalizationsEn();
    case 'vi':
      return AppLocalizationsVi();
  }

  throw FlutterError(
      'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
      'an issue with the localizations generation tool. Please file an issue '
      'on GitHub with a reproducible sample app and the gen-l10n configuration '
      'that was used.');
}
