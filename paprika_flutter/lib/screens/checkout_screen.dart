import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:geolocator/geolocator.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../data/models/branch_model.dart';
import '../data/models/order_model.dart';
import '../providers/providers.dart';
import '../services/api_service.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';
import '../widgets/page_transition_loader.dart';

class CheckoutScreen extends ConsumerStatefulWidget {
  const CheckoutScreen({super.key});

  @override
  ConsumerState<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends ConsumerState<CheckoutScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  final _addressController = TextEditingController();
  final _timeController = TextEditingController();
  final _noteController = TextEditingController();
  final _voucherController = TextEditingController();

  int? _branchId;
  String _fulfillmentMethod = 'pickup';
  bool _submitting = false;
  bool _voucherLoading = false;
  bool _deliveryQuoteLoading = false;
  bool _availabilityLoading = false;
  bool _locationLoading = false;
  String? _error;
  String? _voucherError;
  String? _deliveryQuoteError;
  String? _availabilityError;
  String? _locationError;
  VoucherPreviewResponse? _voucherQuote;
  DeliveryQuoteResponse? _deliveryQuote;
  OrderAvailabilityResponse? _availability;
  Map<String, String> _fieldErrors = const {};
  bool _loadedInitialVoucher = false;
  Timer? _deliveryQuoteDebounce;
  Timer? _addressSuggestDebounce;
  Timer? _availabilityDebounce;
  int _deliveryQuoteRequestId = 0;
  int _addressSuggestRequestId = 0;
  int _availabilityRequestId = 0;
  List<AddressSuggestion> _addressSuggestions = const [];
  bool _addressSuggestLoading = false;
  String? _addressSuggestError;
  double? _selectedAddressLatitude;
  double? _selectedAddressLongitude;
  String? _selectedAddressPlaceId;

  @override
  void dispose() {
    _deliveryQuoteDebounce?.cancel();
    _addressSuggestDebounce?.cancel();
    _availabilityDebounce?.cancel();
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _addressController.dispose();
    _timeController.dispose();
    _noteController.dispose();
    _voucherController.dispose();
    super.dispose();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_loadedInitialVoucher) return;
    _loadedInitialVoucher = true;

