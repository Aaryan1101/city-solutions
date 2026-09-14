import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/activity_empty_state.dart';
import '../../../core/app_theme.dart';
import '../../complaint/complaint_screen.dart';
import '../data/ecommerce_api_client.dart';
import '../data/ecommerce_session_store.dart';
import '../domain/ecommerce_models.dart';

class EcommerceOrderHistoryScreen extends StatefulWidget {
  const EcommerceOrderHistoryScreen({
    super.key,
    required this.guestId,
    this.showAppBar = true,
    this.apiBaseUrl,
    this.title = 'My Orders',
    this.emptyTitle = 'No ecommerce orders yet.',
    this.emptyDetail = 'Placed orders will appear here with their live status.',
  });

  final String guestId;
  final bool showAppBar;
  final String? apiBaseUrl;
  final String title;
  final String emptyTitle;
  final String emptyDetail;

  @override
  State<EcommerceOrderHistoryScreen> createState() =>
      _EcommerceOrderHistoryScreenState();
}

class _EcommerceOrderHistoryScreenState
    extends State<EcommerceOrderHistoryScreen> {
  late final EcommerceApiClient _api;
  late Future<List<EcommerceOrder>> _future;

  @override
  void initState() {
    super.initState();
    _api = EcommerceApiClient(baseUrl: widget.apiBaseUrl);
    _future = _api.fetchOrders(guestId: widget.guestId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: widget.showAppBar ? AppBar(title: Text(widget.title)) : null,
      body: SafeArea(
        child: FutureBuilder<List<EcommerceOrder>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const AppSkeletonPage();
            }
            if (snapshot.hasError) {
              return _OrderMessage(
                icon: Icons.cloud_off_outlined,
                title: 'Orders are not available yet.',
                detail: AppErrorState.userMessage(snapshot.error),
                action: FilledButton(
                  onPressed: () => setState(() {
                    _future = _api.fetchOrders(guestId: widget.guestId);
                  }),
                  child: const Text('Retry'),
                ),
              );
            }

            final orders = snapshot.data ?? const <EcommerceOrder>[];
            if (orders.isEmpty) {
              return _OrderMessage(
                icon: Icons.receipt_long_outlined,
                title: widget.emptyTitle,
                detail: widget.emptyDetail,
              );
            }

            return RefreshIndicator(
              onRefresh: () async {
                setState(() {
                  _future = _api.fetchOrders(guestId: widget.guestId);
                });
                await _future;
              },
              child: ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: orders.length,
                separatorBuilder: (_, __) => const SizedBox(height: 10),
                itemBuilder: (context, index) {
                  final order = orders[index];
                  return _OrderTile(
                    order: order,
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => EcommerceOrderDetailsScreen(
                          orderId: order.id,
                          guestId: widget.guestId,
                          apiBaseUrl: widget.apiBaseUrl,
                        ),
                      ),
                    ),
                  );
                },
              ),
            );
          },
        ),
      ),
    );
  }
}

class EcommerceOrderDetailsScreen extends StatefulWidget {
  const EcommerceOrderDetailsScreen({
    super.key,
    required this.orderId,
    required this.guestId,
    this.apiBaseUrl,
  });

  final int orderId;
  final String guestId;
  final String? apiBaseUrl;

  @override
  State<EcommerceOrderDetailsScreen> createState() =>
      _EcommerceOrderDetailsScreenState();
}

