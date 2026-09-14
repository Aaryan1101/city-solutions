import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../data/ecommerce_api_client.dart';
import '../domain/ecommerce_models.dart';

class EcommerceWalletScreen extends StatefulWidget {
  const EcommerceWalletScreen({super.key, required this.session});

  final EcommerceCustomerSession session;

  @override
  State<EcommerceWalletScreen> createState() => _EcommerceWalletScreenState();
}

class _EcommerceWalletScreenState extends State<EcommerceWalletScreen> {
  final _api = EcommerceApiClient();
  final _amountController = TextEditingController();
  final _bankController = TextEditingController();
  final _noteController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  late Future<EcommerceWalletData> _future;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _future = _api.fetchWallet(token: widget.session.token);
  }

  @override
  void dispose() {
    _amountController.dispose();
    _bankController.dispose();
    _noteController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Wallet')),
      body: FutureBuilder<EcommerceWalletData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return _WalletMessage(
              title: 'Wallet is not available yet.',
              detail: AppErrorState.userMessage(snapshot.error),
              action: FilledButton(
                onPressed: () => setState(() {
                  _future = _api.fetchWallet(token: widget.session.token);
                }),
                child: const Text('Retry'),
              ),
            );
          }

          final wallet = snapshot.data!;
          return RefreshIndicator(
            onRefresh: () async {
              setState(() {
                _future = _api.fetchWallet(token: widget.session.token);
              });
              await _future;
            },
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [Color(0xFF33267A), Color(0xFF6C5CE7)],
                    ),
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(color: Theme.of(context).dividerColor),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF6C5CE7).withValues(alpha: .18),
                        blurRadius: 26,
                        offset: const Offset(0, 16),
                      ),
                    ],
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Available Balance',
                          style: TextStyle(
                              color: Colors.white70,
                              fontWeight: FontWeight.w700)),
                      const SizedBox(height: 8),
                      Text('₹${wallet.balance.toStringAsFixed(2)}',
                          style: const TextStyle(
                              color: Colors.white,
                              fontSize: 32,
                              fontWeight: FontWeight.w900)),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                _WithdrawalForm(
                  formKey: _formKey,
                  amountController: _amountController,
                  bankController: _bankController,
                  noteController: _noteController,
                  submitting: _submitting,
                  onSubmit: _requestWithdrawal,
                ),
                const SizedBox(height: 16),
                const Text('Withdrawal Requests',
                    style:
                        TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
                const SizedBox(height: 10),
                if (wallet.withdrawals.isEmpty)
                  const _WalletMessage(
                    title: 'No withdrawal requests yet.',
                    detail: 'Submitted withdrawal requests will appear here.',
                  )
                else
                  ...wallet.withdrawals
                      .map((request) => _WithdrawalTile(request: request)),
                const SizedBox(height: 16),
                const Text('Transactions',
                    style:
                        TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
                const SizedBox(height: 10),
                if (wallet.ledger.isEmpty)
                  const _WalletMessage(
                    title: 'No wallet transactions yet.',
                    detail:
                        'Refund credits and future wallet activity will show here.',
                  )
                else
                  ...wallet.ledger
                      .map((entry) => _WalletEntryTile(entry: entry)),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _requestWithdrawal() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    try {
      final wallet = await _api.requestWalletWithdrawal(
        token: widget.session.token,
        amount: double.tryParse(_amountController.text.trim()) ?? 0,
        bankDetails: _bankController.text.trim(),
        note: _noteController.text.trim(),
      );
      if (!mounted) return;
      _amountController.clear();
      _bankController.clear();
      _noteController.clear();
      setState(() => _future = Future.value(wallet));
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Withdrawal request submitted')),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }
}

class _WithdrawalForm extends StatelessWidget {
  const _WithdrawalForm({
    required this.formKey,
    required this.amountController,
    required this.bankController,
    required this.noteController,
    required this.submitting,
    required this.onSubmit,
  });

  final GlobalKey<FormState> formKey;
  final TextEditingController amountController;
  final TextEditingController bankController;
  final TextEditingController noteController;
  final bool submitting;
  final VoidCallback onSubmit;

  @override
  Widget build(BuildContext context) {
    return Form(
      key: formKey,
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Theme.of(context).dividerColor),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF6C5CE7).withValues(alpha: .05),
              blurRadius: 18,
              offset: const Offset(0, 10),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Request Withdrawal',
                style: TextStyle(fontWeight: FontWeight.w900)),
            const SizedBox(height: 12),
            TextFormField(
              controller: amountController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Amount',
                prefixIcon: Icon(Icons.currency_rupee_rounded),
              ),
              validator: (value) {
                final amount = double.tryParse((value ?? '').trim()) ?? 0;
                return amount <= 0 ? 'Enter withdrawal amount' : null;
              },
            ),
            const SizedBox(height: 10),
            TextFormField(
              controller: bankController,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'Bank details',
                prefixIcon: Icon(Icons.account_balance_outlined),
              ),
              validator: (value) =>
                  (value ?? '').trim().isEmpty ? 'Enter bank details' : null,
            ),
            const SizedBox(height: 10),
            TextFormField(
              controller: noteController,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Note optional',
                prefixIcon: Icon(Icons.notes_outlined),
              ),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed: submitting ? null : onSubmit,
                icon: submitting
                    ? const AppSkeletonBox(width: 16, height: 16, radius: 8)
                    : const Icon(Icons.send_outlined),
                label: Text(submitting ? 'Submitting...' : 'Submit Request'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _WithdrawalTile extends StatelessWidget {
  const _WithdrawalTile({required this.request});

  final EcommerceWalletWithdrawal request;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF6C5CE7).withValues(alpha: .04),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text('₹${request.amount.toStringAsFixed(2)}',
                    style: const TextStyle(fontWeight: FontWeight.w900)),
              ),
              _WalletStatusPill(status: request.status),
            ],
          ),
          const SizedBox(height: 6),
          Text(request.bankDetails, style: const TextStyle()),
          if (request.adminNote.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text('Admin: ${request.adminNote}', style: const TextStyle()),
          ],
          if (request.createdAt.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(request.createdAt, style: const TextStyle(fontSize: 12)),
          ],
        ],
      ),
    );
  }
}