    final code = GoRouterState.of(context).uri.queryParameters['voucher'];
    if (code != null && code.trim().isNotEmpty) {
      _voucherController.text = code.trim().toUpperCase();
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = ref.watch(cartProvider);
    final branches = ref.watch(branchesProvider);

    return Scaffold(
      backgroundColor: AppColors.cream,
      body: GestureDetector(
        behavior: HitTestBehavior.translucent,
        onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
        child: Column(
          children: [
            const PaprikaHeader(activeRoute: AppRoutes.cart),
            Expanded(
              child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const _CheckoutHero(),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
                    child: Center(
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(
                          maxWidth: AppConstants.maxContentWidth,
                        ),
                        child: cart.isEmpty
                            ? const _EmptyCheckout()
                            : branches.when(
                                data: (items) => _buildForm(items),
                                loading: () => const Padding(
                                  padding: EdgeInsets.all(30),
                                  child: Center(
                                    child: CircularProgressIndicator(
                                      color: AppColors.primary,
                                    ),
                                  ),
                                ),
                                error: (error, _) => _ErrorBox(
                                  message:
                                      'Không tải được danh sách chi nhánh. $error',
                                  onRetry: () => ref.invalidate(branchesProvider),
                                ),
                              ),
                      ),
                    ),
                  ),
                  const PaprikaFooter(),
                ],
              ),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }

  Widget _buildForm(List<Branch> branches) {
    final cart = ref.watch(cartProvider);
    final availableBranches = branches
        .where((branch) => branch.acceptsOfflinePayment != false)
        .toList();
    Branch? selectedBranch;
    for (final branch in availableBranches) {
      if (branch.id == _branchId) {
        selectedBranch = branch;
        break;
      }
    }
    final effectiveBranchId =
        selectedBranch?.id ?? (availableBranches.isNotEmpty ? availableBranches.first.id : null);

    return LayoutBuilder(
      builder: (context, constraints) {
        final isWide = constraints.maxWidth >= 880;
        final form = _CheckoutFormCard(
          formKey: _formKey,
          branches: availableBranches,
          branchId: effectiveBranchId,
          fulfillmentMethod: _fulfillmentMethod,
          nameController: _nameController,
          phoneController: _phoneController,
          emailController: _emailController,
          addressController: _addressController,
          timeController: _timeController,
          noteController: _noteController,
          voucherController: _voucherController,
          fieldErrors: _fieldErrors,
          error: _error,
          submitting: _submitting,
          voucherQuote: _voucherQuote,
          voucherLoading: _voucherLoading,
          voucherError: _voucherError,
          deliveryQuote: _deliveryQuote,
          deliveryQuoteLoading: _deliveryQuoteLoading,
          deliveryQuoteError: _deliveryQuoteError,
          availability: _availability,
          availabilityLoading: _availabilityLoading,
          availabilityError: _availabilityError,
          addressSuggestions: _addressSuggestions,
          addressSuggestLoading: _addressSuggestLoading,
          addressSuggestError: _addressSuggestError,
          locationLoading: _locationLoading,
          locationError: _locationError,
          onBranchChanged: (value) {
            setState(() => _branchId = value);
            _invalidateDeliveryQuote();
            _invalidateVoucherQuote();
            _scheduleAvailabilityCheck(value ?? effectiveBranchId);
            _scheduleAddressSuggest(value ?? effectiveBranchId);
            _scheduleDeliveryQuote(value ?? effectiveBranchId);
          },
          onMethodChanged: (value) {
            if (value == null) return;
            setState(() => _fulfillmentMethod = value);
            _invalidateDeliveryQuote();
            _invalidateVoucherQuote();
            _scheduleAddressSuggest(effectiveBranchId);
            _scheduleDeliveryQuote(effectiveBranchId);
          },
          onAddressChanged: (_) {
            _selectedAddressLatitude = null;
            _selectedAddressLongitude = null;
            _selectedAddressPlaceId = null;
            _locationError = null;
            _invalidateDeliveryQuote();
            _invalidateVoucherQuote();
            _scheduleAddressSuggest(effectiveBranchId);
            _scheduleDeliveryQuote(effectiveBranchId);
          },
          onAddressSuggestionSelected: (suggestion) =>
              _selectAddressSuggestion(suggestion, effectiveBranchId),
          onUseCurrentLocation: () => _useCurrentLocation(effectiveBranchId),
          onTimeChanged: (_) => _scheduleAvailabilityCheck(effectiveBranchId),
          onVoucherChanged: (_) => _invalidateVoucherQuote(),
          onApplyVoucher: () => _applyVoucher(effectiveBranchId),
          onClearVoucher: _clearVoucher,
          onSubmit: () => _submit(effectiveBranchId),
        );
        final summary = _CheckoutSummary(
          subtotal: cart.subtotal,
          itemsCount: cart.count,
          voucherQuote: _voucherQuote,
          fulfillmentMethod: _fulfillmentMethod,
          deliveryQuote: _deliveryQuote,
        );

        if (isWide) {
          return Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(flex: 3, child: form),
              const SizedBox(width: 18),
              Expanded(flex: 2, child: summary),
            ],
          );
        }

        return Column(
          children: [
            form,
            const SizedBox(height: 14),
            summary,
          ],
        );
      },
    );
  }

  Future<void> _submit(int? branchId) async {
    FocusScope.of(context).unfocus();
    setState(() {
      _error = null;
      _fieldErrors = const {};
    });

    if (branchId == null) {
      setState(() => _error = 'Vui lòng chọn chi nhánh.');
      return;
    }

    if (!_formKey.currentState!.validate()) return;

    final cart = ref.read(cartProvider);
    if (cart.isEmpty) {
      context.showPageLoader();
      context.go(AppRoutes.cart);
      return;
    }

    if (_fulfillmentMethod == 'delivery') {
      final quote = _deliveryQuote;
      if (quote == null || !quote.available) {
        final deliveryOk = await _calculateDeliveryQuote(branchId);
        if (!deliveryOk) return;
      }
    }

    final availabilityOk = await _checkOrderAvailability(branchId);
    if (!availabilityOk) return;

    final typedVoucherCode = _voucherController.text.trim();
    if (typedVoucherCode.isNotEmpty &&
        (_voucherQuote?.valid != true ||
            _voucherQuote?.subtotal != cart.subtotal ||
            _voucherQuote?.shippingFee != _currentShippingFee)) {
      final voucherIsValid = await _applyVoucher(branchId);
      if (!voucherIsValid) return;
    }

    setState(() => _submitting = true);
    try {
      final response = await ref.read(orderRepositoryProvider).createOrder(
            CreateOrderRequest(
              branchId: branchId,
              customerName: _nameController.text,
              customerPhone: _phoneController.text,
              customerEmail: _emailController.text,
              fulfillmentMethod: _fulfillmentMethod,
              deliveryAddress: _addressController.text,
              deliveryLatitude:
                  _deliveryQuote?.latitude ?? _selectedAddressLatitude,
              deliveryLongitude:
                  _deliveryQuote?.longitude ?? _selectedAddressLongitude,
              deliveryPlaceId: _deliveryQuote?.placeId ?? _selectedAddressPlaceId,
              requestedTime: _timeController.text.trim().isEmpty
                  ? null
                  : _timeController.text.trim(),
              voucherCode: _voucherController.text,
              note: _noteController.text,
              items: cart.items,
            ),
          );
      await ref.read(cartProvider.notifier).clear();
      if (!mounted) return;
      context.showPageLoader();
      final uri = Uri(
        path: AppRoutes.orderSuccessPath(response.code),
        queryParameters: {
          if (response.invoiceNumber != null) 'invoice': response.invoiceNumber,
          'total': response.total.toString(),
        },
      );
      context.go(uri.toString());
    } on ApiException catch (error) {
      if (!mounted) return;
      final fieldErrors = _flattenFieldErrors(error);
      setState(() {
        _fieldErrors = fieldErrors;
        _error = _generalOrderError(error, fieldErrors);
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = 'Không gửi được đơn hàng. Vui lòng thử lại.');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<bool> _applyVoucher(int? branchId) async {
    FocusScope.of(context).unfocus();
    final code = _voucherController.text.trim();
    setState(() {
      _voucherError = null;
      _fieldErrors = {
        ..._fieldErrors,
      }..remove('voucher_code');
    });

    if (code.isEmpty) {
      setState(() => _voucherError = 'Vui lòng nhập mã giảm giá.');
      return false;
    }

    final cart = ref.read(cartProvider);
    if (cart.isEmpty) {
      setState(() => _voucherError = 'Giỏ hàng đang trống.');
      return false;
    }

    setState(() => _voucherLoading = true);
    try {
      final quote = await ref.read(orderRepositoryProvider).previewVoucher(
            VoucherPreviewRequest(
              voucherCode: code,
              branchId: branchId,
              fulfillmentMethod: _fulfillmentMethod,
              subtotal: cart.subtotal,
              shippingFee: _currentShippingFee,
              customerEmail: _emailController.text,
              customerPhone: _phoneController.text,
            ),
          );

      if (!mounted) return false;
      setState(() {
        _voucherQuote = quote.valid ? quote : null;
        _voucherError = quote.valid
            ? null
            : (quote.message ?? 'Mã giảm giá không hợp lệ.');
        if (quote.valid && quote.voucher?.code.isNotEmpty == true) {
          _voucherController.text = quote.voucher!.code;
        }
      });
      return quote.valid;
    } on ApiException catch (error) {
      if (!mounted) return false;
      final fieldErrors = _flattenFieldErrors(error);
      setState(() {
        _voucherQuote = null;
        _fieldErrors = fieldErrors;
        _voucherError = fieldErrors['voucher_code'] ??
            error.message ??
            'Không kiểm tra được mã giảm giá.';
      });
      return false;
    } catch (_) {
      if (!mounted) return false;
      setState(() {
        _voucherQuote = null;
        _voucherError = 'Không kiểm tra được mã giảm giá. Vui lòng thử lại.';
      });
      return false;
    } finally {
      if (mounted) setState(() => _voucherLoading = false);
    }
  }

  Future<bool> _calculateDeliveryQuote(
    int? branchId, {
    bool dismissKeyboard = true,
    bool showEmptyAddressError = true,
  }) async {
    if (dismissKeyboard) FocusScope.of(context).unfocus();
    setState(() => _deliveryQuoteError = null);

    final cart = ref.read(cartProvider);
    if (branchId == null) {
      setState(() => _deliveryQuoteError = 'Vui lòng chọn chi nhánh.');
      return false;
    }

    if (cart.isEmpty) {
      setState(() => _deliveryQuoteError = 'Giỏ hàng đang trống.');
      return false;
    }

    if (_fulfillmentMethod != 'delivery') {
      setState(() => _deliveryQuote = null);
      return true;
    }

    final address = _addressController.text.trim();
    if (address.isEmpty) {
      if (showEmptyAddressError) {
        setState(
          () => _deliveryQuoteError = 'Vui lòng nhập địa chỉ giao hàng.',
        );
      }
      return false;
    }

    final requestId = ++_deliveryQuoteRequestId;
    setState(() => _deliveryQuoteLoading = true);
    try {
      final quote = await ref.read(orderRepositoryProvider).previewDeliveryQuote(
            DeliveryQuoteRequest(
              branchId: branchId,
              fulfillmentMethod: _fulfillmentMethod,
              subtotal: cart.subtotal,
              deliveryAddress: address,
              deliveryLatitude: _selectedAddressLatitude,
              deliveryLongitude: _selectedAddressLongitude,
              deliveryPlaceId: _selectedAddressPlaceId,
            ),
          );

      if (!mounted) return false;
      if (requestId != _deliveryQuoteRequestId) return false;
      if (_addressController.text.trim() != address ||
          _fulfillmentMethod != 'delivery' ||
          _branchId != null && _branchId != branchId) {
        return false;
      }
      setState(() {
        _deliveryQuote = quote.available ? quote : null;
        _deliveryQuoteError = quote.available
            ? null
            : (quote.message ?? 'Không tính được phí giao hàng.');
        if (quote.formattedAddress?.trim().isNotEmpty == true) {
          _addressController.text = quote.formattedAddress!.trim();
        }
        _selectedAddressLatitude = quote.latitude ?? _selectedAddressLatitude;
        _selectedAddressLongitude = quote.longitude ?? _selectedAddressLongitude;
        _selectedAddressPlaceId = quote.placeId ?? _selectedAddressPlaceId;
      });
      _invalidateVoucherQuote();
      return quote.available;
    } on ApiException catch (error) {
      if (!mounted) return false;
      if (requestId != _deliveryQuoteRequestId) return false;
      setState(() {
        _deliveryQuote = null;
        _deliveryQuoteError = error.message ?? 'Không tính được phí giao hàng.';
      });
      _invalidateVoucherQuote();
      return false;
    } catch (_) {
      if (!mounted) return false;
      if (requestId != _deliveryQuoteRequestId) return false;
      setState(() {
        _deliveryQuote = null;
        _deliveryQuoteError = 'Không tính được phí giao hàng. Vui lòng thử lại.';
      });
      _invalidateVoucherQuote();
      return false;
    } finally {
      if (mounted && requestId == _deliveryQuoteRequestId) {
        setState(() => _deliveryQuoteLoading = false);
      }
    }
  }

  Future<bool> _checkOrderAvailability(
    int? branchId, {
    bool showLoading = true,
  }) async {
    final cart = ref.read(cartProvider);
    if (branchId == null || cart.isEmpty) return false;

    final requestedTime = _timeController.text.trim();
    if (requestedTime.isNotEmpty && !_isValidTime(requestedTime)) {
      setState(() {
        _availability = null;
        _availabilityError = 'Giờ nhận mong muốn phải theo định dạng HH:mm.';
      });
      return false;
    }

    final requestId = ++_availabilityRequestId;
    if (showLoading) {
      setState(() {
        _availabilityLoading = true;
        _availabilityError = null;
      });
    }

    try {
      final availability =
          await ref.read(orderRepositoryProvider).previewAvailability(
                OrderAvailabilityRequest(
                  branchId: branchId,
                  requestedTime:
                      requestedTime.isEmpty ? null : requestedTime,
                  items: cart.items,
                ),
              );

      if (!mounted || requestId != _availabilityRequestId) return false;
      if (_timeController.text.trim() != requestedTime) return false;

      setState(() {
        _availability = availability;
        _availabilityError = availability.blocked
            ? (availability.message ?? 'Một số món chưa khả dụng ở khung giờ này.')
            : null;
      });

      return !availability.blocked;
    } on ApiException catch (error) {
      if (!mounted || requestId != _availabilityRequestId) return false;
      final fieldErrors = _flattenFieldErrors(error);
      setState(() {
        _availability = null;
        _fieldErrors = {
          ..._fieldErrors,
          ...fieldErrors,
        };
        _availabilityError = fieldErrors['requested_time'] ??
            fieldErrors['items'] ??
            error.message ??
            'Không kiểm tra được khung giờ nhận món.';
      });
      return false;
    } catch (_) {
      if (!mounted || requestId != _availabilityRequestId) return false;
      setState(() {
        _availability = null;
        _availabilityError = 'Không kiểm tra được khung giờ nhận món.';
      });
      return false;
    } finally {
      if (mounted && requestId == _availabilityRequestId) {
        setState(() => _availabilityLoading = false);
      }
    }
  }

  void _scheduleAvailabilityCheck(int? branchId) {
    _availabilityDebounce?.cancel();
    _availabilityRequestId++;

    final requestedTime = _timeController.text.trim();
    if (branchId == null || requestedTime.isEmpty) {
      setState(() {
        _availability = null;
        _availabilityError = null;
        _availabilityLoading = false;
      });
      return;
    }

    if (!_isValidTime(requestedTime)) {
      setState(() {
        _availability = null;
        _availabilityError = null;
        _availabilityLoading = false;
      });
      return;
    }

    _availabilityDebounce = Timer(const Duration(milliseconds: 500), () {
      if (!mounted) return;
      _checkOrderAvailability(branchId, showLoading: true);
    });
  }

  bool _isValidTime(String value) {
    final match = RegExp(r'^([01]\d|2[0-3]):([0-5]\d)$').firstMatch(value);
    return match != null;
  }

  void _scheduleDeliveryQuote(int? branchId) {
    _deliveryQuoteDebounce?.cancel();

    if (_fulfillmentMethod != 'delivery') return;
    if (branchId == null) return;

    final address = _addressController.text.trim();
    if (address.length < 5) return;

    _deliveryQuoteDebounce = Timer(const Duration(milliseconds: 850), () {
      if (!mounted) return;
      _calculateDeliveryQuote(
        branchId,
        dismissKeyboard: false,
        showEmptyAddressError: false,
      );
    });
  }

  void _scheduleAddressSuggest(int? branchId) {
    _addressSuggestDebounce?.cancel();

    if (_fulfillmentMethod != 'delivery') {
      setState(() {
        _addressSuggestions = const [];
        _addressSuggestLoading = false;
        _addressSuggestError = null;
      });
      return;
    }

    final query = _addressController.text.trim();
    if (query.length < 3) {
      setState(() {
        _addressSuggestions = const [];
        _addressSuggestLoading = false;
        _addressSuggestError = null;
      });
      return;
    }

    _addressSuggestDebounce = Timer(const Duration(milliseconds: 450), () {
      if (!mounted) return;
      _fetchAddressSuggestions(query, branchId);
    });
  }

  Future<void> _fetchAddressSuggestions(String query, int? branchId) async {
    final requestId = ++_addressSuggestRequestId;
    setState(() {
      _addressSuggestLoading = true;
      _addressSuggestError = null;
    });

    try {
      final suggestions = await ref.read(orderRepositoryProvider).suggestAddresses(
            query: query,
            branchId: branchId,
          );

      if (!mounted || requestId != _addressSuggestRequestId) return;
      if (_addressController.text.trim() != query) return;

      setState(() {
        _addressSuggestions = suggestions;
        _addressSuggestError = null;
      });
    } on ApiException catch (error) {
      if (!mounted || requestId != _addressSuggestRequestId) return;
      setState(() {
        _addressSuggestions = const [];
        _addressSuggestError = error.message;
      });
    } catch (_) {
      if (!mounted || requestId != _addressSuggestRequestId) return;
      setState(() {
        _addressSuggestions = const [];
        _addressSuggestError = 'Không lấy được gợi ý địa chỉ.';
      });
    } finally {
      if (mounted && requestId == _addressSuggestRequestId) {
        setState(() => _addressSuggestLoading = false);
      }
    }
  }

  void _selectAddressSuggestion(
    AddressSuggestion suggestion,
    int? branchId,
  ) {
    FocusScope.of(context).unfocus();
    _addressSuggestDebounce?.cancel();
    setState(() {
      _addressController.text = suggestion.formatted;
      _selectedAddressLatitude = suggestion.latitude;
      _selectedAddressLongitude = suggestion.longitude;
      _selectedAddressPlaceId = suggestion.placeId;
      _addressSuggestions = const [];
      _addressSuggestError = null;
      _locationError = null;
    });
    _invalidateDeliveryQuote();
    _invalidateVoucherQuote();
    _calculateDeliveryQuote(
      branchId,
      dismissKeyboard: false,
      showEmptyAddressError: false,
    );
  }

  Future<void> _useCurrentLocation(int? branchId) async {
    if (_locationLoading) return;
    FocusScope.of(context).unfocus();

    if (branchId == null) {
      setState(() => _locationError = 'Vui lòng chọn chi nhánh.');
      return;
    }

    setState(() {
      _locationLoading = true;
      _locationError = null;
      _addressSuggestError = null;
      _addressSuggestions = const [];
    });

    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        throw const _LocationMessage(
          'Vui lòng bật định vị trên thiết bị rồi thử lại.',
        );
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied) {
        throw const _LocationMessage(
          'Ứng dụng chưa được cấp quyền vị trí.',
        );
      }

      if (permission == LocationPermission.deniedForever) {
        throw const _LocationMessage(
          'Quyền vị trí đang bị chặn. Vui lòng mở cài đặt ứng dụng để cấp quyền.',
        );
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 12),
        ),
      );

      final reversed = await ref.read(orderRepositoryProvider).reverseAddress(
            latitude: position.latitude,
            longitude: position.longitude,
          );

      if (!mounted) return;
      final formatted = reversed.formattedAddress?.trim();
      if (formatted == null || formatted.isEmpty) {
        throw const _LocationMessage(
          'Không tìm được địa chỉ từ vị trí hiện tại.',
        );
      }

      setState(() {
        _addressController.text = formatted;
        _selectedAddressLatitude = reversed.latitude ?? position.latitude;
        _selectedAddressLongitude = reversed.longitude ?? position.longitude;
        _selectedAddressPlaceId = reversed.placeId;
        _addressSuggestions = const [];
        _addressSuggestError = null;
        _locationError = null;
      });
      _invalidateDeliveryQuote();
      _invalidateVoucherQuote();
      await _calculateDeliveryQuote(
        branchId,
        dismissKeyboard: false,
        showEmptyAddressError: false,
      );
    } on _LocationMessage catch (error) {
      if (!mounted) return;
      setState(() => _locationError = error.message);
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(
        () => _locationError = error.message,
      );
    } catch (_) {
      if (!mounted) return;
      setState(
        () => _locationError = 'Không lấy được vị trí hiện tại. Vui lòng thử lại.',
      );
    } finally {
      if (mounted) setState(() => _locationLoading = false);
    }
  }

  void _clearVoucher() {
    setState(() {
      _voucherController.clear();
      _voucherQuote = null;
      _voucherError = null;
      _fieldErrors = {
        ..._fieldErrors,
      }..remove('voucher_code');
    });
  }

  void _invalidateVoucherQuote({bool clearError = true}) {
    if (_voucherQuote == null && (_voucherError == null || !clearError)) return;
    setState(() {
      _voucherQuote = null;
      if (clearError) _voucherError = null;
      _fieldErrors = {
        ..._fieldErrors,
      }..remove('voucher_code');
    });
  }

  void _invalidateDeliveryQuote() {
    _deliveryQuoteRequestId++;
    if (_deliveryQuote == null && _deliveryQuoteError == null) return;
    setState(() {
      _deliveryQuote = null;
      _deliveryQuoteError = null;
      _deliveryQuoteLoading = false;
    });
  }

  int get _currentShippingFee {
    if (_fulfillmentMethod != 'delivery') return 0;
    final quote = _deliveryQuote;
    if (quote == null || quote.manual || !quote.available) return 0;
    return quote.fee;
  }

  Map<String, String> _flattenFieldErrors(ApiException error) {
    final raw = error.errors;
    if (raw == null || raw.isEmpty) return const {};

    final out = <String, String>{};
    for (final entry in raw.entries) {
      final value = entry.value;
      if (value is List && value.isNotEmpty) {
        out[entry.key] = value.first.toString();
      } else if (value != null) {
        out[entry.key] = value.toString();
      }
    }
    return out;
  }

  String? _generalOrderError(ApiException error, Map<String, String> fields) {
    if (fields.isEmpty) return error.message;
    for (final key in fields.keys) {
      if (key == 'items' || key.startsWith('items.')) return fields[key];
      if (key == 'payment_method' || key == 'fulfillment_method') {
        return fields[key];
      }
    }
    return null;
  }
}

