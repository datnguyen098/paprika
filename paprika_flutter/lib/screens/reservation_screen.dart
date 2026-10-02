import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../data/models/branch_model.dart';
import '../data/models/reservation_model.dart';
import '../l10n/generated/app_localizations.dart';
import '../providers/providers.dart';
import '../services/api_service.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/contact_actions.dart';
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
class ReservationScreen extends ConsumerStatefulWidget {
  const ReservationScreen({super.key});

  @override
  ConsumerState<ReservationScreen> createState() => _ReservationScreenState();
}

class _ReservationScreenState extends ConsumerState<ReservationScreen> {
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _noteCtrl = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  // Quick-action picker state.
  DateTime _selectedDate = DateTime.now();
  String _selectedTime = '12:00';
  int _guests = 2;
  int? _selectedBranchId;
  bool _isSubmitting = false;
  ReservationResponse? _createdReservation;
  Map<String, String> _fieldErrors = const {};

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _emailCtrl.dispose();
    _noteCtrl.dispose();
    super.dispose();
  }

  String _dateForApi(DateTime date) {
    return '${date.year.toString().padLeft(4, '0')}-'
        '${date.month.toString().padLeft(2, '0')}-'
        '${date.day.toString().padLeft(2, '0')}';
  }

  void _pickDate() async {
    FocusManager.instance.primaryFocus?.unfocus();
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
    FocusManager.instance.primaryFocus?.unfocus();
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
    final l = AppLocalizations.of(context);
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
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Text(
                  l.reservationFieldTime,
                  style: const TextStyle(
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
    FocusManager.instance.primaryFocus?.unfocus();
    final l = AppLocalizations.of(context);
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
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: Text(
                  l.guestsPickerTitle,
                  style: const TextStyle(
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
                        l.guestsPickerItem(n),
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

  Future<void> _submit({
    required int branchId,
    ReservationAvailabilityResponse? availability,
  }) async {
    FocusManager.instance.primaryFocus?.unfocus();
    if (!_formKey.currentState!.validate()) return;
    if (availability != null && !availability.available) {
      _showSnack(
        availability.message ??
            'Hiện không còn bàn phù hợp trong khung giờ này.',
        isError: true,
      );
      return;
    }

    final request = CreateReservationRequest(
      name: _nameCtrl.text,
      phone: _phoneCtrl.text,
      email: _emailCtrl.text,
      note: _noteCtrl.text,
      branchId: branchId,
      reservationDate: _dateForApi(_selectedDate),
      reservationTime: _selectedTime,
      guests: _guests,
    );
    final localErrors = request.validate();
    if (localErrors.isNotEmpty) {
      setState(() => _fieldErrors = localErrors);
      _formKey.currentState!.validate();
      return;
    }

    setState(() {
      _isSubmitting = true;
      _fieldErrors = const {};
    });
    final l = AppLocalizations.of(context);
    try {
      final response = await ref
          .read(reservationRepositoryProvider)
          .createReservation(request);
      if (!mounted) return;
      _showSnack(
        '${l.reservationSuccessTitle}: ${response.reservationDate} ${response.reservationTime}',
      );
      setState(() => _createdReservation = response);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _fieldErrors = {
          for (final entry in (e.errors ?? {}).entries)
            entry.key: entry.value is List && (entry.value as List).isNotEmpty
                ? (entry.value as List).first.toString()
                : entry.value.toString(),
        };
      });
      _formKey.currentState!.validate();
      _showSnack(e.message, isError: true);
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }

  void _showSnack(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Text(message),
          behavior: SnackBarBehavior.floating,
          backgroundColor: isError ? AppColors.accent : AppColors.primary,
        ),
      );
  }

  void _startNewReservation() {
    setState(() {
      _createdReservation = null;
      _fieldErrors = const {};
      _noteCtrl.clear();
    });
  }

  @override
  Widget build(BuildContext context) {
    final branchesAsync = ref.watch(branchesProvider);
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: GestureDetector(
        behavior: HitTestBehavior.translucent,
        onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
        child: Stack(
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
                        child: branchesAsync.when(
                          data: (branches) {
                            final selectedBranchId = _selectedBranchId ??
                                (branches.isNotEmpty ? branches.first.id : null);
                            final availabilityRequest = selectedBranchId == null
                                ? null
                                : ReservationAvailabilityRequest(
                                    branchId: selectedBranchId,
                                    reservationDate: _dateForApi(_selectedDate),
                                    reservationTime: _selectedTime,
                                    guests: _guests,
                                  );
                            final availabilityAsync = availabilityRequest == null
                                ? null
                                : ref.watch(
                                    reservationAvailabilityProvider(
                                      availabilityRequest,
                                    ),
                                  );

                            final createdReservation = _createdReservation;
                            if (createdReservation != null) {
                              return _ReservationSuccessPanel(
                                reservation: createdReservation,
                                onNewReservation: _startNewReservation,
                              );
                            }

                            return _InfoForm(
                              formKey: _formKey,
                              nameCtrl: _nameCtrl,
                              phoneCtrl: _phoneCtrl,
                              emailCtrl: _emailCtrl,
                              noteCtrl: _noteCtrl,
                              date: _selectedDate,
                              time: _selectedTime,
                              guests: _guests,
                              branches: branches,
                              selectedBranchId: selectedBranchId,
                              availabilityAsync: availabilityAsync,
                              isSubmitting: _isSubmitting,
                              fieldErrors: _fieldErrors,
                              onBranchChanged: (id) {
                                setState(() {
                                  _selectedBranchId = id;
                                  _fieldErrors = const {};
                                });
                              },
                              onSubmit: selectedBranchId == null
                                  ? null
                                  : () => _submit(
                                        branchId: selectedBranchId,
                                        availability:
                                            availabilityAsync?.valueOrNull,
                                      ),
                            );
                          },
                          loading: () => const Center(
                            child: Padding(
                              padding: EdgeInsets.all(AppConstants.spaceLg),
                              child: CircularProgressIndicator(),
                            ),
                          ),
                          error: (error, _) => _LoadError(
                            message: error.toString(),
                            onRetry: () => ref.invalidate(branchesProvider),
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
            const _FloatingActions(),
          ],
        ),
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
    final l = AppLocalizations.of(context);
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
              Text(
                l.reservationBadge,
                style: const TextStyle(
                  color: AppColors.gold,
                  fontSize: 11,
                  fontWeight: FontWeight.w900,
                  letterSpacing: 0.18,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            l.reservationHeroTitle,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 24,
              fontWeight: FontWeight.w900,
              letterSpacing: -0.4,
              height: 1.15,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            l.reservationSubtitle,
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
                  label: l.quickActionDateLabel,
                  value: _dateLabel,
                  hint: l.quickActionDateHint,
                  icon: Icons.calendar_today_outlined,
                  onTap: onPickDate,
                ),
              ),
              const SizedBox(width: AppConstants.spaceSm),
              Expanded(
                child: _QuickAction(
                  label: l.quickActionTimeLabel,
                  value: l.quickActionTimeHintValue,
                  hint: time,
                  icon: Icons.schedule_outlined,
                  onTap: onPickTime,
                ),
              ),
              const SizedBox(width: AppConstants.spaceSm),
              Expanded(
                child: _QuickAction(
                  label: l.quickActionGuestsLabel,
                  value: l.quickActionGuestsHint,
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
    required this.noteCtrl,
    required this.date,
    required this.time,
    required this.guests,
    required this.branches,
    required this.selectedBranchId,
    required this.availabilityAsync,
    required this.isSubmitting,
    required this.fieldErrors,
    required this.onBranchChanged,
    required this.onSubmit,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController nameCtrl;
  final TextEditingController phoneCtrl;
  final TextEditingController emailCtrl;
  final TextEditingController noteCtrl;
  final DateTime date;
  final String time;
  final int guests;
  final List<Branch> branches;
  final int? selectedBranchId;
  final AsyncValue<ReservationAvailabilityResponse>? availabilityAsync;
  final bool isSubmitting;
  final Map<String, String> fieldErrors;
  final ValueChanged<int?> onBranchChanged;
  final VoidCallback? onSubmit;

  @override
  State<_InfoForm> createState() => _InfoFormState();
}

class _InfoFormState extends State<_InfoForm> {
  String? _fieldError(String field) => widget.fieldErrors[field];

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context);
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
              Text(
                l.reservationTitle,
                style: const TextStyle(
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
          const SizedBox(height: AppConstants.spaceMd),
          _LabeledField(
            label: l.reservationFieldName,
            required: true,
            child: TextFormField(
              controller: widget.nameCtrl,
              decoration: InputDecoration(
                hintText: l.contactFieldNameHint,
                errorText: _fieldError('name'),
                prefixIcon: const Icon(Icons.person_outline,
                    color: AppColors.primary, size: 20),
              ),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? l.validationNameRequired : null,
            ),
          ),
          _LabeledField(
            label: l.reservationFieldPhone,
            required: true,
            child: TextFormField(
              controller: widget.phoneCtrl,
              keyboardType: TextInputType.phone,
              decoration: InputDecoration(
                hintText: l.contactFieldPhoneHint,
                errorText: _fieldError('phone'),
                prefixIcon: const Icon(Icons.phone_outlined,
                    color: AppColors.primary, size: 20),
              ),
              validator: (v) {
                if (v == null || v.trim().isEmpty) {
                  return l.validationPhoneRequired;
                }
                if (v.trim().length < 6) return l.validationPhoneInvalid;
                return null;
              },
            ),
          ),
          _LabeledField(
            label: l.reservationFieldEmail,
            child: TextFormField(
              controller: widget.emailCtrl,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(
                hintText: 'email@example.com',
                prefixIcon: Icon(Icons.alternate_email,
                    color: AppColors.primary, size: 20),
              ).copyWith(errorText: _fieldError('email')),
              validator: (v) {
                if (v == null || v.isEmpty) return null;
                final ok = RegExp(r'^[\w.\-+]+@[\w\-]+\.[\w\-.]+$').hasMatch(v);
                return ok ? null : l.validationEmailInvalid;
              },
            ),
          ),
          _LabeledField(
            label: l.reservationFieldBranch,
            required: true,
            child: DropdownButtonFormField<int>(
              initialValue: widget.selectedBranchId,
              isExpanded: true,
              decoration: InputDecoration(
                prefixIcon: Icon(Icons.storefront_outlined,
                    color: AppColors.primary, size: 20),
                errorText: _fieldError('branch_id'),
              ),
              items: [
                for (final b in widget.branches)
                  DropdownMenuItem(value: b.id, child: Text(b.name)),
              ],
              onChanged: widget.onBranchChanged,
              validator: (v) =>
                  v == null ? l.validationBranchRequired : null,
            ),
          ),
          _LabeledField(
            label: l.reservationFieldTime,
            child: _ReadonlyInfoRow(
              icon: Icons.schedule_outlined,
              text: widget.time,
              errorText: _fieldError('reservation_time'),
            ),
          ),
          _LabeledField(
            label: l.reservationFieldGuests,
            child: _ReadonlyInfoRow(
              icon: Icons.people_alt_outlined,
              text: l.guestsPickerItem(widget.guests),
              errorText: _fieldError('guests'),
            ),
          ),
          _LabeledField(
            label: l.reservationFieldNotes,
            child: TextFormField(
              controller: widget.noteCtrl,
              maxLines: 3,
              decoration: InputDecoration(
                hintText: l.reservationFieldNotes,
                errorText: _fieldError('note'),
                prefixIcon: const Icon(Icons.notes_outlined,
                    color: AppColors.primary, size: 20),
              ),
            ),
          ),
          _AvailabilityStatus(availabilityAsync: widget.availabilityAsync),
          const SizedBox(height: AppConstants.spaceMd),
          _LabeledField(
            label: l.reservationFieldDate,
            child: _ReadonlyInfoRow(
              icon: Icons.calendar_today_outlined,
              text: '${widget.date.day.toString().padLeft(2, '0')}/'
                  '${widget.date.month.toString().padLeft(2, '0')}/'
                  '${widget.date.year}',
              errorText: _fieldError('reservation_date'),
            ),
          ),
          const SizedBox(height: AppConstants.spaceLg),
          SizedBox(
            height: 52,
            child: ElevatedButton.icon(
              onPressed: widget.isSubmitting ? null : widget.onSubmit,
              icon: widget.isSubmitting
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.event_available),
              label: Text(
                widget.isSubmitting ? l.commonSending : l.reservationSubmit,
              ),
            ),
          ),
          const SizedBox(height: AppConstants.spaceSm),
          Center(
            child: Text(
              l.reservationReplyHint,
              style: const TextStyle(
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
    final l = AppLocalizations.of(context);
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
                Text(
                  l.requiredAsterisk,
                  style: const TextStyle(
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

class _ReadonlyInfoRow extends StatelessWidget {
  const _ReadonlyInfoRow({
    required this.icon,
    required this.text,
    this.errorText,
  });

  final IconData icon;
  final String text;
  final String? errorText;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.symmetric(
            horizontal: 12,
            vertical: 14,
          ),
          decoration: BoxDecoration(
            color: AppColors.cream,
            border: Border.all(
              color: errorText == null ? AppColors.border : AppColors.accent,
            ),
            borderRadius: BorderRadius.circular(AppConstants.radiusSm),
          ),
          child: Row(
            children: [
              Icon(icon, color: AppColors.primary, size: 20),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  text,
                  style: const TextStyle(
                    color: AppColors.textPrimary,
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
        ),
        if (errorText != null) ...[
          const SizedBox(height: 6),
          Text(
            errorText!,
            style: const TextStyle(
              color: AppColors.accent,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ],
    );
  }
}

class _AvailabilityStatus extends StatelessWidget {
  const _AvailabilityStatus({required this.availabilityAsync});

  final AsyncValue<ReservationAvailabilityResponse>? availabilityAsync;

  @override
  Widget build(BuildContext context) {
    final async = availabilityAsync;
    if (async == null) {
      return const SizedBox.shrink();
    }

    return async.when(
      loading: () => const _StatusPill(
        icon: Icons.hourglass_top,
        text: 'Đang kiểm tra bàn trống...',
        color: AppColors.textMuted,
      ),
      error: (error, _) => _StatusPill(
        icon: Icons.error_outline,
        text: 'Chưa kiểm tra được bàn trống. Vui lòng thử lại.',
        color: AppColors.accent,
      ),
      data: (availability) {
        if (availability.available) {
          return _StatusPill(
            icon: Icons.event_available,
            text: 'Còn ${availability.availableCount} bàn phù hợp.',
            color: AppColors.primary,
          );
        }
        return _StatusPill(
          icon: Icons.event_busy,
          text: availability.message ??
              'Không còn bàn phù hợp trong khung giờ này.',
          color: AppColors.accent,
        );
      },
    );
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({
    required this.icon,
    required this.text,
    required this.color,
  });

  final IconData icon;
  final String text;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: AppConstants.spaceMd),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        border: Border.all(color: color.withValues(alpha: 0.25)),
        borderRadius: BorderRadius.circular(AppConstants.radiusSm),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                color: color,
                fontSize: 12,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _LoadError extends StatelessWidget {
  const _LoadError({
    required this.message,
    required this.onRetry,
  });

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context);
    return Container(
      padding: const EdgeInsets.all(AppConstants.spaceMd),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(AppConstants.radius),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Icon(Icons.error_outline, color: AppColors.accent),
          const SizedBox(height: 8),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: AppColors.textMuted,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          OutlinedButton(
            onPressed: onRetry,
            child: Text(l.commonRetry),
          ),
        ],
      ),
    );
  }
}

class _ReservationSuccessPanel extends StatelessWidget {
  const _ReservationSuccessPanel({
    required this.reservation,
    required this.onNewReservation,
  });

  final ReservationResponse reservation;
  final VoidCallback onNewReservation;

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context);
    return Container(
      padding: const EdgeInsets.all(AppConstants.spaceMd),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: AppColors.border),
        borderRadius: BorderRadius.circular(AppConstants.radius),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Icon(
            Icons.event_available,
            color: AppColors.primary,
            size: 42,
          ),
          const SizedBox(height: AppConstants.spaceSm),
          Text(
            l.reservationSuccessTitle,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: AppColors.primaryStrong,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            l.reservationSuccessBody,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: AppColors.textMuted,
              fontSize: 13,
              height: 1.45,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: AppConstants.spaceMd),
          _SuccessInfoRow(
            icon: Icons.schedule_outlined,
            label:
                '${reservation.reservationDate} ${reservation.reservationTime}',
          ),
          if (reservation.branchName != null &&
              reservation.branchName!.isNotEmpty)
            _SuccessInfoRow(
              icon: Icons.storefront_outlined,
              label: reservation.branchName!,
            ),
          _SuccessInfoRow(
            icon: Icons.people_alt_outlined,
            label: l.guestsPickerItem(reservation.guests),
          ),
          if (reservation.tableName != null &&
              reservation.tableName!.isNotEmpty)
            _SuccessInfoRow(
              icon: Icons.event_seat_outlined,
              label: reservation.tableName!,
            ),
          const SizedBox(height: AppConstants.spaceMd),
          FilledButton.icon(
            onPressed: () => context.go(AppRoutes.reservations),
            icon: const Icon(Icons.event_note_outlined),
            label: const Text('Xem lại đặt bàn'),
          ),
          const SizedBox(height: AppConstants.spaceSm),
          OutlinedButton.icon(
            onPressed: onNewReservation,
            icon: const Icon(Icons.add_circle_outline),
            label: const Text('Tạo yêu cầu mới'),
          ),
        ],
      ),
    );
  }
}

class _SuccessInfoRow extends StatelessWidget {
  const _SuccessInfoRow({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: AppConstants.spaceSm),
      child: Row(
        children: [
          Icon(icon, color: AppColors.primary, size: 18),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                color: AppColors.textPrimary,
                fontSize: 13,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ===========================================================================
// FLOATING ACTIONS — phone + chat (absolute positioned bottom-right)
// ===========================================================================

class _FloatingActions extends ConsumerWidget {
  const _FloatingActions();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = AppLocalizations.of(context);
    final branch = _preferredContactBranch(ref);
    final phone = branch?.displayHotline;
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
            tooltip: l.reservationCallTooltip,
            onTap: () => launchPhoneCall(context, phone),
          ),
          const SizedBox(height: 10),
          _FloatingButton(
            icon: Icons.chat_bubble_outline,
            color: AppColors.accent,
            tooltip: l.reservationChatTooltip,
            onTap: () => openChatSupport(context, branchId: branch?.id),
          ),
        ],
      ),
    );
  }

  Branch? _preferredContactBranch(WidgetRef ref) {
    final branches = ref.watch(branchesProvider).asData?.value;
    if (branches == null || branches.isEmpty) return null;

    final activeBranchId = ref.watch(storageServiceProvider).getActiveBranchId();
    if (activeBranchId != null) {
      for (final branch in branches) {
        if (branch.id == activeBranchId) return branch;
      }
    }

    return branches.first;
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