class _EcommerceOrderDetailsScreenState
    extends State<EcommerceOrderDetailsScreen> {
  late final EcommerceApiClient _api;
  late Future<EcommerceOrderDetails> _future;
  bool _cancelling = false;
  bool _requestingRefund = false;

  @override
  void initState() {
    super.initState();
    _api = EcommerceApiClient(baseUrl: widget.apiBaseUrl);
    _future = _api.fetchOrderDetails(
      orderId: widget.orderId,
      guestId: widget.guestId,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Order Details')),
      body: FutureBuilder<EcommerceOrderDetails>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return _OrderMessage(
              icon: Icons.error_outline_rounded,
              title: 'Could not load this order.',
              detail: AppErrorState.userMessage(snapshot.error),
              action: FilledButton(
                onPressed: () => setState(() {
                  _future = _api.fetchOrderDetails(
                    orderId: widget.orderId,
                    guestId: widget.guestId,
                  );
                }),
                child: const Text('Retry'),
              ),
            );
          }

          final details = snapshot.data!;
          final order = details.order;
          final refundByItemId = {
            for (final refund in details.refunds)
              if (refund.orderItemId > 0) refund.orderItemId: refund,
          };
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _OrderSummaryCard(order: order),
              const SizedBox(height: 12),
              _OrderTimeline(
                  status: order.orderStatus, history: details.history),
              if (details.refunds.isNotEmpty) ...[
                const SizedBox(height: 12),
                _RefundsCard(refunds: details.refunds),
              ],
              const SizedBox(height: 12),
              _OrderIssueCard(onTap: () => _reportOrderIssue(order)),
              if (order.canCancel) ...[
                const SizedBox(height: 12),
                SizedBox(
                  height: 48,
                  child: OutlinedButton.icon(
                    onPressed: _cancelling ? null : () => _cancelOrder(order),
                    icon: _cancelling
                        ? const AppSkeletonBox(width: 18, height: 18, radius: 9)
                        : const Icon(Icons.cancel_outlined),
                    label: Text(_cancelling ? 'Cancelling...' : 'Cancel Order'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.red,
                      side: const BorderSide(color: Colors.red),
                    ),
                  ),
                ),
              ],
              const SizedBox(height: 14),
              const Text('Items',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
              const SizedBox(height: 10),
              ...details.items.map(
                (item) => _OrderItemRow(
                  item: item,
                  refund: refundByItemId[item.id],
                  canRequestRefund: order.orderStatus == 'delivered' ||
                      order.orderStatus == 'cancelled',
                  onRefund: () => _requestRefund(order, item),
                  requestingRefund: _requestingRefund,
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _cancelOrder(EcommerceOrder order) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel order?'),
        content: Text(
          'Order ${order.orderNumber} will be cancelled and the items will be returned to stock.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Keep Order'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            style: FilledButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Cancel Order'),
          ),
        ],
      ),
    );
    if (confirm != true) return;

    setState(() => _cancelling = true);
    try {
      final session = await EcommerceSessionStore().load();
      final message = await _api.cancelOrder(
        orderId: order.id,
        guestId: session.guestId,
        customerToken: session.token,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(message)));
      setState(() {
        _future = _api.fetchOrderDetails(
          orderId: widget.orderId,
          guestId: widget.guestId,
        );
      });
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _cancelling = false);
    }
  }

  Future<void> _reportOrderIssue(EcommerceOrder order) async {
    final session = await EcommerceSessionStore().load();
    if (!mounted) return;
    final moduleKey = _complaintModuleKey;
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => ComplaintScreen(
          initialModuleKey: moduleKey,
          initialReferenceId: order.id,
          initialCategory: 'Order Issue',
          initialLocation: order.address,
          initialName: order.customerName.trim().isEmpty
              ? session.name
              : order.customerName,
          initialPhone: order.customerPhone.trim().isEmpty
              ? session.phone
              : order.customerPhone,
          initialDescription:
              'Order ${order.orderNumber}: report missing, damaged, or wrong items.',
          requirePhoto: false,
          lockRouting: true,
        ),
      ),
    );
  }

  String get _complaintModuleKey {
    final base =
        (widget.apiBaseUrl ?? EcommerceApiConfig.baseUrl).toLowerCase();
    return base.contains('/medical') ? 'medical' : 'ecommerce';
  }

  Future<void> _requestRefund(
      EcommerceOrder order, EcommerceOrderItem item) async {
    final request = await showDialog<_RefundRequestDraft>(
      context: context,
      builder: (context) => const _RefundDialog(),
    );
    if (request == null) return;

    setState(() => _requestingRefund = true);
    try {
      final session = await EcommerceSessionStore().load();
      final message = await _api.requestRefund(
        orderId: order.id,
        orderItemId: item.id,
        guestId: session.guestId,
        reason: request.reason,
        note: request.note,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(message)));
      setState(() {
        _future = _api.fetchOrderDetails(
          orderId: widget.orderId,
          guestId: widget.guestId,
        );
      });
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _requestingRefund = false);
    }
  }
}

class EcommerceRefundsScreen extends StatefulWidget {
  const EcommerceRefundsScreen({
    super.key,
    required this.guestId,
    this.apiBaseUrl,
    this.showAppBar = true,
    this.title = 'Refunds',
    this.emptyTitle = 'No refunds yet.',
    this.emptyDetail = 'Submitted refund requests will appear here.',
  });

