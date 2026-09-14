import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../../../core/adaptive_colors.dart';
import '../../zone/zone_store.dart';
import '../data/mart_api_client.dart';
import '../domain/mart_models.dart';
import 'mart_address_screen.dart';
import 'mart_order_success_screen.dart';

class MartCheckoutScreen extends StatefulWidget {
  const MartCheckoutScreen({
    super.key,
    required this.guestId,
    required this.cart,
    required this.sessionFuture,
    this.apiBaseUrl,
  });

  final String guestId;
  final MartCartData cart;
  final Future<MartCustomerSession> sessionFuture;
  final String? apiBaseUrl;

  @override
  State<MartCheckoutScreen> createState() => _MartCheckoutScreenState();
}

class _MartCheckoutScreenState extends State<MartCheckoutScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController(text: 'Jitendra');
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  final _addressController = TextEditingController(
    text: ZoneStore.instance.selectedLocation.value?.address ?? '',
  );
  final _noteController = TextEditingController();
  final _paymentReferenceController = TextEditingController();
  final _paymentNoteController = TextEditingController();
  MartCustomerSession _session = MartCustomerSession.guest();
  MartAddress? _selectedAddress;
  late Future<MartConfig> _configFuture;
  late final MartApiClient _api;
  String _paymentMethod = 'cash_on_delivery';
  String _substitutionPreference = 'call_before_replace';
  int? _shippingMethodId;
  bool _placing = false;

  @override
  void initState() {
    super.initState();
    _api = MartApiClient(baseUrl: widget.apiBaseUrl);
    _configFuture = _api.fetchConfig();
    widget.sessionFuture.then((session) {
      if (!mounted) return;
      setState(() {
        _session = session;
        if (session.isLoggedIn) {
          _nameController.text = session.name;
          _phoneController.text = session.phone;
          _emailController.text = session.email;
        }
      });
      if (session.isLoggedIn) {
        _loadDefaultAddress(session);
      }
    });
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _addressController.dispose();
    _noteController.dispose();
    _paymentReferenceController.dispose();
    _paymentNoteController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Checkout')),
      body: FutureBuilder<MartConfig>(
        future: _configFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return AppErrorState(
              title: 'Checkout could not load',
              detail: AppErrorState.userMessage(snapshot.error),
              onRetry: () => setState(() => _configFuture = _api.fetchConfig()),
            );
          }

          final config = snapshot.data!;
          final rawMethods = config.paymentMethods
              .where((method) => method.id != 'wallet' || _session.isLoggedIn)
              .toList();
          final methods = rawMethods.isEmpty
              ? const [
                  MartPaymentMethod(
                    id: 'cash_on_delivery',
                    title: 'Cash on Delivery',
                    description: 'Pay in cash when your order arrives.',
                    requiresReference: false,
                    gateway: '',
                    publicKey: '',
                    instructions: '',
                  ),
                ]
              : config.paymentMethods;
          if (!methods.any((method) => method.id == _paymentMethod)) {
            _paymentMethod = methods.first.id;
          }
          final selectedMethod = methods.firstWhere(
            (method) => method.id == _paymentMethod,
            orElse: () => methods.first,
          );
          final shippingMethods = config.shippingMethods;
          if (shippingMethods.isNotEmpty &&
              !shippingMethods
                  .any((method) => method.id == (_shippingMethodId ?? -1))) {
            _shippingMethodId = shippingMethods.first.id;
          }
          final selectedShipping = shippingMethods.isEmpty
              ? null
              : shippingMethods.firstWhere(
                  (method) => method.id == _shippingMethodId,
                  orElse: () => shippingMethods.first,
                );
          final shippingCost =
              selectedShipping?.cost ?? widget.cart.deliveryCharge;
          final displayedTotal = widget.cart.subtotal -
              widget.cart.couponDiscount +
              widget.cart.taxTotal +
              shippingCost;

          return Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _SummaryCard(
                  cart: widget.cart,
                  shippingCost: shippingCost,
                  total: displayedTotal,
                ),
                const SizedBox(height: 16),
                const Text('Delivery Details',
                    style:
                        TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
                const SizedBox(height: 12),
                if (_session.isLoggedIn) ...[
                  _AddressSelector(
                    address: _selectedAddress,
                    onTap: _selectAddress,
                  ),
                  const SizedBox(height: 12),
                ],
                _Field(
                    controller: _nameController,
                    label: 'Name',
                    icon: Icons.person_outline),
                const SizedBox(height: 12),
                _Field(
                    controller: _phoneController,
                    label: 'Phone',
                    icon: Icons.phone_outlined,
                    keyboardType: TextInputType.phone),
                const SizedBox(height: 12),
                _Field(
                    controller: _emailController,
                    label: 'Email optional',
                    icon: Icons.email_outlined,
                    required: false,
                    keyboardType: TextInputType.emailAddress),
                const SizedBox(height: 12),
                _Field(
                    controller: _addressController,
                    label: 'Address',
                    icon: Icons.location_on_outlined,
                    maxLines: 3),
                if (shippingMethods.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  const Text('Shipping Method',
                      style:
                          TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
                  const SizedBox(height: 12),
                  ...shippingMethods.map(
                    (method) => _ShippingMethodTile(
                      method: method,
                      selected: method.id == _shippingMethodId,
                      onTap: () =>
                          setState(() => _shippingMethodId = method.id),
                    ),
                  ),
                ],
                const SizedBox(height: 16),
                const Text('Payment Method',
                    style:
                        TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
                const SizedBox(height: 12),
                ...methods.map(
                  (method) => _PaymentMethodTile(
                    method: method,
                    selected: method.id == _paymentMethod,
                    onTap: () => setState(() => _paymentMethod = method.id),
                  ),
                ),
                if (selectedMethod.requiresReference) ...[
                  if (selectedMethod.instructions.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(16),
                        border:
                            Border.all(color: Theme.of(context).dividerColor),
                        boxShadow: [
                          BoxShadow(
                            color: AppTheme.primary.withValues(alpha: .04),
                            blurRadius: 16,
                            offset: const Offset(0, 8),
                          ),
                        ],
                      ),
                      child: Text(
                        selectedMethod.instructions,
                        style: const TextStyle(
                            fontWeight: FontWeight.w700, height: 1.35),
                      ),
                    ),
                  ],
                  const SizedBox(height: 12),
                  _Field(
                    controller: _paymentReferenceController,
                    label: 'Payment Reference',
                    icon: Icons.receipt_outlined,
                  ),
                  const SizedBox(height: 12),
                  _Field(
                    controller: _paymentNoteController,
                    label: 'Payment Note optional',
                    icon: Icons.notes_outlined,
                    required: false,
                    maxLines: 2,
                  ),
                ],
                const SizedBox(height: 16),
                const Text('If an item is unavailable',
                    style:
                        TextStyle(fontSize: 18, fontWeight: FontWeight.w900)),
                const SizedBox(height: 12),
                _SubstitutionPreferenceTile(
                  title: 'Call before replacing',
                  description: 'Vendor/admin will confirm with you first.',
                  icon: Icons.call_outlined,
                  value: 'call_before_replace',
                  selectedValue: _substitutionPreference,
                  onChanged: (value) =>
                      setState(() => _substitutionPreference = value),
                ),
                _SubstitutionPreferenceTile(
                  title: 'Replace with similar item',
                  description: 'Allow nearest brand/pack alternative.',
                  icon: Icons.swap_horiz_rounded,
                  value: 'auto_replace',
                  selectedValue: _substitutionPreference,
                  onChanged: (value) =>
                      setState(() => _substitutionPreference = value),
                ),
                _SubstitutionPreferenceTile(
                  title: 'Do not replace',
                  description: 'Skip unavailable items from fulfillment.',
                  icon: Icons.block_outlined,
                  value: 'no_replacement',
                  selectedValue: _substitutionPreference,
                  onChanged: (value) =>
                      setState(() => _substitutionPreference = value),
                ),
                const SizedBox(height: 12),
                _Field(
                    controller: _noteController,
                    label: 'Order note optional',
                    icon: Icons.notes_outlined,
                    required: false,
                    maxLines: 2),
                const SizedBox(height: 12),
                _CheckoutPolicyCard(
                  pages: config.cmsPages,
                  fallback:
                      'Orders can be cancelled before processing. Refunds, replacement and delivery timing depend on product availability, payment status and admin/vendor review.',
                ),
                const SizedBox(height: 20),
                SizedBox(
                  height: 52,
                  child: FilledButton.icon(
                    onPressed: _placing ? null : _placeOrder,
                    style: FilledButton.styleFrom(
                        backgroundColor: const Color(0xFF20A66A)),
                    icon: _placing
                        ? const AppSkeletonBox(
                            width: 18,
                            height: 18,
                            radius: 9,
                          )
                        : const Icon(Icons.check_circle_outline),
                    label: Text(
                      _placing
                          ? 'Placing Order...'
                          : selectedMethod.id == 'cash_on_delivery'
                              ? 'Place COD Order'
                              : selectedMethod.id == 'wallet'
                                  ? 'Pay With Wallet'
                                  : 'Submit Order',
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _placeOrder() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _placing = true);
    try {
      final result = await _api.placeOrder(
        guestId: widget.guestId,
        customerId: _session.customerId,
        customerToken: _session.token,
        addressId: _selectedAddress?.id,
        customerName: _nameController.text.trim(),
        customerPhone: _phoneController.text.trim(),
        customerEmail: _emailController.text.trim(),
        address: _addressController.text.trim(),
        orderNote: _noteController.text.trim(),
        substitutionPreference: _substitutionPreference,
        paymentMethod: _paymentMethod,
        shippingMethodId: _shippingMethodId,
        paymentReference: _paymentReferenceController.text.trim(),
        paymentNote: _paymentNoteController.text.trim(),
      );
      if (!mounted) return;
      await Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => MartOrderSuccessScreen(result: result),
        ),
      );
      if (mounted) Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _placing = false);
    }
  }

  Future<void> _selectAddress() async {
    final address = await Navigator.of(context).push<MartAddress>(
      MaterialPageRoute(
        builder: (_) => MartAddressScreen(
          session: _session,
          selectionMode: true,
        ),
      ),
    );
    if (address == null || !mounted) return;
    setState(() {
      _selectedAddress = address;
      _nameController.text = address.contactName;
      _phoneController.text = address.contactPhone;
      _addressController.text = address.fullAddress;
    });
  }

  Future<void> _loadDefaultAddress(MartCustomerSession session) async {
    try {
      final addresses =
          await MartApiClient().fetchAddresses(token: session.token);
      if (!mounted || addresses.isEmpty) return;
      final defaultAddress = addresses.firstWhere(
        (address) => address.isDefault,
        orElse: () => addresses.first,
      );
      setState(() {
        _selectedAddress = defaultAddress;
        _nameController.text = defaultAddress.contactName;
        _phoneController.text = defaultAddress.contactPhone;
        _addressController.text = defaultAddress.fullAddress;
      });
    } catch (_) {
      // Manual address entry remains available if saved addresses are offline.
    }
  }
}