class _LocationMessage implements Exception {
  const _LocationMessage(this.message);

  final String message;
}

class _CheckoutHero extends StatelessWidget {
  const _CheckoutHero();

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.primaryStrong, AppColors.primary],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      padding: const EdgeInsets.fromLTRB(16, 28, 16, 30),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: AppConstants.maxContentWidth),
          child: const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'ĐẶT MÓN',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 34,
                  height: 1.02,
                  fontWeight: FontWeight.w900,
                  fontStyle: FontStyle.italic,
                ),
              ),
              SizedBox(height: 8),
              Text(
                'Tạo đơn và hoá đơn nháp, quán sẽ liên hệ xác nhận. Không thanh toán online.',
                style: TextStyle(
                  color: Color(0xFFD6E5DB),
                  fontSize: 14,
                  height: 1.45,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CheckoutFormCard extends StatelessWidget {
  const _CheckoutFormCard({
    required this.formKey,
    required this.branches,
    required this.branchId,
    required this.fulfillmentMethod,
    required this.nameController,
    required this.phoneController,
    required this.emailController,
    required this.addressController,
    required this.timeController,
    required this.noteController,
    required this.voucherController,
    required this.fieldErrors,
    required this.submitting,
    required this.voucherLoading,
    required this.deliveryQuoteLoading,
    required this.availabilityLoading,
    required this.addressSuggestions,
    required this.addressSuggestLoading,
    required this.locationLoading,
    required this.onBranchChanged,
    required this.onMethodChanged,
    required this.onAddressChanged,
    required this.onAddressSuggestionSelected,
    required this.onUseCurrentLocation,
    required this.onTimeChanged,
    required this.onVoucherChanged,
    required this.onApplyVoucher,
    required this.onClearVoucher,
    required this.onSubmit,
    this.error,
    this.voucherQuote,
    this.voucherError,
    this.deliveryQuote,
    this.deliveryQuoteError,
    this.availability,
    this.availabilityError,
    this.addressSuggestError,
    this.locationError,
  });

  final GlobalKey<FormState> formKey;
  final List<Branch> branches;
  final int? branchId;
  final String fulfillmentMethod;
  final TextEditingController nameController;
  final TextEditingController phoneController;
  final TextEditingController emailController;
  final TextEditingController addressController;
  final TextEditingController timeController;
  final TextEditingController noteController;
  final TextEditingController voucherController;
  final Map<String, String> fieldErrors;
  final bool submitting;
  final bool voucherLoading;
  final bool deliveryQuoteLoading;
  final bool availabilityLoading;
  final List<AddressSuggestion> addressSuggestions;
  final bool addressSuggestLoading;
  final bool locationLoading;
  final String? error;
  final VoucherPreviewResponse? voucherQuote;
  final String? voucherError;
  final DeliveryQuoteResponse? deliveryQuote;
  final String? deliveryQuoteError;
  final OrderAvailabilityResponse? availability;
  final String? availabilityError;
  final String? addressSuggestError;
  final String? locationError;
  final ValueChanged<int?> onBranchChanged;
  final ValueChanged<String?> onMethodChanged;
  final ValueChanged<String> onAddressChanged;
  final ValueChanged<AddressSuggestion> onAddressSuggestionSelected;
  final VoidCallback onUseCurrentLocation;
  final ValueChanged<String> onTimeChanged;
  final ValueChanged<String> onVoucherChanged;
  final VoidCallback onApplyVoucher;
  final VoidCallback onClearVoucher;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
      ),
      child: Form(
        key: formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Thông tin đặt món',
              style: TextStyle(
                color: AppColors.textPrimary,
                fontSize: 20,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 14),
            DropdownButtonFormField<int>(
              value: branchId,
              decoration: _inputDecoration(
                'Chi nhánh',
                errorText: _fieldError('branch_id'),
              ),
              items: branches
                  .map(
                    (branch) => DropdownMenuItem<int>(
                      value: branch.id,
                      child: Text(branch.name),
                    ),
                  )
                  .toList(),
              validator: (value) => value == null ? 'Vui lòng chọn chi nhánh' : null,
              onChanged: onBranchChanged,
            ),
            const SizedBox(height: 12),
            SegmentedButton<String>(
              segments: const [
                ButtonSegment(
                  value: 'pickup',
                  icon: Icon(Icons.storefront_outlined),
                  label: Text('Tự lấy'),
                ),
                ButtonSegment(
                  value: 'delivery',
                  icon: Icon(Icons.delivery_dining_outlined),
                  label: Text('Giao hàng'),
                ),
              ],
              selected: {fulfillmentMethod},
              onSelectionChanged: (values) => onMethodChanged(values.first),
              style: ButtonStyle(
                foregroundColor: WidgetStateProperty.resolveWith(
                  (states) => states.contains(WidgetState.selected)
                      ? Colors.white
                      : AppColors.primary,
                ),
                backgroundColor: WidgetStateProperty.resolveWith(
                  (states) => states.contains(WidgetState.selected)
                      ? AppColors.primary
                      : AppColors.surface,
                ),
              ),
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: nameController,
              decoration: _inputDecoration(
                'Họ tên',
                errorText: _fieldError('customer_name'),
              ),
              textInputAction: TextInputAction.next,
              validator: (value) =>
                  value == null || value.trim().length < 2 ? 'Vui lòng nhập họ tên' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: phoneController,
              decoration: _inputDecoration(
                'Số điện thoại',
                errorText: _fieldError('customer_phone'),
              ),
              keyboardType: TextInputType.phone,
              textInputAction: TextInputAction.next,
              validator: (value) =>
                  value == null || value.trim().isEmpty ? 'Vui lòng nhập số điện thoại' : null,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: emailController,
              decoration: _inputDecoration(
                'Email (tuỳ chọn)',
                errorText: _fieldError('customer_email'),
              ),
              keyboardType: TextInputType.emailAddress,
              textInputAction: TextInputAction.next,
              validator: _validateEmail,
            ),
            if (fulfillmentMethod == 'delivery') ...[
              const SizedBox(height: 12),
              TextFormField(
                controller: addressController,
                onChanged: onAddressChanged,
                decoration: _inputDecoration(
                  'Địa chỉ giao hàng',
                  errorText: _fieldError('delivery_address'),
                ),
                minLines: 2,
                maxLines: 3,
                validator: (value) => value == null || value.trim().isEmpty
                    ? 'Vui lòng nhập địa chỉ giao hàng'
                    : null,
              ),
              _AddressSuggestionsBox(
                suggestions: addressSuggestions,
                loading: addressSuggestLoading,
                errorText: addressSuggestError,
                onSelected: onAddressSuggestionSelected,
              ),
              const SizedBox(height: 8),
              Align(
                alignment: Alignment.centerLeft,
                child: OutlinedButton.icon(
                  onPressed: locationLoading ? null : onUseCurrentLocation,
                  icon: locationLoading
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.my_location_outlined, size: 18),
                  label: Text(
                    locationLoading
                        ? 'Đang lấy vị trí...'
                        : 'Dùng vị trí hiện tại',
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ),
              if (locationError != null && locationError!.isNotEmpty) ...[
                const SizedBox(height: 8),
                _InlineError(message: locationError!),
              ],
              const SizedBox(height: 10),
              _DeliveryQuoteBox(
                quote: deliveryQuote,
                loading: deliveryQuoteLoading,
                errorText: deliveryQuoteError,
              ),
            ],
            const SizedBox(height: 12),
            TextFormField(
              controller: timeController,
              readOnly: true,
              onTap: () => _pickRequestedTime(context),
              decoration: _inputDecoration(
                'Giờ nhận mong muốn (tuỳ chọn)',
                errorText: _fieldError('requested_time'),
                suffixIcon: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    if (timeController.text.trim().isNotEmpty)
                      IconButton(
                        tooltip: 'Bỏ giờ nhận',
                        onPressed: () {
                          timeController.clear();
                          onTimeChanged('');
                        },
                        icon: const Icon(Icons.close, size: 18),
                      ),
                    IconButton(
                      tooltip: 'Chọn giờ nhận',
                      onPressed: () => _pickRequestedTime(context),
                      icon: const Icon(Icons.schedule_outlined, size: 20),
                    ),
                  ],
                ),
              ),
              validator: _validateRequestedTime,
            ),
            _AvailabilityBox(
              availability: availability,
              loading: availabilityLoading,
              errorText: _fieldError('items') ?? availabilityError,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: noteController,
              decoration: _inputDecoration('Ghi chú đơn hàng'),
              minLines: 2,
              maxLines: 4,
            ),
            const SizedBox(height: 12),
            _VoucherInput(
              controller: voucherController,
              quote: voucherQuote,
              loading: voucherLoading,
              errorText: _fieldError('voucher_code') ?? voucherError,
              onChanged: onVoucherChanged,
              onApply: onApplyVoucher,
              onClear: onClearVoucher,
            ),
            if (error != null) ...[
              const SizedBox(height: 12),
              _InlineError(message: error!),
            ],
            const SizedBox(height: 16),
            FilledButton.icon(
              onPressed: submitting ? null : onSubmit,
              style: FilledButton.styleFrom(
                backgroundColor: AppColors.accent,
                foregroundColor: Colors.white,
                disabledBackgroundColor: AppColors.textMuted,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
              icon: submitting
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : const Icon(Icons.receipt_long_outlined, size: 18),
              label: Text(
                submitting ? 'ĐANG GỬI...' : 'GỬI ĐƠN',
                style: const TextStyle(fontWeight: FontWeight.w900),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickRequestedTime(BuildContext context) async {
    FocusScope.of(context).unfocus();
    final initial = _timeOfDayFromText(timeController.text) ?? TimeOfDay.now();
    final picked = await showTimePicker(
      context: context,
      initialTime: initial,
      helpText: 'Chọn giờ nhận món',
      cancelText: 'Huỷ',
      confirmText: 'Chọn',
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: Theme.of(context).colorScheme.copyWith(
                  primary: AppColors.primary,
                ),
          ),
          child: child!,
        );
      },
    );

    if (picked == null) return;
    final text = _formatTimeOfDay(picked);
    timeController.text = text;
    onTimeChanged(text);
  }

  TimeOfDay? _timeOfDayFromText(String value) {
    final match = RegExp(r'^([01]\d|2[0-3]):([0-5]\d)$').firstMatch(value.trim());
    if (match == null) return null;

    return TimeOfDay(
      hour: int.parse(match.group(1)!),
      minute: int.parse(match.group(2)!),
    );
  }

  String _formatTimeOfDay(TimeOfDay time) {
    String two(int value) => value.toString().padLeft(2, '0');
    return '${two(time.hour)}:${two(time.minute)}';
  }

  InputDecoration _inputDecoration(
    String label, {
    String? errorText,
    Widget? suffixIcon,
  }) {
    return InputDecoration(
      labelText: label,
      errorText: errorText,
      suffixIcon: suffixIcon,
      filled: true,
      fillColor: AppColors.cream,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: AppColors.primary, width: 1.4),
      ),
    );
  }

  String? _fieldError(String field) => fieldErrors[field];

  String? _validateRequestedTime(String? value) {
    final text = value?.trim() ?? '';
    if (text.isEmpty) return null;
    final valid = RegExp(r'^([01]\d|2[0-3]):[0-5]\d$').hasMatch(text);
    return valid ? null : 'Giờ nhận phải theo định dạng HH:mm';
  }

  String? _validateEmail(String? value) {
    final text = value?.trim() ?? '';
    if (text.isEmpty) return null;
    final valid = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(text);
    return valid ? null : 'Email không đúng định dạng';
  }
}

