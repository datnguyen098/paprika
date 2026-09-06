import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/coming_soon.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';

/// Reservation screen — form đặt bàn theo Laravel Blade
/// (`pages/reservation.blade.php`).
///
/// Cấu trúc:
///   Scaffold
///     body: Stack([
///       Column([PaprikaHeader, Expanded(SingleChildScrollView([...content]))]),
///       _FloatingActions() — phone + chat (absolute positioned),
///     ])
///     bottomNavigationBar: BottomNavBar
///
/// Form KHÔNG submit (BE chưa có endpoint) — nút "Giữ bàn" chỉ
/// show snackbar "đang phát triển" qua [ComingSoon].
class ReservationScreen extends ConsumerStatefulWidget {
  const ReservationScreen({super.key});

  @override
  ConsumerState<ReservationScreen> createState() => _ReservationScreenState();
}

class _ReservationScreenState extends ConsumerState<ReservationScreen> {
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  // Quick-action picker state.
  DateTime _selectedDate = DateTime.now();
  String _selectedTime = '12:00 - 22:30';
  int _guests = 2;

  static const _branches = ['Paprika Patras', 'Paprika Athens'];

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _emailCtrl.dispose();
    super.dispose();
  }

  void _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 90)),
      locale: const Locale('vi'),
    );
    if (picked != null) {
      setState(() => _selectedDate = picked);
    }
  }

  void _pickTime() {
    // Time slots theo PHP - dải 30 phút trong khung 12:00 - 22:30.
    final slots = <String>[];
    for (var h = 12; h <= 22; h++) {
      for (final m in [0, 30]) {
        final hh = h.toString().padLeft(2, '0');
        final mm = m.toString().padLeft(2, '0');
        slots.add('$hh:$mm');
      }
    }
    final currentTime = _selectedTime;
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: SizedBox(
          height: 360,
          child: Column(
            children: [
              Container(
                margin: const EdgeInsets.symmetric(vertical: 8),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.border,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Text(
                  'CHỌN GIỜ ĐẾN',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 0.14,
                    color: AppColors.primaryStrong,
                  ),
                ),
              ),
              Expanded(
                child: GridView.builder(
                  padding: const EdgeInsets.all(16),
                  gridDelegate:
                      const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 4,
                    childAspectRatio: 2,
                    mainAxisSpacing: 8,
                    crossAxisSpacing: 8,
                  ),
                  itemCount: slots.length,
                  itemBuilder: (_, i) {
                    final slot = slots[i];
                    final selected = slot == currentTime;
                    return InkWell(
                      onTap: () {
                        setState(() => _selectedTime = slot);
                        Navigator.pop(ctx);
                      },
                      child: Container(
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: selected
                              ? AppColors.primary
                              : AppColors.cream,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(
                            color: selected
                                ? AppColors.primary
                                : AppColors.border,
                          ),
                        ),
                        child: Text(
                          slot,
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w800,
                            color: selected
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _pickGuests() {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: SizedBox(
          height: 220,
          child: Column(
            children: [
              Container(
                margin: const EdgeInsets.symmetric(vertical: 8),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.border,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Text(
                  'SỐ KHÁCH',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 0.14,
                    color: AppColors.primaryStrong,
                  ),
                ),
              ),
              Expanded(
                child: ListView.builder(
                  itemCount: 20,
                  itemBuilder: (_, i) {
                    final n = i + 1;
                    final isSelected = n == _guests;
                    return ListTile(
                      title: Text(
                        '$n ${n == 1 ? "khách" : "khách"}',
                        style: TextStyle(
                          fontWeight: isSelected
                              ? FontWeight.w900
                              : FontWeight.w600,
                          color: isSelected
                              ? AppColors.primary
                              : AppColors.textPrimary,
                        ),
                      ),
                      trailing: isSelected
                          ? const Icon(Icons.check_circle,
                              color: AppColors.primary)
                          : null,
                      onTap: () {
                        setState(() => _guests = n);
                        Navigator.pop(ctx);
                      },
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;
    ComingSoon.show(context, feature: 'Đặt bàn');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Stack(
        children: [
          Column(
            children: [
              const PaprikaHeader(),
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _PageTitleCard(
                        date: _selectedDate,
                        time: _selectedTime,
                        guests: _guests,
                        onPickDate: _pickDate,
                        onPickTime: _pickTime,
                        onPickGuests: _pickGuests,
                      ),
                      Padding(
                        padding: const EdgeInsets.all(AppConstants.spaceMd),
                        child: _InfoForm(
                          formKey: _formKey,
                          nameCtrl: _nameCtrl,
                          phoneCtrl: _phoneCtrl,
                          emailCtrl: _emailCtrl,
                          date: _selectedDate,
                          branches: _branches,
                          onSubmit: _submit,
                        ),
                      ),
                      const PaprikaFooter(),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const _FloatingActions(),
        ],
      ),
      bottomNavigationBar: const BottomNavBar(),
    );
  }
}

// ===========================================================================
// PAGE TITLE CARD — dark green hero block (GIỮ BÀN TẠI PAPRIKA)
// ===========================================================================

class _PageTitleCard extends StatelessWidget {
  const _PageTitleCard({
    required this.date,
    required this.time,
    required this.guests,
    required this.onPickDate,
    required this.onPickTime,
    required this.onPickGuests,
  });

  final DateTime date;
  final String time;
  final int guests;
  final VoidCallback onPickDate;
  final VoidCallback onPickTime;
  final VoidCallback onPickGuests;

  String get _dateLabel {
    const m = [
      'THÁNG 1',
      'THÁNG 2',
      'THÁNG 3',
      'THÁNG 4',
      'THÁNG 5',
      'THÁNG 6',
      'THÁNG 7',
      'THÁNG 8',
      'THÁNG 9',
      'THÁNG 10',
      'THÁNG 11',
      'THÁNG 12'
    ];
    return '${date.day} ${m[date.month - 1]}';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(
        AppConstants.spaceMd,
        AppConstants.spaceLg,
        AppConstants.spaceMd,
        AppConstants.spaceLg,
      ),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [AppColors.primary, AppColors.primaryStrong],
        ),
        border: Border(
          bottom: BorderSide(color: AppColors.gold, width: 4),
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
                'ĐẶT BÀN',
                style: TextStyle(
                  color: AppColors.gold,
                  fontSize: 11,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 0.18,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          const Text(
            'GIỮ BÀN TẠI PAPRIKA',
            style: TextStyle(
              color: Colors.white,
              fontSize: 24,
              fontWeight: FontWeight.w900,
              letterSpacing: -0.4,
              height: 1.15,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Chọn cơ sở, ngày, giờ và số khách. Quán sẽ xác nhận đặt bàn qua điện thoại.',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.85),
              fontSize: 13,
              height: 1.45,
            ),
          ),
          const SizedBox(height: AppConstants.spaceMd),
          Row(
            children: [
              Expanded(
                child: _QuickAction(
                  label: 'NGÀY',
                  value: _dateLabel,
                  hint: 'CHỌN',
                  icon: Icons.calendar_today_outlined,
                  onTap: onPickDate,
                ),
              ),
              const SizedBox(width: AppConstants.spaceSm),
              Expanded(
                child: _QuickAction(
                  label: 'GIỜ',
                  value: 'mở',
                  hint: time,
                  icon: Icons.schedule_outlined,
                  onTap: onPickTime,
                ),
              ),
              const SizedBox(width: AppConstants.spaceSm),
              Expanded(
                child: _QuickAction(
                  label: 'KHÁCH',
                  value: 'CÒN BÀN',
                  hint: '$guests',
                  icon: Icons.people_alt_outlined,
                  onTap: onPickGuests,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _QuickAction extends StatelessWidget {
  const _QuickAction({
    required this.label,
    required this.value,
    required this.hint,
    required this.icon,
    required this.onTap,
  });

  final String label;
  final String value;
  final String hint;
  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(AppConstants.radiusSm),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppConstants.radiusSm),
            border: Border.all(color: Colors.white24),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                children: [
                  Icon(icon, color: AppColors.gold, size: 14),
                  const SizedBox(width: 4),
                  Text(
                    label,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.7),
                      fontSize: 9,
                      fontWeight: FontWeight.w900,
                      letterSpacing: 0.12,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                value,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 0.06,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 2),
              Text(
                hint,
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.85),
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ===========================================================================
// INFO FORM — THÔNG TIN ĐẶT BÀN
// ===========================================================================

class _InfoForm extends StatefulWidget {
  const _InfoForm({
    required this.formKey,
    required this.nameCtrl,
    required this.phoneCtrl,
    required this.emailCtrl,
    required this.date,
    required this.branches,
    required this.onSubmit,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController nameCtrl;
  final TextEditingController phoneCtrl;
  final TextEditingController emailCtrl;
  final DateTime date;
  final List<String> branches;
  final VoidCallback onSubmit;

  @override
  State<_InfoForm> createState() => _InfoFormState();
}

class _InfoFormState extends State<_InfoForm> {
  String? _selectedBranch;

  @override
  void initState() {
    super.initState();
    _selectedBranch = widget.branches.first;
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: widget.formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Container(
                width: 4,
                height: 18,
                decoration: BoxDecoration(
                  color: AppColors.accent,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 8),
              const Text(
                'THÔNG TIN ĐẶT BÀN',
                style: TextStyle(
                  color: AppColors.primaryStrong,
                  fontSize: 13,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 0.14,
                ),
              ),
              const SizedBox(width: 8),
              const Expanded(
                child: Divider(color: AppColors.border),
              ),
            ],
          ),
          const SizedBox(height: AppConstants.spaceMd),
          _LabeledField(
            label: 'Họ tên',
            required: true,
            child: TextFormField(
              controller: widget.nameCtrl,
              decoration: const InputDecoration(
                hintText: 'Nguyễn Văn A',
                prefixIcon: Icon(Icons.person_outline,
                    color: AppColors.primary, size: 20),
              ),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? 'Vui lòng nhập họ tên' : null,
            ),
          ),
          _LabeledField(
            label: 'Điện thoại',
            required: true,
            child: TextFormField(
              controller: widget.phoneCtrl,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                hintText: '+30 2610 123 456',
                prefixIcon: Icon(Icons.phone_outlined,
                    color: AppColors.primary, size: 20),
              ),
              validator: (v) {
                if (v == null || v.trim().isEmpty) {
                  return 'Vui lòng nhập số điện thoại';
                }
                if (v.trim().length < 6) return 'Số điện thoại không hợp lệ';
                return null;
              },
            ),
          ),
          _LabeledField(
            label: 'Email',
            child: TextFormField(
              controller: widget.emailCtrl,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(
                hintText: 'email@example.com',
                prefixIcon: Icon(Icons.alternate_email,
                    color: AppColors.primary, size: 20),
              ),
              validator: (v) {
                if (v == null || v.isEmpty) return null;
                final ok = RegExp(r'^[\w.\-+]+@[\w\-]+\.[\w\-.]+$').hasMatch(v);
                return ok ? null : 'Email không hợp lệ';
              },
            ),
          ),
          _LabeledField(
            label: 'Cơ sở',
            required: true,
            child: DropdownButtonFormField<String>(
              initialValue: _selectedBranch,
              isExpanded: true,
              decoration: const InputDecoration(
                prefixIcon: Icon(Icons.storefront_outlined,
                    color: AppColors.primary, size: 20),
              ),
              items: [
                for (final b in widget.branches)
                  DropdownMenuItem(value: b, child: Text(b)),
              ],
              onChanged: (v) => setState(() => _selectedBranch = v),
              validator: (v) =>
                  v == null ? 'Vui lòng chọn cơ sở' : null,
            ),
          ),
          _LabeledField(
            label: 'Ngày',
            child: Container(
              padding: const EdgeInsets.symmetric(
                horizontal: 12,
                vertical: 14,
              ),
              decoration: BoxDecoration(
                color: AppColors.cream,
                border: Border.all(color: AppColors.border),
                borderRadius: BorderRadius.circular(AppConstants.radiusSm),
              ),
              child: Row(
                children: [
                  const Icon(Icons.calendar_today_outlined,
                      color: AppColors.primary, size: 20),
                  const SizedBox(width: 8),
                  Text(
                    '${widget.date.day.toString().padLeft(2, '0')}/'
                    '${widget.date.month.toString().padLeft(2, '0')}/'
                    '${widget.date.year}',
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontSize: 14,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: AppConstants.spaceLg),
          SizedBox(
            height: 52,
            child: ElevatedButton.icon(
              onPressed: widget.onSubmit,
              icon: const Icon(Icons.event_available),
              label: const Text('GIỮ BÀN NGAY'),
            ),
          ),
          const SizedBox(height: AppConstants.spaceSm),
          Center(
            child: Text(
              'Quán sẽ gọi điện xác nhận trong vòng 30 phút.',
              style: TextStyle(
                color: AppColors.textMuted,
                fontSize: 11,
                fontStyle: FontStyle.italic,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _LabeledField extends StatelessWidget {
  const _LabeledField({
    required this.label,
    required this.child,
    this.required = false,
  });

  final String label;
  final Widget child;
  final bool required;

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
                  color: AppColors.primaryStrong,
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                  letterSpacing: 0.04,
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
        ],
      ),
    );
  }
}

// ===========================================================================
// FLOATING ACTIONS — phone + chat (absolute positioned bottom-right)
// ===========================================================================

class _FloatingActions extends StatelessWidget {
  const _FloatingActions();

  @override
  Widget build(BuildContext context) {
    // Padding-bottom = bottom nav height (~72px) + safe area + gap.
    final bottomInset = MediaQuery.of(context).padding.bottom;
    return Positioned(
      right: 12,
      bottom: 80 + bottomInset,
      child: Column(
        children: [
          _FloatingButton(
            icon: Icons.phone,
            color: AppColors.primary,
            tooltip: 'Gọi hotline',
            onTap: () => ComingSoon.show(context, feature: 'Gọi hotline'),
          ),
          const SizedBox(height: 10),
          _FloatingButton(
            icon: Icons.chat_bubble_outline,
            color: AppColors.accent,
            tooltip: 'Chat với quán',
            onTap: () => ComingSoon.show(context, feature: 'Chat với quán'),
          ),
        ],
      ),
    );
  }
}

class _FloatingButton extends StatelessWidget {
  const _FloatingButton({
    required this.icon,
    required this.color,
    required this.tooltip,
    required this.onTap,
  });

  final IconData icon;
  final Color color;
  final String tooltip;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: color,
      shape: const CircleBorder(),
      elevation: 6,
      shadowColor: color.withValues(alpha: 0.4),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: Tooltip(
          message: tooltip,
          child: Container(
            width: 48,
            height: 48,
            alignment: Alignment.center,
            child: Icon(icon, color: Colors.white, size: 22),
          ),
        ),
      ),
    );
  }
}
