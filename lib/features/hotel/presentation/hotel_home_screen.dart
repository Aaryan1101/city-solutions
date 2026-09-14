import 'package:flutter/material.dart';

import '../../../core/adaptive_colors.dart';
import '../../../core/activity_empty_state.dart';
import '../../../core/app_greeting.dart';
import '../../../core/app_theme.dart';
import '../../mart/data/mart_session_store.dart';
import '../../zone/zone_store.dart';
import '../data/hotel_api_client.dart';
import 'hotel_remote_image.dart';

const _hotelColor = Color(0xFF0B7285);
const _hotelAccent = Color(0xFFFF8A4C);
const _hotelDarkBg = Color(0xFF090909);
const _hotelDarkPanel = Color(0xFF141414);
const _hotelDarkCard = Color(0xFF191919);
const _hotelDarkLine = Color(0xFF2A2A2A);
const _hotelPurple = Color(0xFFC46AFF);
const _hotelDarkText = Color(0xFFF7F5F9);
const _hotelDarkMuted = Color(0xFF9A95A1);
const _hotelAppVersion =
    String.fromEnvironment('HOTEL_APP_VERSION', defaultValue: 'dev');

class HotelHomeScreen extends StatefulWidget {
  const HotelHomeScreen({super.key});

  @override
  State<HotelHomeScreen> createState() => _HotelHomeScreenState();
}

class _HotelHomeScreenState extends State<HotelHomeScreen> {
  final _api = HotelApiClient();
  late Future<HotelHomeData> _future;
  int? _categoryId;
  String _query = '';

  @override
  void initState() {
    super.initState();
    _future = _api.fetchHome();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: adaptiveScaffold(context, _hotelDarkBg),
      body: FutureBuilder<HotelHomeData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage(
              layout: AppSkeletonLayout.serviceGrid,
            );
          }
          if (snapshot.hasError) {
            return _ErrorState(
              message: AppErrorState.userMessage(snapshot.error),
              onRetry: () => setState(() => _future = _api.fetchHome()),
            );
          }
          final data = snapshot.data!;
          final forceUpdateRequired =
              data.config.forceUpdateVersion.isNotEmpty &&
                  data.config.forceUpdateVersion != _hotelAppVersion;
          if (data.config.maintenanceMode || forceUpdateRequired) {
            return _HotelBlockingState(
              title: forceUpdateRequired ? 'Update required' : 'Maintenance',
              detail: forceUpdateRequired
                  ? 'Please install app version ${data.config.forceUpdateVersion} to continue.'
                  : (data.config.maintenanceMessage.trim().isEmpty
                      ? 'Hotels are temporarily unavailable.'
                      : data.config.maintenanceMessage),
            );
          }
          final hotels = [
            ...data.featuredHotels,
            ...data.popularHotels.where(
              (hotel) =>
                  !data.featuredHotels.any((item) => item.id == hotel.id),
            ),
          ];
          final visible = hotels.where((hotel) {
            final matchesCategory =
                _categoryId == null || hotel.categoryId == _categoryId;
            final text =
                '${hotel.name} ${hotel.city} ${hotel.area}'.toLowerCase();
            final matchesQuery = _query.trim().isEmpty ||
                text.contains(_query.trim().toLowerCase());
            return matchesCategory && matchesQuery;
          }).toList();
          return RefreshIndicator(
            onRefresh: () async {
              setState(() => _future = _api.fetchHome());
              await _future;
            },
            color: _hotelPurple,
            child: SafeArea(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
                children: [
                  AdaptiveModuleHeaderBand(
                    accent: _hotelPurple,
                    darkBase: _hotelDarkBg,
                    margin: const EdgeInsets.fromLTRB(-16, -12, -16, 0),
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                    child: Column(
                      children: [
                        _HotelHomeTopBar(
                          onBookings: () => Navigator.of(context).push(
                            MaterialPageRoute<void>(
                              builder: (_) => const HotelBookingsScreen(),
                            ),
                          ),
                        ),
                        const SizedBox(height: 12),
                        if (data.config.latestAppVersion.isNotEmpty &&
                            data.config.latestAppVersion !=
                                _hotelAppVersion) ...[
                          _HotelVersionNotice(
                            currentVersion: _hotelAppVersion,
                            latestVersion: data.config.latestAppVersion,
                          ),
                          const SizedBox(height: 12),
                        ],
                        _HotelHomeSearch(
                          onChanged: (value) => setState(() => _query = value),
                        ),
                        const SizedBox(height: 10),
                        _HotelHomeCategoryRail(
                          categories: data.categories,
                          selectedId: _categoryId,
                          onSelected: (id) => setState(() => _categoryId = id),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  if (data.featuredHotels.isNotEmpty) ...[
                    _HotelSpotlightCard(
                      hotel: data.featuredHotels.first,
                      currency: data.config.currencySymbol,
                      onTap: () =>
                          _openDetails(data.featuredHotels.first, data.config),
                    ),
                    const SizedBox(height: 18),
                  ],
                  _HotelHomeSectionHeader(
                    title: 'Nearby Hotels',
                    trailing: visible.isEmpty ? null : 'See all',
                  ),
                  const SizedBox(height: 10),
                  if (visible.isEmpty)
                    const _HotelHomeEmptyState()
                  else
                    SizedBox(
                      height: 186,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: visible.length,
                        separatorBuilder: (_, __) => const SizedBox(width: 12),
                        itemBuilder: (context, index) {
                          final hotel = visible[index];
                          return _NearbyHotelCard(
                            hotel: hotel,
                            currency: data.config.currencySymbol,
                            onTap: () => _openDetails(hotel, data.config),
                          );
                        },
                      ),
                    ),
                  const SizedBox(height: 16),
                  if (visible.isNotEmpty)
                    _HotelWeekendDealCard(
                      hotel: visible.first,
                      onTap: () => _openDetails(visible.first, data.config),
                    ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _openDetails(HotelSummary hotel, HotelConfig config) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => HotelDetailsScreen(hotelId: hotel.id, config: config),
      ),
    );
  }
}

class HotelDetailsScreen extends StatefulWidget {
  const HotelDetailsScreen({
    super.key,
    required this.hotelId,
    required this.config,
  });

  final int hotelId;
  final HotelConfig config;

  @override
  State<HotelDetailsScreen> createState() => _HotelDetailsScreenState();
}

class _HotelDetailsScreenState extends State<HotelDetailsScreen> {
  final _api = HotelApiClient();
  late Future<HotelDetails> _future;

  @override
  void initState() {
    super.initState();
    _future = _api.fetchHotel(widget.hotelId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Hotel Details')),
      body: FutureBuilder<HotelDetails>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage(
              layout: AppSkeletonLayout.detail,
            );
          }
          if (snapshot.hasError) {
            return _ErrorState(
              message: AppErrorState.userMessage(snapshot.error),
              onRetry: () =>
                  setState(() => _future = _api.fetchHotel(widget.hotelId)),
            );
          }
          final details = snapshot.data!;
          final hotel = details.hotel;
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 10, 16, 20),
            children: [
              HotelRemoteImage(
                  path: hotel.thumbnail, width: double.infinity, height: 210),
              const SizedBox(height: 14),
              Text(hotel.name,
                  style: const TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w900,
                  )),
              const SizedBox(height: 6),
              Row(
                children: [
                  const Icon(Icons.location_on_outlined,
                      size: 18, color: _hotelColor),
                  Expanded(
                      child: Text('${hotel.area}, ${hotel.city}',
                          style: const TextStyle())),
                  const Icon(Icons.star_rounded, size: 18, color: _hotelAccent),
                  Text(
                      '${hotel.rating.toStringAsFixed(1)} (${hotel.reviewCount})',
                      style: const TextStyle(fontWeight: FontWeight.w800)),
                ],
              ),
              const SizedBox(height: 12),
              Text(hotel.description, style: const TextStyle(height: 1.45)),
              if (hotel.amenities.isNotEmpty) ...[
                const SizedBox(height: 14),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: hotel.amenities
                      .map((item) => _AmenityChip(label: item))
                      .toList(),
                ),
              ],
              const SizedBox(height: 22),
              const _SectionHeader(title: 'Rooms'),
              const SizedBox(height: 10),
              if (details.rooms.isEmpty)
                const _EmptyState(message: 'No active rooms available.')
              else
                for (final room in details.rooms)
                  _RoomCard(
                    room: room,
                    currency: widget.config.currencySymbol,
                    onBook: () => Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => HotelCheckoutScreen(
                          hotel: hotel,
                          room: room,
                          config: widget.config,
                        ),
                      ),
                    ),
                  ),
              if (details.reviews.isNotEmpty) ...[
                const SizedBox(height: 22),
                const _SectionHeader(title: 'Reviews'),
                const SizedBox(height: 10),
                for (final review in details.reviews.take(4))
                  _ReviewTile(review: review),
              ],
            ],
          );
        },
      ),
    );
  }
}

