import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../../core/app_theme.dart';
import '../../../core/adaptive_colors.dart';
import '../../zone/zone_store.dart';
import '../data/ecommerce_api_client.dart';
import '../domain/ecommerce_models.dart';
import 'ecommerce_address_screen.dart';
import 'ecommerce_order_success_screen.dart';

class EcommerceCheckoutScreen extends StatefulWidget {
  const EcommerceCheckoutScreen({
    super.key,
    required this.guestId,
    required this.cart,
    required this.sessionFuture,
    this.apiBaseUrl,
  });

  final String guestId;
  final EcommerceCartData cart;
  final Future<EcommerceCustomerSession> sessionFuture;
  final String? apiBaseUrl;

  @override
  State<EcommerceCheckoutScreen> createState() =>
      _EcommerceCheckoutScreenState();
}

class _EcommerceCheckoutScreenState extends State<EcommerceCheckoutScreen> {
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
  final _prescriptionController = TextEditingController();
  final _imagePicker = ImagePicker();
  EcommerceCustomerSession _session = EcommerceCustomerSession.guest();
  EcommerceAddress? _selectedAddress;
  late Future<EcommerceConfig> _configFuture;
  late final EcommerceApiClient _api;
  String _paymentMethod = 'cash_on_delivery';
  String _substitutionPreference = 'call_before_replace';
  String _prescriptionFileName = '';
  String _prescriptionFileBase64 = '';
  int? _shippingMethodId;
  bool _placing = false;
  bool _ageConfirmed = false;
  bool get _isMedicalModule =>
      (widget.apiBaseUrl ?? '').toLowerCase().contains('/medical');
  bool get _medicalNeedsPrescription => _isMedicalModule
      ? widget.cart.items.any((item) => item.needsMedicalReview)
      : false;
  bool get _medicalNeedsAgeConfirmation => _isMedicalModule
      ? widget.cart.items.any((item) => item.requiresAgeConfirmation)
      : false;
  bool get _hasBlockedMedicalItems => _isMedicalModule
      ? widget.cart.items.any((item) => item.isBlockedOnline)
      : false;

