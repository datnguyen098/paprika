import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/constants/app_constants.dart';
import '../core/i18n/ui_text.dart';
import '../core/utils/image_helper.dart';
import '../data/models/gallery_model.dart';
import '../providers/providers.dart';
import '../widgets/bottom_nav_bar.dart';
import '../widgets/paprika_footer.dart';
import '../widgets/paprika_header.dart';

class GalleryScreen extends ConsumerStatefulWidget {
  const GalleryScreen({super.key});

  @override
  ConsumerState<GalleryScreen> createState() => _GalleryScreenState();
}

class _GalleryScreenState extends ConsumerState<GalleryScreen> {
  int? _selectedBranchId;
  bool _showSharedOnly = false;

  @override
  Widget build(BuildContext context) {
    final gallery = ref.watch(galleryProvider);
    return Scaffold(
      backgroundColor: AppColors.cream,
      body: Column(
        children: [
          const PaprikaHeader(activeRoute: AppRoutes.gallery),
          Expanded(
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const _GalleryHero(),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(16, 18, 16, 28),
                    child: Center(
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(
                          maxWidth: AppConstants.maxContentWidth,
                        ),
                        child: gallery.when(
                          data: _buildGallery,
                          loading: () => const Padding(
                            padding: EdgeInsets.all(30),
                            child: Center(
                              child: CircularProgressIndicator(
                                color: AppColors.primary,
                              ),
                            ),
                          ),
                          error: (error, _) => _ErrorBox(
                            message: '${UiText.of(context).galleryLoadError} $error',
                            onRetry: () => ref.invalidate(galleryProvider),
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

  Widget _buildGallery(GalleryData data) {
    final images = _filteredImages(data);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (data.branches.isNotEmpty || data.sharedImages.isNotEmpty) ...[
          _GalleryFilters(
            branches: data.branches,
            selectedBranchId: _selectedBranchId,
            showSharedOnly: _showSharedOnly,
            hasSharedImages: data.sharedImages.isNotEmpty,
            onAll: () {
              setState(() {
                _selectedBranchId = null;
                _showSharedOnly = false;
              });
            },
            onShared: () {
              setState(() {
                _selectedBranchId = null;
                _showSharedOnly = true;
              });
            },
            onBranch: (id) {
              setState(() {
                _selectedBranchId = id;
                _showSharedOnly = false;
              });
            },
          ),
          const SizedBox(height: 16),
        ],
        if (images.isEmpty)
          const _EmptyGallery()
        else
          _GalleryGrid(images: images),
      ],
    );
  }

  List<GalleryImageItem> _filteredImages(GalleryData data) {
    if (_showSharedOnly) return data.sharedImages;

    final branchId = _selectedBranchId;
    if (branchId == null) return data.images;

    return data.images
        .where((image) => image.branch?.id == branchId)
        .toList(growable: false);
  }
}

class _GalleryHero extends StatelessWidget {
  const _GalleryHero();

  @override
  Widget build(BuildContext context) {
    final t = UiText.of(context);
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
          constraints: const BoxConstraints(
            maxWidth: AppConstants.maxContentWidth,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                t.galleryTitle,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 34,
                  height: 1.02,
                  fontWeight: FontWeight.w900,
                  fontStyle: FontStyle.italic,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                t.gallerySubtitle,
                style: const TextStyle(
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

class _GalleryFilters extends StatelessWidget {
  const _GalleryFilters({
    required this.branches,
    required this.selectedBranchId,
    required this.showSharedOnly,
    required this.hasSharedImages,
    required this.onAll,
    required this.onShared,
    required this.onBranch,
  });

  final List<GalleryBranch> branches;
  final int? selectedBranchId;
  final bool showSharedOnly;
  final bool hasSharedImages;
  final VoidCallback onAll;
  final VoidCallback onShared;
  final ValueChanged<int> onBranch;

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _FilterChipButton(
            label: UiText.of(context).all,
            selected: selectedBranchId == null && !showSharedOnly,
            onTap: onAll,
          ),
          const SizedBox(width: 8),
          for (final branch in branches) ...[
            _FilterChipButton(
              label: branch.name,
              selected: selectedBranchId == branch.id,
              onTap: () => onBranch(branch.id),
            ),
            const SizedBox(width: 8),
          ],
          if (hasSharedImages)
            _FilterChipButton(
              label: UiText.of(context).sharedImages,
              selected: showSharedOnly,
              onTap: onShared,
            ),
        ],
      ),
    );
  }
}

class _FilterChipButton extends StatelessWidget {
  const _FilterChipButton({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => onTap(),
      selectedColor: AppColors.primary,
      backgroundColor: AppColors.surface,
      labelStyle: TextStyle(
        color: selected ? Colors.white : AppColors.textPrimary,
        fontWeight: FontWeight.w900,
      ),
      side: BorderSide(
        color: selected ? AppColors.primary : AppColors.border,
      ),
    );
  }
}

class _GalleryGrid extends StatelessWidget {
  const _GalleryGrid({required this.images});

  final List<GalleryImageItem> images;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final crossAxisCount = constraints.maxWidth >= 980
            ? 3
            : constraints.maxWidth >= 620
                ? 2
                : 1;
        final gap = crossAxisCount == 1 ? 12.0 : 14.0;
        final itemWidth =
            (constraints.maxWidth - gap * (crossAxisCount - 1)) / crossAxisCount;

        return Wrap(
          spacing: gap,
          runSpacing: gap,
          children: [
            for (final image in images)
              SizedBox(
                width: itemWidth,
                child: _GalleryCard(image: image),
              ),
          ],
        );
      },
    );
  }
}

class _GalleryCard extends StatelessWidget {
  const _GalleryCard({required this.image});

  final GalleryImageItem image;

  @override
  Widget build(BuildContext context) {
    final imageUrl = ImageHelper.url(image.image);
    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(AppConstants.radius),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppConstants.radius),
        onTap: imageUrl == null
            ? null
            : () => showDialog<void>(
                  context: context,
                  builder: (_) => _ImageDialog(image: image, imageUrl: imageUrl),
                ),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(AppConstants.radius),
            border: Border.all(color: AppColors.border),
          ),
          clipBehavior: Clip.antiAlias,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              AspectRatio(
                aspectRatio: image.isFeatured ? 4 / 3 : 16 / 11,
                child: imageUrl == null
                    ? const _ImageFallback()
                    : Image.network(
                        imageUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const _ImageFallback(),
                      ),
              ),
              Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (image.title.isNotEmpty)
                      Text(
                        image.title,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.textPrimary,
                          fontSize: 15,
                          height: 1.25,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    if (image.description.isNotEmpty) ...[
                      const SizedBox(height: 6),
                      Text(
                        image.description,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 12,
                          height: 1.4,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                    if (image.branch?.name.isNotEmpty == true) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(
                            Icons.storefront_outlined,
                            color: AppColors.primary,
                            size: 15,
                          ),
                          const SizedBox(width: 5),
                          Expanded(
                            child: Text(
                              image.branch!.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: AppColors.primary,
                                fontSize: 11,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ImageDialog extends StatelessWidget {
  const _ImageDialog({
    required this.image,
    required this.imageUrl,
  });

  final GalleryImageItem image;
  final String imageUrl;

  @override
  Widget build(BuildContext context) {
    return Dialog(
      insetPadding: const EdgeInsets.all(16),
      backgroundColor: AppColors.surface,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppConstants.radius),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppConstants.radius),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            ConstrainedBox(
              constraints: BoxConstraints(
                maxHeight: MediaQuery.sizeOf(context).height * 0.68,
              ),
              child: InteractiveViewer(
                child: Image.network(
                  imageUrl,
                  fit: BoxFit.contain,
                  errorBuilder: (_, __, ___) => const _ImageFallback(),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(14),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(
                          image.title.isEmpty ? 'Paprika Patras' : image.title,
                          style: const TextStyle(
                            color: AppColors.textPrimary,
                            fontSize: 16,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        if (image.description.isNotEmpty) ...[
                          const SizedBox(height: 4),
                          Text(
                            image.description,
                            style: const TextStyle(
                              color: AppColors.textMuted,
                              fontSize: 12,
                              height: 1.4,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.close),
                    tooltip: UiText.of(context).close,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ImageFallback extends StatelessWidget {
  const _ImageFallback();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: AppColors.warm,
      alignment: Alignment.center,
      child: const Icon(
        Icons.photo_library_outlined,
        color: AppColors.primary,
        size: 42,
      ),
    );
  }
}

class _EmptyGallery extends StatelessWidget {
  const _EmptyGallery();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppConstants.radius),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        children: [
          const Icon(Icons.photo_library_outlined, color: AppColors.primary, size: 42),
          const SizedBox(height: 12),
          Text(
            UiText.of(context).emptyGallery,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: AppColors.textPrimary,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
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
        borderRadius: BorderRadius.circular(AppConstants.radius),
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
            label: Text(UiText.of(context).retry),
          ),
        ],
      ),
    );
  }
}