class HotelCheckoutScreen extends StatefulWidget {
  const HotelCheckoutScreen({
    super.key,
    required this.hotel,
    required this.room,
    required this.config,
  });

  final HotelSummary hotel;
  final HotelRoom room;
  final HotelConfig config;

  @override
  State<HotelCheckoutScreen> createState() => _HotelCheckoutScreenState();
}

class _HotelCheckoutScreenState extends State<HotelCheckoutScreen> {
  final _api = HotelApiClient();
  final _name = TextEditingController(text: 'Customer');
  final _phone = TextEditingController(text: '9999999999');
  final _email = TextEditingController();
  final _paymentReference = TextEditingController();
  final _paymentNote = TextEditingController();
  DateTime _checkIn = DateTime.now().add(const Duration(days: 1));
  DateTime _checkOut = DateTime.now().add(const Duration(days: 2));
  int _rooms = 1;
  int _adults = 1;
  int _children = 0;
  String _paymentMethod = 'pay_at_hotel';
  bool _placing = false;

  int get _nights => _checkOut.difference(_checkIn).inDays.clamp(1, 365);
  double get _roomTotal => widget.room.sellingPrice * _nights * _rooms;
  double get _taxTotal => _roomTotal * widget.room.taxPercent / 100;
  double get _grandTotal => _roomTotal + _taxTotal;
  HotelPaymentMethod get _selectedPaymentMethod {
    return widget.config.paymentMethods.firstWhere(
      (method) => method.id == _paymentMethod,
      orElse: () => widget.config.paymentMethods.isNotEmpty
          ? widget.config.paymentMethods.first
          : const HotelPaymentMethod(
              id: 'pay_at_hotel',
              title: 'Pay At Hotel',
              description: '',
              requiresReference: false,
            ),
    );
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _email.dispose();
    _paymentReference.dispose();
    _paymentNote.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Hotel Checkout')),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(16, 8, 16, 12),
        child: ElevatedButton.icon(
          onPressed: _placing ? null : _place,
          icon: _placing
              ? const AppSkeletonBox(width: 18, height: 18, radius: 9)
              : const Icon(Icons.check_circle_outline),
          label: Text(_placing
              ? 'Placing Booking...'
              : 'Book ${widget.config.currencySymbol}${_grandTotal.toStringAsFixed(0)}'),
          style: ElevatedButton.styleFrom(
              backgroundColor: _hotelColor, foregroundColor: Colors.white),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 10, 16, 20),
        children: [
          _InfoCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.hotel.name,
                    style: const TextStyle(
                        fontWeight: FontWeight.w900, fontSize: 18)),
                const SizedBox(height: 5),
                Text(widget.room.name, style: const TextStyle()),
              ],
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                  child: _DateButton(
                      label: 'Check-in',
                      value: _date(_checkIn),
                      onTap: () => _pickDate(true))),
              const SizedBox(width: 10),
              Expanded(
                  child: _DateButton(
                      label: 'Check-out',
                      value: _date(_checkOut),
                      onTap: () => _pickDate(false))),
            ],
          ),
          const SizedBox(height: 12),
          _InfoCard(
            child: Column(
              children: [
                _StepperRow(
                    label: 'Rooms',
                    value: _rooms,
                    min: 1,
                    max: widget.room.totalRooms,
                    onChanged: (value) => setState(() => _rooms = value)),
                _StepperRow(
                    label: 'Adults',
                    value: _adults,
                    min: 1,
                    max: 12,
                    onChanged: (value) => setState(() => _adults = value)),
                _StepperRow(
                    label: 'Children',
                    value: _children,
                    min: 0,
                    max: 8,
                    onChanged: (value) => setState(() => _children = value)),
              ],
            ),
          ),
          const SizedBox(height: 12),
          _InfoCard(
            child: Column(
              children: [
                TextField(
                    controller: _name,
                    decoration: const InputDecoration(labelText: 'Guest name')),
                TextField(
                    controller: _phone,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(labelText: 'Phone')),
                TextField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    decoration:
                        const InputDecoration(labelText: 'Email optional')),
              ],
            ),
          ),
          const SizedBox(height: 12),
          const _SectionHeader(title: 'Payment Method'),
          const SizedBox(height: 8),
          for (final method in widget.config.paymentMethods)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: _PaymentMethodTile(
                method: method,
                selected: _paymentMethod == method.id,
                onTap: () => setState(() => _paymentMethod = method.id),
              ),
            ),
          if (_selectedPaymentMethod.requiresReference) ...[
            const SizedBox(height: 4),
            _InfoCard(
              child: Column(
                children: [
                  TextField(
                    controller: _paymentReference,
                    decoration: InputDecoration(
                      labelText: '${_selectedPaymentMethod.title} reference',
                    ),
                  ),
                  TextField(
                    controller: _paymentNote,
                    decoration: const InputDecoration(
                        labelText: 'Payment note optional'),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 12),
          _InfoCard(
            child: Column(
              children: [
                _AmountRow(
                    label: 'Room total',
                    value: _roomTotal,
                    currency: widget.config.currencySymbol),
                _AmountRow(
                    label: 'Tax',
                    value: _taxTotal,
                    currency: widget.config.currencySymbol),
                const Divider(),
                _AmountRow(
                    label: 'Grand total',
                    value: _grandTotal,
                    currency: widget.config.currencySymbol,
                    strong: true),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _pickDate(bool checkIn) async {
    final picked = await showDatePicker(
      context: context,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      initialDate: checkIn ? _checkIn : _checkOut,
    );
    if (picked == null) return;
    setState(() {
      if (checkIn) {
        _checkIn = picked;
        if (!_checkOut.isAfter(_checkIn)) {
          _checkOut = _checkIn.add(const Duration(days: 1));
        }
      } else {
        _checkOut = picked.isAfter(_checkIn)
            ? picked
            : _checkIn.add(const Duration(days: 1));
      }
    });
  }

  Future<void> _place() async {
    if (_name.text.trim().isEmpty || _phone.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Guest name and phone are required')));
      return;
    }
    if (_selectedPaymentMethod.requiresReference &&
        _paymentReference.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Payment reference is required')),
      );
      return;
    }
    setState(() => _placing = true);
    try {
      final session = await MartSessionStore().load();
      final guestId = session.token.isNotEmpty
          ? session.token
          : 'hotel_guest_${_phone.text.trim()}';
      final response = await _api.placeBooking(
        roomId: widget.room.id,
        guestId: guestId,
        customerName: _name.text.trim(),
        customerPhone: _phone.text.trim(),
        customerEmail: _email.text.trim(),
        checkIn: _date(_checkIn),
        checkOut: _date(_checkOut),
        rooms: _rooms,
        adults: _adults,
        children: _children,
        paymentMethod: _paymentMethod,
        paymentReference: _paymentReference.text.trim(),
        paymentNote: _paymentNote.text.trim(),
      );
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute<void>(
          builder: (_) => HotelBookingSuccessScreen(
            bookingNumber:
                (response['data'] as Map?)?['booking_number']?.toString() ?? '',
          ),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _placing = false);
    }
  }
}

class HotelBookingsScreen extends StatefulWidget {
  const HotelBookingsScreen({super.key, this.showAppBar = true});

  final bool showAppBar;

  @override
  State<HotelBookingsScreen> createState() => _HotelBookingsScreenState();
}

class _HotelBookingsScreenState extends State<HotelBookingsScreen> {
  final _api = HotelApiClient();
  late Future<List<HotelBooking>> _future;
  String _guestId = '';

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<HotelBooking>> _load() async {
    final session = await MartSessionStore().load();
    if (!session.isLoggedIn) return const <HotelBooking>[];
    _guestId = session.token.isNotEmpty ? session.token : session.guestId;
    return _api.fetchBookings(_guestId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: widget.showAppBar
          ? AppBar(title: const Text('Hotel Bookings'))
          : null,
      body: FutureBuilder<List<HotelBooking>>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return _ErrorState(
                message: AppErrorState.userMessage(snapshot.error),
                onRetry: () => setState(() => _future = _load()));
          }
          final bookings = snapshot.data ?? [];
          if (bookings.isEmpty) {
            return const _EmptyState(message: 'No hotel bookings yet.');
          }
          return RefreshIndicator(
            onRefresh: () async {
              setState(() => _future = _load());
              await _future;
            },
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemBuilder: (context, index) {
                final booking = bookings[index];
                return _BookingTile(
                  booking: booking,
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute<void>(
                      builder: (_) => HotelBookingDetailsScreen(
                        bookingId: booking.id,
                        guestId: _guestId,
                      ),
                    ),
                  ),
                );
              },
              separatorBuilder: (_, __) => const SizedBox(height: 10),
              itemCount: bookings.length,
            ),
          );
        },
      ),
    );
  }
}