  final String guestId;
  final String? apiBaseUrl;
  final bool showAppBar;
  final String title;
  final String emptyTitle;
  final String emptyDetail;

  @override
  State<EcommerceRefundsScreen> createState() => _EcommerceRefundsScreenState();
}

class _EcommerceRefundsScreenState extends State<EcommerceRefundsScreen> {
  late final EcommerceApiClient _api;
  late Future<List<EcommerceRefundRequest>> _future;

  @override
  void initState() {
    super.initState();
    _api = EcommerceApiClient(baseUrl: widget.apiBaseUrl);
    _future = _api.fetchRefunds(guestId: widget.guestId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: widget.showAppBar ? AppBar(title: Text(widget.title)) : null,
      body: FutureBuilder<List<EcommerceRefundRequest>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return _OrderMessage(
              icon: Icons.assignment_return_outlined,
              title: 'Refunds are not available yet.',
              detail: AppErrorState.userMessage(snapshot.error),
              action: FilledButton(
                onPressed: () => setState(() {
                  _future = _api.fetchRefunds(guestId: widget.guestId);
                }),
                child: const Text('Retry'),
              ),
            );
          }

          final refunds = snapshot.data ?? const <EcommerceRefundRequest>[];
          if (refunds.isEmpty) {
            return _OrderMessage(
              icon: Icons.assignment_return_outlined,
              title: widget.emptyTitle,
              detail: widget.emptyDetail,
            );
          }

          return RefreshIndicator(
            onRefresh: () async {
              setState(() {
                _future = _api.fetchRefunds(guestId: widget.guestId);
              });
              await _future;
            },
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: refunds.length,
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemBuilder: (context, index) =>
                  _RefundTile(refund: refunds[index]),
            ),
          );
        },
      ),
    );
  }
}

class _OrderTimeline extends StatelessWidget {
  const _OrderTimeline({required this.status, required this.history});

  final String status;
  final List<EcommerceOrderHistoryItem> history;

  static const _steps = [
    'pending',
    'confirmed',
    'processing',
    'out_for_delivery',
    'delivered',
  ];

  @override
  Widget build(BuildContext context) {
    final orderHistory =
        history.where((row) => row.orderItemId == 0).toList(growable: false);
    if (orderHistory.isNotEmpty) {
      return Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: Theme.of(context).dividerColor),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Status History',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900)),
            const SizedBox(height: 12),
            ...List.generate(orderHistory.length, (index) {
              final row = orderHistory[index];
              final isLast = index == orderHistory.length - 1;
              return Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Column(
                    children: [
                      Container(
                        width: 22,
                        height: 22,
                        decoration: const BoxDecoration(
                          shape: BoxShape.circle,
                          color: AppTheme.primary,
                        ),
                        child: const Icon(Icons.check,
                            size: 14, color: Colors.white),
                      ),
                      if (!isLast)
                        Container(
                          width: 2,
                          height: 48,
                          color: Theme.of(context).dividerColor,
                        ),
                    ],
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 14),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(row.status.replaceAll('_', ' '),
                              style:
                                  const TextStyle(fontWeight: FontWeight.w900)),
                          if (row.note.isNotEmpty) ...[
                            const SizedBox(height: 3),
                            Text(row.note, style: const TextStyle()),
                          ],
                          const SizedBox(height: 3),
                          Text(
                            [
                              if (row.actorName.isNotEmpty) row.actorName,
                              if (row.createdAt.isNotEmpty) row.createdAt,
                            ].join(' - '),
                            style: const TextStyle(fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              );
            }),
          ],
        ),
      );
    }

    if (status == 'cancelled') {
      return Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: Theme.of(context).dividerColor),
        ),
        child: const Row(
          children: [
            Icon(Icons.cancel_outlined, color: Colors.red),
            SizedBox(width: 10),
            Expanded(
              child: Text('This order has been cancelled.',
                  style: TextStyle(fontWeight: FontWeight.w900)),
            ),
          ],
        ),
      );
    }

    final activeIndex = _steps.indexOf(status);
    final progressIndex = activeIndex < 0 ? 0 : activeIndex;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Tracking',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w900)),
          const SizedBox(height: 12),
          ...List.generate(_steps.length, (index) {
            final step = _steps[index];
            final reached = index <= progressIndex;
            final isLast = index == _steps.length - 1;
            return Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Column(
                  children: [
                    Container(
                      width: 22,
                      height: 22,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: reached ? AppTheme.primary : Theme.of(context).dividerColor,
                      ),
                      child: reached
                          ? const Icon(Icons.check,
                              size: 14, color: Colors.white)
                          : null,
                    ),
                    if (!isLast)
                      Container(
                        width: 2,
                        height: 28,
                        color: reached ? AppTheme.primary : Theme.of(context).dividerColor,
                      ),
                  ],
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      step.replaceAll('_', ' '),
                      style: TextStyle(
                        color: reached
                            ? Theme.of(context).colorScheme.onSurface
                            : Theme.of(context).colorScheme.onSurfaceVariant,
                        fontWeight: reached ? FontWeight.w900 : FontWeight.w700,
                      ),
                    ),
                  ),
                ),
              ],
            );
          }),
        ],
      ),
    );
  }
}