class _CheckoutSummary extends ConsumerWidget {
  const _CheckoutSummary({
    required this.subtotal,
    required this.itemsCount,
    required this.fulfillmentMethod,
    this.voucherQuote,
    this.deliveryQuote,
  });

  final int subtotal;
  final int itemsCount;
  final String fulfillmentMethod;
  final VoucherPreviewResponse? voucherQuote;
  final DeliveryQuoteResponse? deliveryQuote;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final items = ref.watch(cartProvider).items;
    final shippingFee = fulfillmentMethod == 'delivery' &&
            deliveryQuote?.available == true &&
            deliveryQuote?.manual != true
        ? deliveryQuote!.fee
        : 0;
    final discountTotal = voucherQuote?.discountTotal ?? 0;
    final total = (subtotal + shippingFee - discountTotal)
        .clamp(0, subtotal + shippingFee)
        .toInt();
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Đơn hàng ($itemsCount món)',
            style: const TextStyle(
              color: AppColors.textPrimary,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 12),
          for (final item in items) ...[
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Text(
                    '${item.quantity}x ${item.name}',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontSize: 13,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Text(
                  _formatPrice(item.lineTotal),
                  style: const TextStyle(
                    color: AppColors.primaryStrong,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
          ],
          const Divider(height: 22, color: AppColors.border),
          _SummaryLine(label: 'Tạm tính', value: _formatPrice(subtotal)),
          const SizedBox(height: 8),
          _SummaryLine(
            label: 'Phí giao hàng',
            value: fulfillmentMethod == 'delivery'
                ? (deliveryQuote == null
                    ? 'Chưa tính'
                    : deliveryQuote?.manual == true
                        ? 'Quán xác nhận'
                        : _formatPrice(shippingFee))
                : 'Không áp dụng',
          ),
          if (discountTotal > 0) ...[
            const SizedBox(height: 8),
            _SummaryLine(
              label: 'Mã ${voucherQuote?.voucher?.code ?? ''}',
              value: '-${_formatPrice(discountTotal)}',
              accent: true,
            ),
          ],
          const SizedBox(height: 8),
          _SummaryLine(
            label: 'Tổng hoá đơn',
            value: _formatPrice(total),
            strong: true,
          ),
        ],
      ),
    );
  }
}

