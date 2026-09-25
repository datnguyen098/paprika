import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../core/constants/app_colors.dart';

/// Screen for managing user's allergen preferences
class AllergenSettingsScreen extends StatefulWidget {
  const AllergenSettingsScreen({super.key});

  @override
  State<AllergenSettingsScreen> createState() => _AllergenSettingsScreenState();
}

class _AllergenSettingsScreenState extends State<AllergenSettingsScreen> {
  static const String _prefsKey = 'user_allergens';

  // Complete list of common allergens (EU regulation 1169/2011)
  static const List<_AllergenOption> _allAllergens = [
    _AllergenOption(key: 'gluten', label: 'Gluten'),
    _AllergenOption(key: 'dairy', label: 'Sữa'),
    _AllergenOption(key: 'egg', label: 'Trứng'),
    _AllergenOption(key: 'soy', label: 'Đậu nành'),
    _AllergenOption(key: 'sesame', label: 'Mè'),
    _AllergenOption(key: 'mustard', label: 'Mù tạt'),
    _AllergenOption(key: 'seafood', label: 'Hải sản'),
    _AllergenOption(key: 'fish', label: 'Cá'),
    _AllergenOption(key: 'peanut', label: 'Đậu phộng'),
    _AllergenOption(key: 'tree_nuts', label: 'Hạt cây'),
    _AllergenOption(key: 'sulphites', label: 'Lưu huỳnh'),
    _AllergenOption(key: 'celery', label: 'Cần tây'),
    _AllergenOption(key: 'molluscs', label: 'Động vật thân mềm'),
    _AllergenOption(key: 'lupin', label: 'Lupin'),
  ];

  final Set<String> _selectedAllergens = {};
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getStringList(_prefsKey) ?? [];
    final normalized = saved
        .map(_allergenKeyForSavedValue)
        .where((key) => key.isNotEmpty)
        .toSet();
    if (normalized.length != saved.length ||
        normalized.any((key) => !saved.contains(key))) {
      await prefs.setStringList(_prefsKey, normalized.toList());
    }
    if (!mounted) return;
    setState(() {
      _selectedAllergens.addAll(normalized);
      _isLoading = false;
    });
  }

  Future<void> _savePreferences() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(_prefsKey, _selectedAllergens.toList());

    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Đã lưu thông tin dị ứng'),
        duration: Duration(seconds: 2),
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
    for (final allergen in _allAllergens) {
      if (allergen.key == key) return allergen.label;
    }
    return key;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.cream,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: AppColors.primary),
          onPressed: () => Navigator.of(context).pop(),
        ),
        title: const Text(
          'Quản lý dị ứng',
          style: TextStyle(
            color: AppColors.textPrimary,
            fontSize: 18,
            fontWeight: FontWeight.w700,
          ),
        ),
        actions: [
          TextButton(
            onPressed: _savePreferences,
            child: const Text(
              'Lưu',
              style: TextStyle(
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
                        children: const [
                          Icon(Icons.info_outline, color: AppColors.primary, size: 20),
                          SizedBox(width: 8),
                          Text(
                            'Chọn các loại thực phẩm bạn dị ứng',
                            style: TextStyle(
                              color: AppColors.textPrimary,
                              fontSize: 14,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Chúng tôi sẽ tự động cảnh báo khi món ăn có chứa các chất gây dị ứng bạn đã chọn.',
                        style: TextStyle(
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
                              Text(
                                allergen.label,
                                style: TextStyle(
                                  color: isSelected 
                                      ? AppColors.accentStrong
                                      : AppColors.textPrimary,
                                  fontSize: 15,
                                  fontWeight: FontWeight.w700,
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
                          'Đã chọn ${_selectedAllergens.length} loại dị ứng',
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
    required this.label,
  });

  final String key;
  final String label;
}
