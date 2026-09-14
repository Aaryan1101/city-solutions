import 'dart:async';

import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../data/mart_api_client.dart';
import '../domain/mart_models.dart';

class MartSupportScreen extends StatefulWidget {
  const MartSupportScreen({
    super.key,
    required this.session,
    this.showAppBar = true,
    this.title = 'Support',
  });

  final MartCustomerSession session;
  final bool showAppBar;
  final String title;

  @override
  State<MartSupportScreen> createState() => _MartSupportScreenState();
}

class _MartSupportScreenState extends State<MartSupportScreen> {
  final _api = MartApiClient();
  final _subjectController = TextEditingController();
  final _messageController = TextEditingController();
  final _attachmentController = TextEditingController();
  late Future<List<MartSupportThread>> _future;
  bool _sending = false;

  @override
  void initState() {
    super.initState();
    _future = _api.fetchSupportThreads(guestId: widget.session.guestId);
  }

  @override
  void dispose() {
    _subjectController.dispose();
    _messageController.dispose();
    _attachmentController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: widget.showAppBar ? AppBar(title: Text(widget.title)) : null,
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Colors.white, Color(0xFFEFF6FF)],
              ),
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: Theme.of(context).dividerColor),
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primary.withValues(alpha: .07),
                  blurRadius: 24,
                  offset: const Offset(0, 14),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      width: 46,
                      height: 46,
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withValues(alpha: .1),
                        borderRadius: BorderRadius.circular(16),
                      ),
                      child: const Icon(Icons.support_agent_rounded,
                          color: AppTheme.primary),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Need help?',
                              style: TextStyle(
                                  fontSize: 18, fontWeight: FontWeight.w900)),
                          SizedBox(height: 2),
                          Text('Create a request and track replies here.',
                              style: TextStyle(
                                  fontSize: 12, fontWeight: FontWeight.w700)),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _subjectController,
                  decoration: const InputDecoration(
                    labelText: 'Subject',
                    prefixIcon: Icon(Icons.subject_rounded),
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _messageController,
                  maxLines: 4,
                  decoration: const InputDecoration(
                    labelText: 'Message',
                    prefixIcon: Icon(Icons.chat_bubble_outline_rounded),
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _attachmentController,
                  decoration: const InputDecoration(
                    labelText: 'Attachment URL optional',
                    prefixIcon: Icon(Icons.attach_file_rounded),
                  ),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: FilledButton.icon(
                    onPressed: _sending ? null : _send,
                    icon: const Icon(Icons.send_outlined),
                    label:
                        Text(_sending ? 'Sending...' : 'Send Support Request'),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 18),
          const Text('Requests',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
          const SizedBox(height: 10),
          FutureBuilder<List<MartSupportThread>>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const AppSkeletonList(cardCount: 3);
              }
              final threads = snapshot.data ?? const <MartSupportThread>[];
              if (threads.isEmpty) {
                return const _SupportEmptyState(
                  icon: Icons.support_agent_outlined,
                  title: 'No support requests yet',
                  message:
                      'Create a request above when you need help with an order.',
                );
              }
              return Column(
                children: threads
                    .map((thread) => InkWell(
                          onTap: () async {
                            await Navigator.of(context).push(
                              MaterialPageRoute<void>(
                                builder: (_) => MartSupportThreadScreen(
                                  session: widget.session,
                                  thread: thread,
                                ),
                              ),
                            );
                            if (!mounted) return;
                            setState(() {
                              _future = _api.fetchSupportThreads(
                                guestId: widget.session.guestId,
                              );
                            });
                          },
                          borderRadius: BorderRadius.circular(18),
                          child: Container(
                            width: double.infinity,
                            margin: const EdgeInsets.only(bottom: 10),
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: Theme.of(context).colorScheme.surface,
                              borderRadius: BorderRadius.circular(18),
                              border: Border.all(
                                  color: Theme.of(context).dividerColor),
                              boxShadow: [
                                BoxShadow(
                                  color:
                                      AppTheme.primary.withValues(alpha: .04),
                                  blurRadius: 16,
                                  offset: const Offset(0, 8),
                                ),
                              ],
                            ),
                            child: Row(
                              children: [
                                Container(
                                  width: 42,
                                  height: 42,
                                  decoration: BoxDecoration(
                                    color:
                                        AppTheme.primary.withValues(alpha: .1),
                                    borderRadius: BorderRadius.circular(14),
                                  ),
                                  child: const Icon(
                                      Icons.confirmation_number_outlined,
                                      color: AppTheme.primary),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(thread.subject,
                                          style: const TextStyle(
                                              fontWeight: FontWeight.w900)),
                                      const SizedBox(height: 4),
                                      Text(
                                          '${thread.status} - ${thread.createdAt}',
                                          style: const TextStyle(fontSize: 12)),
                                    ],
                                  ),
                                ),
                                if (thread.unreadCount > 0)
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: AppTheme.primary,
                                      borderRadius: BorderRadius.circular(999),
                                    ),
                                    child: Text(
                                      thread.unreadCount.toString(),
                                      style: const TextStyle(
                                          color: Colors.white,
                                          fontWeight: FontWeight.w900),
                                    ),
                                  ),
                                const SizedBox(width: 8),
                                const Icon(
                                  Icons.chevron_right_rounded,
                                ),
                              ],
                            ),
                          ),
                        ))
                    .toList(),
              );
            },
          ),
        ],
      ),
    );
  }

  Future<void> _send() async {
    final subject = _subjectController.text.trim();
    final message = _messageController.text.trim();
    final attachmentUrl = _attachmentController.text.trim();
    if (subject.isEmpty || message.isEmpty) return;
    setState(() => _sending = true);
    try {
      await _api.createSupportThread(
        guestId: widget.session.guestId,
        senderName: widget.session.name,
        subject: subject,
        message: message,
        attachmentUrl: attachmentUrl,
      );
      _subjectController.clear();
      _messageController.clear();
      _attachmentController.clear();
      if (!mounted) return;
      setState(() {
        _future = _api.fetchSupportThreads(guestId: widget.session.guestId);
      });
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }
}