class _OrderTile extends StatelessWidget {
  const _OrderTile({required this.order, required this.onTap});

  final EcommerceOrder order;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              Container(
                width: 46,
                height: 46,
                decoration: BoxDecoration(
                  color: AppTheme.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.receipt_long_outlined,
                    color: AppTheme.primary),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(order.orderNumber,
                        style: const TextStyle(fontWeight: FontWeight.w900)),
                    const SizedBox(height: 3),
                    Text(order.createdAt, style: const TextStyle(fontSize: 12)),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text('₹${order.orderAmount.toStringAsFixed(2)}',
                      style: const TextStyle(fontWeight: FontWeight.w900)),
                  const SizedBox(height: 4),
                  _StatusPill(status: order.orderStatus),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _OrderSummaryCard extends StatelessWidget {
  const _OrderSummaryCard({required this.order});

  final EcommerceOrder order;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(order.orderNumber,
                    style: const TextStyle(
                        fontSize: 18, fontWeight: FontWeight.w900)),
              ),
              _StatusPill(status: order.orderStatus),
            ],
          ),
          const SizedBox(height: 12),
          _InfoLine(
              label: 'Amount',
              value: '₹${order.orderAmount.toStringAsFixed(2)}'),
          if (order.couponDiscount > 0)
            _InfoLine(
              label: 'Coupon',
              value:
                  '${order.couponCode} saved ₹${order.couponDiscount.toStringAsFixed(2)}',
            ),
          if (order.taxTotal > 0)
            _InfoLine(
                label: 'Tax', value: '₹${order.taxTotal.toStringAsFixed(2)}'),
          if (order.shippingMethodName.isNotEmpty || order.shippingCost > 0)
            _InfoLine(
              label: 'Shipping',
              value: [
                order.shippingMethodName,
                '₹${order.shippingCost.toStringAsFixed(2)}',
                order.expectedDelivery,
              ].where((part) => part.trim().isNotEmpty).join(' / '),
            ),
          _InfoLine(
              label: 'Payment',
              value: '${order.paymentMethod} / ${order.paymentStatus}'),
          if (order.trackingProvider.isNotEmpty ||
              order.trackingNumber.isNotEmpty)
            _InfoLine(
              label: 'Tracking',
              value: [
                order.trackingProvider,
                order.trackingNumber,
                order.trackingUrl,
              ].where((part) => part.trim().isNotEmpty).join(' / '),
            ),
          if (order.deliveryManName.isNotEmpty)
            _InfoLine(
              label: 'Delivery',
              value: [
                order.deliveryManName,
                order.deliveryManPhone,
                order.deliveryVehicle,
              ].where((part) => part.trim().isNotEmpty).join(' / '),
            ),
          _InfoLine(label: 'Customer', value: order.customerName),
          _InfoLine(label: 'Phone', value: order.customerPhone),
          _InfoLine(label: 'Address', value: order.address),
          if (order.orderNote.isNotEmpty)
            _InfoLine(label: 'Note', value: order.orderNote),
        ],
      ),
    );
  }
}

class _OrderItemRow extends StatelessWidget {
  const _OrderItemRow({
    required this.item,
    required this.refund,
    required this.canRequestRefund,
    required this.onRefund,
    required this.requestingRefund,
  });

