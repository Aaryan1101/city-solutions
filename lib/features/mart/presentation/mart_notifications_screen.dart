import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../data/mart_api_client.dart';
import '../domain/mart_models.dart';
import 'mart_order_history_screen.dart';

class MartNotificationsScreen extends StatefulWidget {
  const MartNotificationsScreen({super.key, required this.guestId});

  final String guestId;

  @override
  State<MartNotificationsScreen> createState() =>
      _MartNotificationsScreenState();
}

class _MartNotificationsScreenState extends State<MartNotificationsScreen> {
  final _api = MartApiClient();
  late Future<List<MartNotification>> _future;

  @override
  void initState() {
    super.initState();
    _future = _api.fetchNotifications(guestId: widget.guestId);
    _api.markNotificationsRead(guestId: widget.guestId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Notifications')),
      body: FutureBuilder<List<MartNotification>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return AppErrorState(
              title: 'Notifications could not load',
              detail: AppErrorState.userMessage(snapshot.error),
              onRetry: () => setState(
                () =>
                    _future = _api.fetchNotifications(guestId: widget.guestId),
              ),
            );
          }

          final notifications = snapshot.data ?? const <MartNotification>[];
          if (notifications.isEmpty) {
            return const Center(
              child: Text(
                'No notifications yet.',
                style: TextStyle(fontWeight: FontWeight.w800),
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () async {
              setState(() {
                _future = _api.fetchNotifications(guestId: widget.guestId);
              });
              await _future;
            },
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: notifications.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, index) {
                final notification = notifications[index];
                return _NotificationTile(
                  notification: notification,
                  guestId: widget.guestId,
                );
              },
            ),
          );
        },
      ),
    );
  }
}

class _NotificationTile extends StatelessWidget {
  const _NotificationTile({
    required this.notification,
    required this.guestId,
  });

  final MartNotification notification;
  final String guestId;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: notification.orderId > 0
          ? () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => MartOrderDetailsScreen(
                    orderId: notification.orderId,
                    guestId: guestId,
                  ),
                ),
              )
          : null,
      borderRadius: BorderRadius.circular(8),
      child: Ink(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
              color: notification.isRead
                  ? Theme.of(context).dividerColor
                  : AppTheme.primary.withValues(alpha: 0.45)),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(
              notification.isRead
                  ? Icons.notifications_none_rounded
                  : Icons.notifications_active_outlined,
              color: AppTheme.primary,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    notification.title,
                    style: const TextStyle(fontWeight: FontWeight.w900),
                  ),
                  if (notification.message.isNotEmpty) ...[
                    const SizedBox(height: 5),
                    Text(
                      notification.message,
                      style: const TextStyle(height: 1.35),
                    ),
                  ],
                  const SizedBox(height: 8),
                  Text(
                    notification.createdAt,
                    style: const TextStyle(fontSize: 12),
                  ),
                ],
              ),
            ),
            if (notification.orderId > 0)
              const Icon(
                Icons.chevron_right_rounded,
              ),
          ],
        ),
      ),
    );
  }
}
