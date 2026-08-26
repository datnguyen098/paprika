import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:paprika_mobile/app/app.dart';
import 'package:paprika_mobile/providers/providers.dart';

void main() {
  testWidgets('Paprika app boots without errors', (WidgetTester tester) async {
    SharedPreferences.setMockInitialValues({});
    final prefs = await SharedPreferences.getInstance();

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          sharedPrefsProvider.overrideWithValue(prefs),
        ],
        child: const PaprikaApp(),
      ),
    );

    await tester.pumpAndSettle();
  });
}