  final EcommerceOrderItem item;
  final EcommerceRefundRequest? refund;
  final bool canRequestRefund;
  final VoidCallback onRefund;
  final bool requestingRefund;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item.productName,
                        style: const TextStyle(fontWeight: FontWeight.w800)),
                    if (item.variantName.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(item.variantName,
                          style: const TextStyle(fontWeight: FontWeight.w700)),
                    ],
                    const SizedBox(height: 6),
                    Wrap(
                      spacing: 6,
                      runSpacing: 6,
                      children: [
                        _StatusPill(status: item.status),
                        if (refund != null)
                          _StatusPill(
                              status: 'refund ${refund!.status}',
                              tone: _StatusTone.warning),
                      ],
                    ),
                  ],
                ),
              ),
              Text('${item.quantity} x ₹${item.price.toStringAsFixed(2)}',
                  style: const TextStyle()),
              const SizedBox(width: 10),
              Text('₹${item.total.toStringAsFixed(2)}',
                  style: const TextStyle(fontWeight: FontWeight.w900)),
            ],
          ),
          if (refund != null) ...[
            const SizedBox(height: 10),
            _RefundMiniDetails(refund: refund!),
          ] else if (item.digitalFileUrl.isNotEmpty) ...[
            const SizedBox(height: 10),
            _DigitalFileCard(
              url: item.digitalFileUrl,
              accent: const Color(0xFF6C5CE7),
            ),
          ] else if (canRequestRefund) ...[
            const SizedBox(height: 10),
            Align(
              alignment: Alignment.centerRight,
              child: OutlinedButton.icon(
                onPressed: requestingRefund ? null : onRefund,
                icon: const Icon(Icons.assignment_return_outlined),
                label: const Text('Request Refund'),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _DigitalFileCard extends StatelessWidget {
  const _DigitalFileCard({
    required this.url,
    required this.accent,
  });

  final String url;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: accent.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: accent.withValues(alpha: 0.18)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.file_download_outlined, color: accent),
              const SizedBox(width: 8),
              const Expanded(
                child: Text(
                  'Digital delivery file',
                  style: TextStyle(
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              TextButton(
                onPressed: () => _openDownload(context),
                child: const Text('Open'),
              ),
            ],
          ),
          const SizedBox(height: 6),
          SelectableText(
            url,
            style: TextStyle(color: accent, fontWeight: FontWeight.w800),
          ),
        ],
      ),
    );
  }

  Future<void> _openDownload(BuildContext context) async {
    final uri = Uri.tryParse(url.trim());
    final canOpen = uri != null &&
        (uri.scheme == 'https' || uri.scheme == 'http') &&
        uri.host.isNotEmpty;
    if (!canOpen) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Invalid digital file URL')),
      );
      return;
    }
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

class _RefundRequestDraft {
  const _RefundRequestDraft({required this.reason, required this.note});

  final String reason;
  final String note;
}

class _RefundDialog extends StatefulWidget {
  const _RefundDialog();

  @override
  State<_RefundDialog> createState() => _RefundDialogState();
}

class _RefundDialogState extends State<_RefundDialog> {
  final _noteController = TextEditingController();
  String _reason = 'Item issue';

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Request refund'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          DropdownButtonFormField<String>(
            initialValue: _reason,
            decoration: const InputDecoration(labelText: 'Reason'),
            items: const [
              DropdownMenuItem(value: 'Item issue', child: Text('Item issue')),
              DropdownMenuItem(
                  value: 'Wrong item received',
                  child: Text('Wrong item received')),
              DropdownMenuItem(
                  value: 'Damaged item', child: Text('Damaged item')),
              DropdownMenuItem(value: 'Other', child: Text('Other')),
            ],
            onChanged: (value) =>
                setState(() => _reason = value ?? 'Item issue'),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _noteController,
            minLines: 3,
            maxLines: 4,
            decoration: const InputDecoration(
              labelText: 'Note optional',
              border: OutlineInputBorder(),
            ),
          ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: () => Navigator.of(context).pop(
            _RefundRequestDraft(
              reason: _reason,
              note: _noteController.text.trim(),
            ),
          ),
          child: const Text('Submit'),
        ),
      ],
    );
  }
}

class _RefundsCard extends StatelessWidget {
  const _RefundsCard({required this.refunds});

  final List<EcommerceRefundRequest> refunds;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text('Refunds',
                    style:
                        TextStyle(fontSize: 16, fontWeight: FontWeight.w900)),
              ),
              Text('${refunds.length}',
                  style: const TextStyle(fontWeight: FontWeight.w800)),
            ],
          ),
          const SizedBox(height: 10),
          ...refunds.map((refund) => Padding(
                padding: const EdgeInsets.only(top: 8),
                child: _RefundTile(refund: refund, compact: true),
              )),
        ],
      ),
    );
  }
}