class HotelBookingDetailsScreen extends StatefulWidget {
  const HotelBookingDetailsScreen({
    super.key,
    required this.bookingId,
    required this.guestId,
  });

  final int bookingId;
  final String guestId;

  @override
  State<HotelBookingDetailsScreen> createState() =>
      _HotelBookingDetailsScreenState();
}

class _HotelBookingDetailsScreenState extends State<HotelBookingDetailsScreen> {
  final _api = HotelApiClient();
  late Future<HotelBooking> _future;
  late Future<HotelConfig> _configFuture;

  @override
  void initState() {
    super.initState();
    _future =
        _api.fetchBooking(bookingId: widget.bookingId, guestId: widget.guestId);
    _configFuture = _api.fetchConfig();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(title: const Text('Hotel Booking')),
      body: FutureBuilder<HotelBooking>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage();
          }
          if (snapshot.hasError) {
            return _ErrorState(
                message: AppErrorState.userMessage(snapshot.error),
                onRetry: () => setState(() => _future = _api.fetchBooking(
                    bookingId: widget.bookingId, guestId: widget.guestId)));
          }
          final booking = snapshot.data!;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _InfoCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(booking.bookingNumber,
                        style: const TextStyle(
                            fontSize: 20, fontWeight: FontWeight.w900)),
                    const SizedBox(height: 10),
                    _DetailRow(label: 'Hotel', value: booking.hotelName),
                    _DetailRow(label: 'Room', value: booking.roomName),
                    _DetailRow(
                        label: 'Stay',
                        value: '${booking.checkIn} to ${booking.checkOut}'),
                    _DetailRow(
                        label: 'Guests',
                        value:
                            '${booking.adults} adults, ${booking.children} children'),
                    _DetailRow(
                        label: 'Payment',
                        value:
                            '${booking.paymentMethod} / ${booking.paymentStatus}'),
                    _DetailRow(label: 'Status', value: booking.status),
                    _DetailRow(
                        label: 'Amount',
                        value: '₹${booking.grandTotal.toStringAsFixed(2)}'),
                    if (booking.refundStatus != 'none' ||
                        booking.refundAmount > 0) ...[
                      _DetailRow(label: 'Refund', value: _refundText(booking)),
                      if (booking.refundNote.isNotEmpty)
                        _DetailRow(
                            label: 'Refund Note', value: booking.refundNote),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 18),
              if (!['completed', 'cancelled', 'cancellation_requested']
                  .contains(booking.status))
                FutureBuilder<HotelConfig>(
                  future: _configFuture,
                  builder: (context, configSnapshot) {
                    final loadingPolicy = configSnapshot.connectionState ==
                        ConnectionState.waiting;
                    final policy = configSnapshot.data?.cancellationPolicy;
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        if (loadingPolicy) ...[
                          const AppSkeletonBox(height: 88, radius: 18),
                          const SizedBox(height: 12),
                        ],
                        if (policy != null) ...[
                          _HotelPolicyCard(policy: policy),
                          const SizedBox(height: 12),
                        ],
                        OutlinedButton.icon(
                          onPressed: () => _cancel(booking, policy),
                          icon: const Icon(Icons.cancel_outlined),
                          label: const Text('Request Cancellation'),
                          style: OutlinedButton.styleFrom(
                              foregroundColor: Colors.red),
                        ),
                      ],
                    );
                  },
                ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _cancel(
      HotelBooking booking, HotelCancellationPolicy? policy) async {
    final reason = await showDialog<String>(
      context: context,
      builder: (context) {
        final controller = TextEditingController();
        return AlertDialog(
          title: const Text('Cancel Booking'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (policy != null) ...[
                Text(
                  _hotelPolicyText(policy),
                  style: const TextStyle(height: 1.35),
                ),
                const SizedBox(height: 12),
              ],
              TextField(
                  controller: controller,
                  decoration: const InputDecoration(labelText: 'Reason'),
                  maxLines: 3),
            ],
          ),
          actions: [
            TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Close')),
            FilledButton(
                onPressed: () => Navigator.pop(context, controller.text),
                child: const Text('Send Request')),
          ],
        );
      },
    );
    if (reason == null) return;
    await _api.cancelBooking(
        bookingId: booking.id, guestId: widget.guestId, reason: reason);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Cancellation request sent')));
    setState(() => _future = _api.fetchBooking(
        bookingId: widget.bookingId, guestId: widget.guestId));
  }
}