class MartSupportThreadScreen extends StatefulWidget {
  const MartSupportThreadScreen({
    super.key,
    required this.session,
    required this.thread,
  });

  final MartCustomerSession session;
  final MartSupportThread thread;

  @override
  State<MartSupportThreadScreen> createState() =>
      _MartSupportThreadScreenState();
}

class _MartSupportThreadScreenState extends State<MartSupportThreadScreen> {
  final _api = MartApiClient();
  final _messageController = TextEditingController();
  final _attachmentController = TextEditingController();
  Timer? _pollTimer;
  late Future<MartSupportThreadDetails> _future;
  bool _sending = false;
  int _pollSeconds = 15;

  @override
  void initState() {
    super.initState();
    _future = _load();
    _configurePolling();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _messageController.dispose();
    _attachmentController.dispose();
    super.dispose();
  }

  Future<MartSupportThreadDetails> _load() {
    return _api.fetchSupportThread(
      guestId: widget.session.guestId,
      threadId: widget.thread.id,
    );
  }

  Future<void> _configurePolling() async {
    try {
      final config = await _api.fetchConfig();
      _pollSeconds = config.supportPollIntervalSeconds.clamp(5, 120);
    } catch (_) {
      _pollSeconds = 15;
    }
    if (!mounted) return;
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(Duration(seconds: _pollSeconds), (_) {
      if (!mounted || _sending) return;
      setState(() => _future = _load());
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: Text(widget.thread.subject)),
      body: FutureBuilder<MartSupportThreadDetails>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting &&
              !snapshot.hasData) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return AppErrorState(
              title: 'Could not load support',
              detail: AppErrorState.userMessage(snapshot.error),
              onRetry: () => setState(() => _future = _load()),
            );
          }
          final details = snapshot.data;
          final messages = details?.messages ?? const <MartSupportMessage>[];
          return RefreshIndicator(
            onRefresh: () async {
              setState(() => _future = _load());
              await _future;
            },
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 180),
              children: [
                Row(
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withValues(alpha: .1),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: const Icon(Icons.confirmation_number_outlined,
                          color: AppTheme.primary),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        details?.thread.status ?? widget.thread.status,
                        style: const TextStyle(fontWeight: FontWeight.w900),
                      ),
                    ),
                    IconButton(
                      tooltip: 'Refresh',
                      onPressed: () => setState(() => _future = _load()),
                      icon: const Icon(Icons.refresh_rounded),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                if (messages.isEmpty)
                  const _SupportEmptyState(
                    icon: Icons.chat_bubble_outline_rounded,
                    title: 'No messages yet',
                    message: 'Send a reply below to continue this request.',
                  )
                else
                  for (final message in messages) _MessageBubble(message),
              ],
            ),
          );
        },
      ),
      bottomSheet: SafeArea(
        child: Container(
          padding: const EdgeInsets.fromLTRB(16, 10, 16, 16),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            border:
                Border(top: BorderSide(color: Theme.of(context).dividerColor)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: _messageController,
                minLines: 1,
                maxLines: 4,
                decoration: const InputDecoration(
                  labelText: 'Reply',
                  prefixIcon: Icon(Icons.chat_bubble_outline_rounded),
                ),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: _attachmentController,
                decoration: const InputDecoration(
                  labelText: 'Attachment URL optional',
                  prefixIcon: Icon(Icons.attach_file_rounded),
                ),
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: FilledButton.icon(
                  onPressed: _sending ? null : _reply,
                  icon: const Icon(Icons.send_outlined),
                  label: Text(_sending ? 'Sending...' : 'Send Reply'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _reply() async {
    final message = _messageController.text.trim();
    final attachmentUrl = _attachmentController.text.trim();
    if (message.isEmpty) return;
    setState(() => _sending = true);
    try {
      final details = await _api.replySupportThread(
        guestId: widget.session.guestId,
        threadId: widget.thread.id,
        senderName: widget.session.name,
        message: message,
        attachmentUrl: attachmentUrl,
      );
      _messageController.clear();
      _attachmentController.clear();
      if (!mounted) return;
      setState(() => _future = Future.value(details));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }
}

class _SupportEmptyState extends StatelessWidget {
  const _SupportEmptyState({
    required this.icon,
    required this.title,
    required this.message,
  });

  final IconData icon;
  final String title;
  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .05),
            blurRadius: 20,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 68,
            height: 68,
            decoration: BoxDecoration(
              color: AppTheme.primary.withValues(alpha: .09),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: AppTheme.primary, size: 34),
          ),
          const SizedBox(height: 14),
          Text(
            title,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            message,
            textAlign: TextAlign.center,
            style: const TextStyle(height: 1.35),
          ),
        ],
      ),
    );
  }
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble(this.message);

  final MartSupportMessage message;

  @override
  Widget build(BuildContext context) {
    final mine = message.senderType == 'customer';
    return Align(
      alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        constraints:
            BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * .78),
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(13),
        decoration: BoxDecoration(
          color: mine ? AppTheme.primary : Colors.white,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(18),
            topRight: const Radius.circular(18),
            bottomLeft: Radius.circular(mine ? 18 : 4),
            bottomRight: Radius.circular(mine ? 4 : 18),
          ),
          border: Border.all(color: mine ? AppTheme.primary : Theme.of(context).dividerColor),
          boxShadow: [
            BoxShadow(
              color: (mine
                      ? AppTheme.primary
                      : Theme.of(context).colorScheme.onSurface)
                  .withValues(alpha: mine ? .1 : .04),
              blurRadius: 14,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              message.senderName.isEmpty
                  ? message.senderType
                  : '${message.senderName} (${message.senderType})',
              style: TextStyle(
                color: mine
                    ? Colors.white70
                    : Theme.of(context).colorScheme.onSurfaceVariant,
                fontSize: 12,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              message.message,
              style: TextStyle(
                color: mine
                    ? Colors.white
                    : Theme.of(context).colorScheme.onSurface,
                fontWeight: FontWeight.w700,
                height: 1.35,
              ),
            ),
            if (message.attachmentUrl.isNotEmpty) ...[
              const SizedBox(height: 8),
              SelectableText(
                message.attachmentUrl,
                style: TextStyle(
                  color: mine ? Colors.white : AppTheme.primary,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
            const SizedBox(height: 6),
            Text(
              message.createdAt,
              style: TextStyle(
                color: mine
                    ? Colors.white70
                    : Theme.of(context).colorScheme.onSurfaceVariant,
                fontSize: 11,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
