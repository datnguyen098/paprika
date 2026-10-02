import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../data/models/reservation_model.dart';
import '../providers/providers.dart';
import '../services/api_service.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';

class ReservationsScreen extends ConsumerStatefulWidget {
  const ReservationsScreen({super.key});

  @override
  ConsumerState<ReservationsScreen> createState() => _ReservationsScreenState();
}

class _ReservationsScreenState extends ConsumerState<ReservationsScreen> {
  final _queryCtrl = TextEditingController();
  bool _isLoading = false;
  String? _error;
  List<ReservationResponse>? _reservations;

  @override
  void dispose() {
    _queryCtrl.dispose();
    super.dispose();
  }

  Future<void> _lookup() async {
    FocusScope.of(context).unfocus();
    final query = _queryCtrl.text.trim();
    if (query.isEmpty) {
      setState(() => _error = 'Vui lòng nhập email hoặc số điện thoại.');
      return;
    }

    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final reservations =
          await ref.read(reservationRepositoryProvider).lookupReservations(query);
      if (!mounted) return;
      setState(() => _reservations = reservations);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final reservations = _reservations;
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: GestureDetector(
        behavior: HitTestBehavior.translucent,
        onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
        child: Column(
          children: [
            const PaprikaHeader(activeRoute: AppRoutes.reservation),
            Expanded(
              child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const _ReservationsHero(),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
                    child: Center(
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(
                          maxWidth: AppConstants.maxContentWidth,
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            _LookupCard(
                              controller: _queryCtrl,
                              isLoading: _isLoading,
                              error: _error,
                              onSubmit: _lookup,
                            ),
                            const SizedBox(height: 14),
                            if (reservations == null)
                              const _Hint()
                            else if (reservations.isEmpty)
                              const _NoReservations()
                            else
                              for (final reservation in reservations) ...[
                                _ReservationCard(reservation: reservation),
                                const SizedBox(height: 10),
                              ],
                          ],
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
}

class _ReservationsHero extends StatelessWidget {
  const _ReservationsHero();

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
                'XEM LẠI ĐẶT BÀN',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 32,
                  height: 1.05,
                  fontWeight: FontWeight.w900,
                ),
              ),
              SizedBox(height: 8),
              Text(
                'Nhập email hoặc số điện thoại để xem các yêu cầu đặt bàn đã gửi.',
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

class _LookupCard extends StatelessWidget {
  const _LookupCard({
    required this.controller,
    required this.isLoading,
    required this.onSubmit,
    this.error,
  });

  final TextEditingController controller;
  final bool isLoading;
  final String? error;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppConstants.radius),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TextField(
            controller: controller,
            textInputAction: TextInputAction.search,
            keyboardType: TextInputType.emailAddress,
            onSubmitted: (_) => onSubmit(),
            decoration: InputDecoration(
              hintText: 'Email hoặc số điện thoại',
              errorText: error,
              prefixIcon: const Icon(Icons.search, color: AppColors.primary),
            ),
          ),
          const SizedBox(height: 12),
          FilledButton.icon(
            onPressed: isLoading ? null : onSubmit,
            icon: isLoading
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.event_note_outlined),
            label: Text(isLoading ? 'Đang tra cứu...' : 'XEM ĐẶT BÀN'),
          ),
        ],
      ),
    );
  }
}

class _ReservationCard extends StatelessWidget {
  const _ReservationCard({required this.reservation});

  final ReservationResponse reservation;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppConstants.radius),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '${reservation.reservationDate} ${reservation.reservationTime}',
                  style: const TextStyle(
                    color: AppColors.primaryStrong,
                    fontSize: 16,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              _StatusBadge(label: reservation.statusLabel),
            ],
          ),
          const SizedBox(height: 8),
          _InfoLine(
            icon: Icons.people_alt_outlined,
            label: '${reservation.guests} khách',
          ),
          if (reservation.branchName != null &&
              reservation.branchName!.isNotEmpty)
            _InfoLine(
              icon: Icons.storefront_outlined,
              label: reservation.branchName!,
            ),
          if (reservation.tableName != null && reservation.tableName!.isNotEmpty)
            _InfoLine(
              icon: Icons.event_seat_outlined,
              label: reservation.tableSeats == null
                  ? reservation.tableName!
                  : '${reservation.tableName!} · ${reservation.tableSeats} ghế',
            ),
          if (reservation.note != null && reservation.note!.isNotEmpty)
            _InfoLine(icon: Icons.notes_outlined, label: reservation.note!),
        ],
      ),
    );
  }
}

class _InfoLine extends StatelessWidget {
  const _InfoLine({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 6),
      child: Row(
        children: [
          Icon(icon, color: AppColors.primary, size: 18),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                color: AppColors.textPrimary,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: AppColors.successBg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        child: Text(
          label,
          style: const TextStyle(
            color: AppColors.primary,
            fontSize: 11,
            fontWeight: FontWeight.w900,
          ),
        ),
      ),
    );
  }
}

class _Hint extends StatelessWidget {
  const _Hint();

  @override
  Widget build(BuildContext context) {
    return const Text(
      'Dùng đúng email hoặc số điện thoại đã nhập khi đặt bàn.',
      textAlign: TextAlign.center,
      style: TextStyle(color: AppColors.textMuted, fontWeight: FontWeight.w700),
    );
  }
}

class _NoReservations extends StatelessWidget {
  const _NoReservations();

  @override
  Widget build(BuildContext context) {
    return const Text(
      'Không tìm thấy yêu cầu đặt bàn phù hợp.',
      textAlign: TextAlign.center,
      style: TextStyle(color: AppColors.textMuted, fontWeight: FontWeight.w800),
    );
  }
}