class _WalletStatusPill extends StatelessWidget {
  const _WalletStatusPill({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'paid' || 'approved' => const Color(0xFF6C5CE7),
      'rejected' => Colors.red,
      _ => AppTheme.primary,
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        status,
        style: TextStyle(color: color, fontWeight: FontWeight.w900),
      ),
    );
  }
}

class _WalletEntryTile extends StatelessWidget {
  const _WalletEntryTile({required this.entry});

  final EcommerceWalletEntry entry;

  @override
  Widget build(BuildContext context) {
    final credit = entry.direction == 'credit';
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF6C5CE7).withValues(alpha: .04),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: (credit ? const Color(0xFF6C5CE7) : Colors.red)
                  .withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Icon(
              credit ? Icons.south_west_rounded : Icons.north_east_rounded,
              color: credit ? const Color(0xFF6C5CE7) : Colors.red,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(entry.entryType.replaceAll('_', ' '),
                    style: const TextStyle(fontWeight: FontWeight.w900)),
                if (entry.description.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(entry.description, style: const TextStyle()),
                ],
                const SizedBox(height: 4),
                Text(entry.createdAt, style: const TextStyle(fontSize: 12)),
              ],
            ),
          ),
          Text(
            '${credit ? '+' : '-'}₹${entry.amount.toStringAsFixed(2)}',
            style: TextStyle(
              color: credit ? const Color(0xFF6C5CE7) : Colors.red,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _WalletMessage extends StatelessWidget {
  const _WalletMessage({
    required this.title,
    required this.detail,
    this.action,
  });

  final String title;
  final String detail;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Container(
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(22),
            border: Border.all(color: Theme.of(context).dividerColor),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 68,
                height: 68,
                decoration: BoxDecoration(
                  color: const Color(0xFF6C5CE7).withValues(alpha: .1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.account_balance_wallet_outlined,
                    size: 34, color: Color(0xFF6C5CE7)),
              ),
              const SizedBox(height: 12),
              Text(title,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                      fontSize: 18, fontWeight: FontWeight.w900)),
              const SizedBox(height: 8),
              Text(detail,
                  textAlign: TextAlign.center,
                  style: const TextStyle(height: 1.35)),
              if (action != null) ...[
                const SizedBox(height: 16),
                action!,
              ],
            ],
          ),
        ),
      ),
    );
  }
}
