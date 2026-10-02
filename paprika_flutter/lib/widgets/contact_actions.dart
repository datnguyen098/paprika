import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../data/models/branch_model.dart';
import 'chat_sheet.dart';

Future<void> launchPhoneCall(BuildContext context, String? phone) async {
  final messenger = ScaffoldMessenger.of(context);
  final normalized = phone?.replaceAll(RegExp(r'[^0-9+]'), '') ?? '';
  if (normalized.isEmpty) {
    messenger.showSnackBar(
      const SnackBar(content: Text('Chưa có số điện thoại để gọi.')),
    );
    return;
  }

  final ok = await _safeLaunchUrl(
    context,
    Uri(scheme: 'tel', path: normalized),
  );
  if (!context.mounted || ok != false) return;
  if (ok == false) {
    messenger.showSnackBar(
      SnackBar(content: Text('Không mở được cuộc gọi tới $phone.')),
    );
  }
}

Future<void> launchBranchMap(BuildContext context, Branch branch) async {
  await _launchBranchUri(
    context,
    branch: branch,
    uri: branch.mapSearchUri,
    failureMessage: 'Không mở được bản đồ cho ${branch.name}.',
  );
}

Future<void> launchBranchDirections(BuildContext context, Branch branch) async {
  await _launchBranchUri(
    context,
    branch: branch,
    uri: branch.mapDirectionsUri,
    failureMessage: 'Không mở được chỉ đường tới ${branch.name}.',
  );
}

Future<void> _launchBranchUri(
  BuildContext context, {
  required Branch branch,
  required Uri uri,
  required String failureMessage,
}) async {
  final messenger = ScaffoldMessenger.of(context);
  if (!branch.hasMapLocation) {
    messenger.showSnackBar(
      const SnackBar(content: Text('Chi nhánh chưa có địa chỉ để mở bản đồ.')),
    );
    return;
  }

  final ok = await _safeLaunchUrl(
    context,
    uri,
    mode: LaunchMode.externalApplication,
  );
  if (!context.mounted || ok != false) return;
  if (ok == false) {
    messenger.showSnackBar(SnackBar(content: Text(failureMessage)));
  }
}

Future<bool?> _safeLaunchUrl(
  BuildContext context,
  Uri uri, {
  LaunchMode mode = LaunchMode.platformDefault,
}) async {
  try {
    return await launchUrl(uri, mode: mode);
  } on PlatformException catch (error) {
    if (!context.mounted) return null;
    final channelMissing = error.code == 'channel-error';
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          channelMissing
              ? 'Cần tắt app và chạy lại để kích hoạt mở bản đồ/gọi điện.'
              : 'Không mở được liên kết: ${error.message ?? error.code}',
        ),
      ),
    );
    return null;
  }
}

Future<void> openChatSupport(
  BuildContext context, {
  int? branchId,
}) {
  return showChatSheet(context, branchId: branchId);
}