class _PaymentMethodTile extends StatelessWidget {
  const _PaymentMethodTile({
    required this.method,
    required this.selected,
    required this.onTap,
  });

  final MartPaymentMethod method;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: onTap,
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: selected
                  ? AppTheme.primary.withValues(alpha: .06)
                  : Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: selected
                    ? AppTheme.primary
                    : Theme.of(context).dividerColor,
                width: selected ? 1.5 : 1,
              ),
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primary.withValues(alpha: .035),
                  blurRadius: 14,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            child: Row(
              children: [
                Icon(
                  selected
                      ? Icons.radio_button_checked
                      : Icons.radio_button_off,
                  color: selected
                      ? AppTheme.primary
                      : Theme.of(context).colorScheme.onSurfaceVariant,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(method.title,
                          style: const TextStyle(fontWeight: FontWeight.w900)),
                      if (method.description.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(method.description, style: const TextStyle()),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SubstitutionPreferenceTile extends StatelessWidget {
  const _SubstitutionPreferenceTile({
    required this.title,
    required this.description,
    required this.icon,
    required this.value,
    required this.selectedValue,
    required this.onChanged,
  });

  final String title;
  final String description;
  final IconData icon;
  final String value;
  final String selectedValue;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final selected = value == selectedValue;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: () => onChanged(value),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: selected
                  ? const Color(0xFF20A66A).withValues(alpha: .07)
                  : Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: selected
                    ? const Color(0xFF20A66A)
                    : Theme.of(context).dividerColor,
                width: selected ? 1.5 : 1,
              ),
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primary.withValues(alpha: .035),
                  blurRadius: 14,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            child: Row(
              children: [
                Icon(icon,
                    color: selected
                        ? const Color(0xFF20A66A)
                        : Theme.of(context).colorScheme.onSurfaceVariant),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title,
                          style: const TextStyle(fontWeight: FontWeight.w900)),
                      const SizedBox(height: 4),
                      Text(description, style: const TextStyle()),
                    ],
                  ),
                ),
                Icon(
                  selected
                      ? Icons.radio_button_checked
                      : Icons.radio_button_off,
                  color: selected
                      ? const Color(0xFF20A66A)
                      : Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ShippingMethodTile extends StatelessWidget {
  const _ShippingMethodTile({
    required this.method,
    required this.selected,
    required this.onTap,
  });

  final MartShippingMethod method;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: onTap,
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: selected
                  ? AppTheme.primary.withValues(alpha: .06)
                  : Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: selected
                    ? AppTheme.primary
                    : Theme.of(context).dividerColor,
                width: selected ? 1.5 : 1,
              ),
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primary.withValues(alpha: .035),
                  blurRadius: 14,
                  offset: const Offset(0, 8),
                ),
              ],
            ),
            child: Row(
              children: [
                Icon(
                  selected
                      ? Icons.radio_button_checked
                      : Icons.radio_button_off,
                  color: selected
                      ? AppTheme.primary
                      : Theme.of(context).colorScheme.onSurfaceVariant,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(method.name,
                          style: const TextStyle(fontWeight: FontWeight.w900)),
                      if (method.description.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(method.description, style: const TextStyle()),
                      ],
                      if (method.expectedDays.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(method.expectedDays,
                            style: const TextStyle(fontSize: 12)),
                      ],
                    ],
                  ),
                ),
                Text('₹${method.cost.toStringAsFixed(0)}',
                    style: const TextStyle(fontWeight: FontWeight.w900)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _AddressSelector extends StatelessWidget {
  const _AddressSelector({required this.address, required this.onTap});

  final MartAddress? address;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: Theme.of(context).dividerColor),
            boxShadow: [
              BoxShadow(
                color: AppTheme.primary.withValues(alpha: .035),
                blurRadius: 14,
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
                  color: AppTheme.primary.withValues(alpha: .1),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(Icons.location_on_outlined,
                    color: AppTheme.primary),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  address == null
                      ? 'Choose saved address'
                      : '${address!.label}: ${address!.fullAddress}',
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
              ),
              const Icon(
                Icons.chevron_right_rounded,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SummaryCard extends StatelessWidget {
  const _SummaryCard({
    required this.cart,
    required this.shippingCost,
    required this.total,
  });

  final MartCartData cart;
  final double shippingCost;
  final double total;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            adaptiveColor(
              context,
              light: const Color(0xFFE8F7EF),
              dark: const Color(0xFF123126),
            ),
            Theme.of(context).colorScheme.surface,
          ],
        ),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF20A66A).withValues(alpha: .08),
            blurRadius: 22,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Row(
        children: [
          const Icon(Icons.receipt_long_outlined,
              color: Color(0xFF20A66A), size: 34),
          const SizedBox(width: 12),
          Expanded(
              child: Text('${cart.itemsCount} items in this order',
                  style: const TextStyle(fontWeight: FontWeight.w800))),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              if (cart.couponDiscount > 0)
                Text('-₹${cart.couponDiscount.toStringAsFixed(0)} coupon',
                    style: const TextStyle(
                        color: Color(0xFF20A66A), fontWeight: FontWeight.w800)),
              if (cart.taxTotal > 0)
                Text('+₹${cart.taxTotal.toStringAsFixed(0)} tax',
                    style: const TextStyle(fontWeight: FontWeight.w800)),
              if (shippingCost > 0)
                Text('+₹${shippingCost.toStringAsFixed(0)} shipping',
                    style: const TextStyle(fontWeight: FontWeight.w800)),
              Text('₹${total.toStringAsFixed(0)}',
                  style: const TextStyle(
                      fontSize: 22, fontWeight: FontWeight.w900)),
            ],
          ),
        ],
      ),
    );
  }
}