  @override
  void initState() {
    super.initState();
    _api = EcommerceApiClient(baseUrl: widget.apiBaseUrl);
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
    _prescriptionController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Checkout')),
      body: FutureBuilder<EcommerceConfig>(
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
                  EcommercePaymentMethod(
                    id: 'cash_on_delivery',
                    title: 'Cash on Delivery',
                    description: 'Pay in cash when your order arrives.',
                    requiresReference: false,
                    gateway: '',
                    publicKey: '',
                    instructions: '',
                  ),
                ]
              : rawMethods;
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
                if (_isMedicalModule) ...[
                  const SizedBox(height: 12),
                  _MedicalReviewCard(items: widget.cart.items),
                ],
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
                if (_isMedicalModule) ...[
                  const SizedBox(height: 12),
                  _PrescriptionUploadTile(
                    fileName: _prescriptionFileName,
                    onPick: _pickPrescription,
                    onClear: _prescriptionFileBase64.isEmpty
                        ? null
                        : () => setState(() {
                              _prescriptionFileBase64 = '';
                              _prescriptionFileName = '';
                            }),
                  ),
                  const SizedBox(height: 12),
                  _Field(
                    controller: _prescriptionController,
                    label: 'Prescription note or reference if required',
                    icon: Icons.medical_information_outlined,
                    required: false,
                    maxLines: 2,
                  ),
                  const SizedBox(height: 12),
                  _AgeConfirmationTile(
                    value: _ageConfirmed,
                    onChanged: (value) => setState(() => _ageConfirmed = value),
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
                  includePrescription: _isMedicalModule,
                  accent: _isMedicalModule
                      ? const Color(0xFFE94C3D)
                      : const Color(0xFF6C5CE7),
                  fallback: _isMedicalModule
                      ? 'Prescription and restricted medicines may require admin/pharmacist review. Orders can be rejected if prescription or eligibility details are invalid.'
                      : 'Orders can be cancelled before processing. Refunds, replacement and delivery timing depend on product availability, payment status and admin/vendor review.',
                ),
                const SizedBox(height: 20),
                SizedBox(
                  height: 52,
                  child: FilledButton.icon(
                    onPressed: _placing ? null : _placeOrder,
                    style: FilledButton.styleFrom(
                        backgroundColor: const Color(0xFF6C5CE7)),
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
    final medicalError = _medicalPreflightError();
    if (medicalError != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(medicalError)),
      );
      return;
    }
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
        prescriptionReference: _prescriptionController.text.trim(),
        prescriptionFileBase64: _prescriptionFileBase64,
        ageConfirmed: _ageConfirmed,
      );
      if (!mounted) return;
      await Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => EcommerceOrderSuccessScreen(result: result),
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

  Future<void> _pickPrescription() async {
    final file = await _imagePicker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
      maxWidth: 1800,
    );
    if (file == null) return;
    final bytes = await file.readAsBytes();
    if (bytes.length > 5 * 1024 * 1024) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Prescription image must be up to 5 MB')),
      );
      return;
    }
    setState(() {
      _prescriptionFileName = file.name;
      _prescriptionFileBase64 = base64Encode(bytes);
    });
  }

  String? _medicalPreflightError() {
    if (!_isMedicalModule) return null;
    if (_hasBlockedMedicalItems) {
      return 'Remove medicines marked not available for online ordering before checkout.';
    }
    for (final item in widget.cart.items) {
      final maxQty = item.maxQtyPerOrder;
      if (maxQty != null && item.quantity > maxQty) {
        return '${item.name} has a maximum quantity limit of $maxQty per order.';
      }
    }
    if (_medicalNeedsPrescription &&
        _prescriptionFileBase64.trim().isEmpty &&
        _prescriptionController.text.trim().isEmpty) {
      return 'Upload a prescription image or enter a prescription reference.';
    }
    if (_medicalNeedsAgeConfirmation && !_ageConfirmed) {
      return 'Confirm medicine eligibility before placing this order.';
    }
    return null;
  }

  Future<void> _selectAddress() async {
    final address = await Navigator.of(context).push<EcommerceAddress>(
      MaterialPageRoute(
        builder: (_) => EcommerceAddressScreen(
          session: _session,
          selectionMode: true,
          apiBaseUrl: widget.apiBaseUrl,
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

  Future<void> _loadDefaultAddress(EcommerceCustomerSession session) async {
    try {
      final addresses = await EcommerceApiClient(baseUrl: widget.apiBaseUrl)
          .fetchAddresses(token: session.token);
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

  final EcommercePaymentMethod method;
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
                  ? const Color(0xFF6C5CE7).withValues(alpha: .07)
                  : Theme.of(context).colorScheme.surface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: selected
                    ? const Color(0xFF6C5CE7)
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
                        ? const Color(0xFF6C5CE7)
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
                      ? const Color(0xFF6C5CE7)
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

  final EcommerceShippingMethod method;
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

  final EcommerceAddress? address;
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

  final EcommerceCartData cart;
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
              light: const Color(0xFFF0EEFF),
              dark: const Color(0xFF28243D),
            ),
            Theme.of(context).colorScheme.surface,
          ],
        ),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: Theme.of(context).dividerColor),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF6C5CE7).withValues(alpha: .08),
            blurRadius: 22,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Row(
        children: [
          const Icon(Icons.receipt_long_outlined,
              color: Color(0xFF6C5CE7), size: 34),
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
                        color: Color(0xFF6C5CE7), fontWeight: FontWeight.w800)),
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
    required this.includePrescription,
    required this.accent,
    required this.fallback,
  });

  final List<EcommerceCmsPage> pages;
  final bool includePrescription;
  final Color accent;
  final String fallback;

  @override
  Widget build(BuildContext context) {
    final allowed = {
      'terms-conditions',
      'refund-policy',
      'shipping-policy',
      if (includePrescription) 'prescription-policy',
    };
    final visible = pages
        .where((page) =>
            allowed.contains(page.slug) && page.content.trim().isNotEmpty)
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
              color: accent.withValues(alpha: .1),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(Icons.policy_outlined, color: accent),
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
                  for (final page in visible.take(includePrescription ? 4 : 3))
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

class _PrescriptionUploadTile extends StatelessWidget {
  const _PrescriptionUploadTile({
    required this.fileName,
    required this.onPick,
    required this.onClear,
  });

  final String fileName;
  final VoidCallback onPick;
  final VoidCallback? onClear;

  @override
  Widget build(BuildContext context) {
    final hasFile = fileName.isNotEmpty;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(18),
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
        children: [
          Icon(
            hasFile ? Icons.check_circle_outline : Icons.upload_file_outlined,
            color: hasFile ? const Color(0xFF20A464) : AppTheme.primary,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              hasFile ? fileName : 'Upload prescription image',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          IconButton(
            tooltip: hasFile ? 'Change prescription' : 'Pick prescription',
            onPressed: onPick,
            icon: const Icon(Icons.photo_library_outlined),
          ),
          if (hasFile)
            IconButton(
              tooltip: 'Remove prescription',
              onPressed: onClear,
              icon: const Icon(Icons.close),
            ),
        ],
      ),
    );
  }
}

