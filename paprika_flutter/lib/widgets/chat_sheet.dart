import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/constants/app_colors.dart';
import '../data/models/chat_model.dart';
import '../providers/providers.dart';
import '../services/api_service.dart';

Future<void> showChatSheet(
  BuildContext context, {
  int? branchId,
}) {
  FocusManager.instance.primaryFocus?.unfocus();

  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    backgroundColor: Colors.transparent,
    builder: (_) => ChatSheet(branchId: branchId),
  );
}

class ChatSheet extends ConsumerStatefulWidget {
  const ChatSheet({super.key, this.branchId});

  final int? branchId;

  @override
  ConsumerState<ChatSheet> createState() => _ChatSheetState();
}

class _ChatSheetState extends ConsumerState<ChatSheet> {
  final _nameCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _messageCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();

  ChatSessionResponse? _session;
  Map<String, String> _fieldErrors = const {};
  String? _error;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadSavedSession());
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    _emailCtrl.dispose();
    _messageCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadSavedSession() async {
    final sessionId = ref.read(chatRepositoryProvider).savedSessionId;
    if (sessionId == null || sessionId.isEmpty) return;

    setState(() => _loading = true);
    try {
      final session = await ref.read(chatRepositoryProvider).messages(sessionId);
      if (!mounted) return;
      setState(() {
        _session = session;
        _error = null;
      });
      _scrollToBottom();
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _startChat() async {
    final request = StartChatRequest(
      visitorName: _nameCtrl.text,
      phone: _phoneCtrl.text,
      email: _emailCtrl.text,
      message: _messageCtrl.text,
      branchId: widget.branchId,
    );
    final errors = request.validate();
    if (errors.isNotEmpty) {
      setState(() => _fieldErrors = errors);
      return;
    }

    await _runChatAction(() async {
      final session = await ref.read(chatRepositoryProvider).start(request);
      setState(() {
        _session = session;
        _fieldErrors = const {};
        _messageCtrl.clear();
      });
    });
  }

  Future<void> _sendMessage() async {
    final sessionId = _session?.sessionId;
    final message = _messageCtrl.text.trim();
    if (sessionId == null || sessionId.isEmpty) return;
    if (message.isEmpty) {
      setState(() {
        _fieldErrors = const {'message': 'Vui lòng nhập nội dung cần gửi.'};
      });
      return;
    }

    await _runChatAction(() async {
      final session = await ref
          .read(chatRepositoryProvider)
          .send(sessionId: sessionId, message: message);
      setState(() {
        _session = session;
        _fieldErrors = const {};
        _messageCtrl.clear();
      });
    });
  }

  Future<void> _refreshMessages() async {
    final sessionId = _session?.sessionId;
    if (sessionId == null || sessionId.isEmpty) return;
    await _runChatAction(() async {
      final session = await ref.read(chatRepositoryProvider).messages(sessionId);
      setState(() => _session = session);
    }, dismissKeyboard: false);
  }

  Future<void> _runChatAction(
    Future<void> Function() action, {
    bool dismissKeyboard = true,
  }) async {
    if (dismissKeyboard) FocusManager.instance.primaryFocus?.unfocus();
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await action();
      _scrollToBottom();
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scrollCtrl.hasClients) return;
      _scrollCtrl.animateTo(
        _scrollCtrl.position.maxScrollExtent,
        duration: const Duration(milliseconds: 240),
        curve: Curves.easeOut,
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.viewInsetsOf(context).bottom;
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: () => FocusManager.instance.primaryFocus?.unfocus(),
      child: AnimatedPadding(
        duration: const Duration(milliseconds: 180),
        curve: Curves.easeOut,
        padding: EdgeInsets.only(bottom: bottomInset),
        child: Align(
          alignment: Alignment.bottomCenter,
          child: ConstrainedBox(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.sizeOf(context).height * 0.88,
            ),
            child: Material(
              color: AppColors.cream,
              borderRadius:
                  const BorderRadius.vertical(top: Radius.circular(24)),
              clipBehavior: Clip.antiAlias,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const _ChatHandle(),
                  _ChatHeader(
                    loading: _loading,
                    onRefresh: _session == null ? null : _refreshMessages,
                  ),
                  if (_error != null) _ErrorBanner(message: _error!),
                  Flexible(
                    child: _session == null
                        ? _StartChatForm(
                            nameCtrl: _nameCtrl,
                            phoneCtrl: _phoneCtrl,
                            emailCtrl: _emailCtrl,
                            messageCtrl: _messageCtrl,
                            fieldErrors: _fieldErrors,
                            loading: _loading,
                            onSubmit: _startChat,
                          )
                        : _Conversation(
                            messages: _session!.messages,
                            scrollCtrl: _scrollCtrl,
                          ),
                  ),
                  if (_session != null)
                    _MessageComposer(
                      controller: _messageCtrl,
                      errorText: _fieldErrors['message'],
                      loading: _loading,
                      onSend: _sendMessage,
                    ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _ChatHandle extends StatelessWidget {
  const _ChatHandle();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Container(
        width: 46,
        height: 5,
        margin: const EdgeInsets.only(top: 10, bottom: 10),
        decoration: BoxDecoration(
          color: AppColors.border,
          borderRadius: BorderRadius.circular(999),
        ),
      ),
    );
  }
}

class _ChatHeader extends StatelessWidget {
  const _ChatHeader({required this.loading, required this.onRefresh});

  final bool loading;
  final VoidCallback? onRefresh;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(18, 0, 10, 12),
      child: Row(
        children: [
          const Icon(Icons.chat_bubble_outline, color: AppColors.primary),
          const SizedBox(width: 8),
          const Expanded(
            child: Text(
              'Chat với Paprika',
              style: TextStyle(
                color: AppColors.textPrimary,
                fontSize: 20,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
          if (loading)
            const SizedBox(
              width: 18,
              height: 18,
              child: CircularProgressIndicator(strokeWidth: 2),
            )
          else
            IconButton(
              tooltip: 'Tải lại',
              onPressed: onRefresh,
              icon: const Icon(Icons.refresh, color: AppColors.textMuted),
            ),
          IconButton(
            tooltip: 'Đóng',
            onPressed: () => Navigator.of(context).pop(),
            icon: const Icon(Icons.close, color: AppColors.textMuted),
          ),
        ],
      ),
    );
  }
}

class _StartChatForm extends StatelessWidget {
  const _StartChatForm({
    required this.nameCtrl,
    required this.phoneCtrl,
    required this.emailCtrl,
    required this.messageCtrl,
    required this.fieldErrors,
    required this.loading,
    required this.onSubmit,
  });

  final TextEditingController nameCtrl;
  final TextEditingController phoneCtrl;
  final TextEditingController emailCtrl;
  final TextEditingController messageCtrl;
  final Map<String, String> fieldErrors;
  final bool loading;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(18, 0, 18, 18),
      shrinkWrap: true,
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      children: [
        const Text(
          'Gửi tin nhắn cho quán, admin sẽ trả lời trong trang quản trị.',
          style: TextStyle(
            color: AppColors.textMuted,
            fontSize: 13,
            height: 1.45,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 14),
        TextField(
          controller: nameCtrl,
          textInputAction: TextInputAction.next,
          decoration: const InputDecoration(
            labelText: 'Tên của bạn',
            prefixIcon: Icon(Icons.person_outline),
          ),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: phoneCtrl,
          keyboardType: TextInputType.phone,
          textInputAction: TextInputAction.next,
          decoration: InputDecoration(
            labelText: 'Số điện thoại',
            prefixIcon: const Icon(Icons.phone_outlined),
            errorText: fieldErrors['phone'],
          ),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: emailCtrl,
          keyboardType: TextInputType.emailAddress,
          textInputAction: TextInputAction.next,
          decoration: InputDecoration(
            labelText: 'Email',
            prefixIcon: const Icon(Icons.email_outlined),
            errorText: fieldErrors['email'],
          ),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: messageCtrl,
          minLines: 3,
          maxLines: 5,
          textInputAction: TextInputAction.newline,
          decoration: InputDecoration(
            labelText: 'Bạn cần hỗ trợ gì?',
            prefixIcon: const Icon(Icons.chat_outlined),
            errorText: fieldErrors['message'],
          ),
        ),
        const SizedBox(height: 14),
        FilledButton.icon(
          onPressed: loading ? null : onSubmit,
          style: FilledButton.styleFrom(
            backgroundColor: AppColors.primary,
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 13),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
          icon: const Icon(Icons.send, size: 18),
          label: Text(
            loading ? 'Đang gửi...' : 'Bắt đầu chat',
            style: const TextStyle(fontWeight: FontWeight.w900),
          ),
        ),
      ],
    );
  }
}

class _Conversation extends StatelessWidget {
  const _Conversation({required this.messages, required this.scrollCtrl});

  final List<ChatMessage> messages;
  final ScrollController scrollCtrl;

  @override
  Widget build(BuildContext context) {
    if (messages.isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(24),
          child: Text('Chưa có tin nhắn.'),
        ),
      );
    }

    return ListView.separated(
      controller: scrollCtrl,
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
      itemCount: messages.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, index) => _MessageBubble(message: messages[index]),
    );
  }
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({required this.message});

  final ChatMessage message;

  @override
  Widget build(BuildContext context) {
    final isMine = message.isVisitor;
    return Align(
      alignment: isMine ? Alignment.centerRight : Alignment.centerLeft,
      child: ConstrainedBox(
        constraints: BoxConstraints(
          maxWidth: MediaQuery.sizeOf(context).width * 0.76,
        ),
        child: DecoratedBox(
          decoration: BoxDecoration(
            color: isMine ? AppColors.primary : AppColors.surface,
            borderRadius: BorderRadius.circular(14),
            border: isMine ? null : Border.all(color: AppColors.border),
          ),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(12, 9, 12, 9),
            child: Column(
              crossAxisAlignment:
                  isMine ? CrossAxisAlignment.end : CrossAxisAlignment.start,
              children: [
                Text(
                  message.message,
                  style: TextStyle(
                    color: isMine ? Colors.white : AppColors.textPrimary,
                    fontSize: 13,
                    height: 1.35,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (message.createdAt?.isNotEmpty == true) ...[
                  const SizedBox(height: 4),
                  Text(
                    _formatTime(message.createdAt!),
                    style: TextStyle(
                      color: isMine
                          ? Colors.white.withValues(alpha: 0.72)
                          : AppColors.textMuted,
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _MessageComposer extends StatelessWidget {
  const _MessageComposer({
    required this.controller,
    required this.errorText,
    required this.loading,
    required this.onSend,
  });

  final TextEditingController controller;
  final String? errorText;
  final bool loading;
  final VoidCallback onSend;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        14,
        10,
        14,
        MediaQuery.paddingOf(context).bottom + 12,
      ),
      decoration: const BoxDecoration(
        color: AppColors.surface,
        border: Border(top: BorderSide(color: AppColors.border)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Expanded(
            child: TextField(
              controller: controller,
              minLines: 1,
              maxLines: 4,
              decoration: InputDecoration(
                hintText: 'Nhập tin nhắn...',
                errorText: errorText,
                isDense: true,
              ),
            ),
          ),
          const SizedBox(width: 8),
          IconButton.filled(
            onPressed: loading ? null : onSend,
            style: IconButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
            ),
            icon: const Icon(Icons.send, size: 18),
          ),
        ],
      ),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: AppColors.accent.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.accent.withValues(alpha: 0.25)),
      ),
      child: Text(
        message,
        style: const TextStyle(
          color: AppColors.accent,
          fontSize: 12,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

String _formatTime(String raw) {
  final parsed = DateTime.tryParse(raw);
  if (parsed == null) return raw;
  final local = parsed.toLocal();
  final hour = local.hour.toString().padLeft(2, '0');
  final minute = local.minute.toString().padLeft(2, '0');
  return '$hour:$minute';
}