class HotelBookingSuccessScreen extends StatelessWidget {
  const HotelBookingSuccessScreen({super.key, required this.bookingNumber});

  final String bookingNumber;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: adaptiveSurface(context, _hotelDarkCard),
                borderRadius: BorderRadius.circular(28),
                border:
                    Border.all(color: adaptiveLine(context, _hotelDarkLine)),
                boxShadow: [
                  BoxShadow(
                    color: _hotelColor.withValues(alpha: .1),
                    blurRadius: 30,
                    offset: const Offset(0, 18),
                  ),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 92,
                    height: 92,
                    decoration: const BoxDecoration(
                      color: _hotelColor,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.check_rounded,
                        color: Colors.white, size: 54),
                  ),
                  const SizedBox(height: 18),
                  const Text('Hotel booking placed',
                      textAlign: TextAlign.center,
                      style:
                          TextStyle(fontSize: 24, fontWeight: FontWeight.w900)),
                  const SizedBox(height: 8),
                  Text(bookingNumber,
                      textAlign: TextAlign.center, style: const TextStyle()),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: FilledButton(
                      onPressed: () => Navigator.of(context).pushReplacement(
                          MaterialPageRoute<void>(
                              builder: (_) => const HotelBookingsScreen())),
                      style: FilledButton.styleFrom(
                          backgroundColor: _hotelColor,
                          foregroundColor: Colors.white),
                      child: const Text('View Bookings'),
                    ),
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

class _HotelHomeTopBar extends StatelessWidget {
  const _HotelHomeTopBar({required this.onBookings});

