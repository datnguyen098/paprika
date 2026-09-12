import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../data/models/contact_model.dart';
import '../providers/providers.dart';
import '../services/api_service.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';

/// Contact screen — form liên hệ.
///
/// Validation: dùng [ContactRequest.validate()] phía client TRƯỚC khi submit.
/// Khi submit thành công hiển thị dialog xác nhận + reset form.
/// Nếu BE trả 422 → hiển thị inline errors dùng [ApiException.errorsFor].
class ContactScreen extends ConsumerStatefulWidget {
  const ContactScreen({super.key});

  @override
  ConsumerState<ContactScreen> createState() => _ContactScreenState();
}

class _ContactScreenState extends ConsumerState<ContactScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _subjectCtrl = TextEditingController();
  final _messageCtrl = TextEditingController();

  // Track field-level errors from BE (422 response)
  final Map<String, String> _fieldErrors = {};

  bool _isSubmitting = false;

  @override
  void dispose() {
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _phoneCtrl.dispose();
    _subjectCtrl.dispose();
    _messageCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    // 1. Clear previous errors
    setState(() => _fieldErrors.clear());

    // 2. Client-side validate
    final request = ContactRequest(
      name: _nameCtrl.text.trim(),
      email: _emailCtrl.text.trim(),
      message: _messageCtrl.text.trim(),
      phone: _phoneCtrl.text.trim(),
      subject: _subjectCtrl.text.trim(),
      branchId: ref.read(selectedBranchIdProvider),
    );

    final clientErrors = request.validate();
    if (clientErrors.isNotEmpty) {
      setState(() => _fieldErrors.addAll(clientErrors));
      return; // không gọi API
    }

    // 3. Submit
    setState(() => _isSubmitting = true);

    try {
      await ref.read(sendContactProvider(request).future);
      if (!mounted) return;

      // Thành công
      _showSuccessDialog();
      _resetForm();
    } on Exception catch (e) {
      if (!mounted) return;

      // 422: hiển thị inline errors từ BE
      if (e is ApiException && e.isValidationError) {
        final allErrors = <String, String>{};
        for (final entry in (e.errors ?? {}).entries) {
          final field = entry.key;
          if (entry.value is List) {
            for (final msg in (entry.value as List)) {
              allErrors[field] = msg.toString();
            }
          }
        }
        setState(() => _fieldErrors.addAll(allErrors));
      } else {
        _showErrorSnackBar(e.toString());
      }
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  void _resetForm() {
    _nameCtrl.clear();
    _emailCtrl.clear();
    _phoneCtrl.clear();
    _subjectCtrl.clear();
    _messageCtrl.clear();
    setState(() => _fieldErrors.clear());
  }

  void _showSuccessDialog() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppConstants.radius),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: const BoxDecoration(
                color: AppColors.successBg,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.check_circle,
                  color: AppColors.successText, size: 40),
            ),
            const SizedBox(height: 16),
            const Text(
              'Gửi liên hệ thành công!',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w900,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Chúng tôi sẽ phản hồi qua email trong thời gian sớm nhất.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 13,
                color: AppColors.textMuted,
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Đóng'),
            ),
          ],
        ),
      ),
    );
  }

  void _showErrorSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: AppColors.errorText,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  String? _errorFor(String field) {
    return _fieldErrors[field];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(),
          Expanded(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Page hero
                  Container(
                    padding: const EdgeInsets.all(AppConstants.spaceMd),
                    decoration: const BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [AppColors.primary, AppColors.primaryStrong],
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 4,
                              height: 18,
                              decoration: BoxDecoration(
                                color: AppColors.gold,
                                borderRadius: BorderRadius.circular(2),
                              ),
                            ),
                            const SizedBox(width: 8),
                            const Text(
                              'LIÊN HỆ',
                              style: TextStyle(
                                color: AppColors.gold,
                                fontSize: 11,
                                fontWeight: FontWeight.w900,
                                letterSpacing: 0.18,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'LIÊN HỆ VỚI CHÚNG TÔI',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 22,
                            fontWeight: FontWeight.w900,
                            letterSpacing: -0.3,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Gửi tin nhắn, chúng tôi sẽ phản hồi qua email.',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.85),
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Form
                  Padding(
                    padding: const EdgeInsets.all(AppConstants.spaceMd),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          _Field(
                            label: 'Họ tên',
                            required: true,
                            error: _errorFor('name'),
                            child: TextFormField(
                              controller: _nameCtrl,
                              decoration: const InputDecoration(
                                hintText: 'Nguyễn Văn A',
                                prefixIcon: Icon(Icons.person_outline,
                                    color: AppColors.primary, size: 20),
                              ),
                              onChanged: (_) =>
                                  setState(() => _fieldErrors.remove('name')),
                            ),
                          ),
                          _Field(
                            label: 'Email',
                            required: true,
                            error: _errorFor('email'),
                            child: TextFormField(
                              controller: _emailCtrl,
                              keyboardType: TextInputType.emailAddress,
                              decoration: const InputDecoration(
                                hintText: 'email@example.com',
                                prefixIcon: Icon(Icons.alternate_email,
                                    color: AppColors.primary, size: 20),
                              ),
                              onChanged: (_) =>
                                  setState(() => _fieldErrors.remove('email')),
                            ),
                          ),
                          _Field(
                            label: 'Điện thoại',
                            error: _errorFor('phone'),
                            child: TextFormField(
                              controller: _phoneCtrl,
                              keyboardType: TextInputType.phone,
                              decoration: const InputDecoration(
                                hintText: '+30 2610 123 456',
                                prefixIcon: Icon(Icons.phone_outlined,
                                    color: AppColors.primary, size: 20),
                              ),
                              onChanged: (_) =>
                                  setState(() => _fieldErrors.remove('phone')),
                            ),
                          ),
                          _Field(
                            label: 'Tiêu đề',
                            error: _errorFor('subject'),
                            child: TextFormField(
                              controller: _subjectCtrl,
                              decoration: const InputDecoration(
                                hintText: 'Tiêu đề tin nhắn (tùy chọn)',
                                prefixIcon: Icon(Icons.subject,
                                    color: AppColors.primary, size: 20),
                              ),
                              onChanged: (_) =>
                                  setState(() => _fieldErrors.remove('subject')),
                            ),
                          ),
                          _Field(
                            label: 'Nội dung',
                            required: true,
                            error: _errorFor('message'),
                            child: TextFormField(
                              controller: _messageCtrl,
                              maxLines: 5,
                              decoration: const InputDecoration(
                                hintText: 'Viết nội dung liên hệ của bạn...',
                                alignLabelWithHint: true,
                              ),
                              onChanged: (_) => setState(
                                  () => _fieldErrors.remove('message')),
                            ),
                          ),
                          const SizedBox(height: AppConstants.spaceMd),
                          SizedBox(
                            height: 52,
                            child: ElevatedButton.icon(
                              onPressed:
                                  _isSubmitting ? null : _submit,
                              icon: _isSubmitting
                                  ? const SizedBox(
                                      width: 18,
                                      height: 18,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Icon(Icons.send),
                              label: Text(
                                _isSubmitting
                                    ? 'Đang gửi...'
                                    : 'GỬI LIÊN HỆ',
                              ),
                            ),
                          ),
                          const SizedBox(height: AppConstants.spaceSm),
                          const Center(
                            child: Text(
                              'Chúng tôi sẽ phản hồi qua email trong vòng 24 giờ.',
                              style: TextStyle(
                                fontSize: 11,
                                color: AppColors.textMuted,
                                fontStyle: FontStyle.italic,
                              ),
                              textAlign: TextAlign.center,
                            ),
                          ),
                        ],
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
}

class _Field extends StatelessWidget {
  const _Field({
    required this.label,
    required this.child,
    this.required = false,
    this.error,
  });
  final String label;
  final Widget child;
  final bool required;
  final String? error;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppConstants.spaceMd),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                  color: AppColors.primaryStrong,
                ),
              ),
              if (required)
                const Text(
                  ' *',
                  style: TextStyle(
                    color: AppColors.accent,
                    fontSize: 12,
                    fontWeight: FontWeight.w900,
                  ),
                ),
            ],
          ),
          const SizedBox(height: 6),
          child,
          if (error != null) ...[
            const SizedBox(height: 4),
            Text(
              error!,
              style: const TextStyle(
                fontSize: 11,
                color: AppColors.errorText,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ],
      ),
    );
  }
}
