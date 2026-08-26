import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';

/// Home screen base - mount Header + Footer + body placeholder.
///
/// Header sticky tự nhiên nhờ Column([header, Expanded(scroll), footer]).
///
/// Team FE sẽ thay body placeholder bằng nội dung thật.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      body: Column(
        children: [
          const PaprikaHeader(cartItemsCount: 0),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(
                vertical: AppConstants.spaceXl,
                horizontal: AppConstants.spaceMd,
              ),
              child: Center(
                child: Column(
                  children: [
                    const Icon(
                      Icons.restaurant_menu,
                      size: 80,
                      color: AppColors.primaryMuted,
                    ),
                    const SizedBox(height: AppConstants.spaceMd),
                    Text(
                      'Paprika Patras',
                      style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                            color: AppColors.primaryStrong,
                            fontWeight: FontWeight.w900,
                          ),
                    ),
                    const SizedBox(height: AppConstants.spaceSm),
                    Text(
                      'Vietnamese & Greek Restaurant',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: AppColors.textMuted,
                          ),
                    ),
                    const SizedBox(height: AppConstants.spaceLg),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: AppConstants.spaceMd,
                        vertical: AppConstants.spaceSm,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.accentSoft,
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        '€ EUR  ·  vi / en / el',
                        style: Theme.of(context).textTheme.labelMedium?.copyWith(
                              color: AppColors.accentStrong,
                              fontWeight: FontWeight.w800,
                            ),
                      ),
                    ),
                    const SizedBox(height: AppConstants.spaceXxl),
                    Text(
                      'Body sẽ do team FE dựng tiếp...',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: AppColors.textMuted,
                          ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const PaprikaFooter(),
        ],
      ),
    );
  }
}