  final VoidCallback onBookings;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _hotelDarkBg,
      darkSurface: _hotelDarkPanel,
      darkCard: _hotelDarkCard,
      darkLine: _hotelDarkLine,
      darkText: _hotelDarkText,
      darkMuted: _hotelDarkMuted,
    );
    return FutureBuilder(
      future: MartSessionStore().load(),
      builder: (context, snapshot) {
        final name = appDisplayName(snapshot.data?.name);
        final location = ZoneStore.instance.selectedLocation.value;
        final locationText = location?.zone.name ??
            (location?.address.trim().isNotEmpty == true
                ? location!.address
                : 'Select area');
        return Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: _hotelPurple.withValues(alpha: .18),
              child: Icon(Icons.person_rounded, color: colors.text, size: 20),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    appGreeting(),
                    style: TextStyle(
                      color: colors.muted,
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  Text(
                    name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: colors.text,
                      fontSize: 14,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      const Icon(Icons.location_on_rounded,
                          color: _hotelPurple, size: 13),
                      const SizedBox(width: 4),
                      Flexible(
                        child: Text(
                          locationText,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: colors.muted,
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                      ),
                      Icon(Icons.keyboard_arrow_down_rounded,
                          color: colors.muted, size: 14),
                    ],
                  ),
                ],
              ),
            ),
            IconButton(
              tooltip: 'Hotel bookings',
              onPressed: onBookings,
              icon: Icon(Icons.event_available_outlined, color: colors.text),
            ),
            IconButton(
              tooltip: 'Back',
              onPressed: () => Navigator.of(context).maybePop(),
              icon: Icon(Icons.menu_rounded, color: colors.text),
            ),
          ],
        );
      },
    );
  }
}

class _HotelHomeSearch extends StatelessWidget {
  const _HotelHomeSearch({required this.onChanged});

  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _hotelDarkBg,
      darkSurface: _hotelDarkPanel,
      darkCard: _hotelDarkCard,
      darkLine: _hotelDarkLine,
      darkText: _hotelDarkText,
      darkMuted: _hotelDarkMuted,
    );
    return TextField(
      onChanged: onChanged,
      style: TextStyle(color: colors.text, fontWeight: FontWeight.w700),
      decoration: InputDecoration(
        hintText: 'Search hotels, resorts, homestays...',
        prefixIcon: const Icon(Icons.search_rounded),
        suffixIcon: const Icon(Icons.tune_rounded),
        filled: true,
        fillColor: colors.surface,
        hintStyle: TextStyle(color: colors.muted, fontSize: 12),
        prefixIconColor: colors.muted,
        suffixIconColor: colors.muted,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: BorderSide(color: colors.line),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(16),
          borderSide: const BorderSide(color: _hotelPurple, width: 1.4),
        ),
      ),
    );
  }
}

class _HotelHomeCategoryRail extends StatelessWidget {
  const _HotelHomeCategoryRail({
    required this.categories,
    required this.selectedId,
    required this.onSelected,
  });