class _CheckoutPolicyCard extends StatelessWidget {
  const _CheckoutPolicyCard({
    required this.pages,
    required this.fallback,
  });

  final List<MartCmsPage> pages;
  final String fallback;

  @override
  Widget build(BuildContext context) {
    final visible = pages
        .where((page) =>
            {
              'terms-conditions',
              'refund-policy',
              'shipping-policy',
            }.contains(page.slug) &&
            page.content.trim().isNotEmpty)
        .toList();
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .04),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFF20A66A).withValues(alpha: .1),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(Icons.policy_outlined, color: Color(0xFF20A66A)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Before you place this order',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 6),
                if (visible.isEmpty)
                  Text(
                    fallback,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      height: 1.35,
                    ),
                  )
                else
                  for (final page in visible.take(3))
                    Padding(
                      padding: const EdgeInsets.only(bottom: 6),
                      child: Text(
                        '${page.title}: ${_policyPreview(page.content)}',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          height: 1.35,
                        ),
                      ),
                    ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  static String _policyPreview(String value) {
    return value.replaceAll(RegExp(r'\s+'), ' ').trim();
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.controller,
    required this.label,
    required this.icon,
    this.required = true,
    this.maxLines = 1,
    this.keyboardType,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final bool required;
  final int maxLines;
  final TextInputType? keyboardType;

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      validator: required
          ? (value) => value == null || value.trim().isEmpty ? 'Required' : null
          : null,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon),
        filled: true,
        fillColor: Theme.of(context).cardColor,
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none),
      ),
    );
  }
}