class _MedicalReviewCard extends StatelessWidget {
  const _MedicalReviewCard({required this.items});

  final List<EcommerceCartItem> items;

  @override
  Widget build(BuildContext context) {
    final controlled = items.where((item) => item.hasMedicalControls).toList();
    if (controlled.isEmpty) {
      return const _Shell(
        icon: Icons.verified_user_outlined,
        title: 'Medical safety review',
        children: [
          Text(
            'No restricted medicine controls were detected in this cart.',
            style: TextStyle(fontWeight: FontWeight.w700),
          ),
        ],
      );
    }

    final hasBlocked = controlled.any((item) => item.isBlockedOnline);
    return _Shell(
      icon: Icons.medical_information_outlined,
      title: 'Medical safety review',
      children: [
        Text(
          hasBlocked
              ? 'Remove blocked-online medicines before checkout. Other controlled items may need prescription/admin review.'
              : 'These items may need prescription/admin review before fulfillment.',
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 10),
        for (final item in controlled)
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: _MedicalReviewItem(item: item),
          ),
      ],
    );
  }
}

class _MedicalReviewItem extends StatelessWidget {
  const _MedicalReviewItem({required this.item});

  final EcommerceCartItem item;

  @override
  Widget build(BuildContext context) {
    final flags = <String>[
      if (item.isBlockedOnline) 'Not available for online ordering',
      if (item.medicineType == 'prescription_required') 'Prescription required',
      if (item.medicineType == 'restricted') 'Restricted medicine',
      if (item.requiresPharmacistReview) 'Pharmacist review',
      if (item.requiresAgeConfirmation) 'Age confirmation',
      if (item.scheduleTag.isNotEmpty) 'Schedule ${item.scheduleTag}',
      if (item.maxQtyPerOrder != null) 'Max ${item.maxQtyPerOrder}/order',
      if (item.maxQtyPerMonth != null) 'Max ${item.maxQtyPerMonth}/month',
    ];
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF6F2),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFFFD8C8)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            item.name,
            style: const TextStyle(
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: [
              for (final flag in flags)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.surface,
                    borderRadius: BorderRadius.circular(999),
                    border: Border.all(color: const Color(0xFFFFD8C8)),
                  ),
                  child: Text(
                    flag,
                    style: const TextStyle(
                      color: Color(0xFFE94C3D),
                      fontSize: 11,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
            ],
          ),
        ],
      ),
    );
  }
}

class _Shell extends StatelessWidget {
  const _Shell({
    required this.icon,
    required this.title,
    required this.children,
  });

  final IconData icon;
  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
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
              color: const Color(0xFFE94C3D).withValues(alpha: .1),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: const Color(0xFFE94C3D)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 6),
                ...children,
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _AgeConfirmationTile extends StatelessWidget {
  const _AgeConfirmationTile({
    required this.value,
    required this.onChanged,
  });

  final bool value;
  final ValueChanged<bool> onChanged;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => onChanged(!value),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: value
              ? AppTheme.primary.withValues(alpha: .10)
              : Theme.of(context).colorScheme.surface,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: value ? AppTheme.primary : Theme.of(context).dividerColor,
          ),
        ),
        child: Row(
          children: [
            Checkbox(
              value: value,
              onChanged: (checked) => onChanged(checked ?? false),
              activeColor: AppTheme.primary,
            ),
            const SizedBox(width: 8),
            const Expanded(
              child: Text(
                'I confirm I am eligible to order these medicines and will use prescription/restricted items only under valid medical advice.',
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  height: 1.35,
                ),
              ),
            ),
          ],
        ),
      ),
    );
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