  final List<HotelCategory> categories;
  final int? selectedId;
  final ValueChanged<int?> onSelected;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _hotelDarkBg,
      darkSurface: _hotelDarkPanel,
      darkCard: _hotelDarkCard,
      darkLine: _hotelDarkLine,
      darkText: _hotelDarkText,
      darkMuted: _hotelDarkMuted,
    );
    final items = <({int? id, String name})>[
      (id: null, name: 'All'),
      ...categories.map((category) => (id: category.id, name: category.name)),
    ];
    return SizedBox(
      height: 34,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final item = items[index];
          final selected = selectedId == item.id;
          return InkWell(
            onTap: () => onSelected(item.id),
            borderRadius: BorderRadius.circular(999),
            child: Container(
              alignment: Alignment.center,
              padding: const EdgeInsets.symmetric(horizontal: 13),
              decoration: BoxDecoration(
                color: selected ? _hotelPurple : colors.surface,
                borderRadius: BorderRadius.circular(999),
                border: Border.all(
                  color: selected ? _hotelPurple : colors.line,
                ),
              ),
              child: Text(
                item.name,
                style: TextStyle(
                  color: selected ? Colors.white : colors.muted,
                  fontSize: 11,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _HotelSpotlightCard extends StatelessWidget {
  const _HotelSpotlightCard({
    required this.hotel,
    required this.currency,
    required this.onTap,
  });

  final HotelSummary hotel;
  final String currency;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _hotelDarkBg,
      darkSurface: _hotelDarkPanel,
      darkCard: _hotelDarkCard,
      darkLine: _hotelDarkLine,
      darkText: _hotelDarkText,
      darkMuted: _hotelDarkMuted,
    );
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Ink(
        height: 238,
        decoration: BoxDecoration(
          color: colors.card,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: colors.line),
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(18),
          child: Stack(
            fit: StackFit.expand,
            children: [
              HotelRemoteImage(
                path: hotel.thumbnail,
                width: double.infinity,
                height: 238,
                borderRadius: 18,
              ),
              DecoratedBox(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [
                      Colors.black.withValues(alpha: .08),
                      Colors.black.withValues(alpha: .72),
                    ],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  ),
                ),
              ),
              Positioned(
                left: 10,
                top: 10,
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
                  decoration: BoxDecoration(
                    color: _hotelPurple,
                    borderRadius: BorderRadius.circular(7),
                  ),
                  child: const Text(
                    'Featured',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
              ),
              Positioned(
                left: 12,
                right: 12,
                bottom: 12,
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            hotel.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: _hotelDarkText,
                              fontSize: 16,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '${hotel.area}, ${hotel.city}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: _hotelDarkMuted,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              const Icon(Icons.star_rounded,
                                  color: _hotelPurple, size: 14),
                              const SizedBox(width: 3),
                              Text(
                                '${hotel.rating.toStringAsFixed(1)}  ${hotel.reviewCount} reviews',
                                style: const TextStyle(
                                  color: _hotelDarkText,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    Text(
                      '$currency${hotel.startingPrice.toStringAsFixed(0)}',
                      style: const TextStyle(
                        color: _hotelPurple,
                        fontSize: 15,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const Text(
                      '\n/night',
                      style: TextStyle(
                        color: _hotelDarkMuted,
                        fontSize: 10,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _HotelHomeSectionHeader extends StatelessWidget {
  const _HotelHomeSectionHeader({required this.title, this.trailing});

  final String title;
  final String? trailing;

  @override
  Widget build(BuildContext context) {
    final textColor = adaptiveText(context, _hotelDarkText);
    return Row(
      children: [
        Expanded(
          child: Text(
            title,
            style: TextStyle(
              color: textColor,
              fontSize: 15,
              fontWeight: FontWeight.w900,
            ),
          ),
        ),
        if (trailing != null)
          Text(
            trailing!,
            style: const TextStyle(
              color: _hotelPurple,
              fontSize: 10,
              fontWeight: FontWeight.w900,
            ),
          ),
      ],
    );
  }
}

class _NearbyHotelCard extends StatelessWidget {
  const _NearbyHotelCard({
    required this.hotel,
    required this.currency,
    required this.onTap,
  });

  final HotelSummary hotel;
  final String currency;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _hotelDarkBg,
      darkSurface: _hotelDarkPanel,
      darkCard: _hotelDarkCard,
      darkLine: _hotelDarkLine,
      darkText: _hotelDarkText,
      darkMuted: _hotelDarkMuted,
    );
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Ink(
        width: 132,
        decoration: BoxDecoration(
          color: colors.card,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: colors.line),
        ),
        child: Padding(
          padding: const EdgeInsets.all(8),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              HotelRemoteImage(
                path: hotel.thumbnail,
                width: double.infinity,
                height: 88,
                borderRadius: 12,
              ),
              const SizedBox(height: 8),
              Text(
                hotel.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: colors.text,
                  fontSize: 12,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                hotel.city,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: colors.muted,
                  fontSize: 10,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const Spacer(),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      '$currency${hotel.startingPrice.toStringAsFixed(0)}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: _hotelPurple,
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                  const Icon(Icons.star_rounded, color: _hotelPurple, size: 12),
                  Text(
                    hotel.rating.toStringAsFixed(1),
                    style: TextStyle(
                      color: colors.text,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _HotelWeekendDealCard extends StatelessWidget {
  const _HotelWeekendDealCard({required this.hotel, required this.onTap});

  final HotelSummary hotel;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _hotelDarkBg,
      darkSurface: _hotelDarkPanel,
      darkCard: _hotelDarkCard,
      darkLine: _hotelDarkLine,
      darkText: _hotelDarkText,
      darkMuted: _hotelDarkMuted,
    );
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Ink(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: colors.card,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: colors.line),
        ),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Weekend Special',
                    style: TextStyle(
                      color: _hotelPurple,
                      fontSize: 11,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '20% off on Luxury stays',
                    style: TextStyle(
                      color: colors.text,
                      fontSize: 15,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    'Valid for ${hotel.name}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: colors.muted,
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(width: 12),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
              decoration: BoxDecoration(
                color: _hotelPurple,
                borderRadius: BorderRadius.circular(999),
              ),
              child: const Text(
                'Book',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 11,
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _HotelHomeEmptyState extends StatelessWidget {
  const _HotelHomeEmptyState();

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _hotelDarkBg,
      darkSurface: _hotelDarkPanel,
      darkCard: _hotelDarkCard,
      darkLine: _hotelDarkLine,
      darkText: _hotelDarkText,
      darkMuted: _hotelDarkMuted,
    );
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: colors.card,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: colors.line),
      ),
      child: Column(
        children: [
          const Icon(Icons.hotel_outlined, color: _hotelPurple, size: 34),
          const SizedBox(height: 10),
          Text(
            'No hotels found for this filter.',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: colors.text,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Try another stay type or search area.',
            textAlign: TextAlign.center,
            style: TextStyle(color: colors.muted, fontSize: 12),
          ),
        ],
      ),
    );
  }
}

class _HotelPolicyCard extends StatelessWidget {
  const _HotelPolicyCard({required this.policy});

  final HotelCancellationPolicy policy;

  @override
  Widget build(BuildContext context) {
    return _InfoCard(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.policy_outlined, color: _hotelColor),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              _hotelPolicyText(policy),
              style: const TextStyle(height: 1.35),
            ),
          ),
        ],
      ),
    );
  }
}

class _RoomCard extends StatelessWidget {
  const _RoomCard(
      {required this.room, required this.currency, required this.onBook});
  final HotelRoom room;
  final String currency;
  final VoidCallback onBook;
  @override
  Widget build(BuildContext context) {
    return _InfoCard(
      margin: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          HotelRemoteImage(path: room.thumbnail, width: 82, height: 82),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(room.name,
                    style: const TextStyle(fontWeight: FontWeight.w900)),
                const SizedBox(height: 4),
                Text('${room.adults} adults, ${room.children} children',
                    style: const TextStyle()),
                const SizedBox(height: 6),
                Text('$currency${room.sellingPrice.toStringAsFixed(0)} / night',
                    style: const TextStyle(
                        color: _hotelColor, fontWeight: FontWeight.w900)),
              ],
            ),
          ),
          ElevatedButton(
              onPressed: onBook,
              style: ElevatedButton.styleFrom(
                  backgroundColor: _hotelColor, foregroundColor: Colors.white),
              child: const Text('Book')),
        ],
      ),
    );
  }
}

class _BookingTile extends StatelessWidget {
  const _BookingTile({required this.booking, required this.onTap});
  final HotelBooking booking;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) {
    final status =
        booking.refundStatus != 'none' ? booking.refundStatus : booking.status;
    return Material(
      color: adaptiveSurface(context, _hotelDarkCard),
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: Theme.of(context).dividerColor),
            boxShadow: [
              BoxShadow(
                color: _hotelColor.withValues(alpha: .05),
                blurRadius: 18,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: _hotelColor.withValues(alpha: .1),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(Icons.event_available_outlined,
                    color: _hotelColor),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(booking.bookingNumber,
                        style: const TextStyle(fontWeight: FontWeight.w900)),
                    const SizedBox(height: 4),
                    Text(booking.hotelName,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                            fontSize: 12, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 3),
                    Text('${booking.checkIn} to ${booking.checkOut}',
                        style: const TextStyle(fontSize: 12)),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text('₹${booking.grandTotal.toStringAsFixed(0)}',
                      style: const TextStyle(fontWeight: FontWeight.w900)),
                  const SizedBox(height: 5),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 9, vertical: 5),
                    decoration: BoxDecoration(
                      color: (booking.refundStatus != 'none'
                              ? Colors.green
                              : _hotelColor)
                          .withValues(alpha: .1),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      status,
                      style: TextStyle(
                        color: booking.refundStatus != 'none'
                            ? Colors.green
                            : _hotelColor,
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

String _refundText(HotelBooking booking) {
  final amount = booking.refundAmount > 0
      ? ' / ₹${booking.refundAmount.toStringAsFixed(2)}'
      : '';
  return '${booking.refundStatus}$amount';
}

String _hotelPolicyText(HotelCancellationPolicy policy) {
  return 'Free cancellation until ${policy.freeHoursBeforeCheckIn} hours before check-in. Later cancellations may refund ${policy.lateRefundPercent.toStringAsFixed(0)}%.';
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title});
  final String title;
  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
            child: Text(title,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                ))),
      ],
    );
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({required this.child, this.margin});
  final Widget child;
  final EdgeInsetsGeometry? margin;
  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _hotelDarkCard),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: adaptiveLine(context, _hotelDarkLine)),
        boxShadow: [
          BoxShadow(
            color: _hotelColor.withValues(alpha: .05),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: child,
    );
  }
}

