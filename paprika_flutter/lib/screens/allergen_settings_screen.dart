import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../app/routes.dart';
import '../core/constants/app_colors.dart';
import '../core/i18n/ui_text.dart';
import '../providers/providers.dart';

/// Screen for managing user's allergen preferences
class AllergenSettingsScreen extends ConsumerStatefulWidget {
  const AllergenSettingsScreen({super.key});

  @override
  ConsumerState<AllergenSettingsScreen> createState() =>
      _AllergenSettingsScreenState();
}

class _AllergenSettingsScreenState
    extends ConsumerState<AllergenSettingsScreen> {
  // Complete list of common allergens (EU regulation 1169/2011)
  static const List<_AllergenOption> _allAllergens = [
    _AllergenOption(key: 'gluten'),
    _AllergenOption(key: 'dairy'),
    _AllergenOption(key: 'egg'),
    _AllergenOption(key: 'soy'),
    _AllergenOption(key: 'sesame'),
    _AllergenOption(key: 'mustard'),
    _AllergenOption(key: 'seafood'),
    _AllergenOption(key: 'fish'),
    _AllergenOption(key: 'peanut'),
    _AllergenOption(key: 'tree_nuts'),
    _AllergenOption(key: 'sulphites'),
    _AllergenOption(key: 'celery'),
    _AllergenOption(key: 'molluscs'),
    _AllergenOption(key: 'lupin'),
  ];

  final Set<String> _selectedAllergens = {};
  bool _isLoading = true;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    final storage = ref.read(storageServiceProvider);
    final saved = storage.getUserAllergens();
    final normalized = saved
        .map(_allergenKeyForSavedValue)
        .where((key) => key.isNotEmpty)
        .toSet();
    if (normalized.length != saved.length ||
        normalized.any((key) => !saved.contains(key))) {
      await storage.setUserAllergens(normalized);
    }
    if (!mounted) return;
    setState(() {
      _selectedAllergens.clear();
      _selectedAllergens.addAll(normalized);
      _isLoading = false;
    });
  }

  Future<void> _savePreferences() async {
    if (_isSaving) return;
    setState(() => _isSaving = true);

    try {
      await ref
          .read(storageServiceProvider)
          .setUserAllergens(_selectedAllergens);
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          _selectedAllergens.isEmpty
              ? UiText.of(context).allergensCleared
              : UiText.of(context).allergensSaved(_selectedAllergens.length),
        ),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  void _toggleAllergen(String key) {
    setState(() {
      if (_selectedAllergens.contains(key)) {
        _selectedAllergens.remove(key);
      } else {
        _selectedAllergens.add(key);
      }
    });
  }

  String _allergenKeyForSavedValue(String value) {
    final normalized = value.trim().toLowerCase();
    const legacyMap = {
      'gluten': 'gluten',
      'dairy': 'dairy',
      'milk': 'dairy',
      'sữa': 'dairy',
      'egg': 'egg',
      'eggs': 'egg',
      'trứng': 'egg',
      'soy': 'soy',
      'soya': 'soy',
      'đậu nành': 'soy',
      'sesame': 'sesame',
      'mè': 'sesame',
      'mustard': 'mustard',
      'mù tạt': 'mustard',
      'seafood': 'seafood',
      'hải sản': 'seafood',
      'fish': 'fish',
      'cá': 'fish',
      'peanut': 'peanut',
      'peanuts': 'peanut',
      'đậu phộng': 'peanut',
      'tree_nuts': 'tree_nuts',
      'tree nuts': 'tree_nuts',
      'hạt cây': 'tree_nuts',
      'sulphites': 'sulphites',
      'sulfites': 'sulphites',
      'lưu huỳnh': 'sulphites',
      'celery': 'celery',
      'cần tây': 'celery',
      'molluscs': 'molluscs',
      'động vật thân mềm': 'molluscs',
      'lupin': 'lupin',
    };
    return legacyMap[normalized] ?? normalized;
  }

  String _labelForKey(String key) {
    return UiText.of(context).allergenLabel(key);
  }

  void _goBack() {
    if (context.canPop()) {
      context.pop();
      return;
    }

    context.go(AppRoutes.menu);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.cream,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        titleSpacing: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: AppColors.primary),
          onPressed: _goBack,
        ),
        title: Text(
          UiText.of(context).allergenTitle,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(
            color: AppColors.textPrimary,
            fontSize: 16,
            fontWeight: FontWeight.w700,
          ),
        ),
        actions: [
          TextButton(
            onPressed: _isLoading || _isSaving ? null : _savePreferences,
            child: _isSaving
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : Text(
                    UiText.of(context).save,
                    style: const TextStyle(
                      color: AppColors.accent,
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Header
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.info_outline, color: AppColors.primary, size: 20),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              UiText.of(context).allergenIntroTitle,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: AppColors.textPrimary,
                                fontSize: 14,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        UiText.of(context).allergenIntroBody,
                        style: const TextStyle(
                          color: AppColors.textMuted,
                          fontSize: 13,
                          height: 1.5,
                        ),
                      ),
                    ],
                  ),
                ),
                
                const SizedBox(height: 24),
                
                // Allergen grid
                ..._allAllergens.map((allergen) {
                  final isSelected = _selectedAllergens.contains(allergen.key);
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: () => _toggleAllergen(allergen.key),
                        borderRadius: BorderRadius.circular(12),
                        child: Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: isSelected 
                                ? AppColors.accentSoft
                                : AppColors.surface,
                            border: Border.all(
                              color: isSelected 
                                  ? AppColors.accent
                                  : AppColors.border,
                              width: isSelected ? 2 : 1,
                            ),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 24,
                                height: 24,
                                decoration: BoxDecoration(
                                  color: isSelected 
                                      ? AppColors.accent
                                      : AppColors.surface,
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(
                                    color: isSelected 
                                        ? AppColors.accent
                                        : AppColors.border,
                                    width: 2,
                                  ),
                                ),
                                child: isSelected
                                    ? const Icon(
                                        Icons.check,
                                        color: Colors.white,
                                        size: 16,
                                      )
                                    : null,
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  UiText.of(context).allergenLabel(allergen.key),
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    color: isSelected
                                        ? AppColors.accentStrong
                                        : AppColors.textPrimary,
                                    fontSize: 15,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  );
                }).toList(),
                
                const SizedBox(height: 24),
                
                // Summary
                if (_selectedAllergens.isNotEmpty)
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          UiText.of(context).allergensSelected(_selectedAllergens.length),
                          style: const TextStyle(
                            color: AppColors.textPrimary,
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 6,
                          runSpacing: 6,
                          children: _selectedAllergens.map((allergen) {
                            return Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 4,
                              ),
                              decoration: BoxDecoration(
                                color: AppColors.accentSoft,
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: Text(
                                _labelForKey(allergen),
                                style: const TextStyle(
                                  color: AppColors.accentStrong,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            );
                          }).toList(),
                        ),
                      ],
                    ),
                  ),
              ],
            ),
    );
  }
}

class _AllergenOption {
  const _AllergenOption({
    required this.key,
  });

  final String key;
}
