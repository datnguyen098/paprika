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
  static const List<String> _allAllergens = [
    'Gluten',
    'Sữa',
    'Trứng',
    'Đậu nành',
    'Mè',
    'Mù tạt',
    'Hải sản',
    'Cá',
    'Đậu phộng',
    'Hạt cây',
    'Lưu huỳnh',
    'Cần tây',
    'Động vật thân mềm',
    'Lupin',
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
    setState(() {
      _selectedAllergens.addAll(saved);
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

  void _toggleAllergen(String allergen) {
    setState(() {
      if (_selectedAllergens.contains(allergen)) {
        _selectedAllergens.remove(allergen);
      } else {
        _selectedAllergens.add(allergen);
      }
    });
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
                  final isSelected = _selectedAllergens.contains(allergen);
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: () => _toggleAllergen(allergen),
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
                                allergen,
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
                                allergen,
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