class _DateButton extends StatelessWidget {
  const _DateButton(
      {required this.label, required this.value, required this.onTap});
  final String label;
  final String value;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: _InfoCard(
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(label, style: const TextStyle(fontSize: 12)),
          const SizedBox(height: 4),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w900)),
        ]),
      ),
    );
  }
}

class _PaymentMethodTile extends StatelessWidget {
  const _PaymentMethodTile({
    required this.method,
    required this.selected,
    required this.onTap,
  });

  final HotelPaymentMethod method;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: selected
              ? _hotelColor.withValues(alpha: .12)
              : adaptiveSurface(context, _hotelDarkCard),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color:
                selected ? _hotelColor : adaptiveLine(context, _hotelDarkLine),
          ),
          boxShadow: [
            BoxShadow(
              color: _hotelColor.withValues(alpha: .04),
              blurRadius: 16,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Row(
          children: [
            Icon(
              selected ? Icons.radio_button_checked : Icons.radio_button_off,
              color: selected
                  ? _hotelColor
                  : Theme.of(context).colorScheme.onSurfaceVariant,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(method.title,
                      style: const TextStyle(fontWeight: FontWeight.w900)),
                  const SizedBox(height: 3),
                  Text(method.description, style: const TextStyle()),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _StepperRow extends StatelessWidget {
  const _StepperRow(
      {required this.label,
      required this.value,
      required this.min,
      required this.max,
      required this.onChanged});
  final String label;
  final int value;
  final int min;
  final int max;
  final ValueChanged<int> onChanged;
  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
            child: Text(label,
                style: const TextStyle(fontWeight: FontWeight.w800))),
        IconButton(
            onPressed: value > min ? () => onChanged(value - 1) : null,
            icon: const Icon(Icons.remove)),
        SizedBox(
            width: 32,
            child: Text('$value',
                textAlign: TextAlign.center,
                style: const TextStyle(fontWeight: FontWeight.w900))),
        IconButton(
            onPressed: value < max ? () => onChanged(value + 1) : null,
            icon: const Icon(Icons.add)),
      ],
    );
  }
}

class _AmountRow extends StatelessWidget {
  const _AmountRow(
      {required this.label,
      required this.value,
      required this.currency,
      this.strong = false});
  final String label;
  final double value;
  final String currency;
  final bool strong;
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        children: [
          Expanded(
              child: Text(label,
                  style: TextStyle(
                      fontWeight: strong ? FontWeight.w900 : FontWeight.w600))),
          Text('$currency${value.toStringAsFixed(2)}',
              style: TextStyle(
                  fontWeight: strong ? FontWeight.w900 : FontWeight.w700)),
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({required this.label, required this.value});
  final String label;
  final String value;
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        SizedBox(
            width: 90,
            child: Text(label,
                style: const TextStyle(fontWeight: FontWeight.w800))),
        Expanded(
            child: Text(value,
                style: const TextStyle(fontWeight: FontWeight.w800))),
      ]),
    );
  }
}