class _AddressSuggestionsBox extends StatelessWidget {
  const _AddressSuggestionsBox({
    required this.suggestions,
    required this.loading,
    required this.onSelected,
    this.errorText,
  });

  final List<AddressSuggestion> suggestions;
  final bool loading;
  final String? errorText;
  final ValueChanged<AddressSuggestion> onSelected;

  @override
  Widget build(BuildContext context) {
    if (!loading && suggestions.isEmpty && (errorText ?? '').isEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      margin: const EdgeInsets.only(top: 8),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (loading)
            const Padding(
              padding: EdgeInsets.all(12),
              child: Row(
                children: [
                  SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                  SizedBox(width: 10),
                  Text(
                    'Đang tìm địa chỉ...',
                    style: TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            )
          else if ((errorText ?? '').isNotEmpty)
            Padding(
              padding: const EdgeInsets.all(12),
              child: Text(
                errorText!,
                style: const TextStyle(
                  color: AppColors.errorText,
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            )
          else
            for (final suggestion in suggestions)
              InkWell(
                onTap: () => onSelected(suggestion),
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 10,
                  ),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Icon(
                        Icons.place_outlined,
                        color: AppColors.primary,
                        size: 18,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          suggestion.formatted,
                          style: const TextStyle(
                            color: AppColors.textPrimary,
                            fontSize: 12,
                            height: 1.35,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
        ],
      ),
    );
  }
}

class _SummaryLine extends StatelessWidget {
  const _SummaryLine({
    required this.label,
    required this.value,
    this.strong = false,
    this.accent = false,
  });

  final String label;
  final String value;
  final bool strong;
  final bool accent;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: Text(
            label,
            style: TextStyle(
              color: strong ? AppColors.textPrimary : AppColors.textMuted,
              fontWeight: strong ? FontWeight.w900 : FontWeight.w700,
            ),
          ),
        ),
        Text(
          value,
          style: TextStyle(
            color: accent
                ? AppColors.successText
                : strong
                    ? AppColors.primaryStrong
                    : AppColors.textPrimary,
            fontSize: strong ? 18 : 13,
            fontWeight: FontWeight.w900,
          ),
        ),
      ],
    );
  }
}

class _AvailabilityBox extends StatelessWidget {
  const _AvailabilityBox({
    required this.loading,
    this.availability,
    this.errorText,
  });