class _OrderIssueCard extends StatelessWidget {
  const _OrderIssueCard({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: Theme.of(context).dividerColor),
        ),
        child: const Row(
          children: [
            Icon(Icons.report_problem_outlined, color: AppTheme.warning),
            SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Report an order issue',
                    style: TextStyle(
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  SizedBox(height: 3),
                  Text(
                    'Missing, damaged, wrong item, or delivery concern',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
            Icon(
              Icons.chevron_right_rounded,
            ),
          ],
        ),
      ),
    );
  }
}

class _RefundMiniDetails extends StatelessWidget {
  const _RefundMiniDetails({required this.refund});

  final EcommerceRefundRequest refund;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: AppTheme.primary.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        'Refund ${refund.status.replaceAll('_', ' ')}'
        '${refund.adminNote.isEmpty ? '' : ' - ${refund.adminNote}'}',
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _RefundTile extends StatelessWidget {
  const _RefundTile({required this.refund, this.compact = false});

  final EcommerceRefundRequest refund;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(compact ? 10 : 14),
      decoration: BoxDecoration(
        color: compact ? Theme.of(context).scaffoldBackgroundColor : Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Theme.of(context).dividerColor),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  refund.productName.isEmpty
                      ? refund.orderNumber
                      : refund.productName,
                  style: const TextStyle(
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              _StatusPill(
                status: refund.status,
                tone: _refundTone(refund.status),
              ),
            ],
          ),
          const SizedBox(height: 8),
          if (refund.productName.isNotEmpty && refund.orderNumber.isNotEmpty)
            Text(refund.orderNumber, style: const TextStyle(fontSize: 12)),
          const SizedBox(height: 6),
          _InfoLine(
              label: 'Amount', value: '₹${refund.amount.toStringAsFixed(2)}'),
          _InfoLine(label: 'Reason', value: refund.reason),
          if (refund.note.isNotEmpty)
            _InfoLine(label: 'Note', value: refund.note),
          if (refund.adminNote.isNotEmpty)
            _InfoLine(label: 'Admin', value: refund.adminNote),
          if (!compact && refund.createdAt.isNotEmpty)
            _InfoLine(label: 'Date', value: refund.createdAt),
        ],
      ),
    );
  }

  static _StatusTone _refundTone(String status) {
    return switch (status) {
      'approved' || 'refunded' => _StatusTone.success,
      'rejected' => _StatusTone.danger,
      _ => _StatusTone.warning,
    };
  }
}

class _InfoLine extends StatelessWidget {
  const _InfoLine({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 82,
            child: Text(label,
                style: const TextStyle(fontWeight: FontWeight.w700)),
          ),
          Expanded(
            child: Text(value.isEmpty ? '-' : value,
                style: const TextStyle(fontWeight: FontWeight.w700)),
          ),
        ],
      ),
    );
  }
}

enum _StatusTone { primary, success, warning, danger }

class _StatusPill extends StatelessWidget {
  const _StatusPill({required this.status, this.tone = _StatusTone.primary});

  final String status;
  final _StatusTone tone;

  @override
  Widget build(BuildContext context) {
    final effectiveTone =
        tone == _StatusTone.primary ? _inferredTone(status) : tone;
    final color = switch (effectiveTone) {
      _StatusTone.primary => AppTheme.primary,
      _StatusTone.success => const Color(0xFF6C5CE7),
      _StatusTone.warning => const Color(0xFFE08A00),
      _StatusTone.danger => Colors.red,
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        status.replaceAll('_', ' '),
        style:
            TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w900),
      ),
    );
  }

  static _StatusTone _inferredTone(String status) {
    return switch (status) {
      'delivered' || 'paid' || 'refunded' => _StatusTone.success,
      'cancelled' ||
      'refund_rejected' ||
      'payment_rejected' =>
        _StatusTone.danger,
      'refund_pending' ||
      'refund_approved' ||
      'processing' ||
      'out_for_delivery' =>
        _StatusTone.warning,
      _ => _StatusTone.primary,
    };
  }
}

class _OrderMessage extends StatelessWidget {
  const _OrderMessage({
    required this.icon,
    required this.title,
    required this.detail,
    this.action,
  });

  final IconData icon;
  final String title;
  final String detail;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return AppActivityEmptyState(
      icon: icon,
      title: title,
      detail: detail,
      accent: const Color(0xFF6C5CE7),
      action: action,
    );
  }
}
