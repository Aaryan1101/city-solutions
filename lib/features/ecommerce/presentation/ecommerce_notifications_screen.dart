import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../data/ecommerce_api_client.dart';
import '../domain/ecommerce_models.dart';
import 'ecommerce_order_history_screen.dart';

class EcommerceNotificationsScreen extends StatefulWidget {
  const EcommerceNotificationsScreen({
    super.key,
    required this.guestId,
    this.apiBaseUrl,
  });

  final String guestId;
  final String? apiBaseUrl;

  @override
  State<EcommerceNotificationsScreen> createState() =>
      _EcommerceNotificationsScreenState();
}

class _EcommerceNotificationsScreenState
    extends State<EcommerceNotificationsScreen> {
  late final EcommerceApiClient _api;
  late Future<List<EcommerceNotification>> _future;

  @override
  void initState() {
    super.initState();
    _api = EcommerceApiClient(baseUrl: widget.apiBaseUrl);
    _future = _api.fetchNotifications(guestId: widget.guestId);
    _api.markNotificationsRead(guestId: widget.guestId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Notifications')),
      body: FutureBuilder<List<EcommerceNotification>>(
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

          final notifications =
              snapshot.data ?? const <EcommerceNotification>[];
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
                  apiBaseUrl: widget.apiBaseUrl,
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
    required this.apiBaseUrl,
  });

  final EcommerceNotification notification;
  final String guestId;
  final String? apiBaseUrl;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: notification.orderId > 0
          ? () => Navigator.of(context).push(
                MaterialPageRoute<void>(
                  builder: (_) => EcommerceOrderDetailsScreen(
                    orderId: notification.orderId,
                    guestId: guestId,
                    apiBaseUrl: apiBaseUrl,
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