class _AmenityChip extends StatelessWidget {
  const _AmenityChip({required this.label});
  final String label;
  @override
  Widget build(BuildContext context) {
    return Chip(
      label: Text(label),
      avatar:
          const Icon(Icons.check_circle_outline, color: _hotelColor, size: 18),
      backgroundColor: Theme.of(context).cardColor,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
    );
  }
}

class _ReviewTile extends StatelessWidget {
  const _ReviewTile({required this.review});
  final HotelReview review;
  @override
  Widget build(BuildContext context) {
    return _InfoCard(
      margin: const EdgeInsets.only(bottom: 8),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(
              child: Text(review.customerName,
                  style: const TextStyle(fontWeight: FontWeight.w900))),
          Text('${review.rating}/5',
              style: const TextStyle(
                  color: _hotelAccent, fontWeight: FontWeight.w900)),
        ]),
        if (review.comment.isNotEmpty) ...[
          const SizedBox(height: 6),
          Text(review.comment, style: const TextStyle()),
        ],
      ]),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.message});
  final String message;
  @override
  Widget build(BuildContext context) {
    return AppActivityEmptyState(
      icon: Icons.hotel_outlined,
      title: message,
      detail: 'Your hotel stays will appear here after booking.',
      accent: _hotelColor,
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.onRetry});
  final String message;
  final VoidCallback onRetry;
  @override
  Widget build(BuildContext context) {
    final normalized = message.toLowerCase();
    final isNotFound = normalized.contains('not found');
    return AppErrorState(
      title: isNotFound ? message : 'Hotel data could not load',
      detail: isNotFound ? null : 'Check your connection and try again.',
      onRetry: onRetry,
      icon: Icons.hotel_outlined,
    );
  }
}

class _HotelBlockingState extends StatelessWidget {
  const _HotelBlockingState({
    required this.title,
    required this.detail,
  });

  final String title;
  final String detail;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: adaptiveSurface(context, _hotelDarkCard),
            borderRadius: BorderRadius.circular(24),
            border: Border.all(color: adaptiveLine(context, _hotelDarkLine)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.info_outline_rounded,
                  color: _hotelColor, size: 54),
              const SizedBox(height: 12),
              Text(
                title,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 10),
              Text(
                detail,
                textAlign: TextAlign.center,
                style: const TextStyle(height: 1.45),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _HotelVersionNotice extends StatelessWidget {
  const _HotelVersionNotice({
    required this.currentVersion,
    required this.latestVersion,
  });

  final String currentVersion;
  final String latestVersion;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFE8F7FA),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFBFE7EE)),
      ),
      child: Row(
        children: [
          const Icon(Icons.system_update_alt_rounded, color: _hotelColor),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              'App update available. Current: $currentVersion  Latest: $latestVersion',
              style: const TextStyle(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

String _date(DateTime value) {
  final month = value.month.toString().padLeft(2, '0');
  final day = value.day.toString().padLeft(2, '0');
  return '${value.year}-$month-$day';
}
