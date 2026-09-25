import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
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

  int? _branchId;
  String _fulfillmentMethod = 'pickup';
  bool _submitting = false;
  String? _error;
  Map<String, String> _fieldErrors = const {};

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _addressController.dispose();
    _timeController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final cart = ref.watch(cartProvider);
    final branches = ref.watch(branchesProvider);

    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
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
          fieldErrors: _fieldErrors,
          error: _error,
          submitting: _submitting,
          onBranchChanged: (value) => setState(() => _branchId = value),
          onMethodChanged: (value) {
            if (value == null) return;
            setState(() => _fulfillmentMethod = value);
          },
          onSubmit: () => _submit(effectiveBranchId),
        );
        final summary = _CheckoutSummary(
          subtotal: cart.subtotal,
          itemsCount: cart.count,
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
              requestedTime: _timeController.text.trim().isEmpty
                  ? null
                  : _timeController.text.trim(),
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
    required this.fieldErrors,
    required this.submitting,
    required this.onBranchChanged,
    required this.onMethodChanged,
    required this.onSubmit,
    this.error,
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
  final Map<String, String> fieldErrors;
  final bool submitting;
  final String? error;
  final ValueChanged<int?> onBranchChanged;
  final ValueChanged<String?> onMethodChanged;
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
            ],
            const SizedBox(height: 12),
            TextFormField(
              controller: timeController,
              decoration: _inputDecoration(
                'Giờ nhận mong muốn (HH:mm, tuỳ chọn)',
                errorText: _fieldError('requested_time'),
              ),
              keyboardType: TextInputType.datetime,
              validator: _validateRequestedTime,
            ),
            const SizedBox(height: 12),
            TextFormField(
              controller: noteController,
              decoration: _inputDecoration('Ghi chú đơn hàng'),
              minLines: 2,
              maxLines: 4,
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

  InputDecoration _inputDecoration(String label, {String? errorText}) {
    return InputDecoration(
      labelText: label,
      errorText: errorText,
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
  });

  final int subtotal;
  final int itemsCount;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final items = ref.watch(cartProvider).items;
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
          const _SummaryLine(label: 'Phí giao hàng', value: 'Quán xác nhận'),
          const SizedBox(height: 8),
          _SummaryLine(
            label: 'Tổng hoá đơn',
            value: _formatPrice(subtotal),
            strong: true,
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
  });

  final String label;
  final String value;
  final bool strong;

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
            color: strong ? AppColors.primaryStrong : AppColors.textPrimary,
            fontSize: strong ? 18 : 13,
            fontWeight: FontWeight.w900,
          ),
        ),
      ],
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
