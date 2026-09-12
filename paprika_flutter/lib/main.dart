import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'app/app.dart';
import 'core/config/env.dart';
import 'core/constants/app_colors.dart';
import 'providers/providers.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Khởi tạo Env trước khi runApp để ApiConstants.baseUrl / webOrigin
  // đã sẵn sàng khi ApiService được tạo (lazy qua Riverpod).
  await Env.init();

  // Lock orientation dọc - mobile restaurant app
  await SystemChrome.setPreferredOrientations(<DeviceOrientation>[
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  // Status bar style: trắng trên nền xanh lá header
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      statusBarBrightness: Brightness.dark,
      systemNavigationBarColor: AppColors.primaryStrong,
      systemNavigationBarIconBrightness: Brightness.light,
    ),
  );

  // Init SharedPreferences rồi override provider
  final prefs = await SharedPreferences.getInstance();

  runApp(
    ProviderScope(
      overrides: <Override>[
        sharedPrefsProvider.overrideWithValue(prefs),
      ],
      child: const PaprikaApp(),
    ),
  );
}