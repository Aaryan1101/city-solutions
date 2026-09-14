import 'package:flutter/material.dart';

import '../../../core/app_theme.dart';
import '../data/ecommerce_api_client.dart';
import '../domain/ecommerce_models.dart';

class EcommerceAddressScreen extends StatefulWidget {
  const EcommerceAddressScreen({
    super.key,
    required this.session,
    this.selectionMode = false,
    this.apiBaseUrl,
  });

  final EcommerceCustomerSession session;
  final bool selectionMode;
  final String? apiBaseUrl;

  @override
  State<EcommerceAddressScreen> createState() => _EcommerceAddressScreenState();
}

class _EcommerceAddressScreenState extends State<EcommerceAddressScreen> {
  late final EcommerceApiClient _api;
  late Future<List<EcommerceAddress>> _future;

  @override
  void initState() {
    super.initState();
    _api = EcommerceApiClient(baseUrl: widget.apiBaseUrl);
    _future = _api.fetchAddresses(token: widget.session.token);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Addresses')),
      floatingActionButton: FloatingActionButton(
        onPressed: _addAddress,
        child: const Icon(Icons.add_rounded),
      ),
      body: FutureBuilder<List<EcommerceAddress>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return AppErrorState(
              title: 'Addresses could not load',
              detail: AppErrorState.userMessage(snapshot.error),
              onRetry: () => setState(
                () =>
                    _future = _api.fetchAddresses(token: widget.session.token),
              ),
            );
          }
          final addresses = snapshot.data ?? const <EcommerceAddress>[];
          if (addresses.isEmpty) {
            return const Center(
              child: Text('Add an address to make checkout faster.',
                  style: TextStyle()),
            );
          }

          return ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: addresses.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (context, index) {
              final address = addresses[index];
              return _AddressTile(
                address: address,
                selectionMode: widget.selectionMode,
                onTap: widget.selectionMode
                    ? () => Navigator.of(context).pop(address)
                    : null,
                onDefault: () => _setDefault(address),
                onDelete: () => _delete(address),
              );
            },
          );
        },
      ),
    );
  }

  Future<void> _addAddress() async {
    final saved = await Navigator.of(context).push<bool>(
      MaterialPageRoute(
        builder: (_) => EcommerceAddressFormScreen(
          session: widget.session,
          apiBaseUrl: widget.apiBaseUrl,
        ),
      ),
    );
    if (saved == true && mounted) {
      setState(() {
        _future = _api.fetchAddresses(token: widget.session.token);
      });
    }
  }

  Future<void> _setDefault(EcommerceAddress address) async {
    final addresses = await _api.setDefaultAddress(
      token: widget.session.token,
      addressId: address.id,
    );
    if (!mounted) return;
    setState(() => _future = Future.value(addresses));
  }

  Future<void> _delete(EcommerceAddress address) async {
    final addresses = await _api.deleteAddress(
      token: widget.session.token,
      addressId: address.id,
    );
    if (!mounted) return;
    setState(() => _future = Future.value(addresses));
  }
}

class EcommerceAddressFormScreen extends StatefulWidget {
  const EcommerceAddressFormScreen({
    super.key,
    required this.session,
    this.apiBaseUrl,
  });

  final EcommerceCustomerSession session;
  final String? apiBaseUrl;

  @override
  State<EcommerceAddressFormScreen> createState() =>
      _EcommerceAddressFormScreenState();
}

class _EcommerceAddressFormScreenState
    extends State<EcommerceAddressFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _labelController = TextEditingController(text: 'Home');
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _addressController = TextEditingController();
  final _cityController = TextEditingController(text: 'Lucknow');
  final _stateController = TextEditingController();
  final _pincodeController = TextEditingController();
  bool _isDefault = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _nameController.text = widget.session.name;
    _phoneController.text = widget.session.phone;
  }

  @override
  void dispose() {
    _labelController.dispose();
    _nameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _cityController.dispose();
    _stateController.dispose();
    _pincodeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Add Address')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            _Field(controller: _labelController, label: 'Label'),
            const SizedBox(height: 12),
            _Field(controller: _nameController, label: 'Contact Name'),
            const SizedBox(height: 12),
            _Field(
                controller: _phoneController,
                label: 'Contact Phone',
                keyboardType: TextInputType.phone),
            const SizedBox(height: 12),
            _Field(
                controller: _addressController, label: 'Address', maxLines: 3),
            const SizedBox(height: 12),
            _Field(controller: _cityController, label: 'City'),
            const SizedBox(height: 12),
            _Field(
                controller: _stateController,
                label: 'State optional',
                required: false),
            const SizedBox(height: 12),
            _Field(
                controller: _pincodeController,
                label: 'Pincode optional',
                required: false,
                keyboardType: TextInputType.number),
            const SizedBox(height: 8),
            CheckboxListTile(
              value: _isDefault,
              onChanged: (value) => setState(() => _isDefault = value ?? true),
              title: const Text('Use as default address'),
              contentPadding: EdgeInsets.zero,
            ),
            const SizedBox(height: 14),
            SizedBox(
              height: 52,
              child: FilledButton(
                onPressed: _saving ? null : _save,
                child: Text(_saving ? 'Saving...' : 'Save Address'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      await EcommerceApiClient(baseUrl: widget.apiBaseUrl).saveAddress(
        token: widget.session.token,
        label: _labelController.text.trim(),
        contactName: _nameController.text.trim(),
        contactPhone: _phoneController.text.trim(),
        address: _addressController.text.trim(),
        city: _cityController.text.trim(),
        state: _stateController.text.trim(),
        pincode: _pincodeController.text.trim(),
        isDefault: _isDefault,
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _AddressTile extends StatelessWidget {
  const _AddressTile({
    required this.address,
    required this.selectionMode,
    required this.onTap,
    required this.onDefault,
    required this.onDelete,
  });

  final EcommerceAddress address;
  final bool selectionMode;
  final VoidCallback? onTap;
  final VoidCallback onDefault;
  final VoidCallback onDelete;

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
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Text(address.label,
                      style: const TextStyle(fontWeight: FontWeight.w900)),
                  const SizedBox(width: 8),
                  if (address.isDefault) const _Pill(label: 'Default'),
                  const Spacer(),
                  if (selectionMode)
                    const Icon(Icons.check_circle_outline,
                        color: AppTheme.primary),
                ],
              ),
              const SizedBox(height: 6),
              Text('${address.contactName}, ${address.contactPhone}',
                  style: const TextStyle()),
              const SizedBox(height: 6),
              Text(address.fullAddress,
                  style: const TextStyle(fontWeight: FontWeight.w700)),
              if (!selectionMode) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    TextButton(
                      onPressed: address.isDefault ? null : onDefault,
                      child: const Text('Set Default'),
                    ),
                    TextButton(
                      onPressed: onDelete,
                      child: const Text('Delete'),
                    ),
                  ],
                )
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: AppTheme.primary.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(label,
          style: const TextStyle(
              color: AppTheme.primary,
              fontSize: 11,
              fontWeight: FontWeight.w900)),
    );
  }
}

class _Field extends StatelessWidget {
  const _Field({
    required this.controller,
    required this.label,
    this.required = true,
    this.maxLines = 1,
    this.keyboardType,
  });

  final TextEditingController controller;
  final String label;
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
        filled: true,
        fillColor: Theme.of(context).cardColor,
        border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(8),
            borderSide: BorderSide.none),
      ),
    );
  }
}