  final OrderAvailabilityResponse? availability;
  final bool loading;
  final String? errorText;

  @override
  Widget build(BuildContext context) {
    final current = availability;
    final hasError = (errorText ?? '').isNotEmpty;
    final shouldShow = loading || hasError || current?.blocked == true;
    if (!shouldShow) return const SizedBox.shrink();

    final blocked = current?.blocked == true || hasError;
    final title = loading
        ? 'Đang kiểm tra khung giờ món...'
        : blocked
            ? 'Có món chưa khả dụng ở giờ này'
            : 'Khung giờ nhận món phù hợp';
    final message = errorText ??
        current?.message ??
        current?.itemsMessage ??
        'Vui lòng chọn khung giờ khác.';

    return Container(
      margin: const EdgeInsets.only(top: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: blocked ? const Color(0xFFFFF1F2) : const Color(0xFFEFFAF3),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: blocked ? AppColors.errorText : AppColors.successBorder,
        ),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (loading)
            const SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(strokeWidth: 2),
            )
          else
            Icon(
              blocked ? Icons.schedule_outlined : Icons.check_circle_outline,
              color: blocked ? AppColors.errorText : AppColors.successText,
              size: 19,
            ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    color: blocked ? AppColors.errorText : AppColors.successText,
                    fontSize: 13,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                if (message.trim().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    message,
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontSize: 12,
                      height: 1.35,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _DeliveryQuoteBox extends StatelessWidget {
  const _DeliveryQuoteBox({
    required this.loading,
    this.quote,
    this.errorText,
  });

  final DeliveryQuoteResponse? quote;
  final bool loading;
  final String? errorText;

  @override
  Widget build(BuildContext context) {
    final currentQuote = quote;
    final title = loading
        ? 'Đang tự tính phí giao hàng...'
        : currentQuote?.available == true
            ? (currentQuote!.manual
                ? 'Phí ship: Quán xác nhận'
                : 'Phí ship: ${_formatPrice(currentQuote.fee)}')
            : 'Nhập địa chỉ để tự tính phí ship';

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.cream,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: currentQuote?.available == true
              ? AppColors.successBorder
              : AppColors.border,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    color: AppColors.textPrimary,
                    fontSize: 13,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              if (loading)
                const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              else
                const Icon(
                  Icons.delivery_dining_outlined,
                  color: AppColors.primary,
                  size: 20,
                ),
            ],
          ),
          if (currentQuote?.available == true &&
              (currentQuote?.message?.isNotEmpty == true ||
                  currentQuote?.distanceLabel?.isNotEmpty == true ||
                  currentQuote?.zoneLabel?.isNotEmpty == true)) ...[
            const SizedBox(height: 8),
            Text(
              [
                if (currentQuote!.distanceLabel?.isNotEmpty == true)
                  currentQuote.distanceLabel!,
                if (currentQuote.zoneLabel?.isNotEmpty == true)
                  currentQuote.zoneLabel!,
                if (currentQuote.message?.isNotEmpty == true)
                  currentQuote.message!,
              ].join(' · '),
              style: const TextStyle(
                color: AppColors.textMuted,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
          if (errorText != null && errorText!.isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              errorText!,
              style: const TextStyle(
                color: AppColors.errorText,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _VoucherInput extends StatelessWidget {
  const _VoucherInput({
    required this.controller,
    required this.loading,
    required this.onChanged,
    required this.onApply,
    required this.onClear,
    this.quote,
    this.errorText,
  });

  final TextEditingController controller;
  final VoucherPreviewResponse? quote;
  final bool loading;
  final String? errorText;
  final ValueChanged<String> onChanged;
  final VoidCallback onApply;
  final VoidCallback onClear;

  bool get _hasAppliedVoucher => quote?.valid == true;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.cream,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color:
              _hasAppliedVoucher ? AppColors.successBorder : AppColors.border,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Mã giảm giá',
                  style: TextStyle(
                    color: AppColors.textPrimary,
                    fontSize: 14,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              if (_hasAppliedVoucher)
                TextButton.icon(
                  onPressed: loading ? null : onClear,
                  icon: const Icon(Icons.close, size: 16),
                  label: const Text('Huỷ'),
                ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: TextFormField(
                  controller: controller,
                  enabled: !loading,
                  onChanged: onChanged,
                  textCapitalization: TextCapitalization.characters,
                  decoration: InputDecoration(
                    hintText: 'Nhập mã voucher',
                    errorText: errorText,
                    filled: true,
                    fillColor: AppColors.surface,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: AppColors.border),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: AppColors.border),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(
                        color: AppColors.primary,
                        width: 1.4,
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              SizedBox(
                height: 56,
                child: FilledButton(
                  onPressed: loading ? null : onApply,
                  style: FilledButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: loading
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Colors.white,
                          ),
                        )
                      : const Text(
                          'Áp dụng',
                          style: TextStyle(fontWeight: FontWeight.w900),
                        ),
                ),
              ),
            ],
          ),
          if (_hasAppliedVoucher) ...[
            const SizedBox(height: 8),
            Text(
              '${quote!.voucher?.name ?? quote!.voucher?.code ?? 'Voucher'} giảm ${_formatPrice(quote!.discountTotal)}.',
              style: const TextStyle(
                color: AppColors.successText,
                fontSize: 12,
                fontWeight: FontWeight.w800,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _InlineError extends StatelessWidget {
  const _InlineError({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.errorBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.errorBorder),
      ),
      child: Text(
        message,
        style: const TextStyle(
          color: AppColors.errorText,
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _ErrorBox extends StatelessWidget {
  const _ErrorBox({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.errorText),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: const Text('Thử lại'),
          ),
        ],
      ),
    );
  }
}

class _EmptyCheckout extends StatelessWidget {
  const _EmptyCheckout();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          const Icon(Icons.shopping_bag_outlined, color: AppColors.primary, size: 42),
          const SizedBox(height: 12),
          const Text(
            'Giỏ hàng đang trống',
            style: TextStyle(
              color: AppColors.textPrimary,
              fontSize: 20,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 14),
          FilledButton.icon(
            onPressed: () {
              context.showPageLoader();
              context.go(AppRoutes.menu);
            },
            icon: const Icon(Icons.restaurant_menu, size: 18),
            label: const Text('Xem thực đơn'),
          ),
        ],
      ),
    );
  }
}

String _formatPrice(int cents) {
  final euros = cents / 100;
  return '€ ${euros.toStringAsFixed(2).replaceAll('.', ',')}';
}
