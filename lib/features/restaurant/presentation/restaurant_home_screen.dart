import 'package:flutter/material.dart';

import '../../../core/adaptive_colors.dart';
import '../../../core/activity_empty_state.dart';
import '../../../core/app_greeting.dart';
import '../../../core/app_theme.dart';
import '../../mart/data/mart_session_store.dart';
import '../../mart/domain/mart_models.dart';
import '../../restaurant/data/restaurant_api_client.dart';
import '../../zone/zone_store.dart';

const _restaurantBg = Color(0xFF090909);
const _restaurantPanel = Color(0xFF141414);
const _restaurantCard = Color(0xFF1A1A1A);
const _restaurantLine = Color(0xFF2A2A2A);
const _restaurantRed = Color(0xFFFF4940);
const _restaurantText = Color(0xFFF8F6F2);
const _restaurantMuted = Color(0xFF9B9690);
const _restaurantAppVersion =
    String.fromEnvironment('RESTAURANT_APP_VERSION', defaultValue: 'dev');

class RestaurantHomeScreen extends StatefulWidget {
  const RestaurantHomeScreen({super.key});

  @override
  State<RestaurantHomeScreen> createState() => _RestaurantHomeScreenState();
}

class _RestaurantHomeScreenState extends State<RestaurantHomeScreen> {
  final _client = RestaurantApiClient();
  late Future<RestaurantHomeData> _future;
  final _cart = <int, RestaurantFoodCartItem>{};
  int? _foodCategoryId;
  String _query = '';

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<RestaurantHomeData> _load() async {
    await ZoneStore.instance.load();
    return _client.fetchHome(zoneId: ZoneStore.instance.selectedZoneId);
  }

  void _retry() {
    setState(() => _future = _load());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: adaptiveScaffold(context, _restaurantBg),
      bottomNavigationBar: _cart.isEmpty
          ? null
          : SafeArea(
              minimum: const EdgeInsets.fromLTRB(16, 8, 16, 14),
              child: _RestaurantCartBar(
                count: _cart.values
                    .fold<int>(0, (sum, item) => sum + item.quantity),
                total: _cart.values
                    .fold<double>(0, (sum, item) => sum + item.lineTotal),
                onCheckout: _placeFoodOrder,
              ),
            ),
      body: FutureBuilder<RestaurantHomeData>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const AppSkeletonPage(
              layout: AppSkeletonLayout.serviceGrid,
            );
          }
          if (snapshot.hasError) {
            return AppErrorState(
              title: 'Restaurants could not load',
              detail: 'Check your connection and try again.',
              onRetry: _retry,
            );
          }
          final data = snapshot.data!;
          final forceUpdateRequired =
              data.config.forceUpdateVersion.isNotEmpty &&
                  data.config.forceUpdateVersion != _restaurantAppVersion;
          if (data.config.maintenanceMode || forceUpdateRequired) {
            return _EmptyState(
              title: forceUpdateRequired ? 'Update required' : 'Maintenance',
              message: forceUpdateRequired
                  ? 'Please install app version ${data.config.forceUpdateVersion} to continue.'
                  : (data.config.maintenanceMessage.trim().isEmpty
                      ? 'Restaurant booking is temporarily unavailable.'
                      : data.config.maintenanceMessage),
              onRetry: _retry,
            );
          }
          final foodItems = data.popularFoodItems.where((item) {
            final categoryOk =
                _foodCategoryId == null || item.categoryId == _foodCategoryId;
            final text =
                '${item.name} ${item.restaurantName} ${item.categoryName}'
                    .toLowerCase();
            final queryOk = _query.trim().isEmpty ||
                text.contains(_query.trim().toLowerCase());
            return categoryOk && queryOk;
          }).toList();
          return RefreshIndicator(
            onRefresh: () async => _retry(),
            color: _restaurantRed,
            child: SafeArea(
              child: ListView(
                padding:
                    EdgeInsets.fromLTRB(16, 12, 16, _cart.isEmpty ? 28 : 112),
                children: [
                  AdaptiveModuleHeaderBand(
                    accent: _restaurantRed,
                    darkBase: _restaurantBg,
                    margin: const EdgeInsets.fromLTRB(-16, -12, -16, 0),
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                    child: Column(
                      children: [
                        _RestaurantTopBar(onRefresh: _retry),
                        const SizedBox(height: 12),
                        if (data.config.latestAppVersion.isNotEmpty &&
                            data.config.latestAppVersion !=
                                _restaurantAppVersion) ...[
                          _RestaurantVersionNotice(
                            currentVersion: _restaurantAppVersion,
                            latestVersion: data.config.latestAppVersion,
                          ),
                          const SizedBox(height: 12),
                        ],
                        _RestaurantDeliveryPill(
                          address: ZoneStore.instance.selectedLocation.value
                                  ?.shortAddress ??
                              'Delivery to selected zone',
                        ),
                        const SizedBox(height: 10),
                        _RestaurantSearch(
                          onChanged: (value) => setState(() => _query = value),
                        ),
                        const SizedBox(height: 12),
                        _FoodCategoryRail(
                          categories: data.foodCategories,
                          selectedId: _foodCategoryId,
                          onSelected: (id) =>
                              setState(() => _foodCategoryId = id),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  const _RestaurantOfferBanner(),
                  const SizedBox(height: 18),
                  _DarkSectionHeader(
                      title: 'Popular Near You',
                      trailing:
                          data.popularRestaurants.isEmpty ? null : 'See all'),
                  const SizedBox(height: 10),
                  _RestaurantRail(
                    restaurants: data.popularRestaurants,
                    onOpen: (restaurant) =>
                        _openDetails(restaurant, data.config),
                  ),
                  const SizedBox(height: 14),
                  if (data.popularRestaurants.isNotEmpty)
                    _DeliveryStatusCard(
                      restaurant: data.popularRestaurants.first,
                      onTrack: () => _openDetails(
                          data.popularRestaurants.first, data.config),
                    ),
                  const SizedBox(height: 18),
                  _DarkSectionHeader(
                    title: 'Trending Dishes',
                    trailing: foodItems.isEmpty ? null : 'See all',
                  ),
                  const SizedBox(height: 10),
                  if (foodItems.isEmpty)
                    const _RestaurantDarkEmpty(
                        message: 'No dishes found for this filter.')
                  else
                    _FoodItemGrid(
                      items: foodItems,
                      onAdd: _addFoodItem,
                      onOpen: _openFoodDetails,
                    ),
                  const SizedBox(height: 18),
                  _DarkSectionHeader(
                    title: 'Reserve a Table',
                    trailing: data.bookTonight.isEmpty ? null : 'Book',
                  ),
                  const SizedBox(height: 10),
                  _RestaurantRail(
                    restaurants: data.bookTonight,
                    onOpen: (restaurant) =>
                        _openDetails(restaurant, data.config),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _openDetails(RestaurantSummary restaurant, RestaurantConfig config) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RestaurantDetailsScreen(
          restaurantId: restaurant.id,
          initial: restaurant,
          config: config,
        ),
      ),
    );
  }

  void _addFoodItem(RestaurantFoodItem item) {
    if (_cart.isNotEmpty &&
        _cart.values.first.item.restaurantId != item.restaurantId) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Order from one restaurant at a time.')),
      );
      return;
    }
    setState(() {
      final current = _cart[item.id];
      _cart[item.id] = RestaurantFoodCartItem(
        item: item,
        quantity: (current?.quantity ?? 0) + 1,
      );
    });
  }

  void _openFoodDetails(RestaurantFoodItem item) {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => RestaurantFoodDetailsScreen(
          item: item,
          onAdd: _addFoodItem,
        ),
      ),
    );
  }

  Future<void> _placeFoodOrder() async {
    if (_cart.isEmpty) return;
    final session = await MartSessionStore().load();
    if (!mounted) return;
    final address =
        ZoneStore.instance.selectedLocation.value?.address ?? 'Lucknow, India';
    final name = session.name.trim().isEmpty ? 'Customer' : session.name.trim();
    final phone =
        session.phone.trim().isEmpty ? '9999999999' : session.phone.trim();
    try {
      final first = _cart.values.first.item;
      await _client.placeFoodOrder(
        restaurantId: first.restaurantId,
        zoneId: ZoneStore.instance.selectedZoneId,
        guestId: session.guestId,
        customerName: name,
        customerPhone: phone,
        customerEmail: session.email,
        deliveryAddress: address,
        paymentMethod: 'cash_on_delivery',
        items: _cart.values.toList(),
      );
      if (!mounted) return;
      setState(_cart.clear);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Food order placed successfully.')),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    }
  }
}

class _RestaurantTopBar extends StatelessWidget {
  const _RestaurantTopBar({required this.onRefresh});

  final VoidCallback onRefresh;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _restaurantBg,
      darkSurface: _restaurantPanel,
      darkCard: _restaurantCard,
      darkLine: _restaurantLine,
      darkText: _restaurantText,
      darkMuted: _restaurantMuted,
    );
    return FutureBuilder(
      future: MartSessionStore().load(),
      builder: (context, snapshot) {
        final name = appDisplayName(snapshot.data?.name);
        return Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: _restaurantRed.withValues(alpha: .18),
              child: Icon(Icons.person_rounded, color: colors.text),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(appGreeting(),
                      style: TextStyle(
                          color: colors.muted,
                          fontSize: 10,
                          fontWeight: FontWeight.w700)),
                  Text(name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                          color: colors.text,
                          fontSize: 14,
                          fontWeight: FontWeight.w900)),
                ],
              ),
            ),
            IconButton(
              tooltip: 'Refresh',
              onPressed: onRefresh,
              icon: Icon(Icons.notifications_none_rounded, color: colors.text),
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

class _RestaurantDeliveryPill extends StatelessWidget {
  const _RestaurantDeliveryPill({required this.address});

  final String address;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _restaurantBg,
      darkSurface: _restaurantPanel,
      darkCard: _restaurantCard,
      darkLine: _restaurantLine,
      darkText: _restaurantText,
      darkMuted: _restaurantMuted,
    );
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 11),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: colors.line),
      ),
      child: Row(
        children: [
          const Icon(Icons.location_on_rounded,
              color: _restaurantRed, size: 16),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              address,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: colors.text,
                fontSize: 12,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
          Icon(Icons.keyboard_arrow_down_rounded,
              color: colors.muted, size: 18),
        ],
      ),
    );
  }
}

class _RestaurantSearch extends StatelessWidget {
  const _RestaurantSearch({required this.onChanged});

  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _restaurantBg,
      darkSurface: _restaurantPanel,
      darkCard: _restaurantCard,
      darkLine: _restaurantLine,
      darkText: _restaurantText,
      darkMuted: _restaurantMuted,
    );
    return TextField(
      onChanged: onChanged,
      style: TextStyle(color: colors.text, fontWeight: FontWeight.w700),
      decoration: InputDecoration(
        hintText: 'Search restaurants, dishes...',
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
          borderSide: const BorderSide(color: _restaurantRed, width: 1.4),
        ),
      ),
    );
  }
}

class _FoodCategoryRail extends StatelessWidget {
  const _FoodCategoryRail({
    required this.categories,
    required this.selectedId,
    required this.onSelected,
  });

  final List<RestaurantFoodCategory> categories;
  final int? selectedId;
  final ValueChanged<int?> onSelected;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _restaurantBg,
      darkSurface: _restaurantPanel,
      darkCard: _restaurantCard,
      darkLine: _restaurantLine,
      darkText: _restaurantText,
      darkMuted: _restaurantMuted,
    );
    final items = <({int? id, String name, IconData icon})>[
      (id: null, name: 'Pizza', icon: Icons.local_pizza_outlined),
      ...categories.map((category) => (
            id: category.id,
            name: category.name,
            icon: _foodIcon(category.icon),
          )),
    ];
    return SizedBox(
      height: 72,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: items.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final item = items[index];
          final selected = selectedId == item.id;
          return InkWell(
            onTap: () => onSelected(item.id),
            borderRadius: BorderRadius.circular(18),
            child: SizedBox(
              width: 64,
              child: Column(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: selected
                          ? _restaurantRed.withValues(alpha: .22)
                          : colors.surface,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(
                        color: selected ? _restaurantRed : colors.line,
                      ),
                    ),
                    child: Icon(item.icon, color: _restaurantRed, size: 22),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    item.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: colors.muted,
                      fontSize: 10,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  IconData _foodIcon(String value) {
    return switch (value) {
      'rice_bowl' => Icons.rice_bowl_outlined,
      'lunch_dining' => Icons.lunch_dining_outlined,
      'restaurant' => Icons.restaurant_outlined,
      'local_pizza' => Icons.local_pizza_outlined,
      _ => Icons.fastfood_outlined,
    };
  }
}

class _RestaurantOfferBanner extends StatelessWidget {
  const _RestaurantOfferBanner();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 116,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        gradient: const LinearGradient(
          colors: [Color(0xFF46120E), Color(0xFFBE251D)],
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
      ),
      child: Stack(
        children: [
          Positioned(
            right: 12,
            top: 14,
            bottom: 14,
            child: Icon(Icons.restaurant_menu_rounded,
                color: Colors.white.withValues(alpha: .16), size: 88),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Text('30% Off',
                    style: TextStyle(
                        color: _restaurantRed,
                        fontSize: 24,
                        fontWeight: FontWeight.w900)),
                const SizedBox(height: 3),
                const Text('First 3 orders this week',
                    style: TextStyle(
                        color: _restaurantText,
                        fontSize: 12,
                        fontWeight: FontWeight.w800)),
                const SizedBox(height: 8),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
                  decoration: BoxDecoration(
                    color: _restaurantRed,
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: const Text('Order Now',
                      style: TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.w900)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _DarkSectionHeader extends StatelessWidget {
  const _DarkSectionHeader({required this.title, this.trailing});

  final String title;
  final String? trailing;

  @override
  Widget build(BuildContext context) {
    final textColor = adaptiveText(context, _restaurantText);
    return Row(
      children: [
        Expanded(
          child: Text(title,
              style: TextStyle(
                  color: textColor, fontSize: 15, fontWeight: FontWeight.w900)),
        ),
        if (trailing != null)
          Text(trailing!,
              style: const TextStyle(
                  color: _restaurantRed,
                  fontSize: 10,
                  fontWeight: FontWeight.w900)),
      ],
    );
  }
}

class _RestaurantRail extends StatelessWidget {
  const _RestaurantRail({required this.restaurants, required this.onOpen});

  final List<RestaurantSummary> restaurants;
  final ValueChanged<RestaurantSummary> onOpen;

  @override
  Widget build(BuildContext context) {
    if (restaurants.isEmpty) {
      return const _RestaurantDarkEmpty(message: 'No restaurants found.');
    }
    final colors = adaptiveModuleColors(
      context,
      darkBg: _restaurantBg,
      darkSurface: _restaurantPanel,
      darkCard: _restaurantCard,
      darkLine: _restaurantLine,
      darkText: _restaurantText,
      darkMuted: _restaurantMuted,
    );
    return SizedBox(
      height: 182,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: restaurants.length,
        separatorBuilder: (_, __) => const SizedBox(width: 12),
        itemBuilder: (context, index) {
          final restaurant = restaurants[index];
          return InkWell(
            onTap: () => onOpen(restaurant),
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
                    _RestaurantImage(url: restaurant.thumbnail, compact: true),
                    const SizedBox(height: 8),
                    Text(restaurant.name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                            color: colors.text,
                            fontSize: 12,
                            fontWeight: FontWeight.w900)),
                    const SizedBox(height: 3),
                    Text(
                      'From ${restaurant.deliveryTimeMinutes == 0 ? 30 : restaurant.deliveryTimeMinutes} min',
                      style: TextStyle(
                          color: colors.muted,
                          fontSize: 10,
                          fontWeight: FontWeight.w700),
                    ),
                    const Spacer(),
                    Row(
                      children: [
                        const Icon(Icons.star_rounded,
                            color: _restaurantRed, size: 12),
                        Text(restaurant.rating.toStringAsFixed(1),
                            style: TextStyle(
                                color: colors.text,
                                fontSize: 10,
                                fontWeight: FontWeight.w900)),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _DeliveryStatusCard extends StatelessWidget {
  const _DeliveryStatusCard({required this.restaurant, required this.onTrack});

  final RestaurantSummary restaurant;
  final VoidCallback onTrack;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _restaurantBg,
      darkSurface: _restaurantPanel,
      darkCard: _restaurantCard,
      darkLine: _restaurantLine,
      darkText: _restaurantText,
      darkMuted: _restaurantMuted,
    );
    final minutes = restaurant.deliveryTimeMinutes == 0
        ? 30
        : restaurant.deliveryTimeMinutes;
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: colors.card,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: colors.line),
      ),
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: _restaurantRed.withValues(alpha: .16),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(Icons.delivery_dining_rounded,
                color: _restaurantRed),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              'Order arriving in $minutes mins\n${restaurant.name}',
              style: TextStyle(
                color: colors.text,
                fontSize: 12,
                height: 1.3,
                fontWeight: FontWeight.w900,
              ),
            ),
          ),
          OutlinedButton(
            onPressed: onTrack,
            style: OutlinedButton.styleFrom(
              foregroundColor: _restaurantRed,
              side: const BorderSide(color: _restaurantRed),
              shape: const StadiumBorder(),
            ),
            child: const Text('Track'),
          ),
        ],
      ),
    );
  }
}

class _FoodItemGrid extends StatelessWidget {
  const _FoodItemGrid({
    required this.items,
    required this.onAdd,
    required this.onOpen,
  });

  final List<RestaurantFoodItem> items;
  final ValueChanged<RestaurantFoodItem> onAdd;
  final ValueChanged<RestaurantFoodItem> onOpen;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _restaurantBg,
      darkSurface: _restaurantPanel,
      darkCard: _restaurantCard,
      darkLine: _restaurantLine,
      darkText: _restaurantText,
      darkMuted: _restaurantMuted,
    );
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: items.length,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
        childAspectRatio: .86,
      ),
      itemBuilder: (context, index) {
        final item = items[index];
        return Material(
          color: colors.card,
          borderRadius: BorderRadius.circular(16),
          child: InkWell(
            onTap: () => onOpen(item),
            borderRadius: BorderRadius.circular(16),
            child: Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: colors.line),
              ),
              child: Padding(
                padding: const EdgeInsets.all(8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: _RestaurantImage(url: item.image, fill: true),
                    ),
                    const SizedBox(height: 8),
                    Text(item.name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                            color: colors.text,
                            fontSize: 12,
                            fontWeight: FontWeight.w900)),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                              '₹${item.sellingPrice.toStringAsFixed(0)}',
                              style: const TextStyle(
                                  color: _restaurantRed,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w900)),
                        ),
                        InkWell(
                          onTap: item.stock <= 0 ? null : () => onAdd(item),
                          borderRadius: BorderRadius.circular(999),
                          child: Container(
                            width: 28,
                            height: 28,
                            decoration: BoxDecoration(
                              color: item.stock <= 0
                                  ? colors.line
                                  : _restaurantRed,
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.add_rounded,
                                color: Colors.white, size: 18),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}

class RestaurantFoodDetailsScreen extends StatelessWidget {
  const RestaurantFoodDetailsScreen({
    super.key,
    required this.item,
    required this.onAdd,
  });

  final RestaurantFoodItem item;
  final ValueChanged<RestaurantFoodItem> onAdd;

  @override
  Widget build(BuildContext context) {
    final dark = appIsDark(context);
    final bg = adaptiveScaffold(context, _restaurantBg);
    final card = adaptiveSurface(context, _restaurantCard);
    final text = adaptiveText(context, _restaurantText);
    final muted = adaptiveMuted(context, _restaurantMuted);
    final line = adaptiveLine(context, _restaurantLine);
    return Scaffold(
      backgroundColor: bg,
      appBar: AppBar(
        backgroundColor: bg,
        foregroundColor: text,
        surfaceTintColor: Colors.transparent,
        title: Text(item.name),
        actions: [
          IconButton(
            tooltip: 'Search',
            onPressed: () {},
            icon: const Icon(Icons.search_rounded),
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        child: FilledButton.icon(
          onPressed: item.stock <= 0
              ? null
              : () {
                  onAdd(item);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('${item.name} added to cart.')),
                  );
                },
          style: FilledButton.styleFrom(
            backgroundColor: _restaurantRed,
            foregroundColor: Colors.white,
            minimumSize: const Size.fromHeight(56),
          ),
          icon: const Icon(Icons.add_shopping_cart_rounded),
          label: Text(item.stock <= 0 ? 'Out of stock' : 'Add to cart'),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
        children: [
          AspectRatio(
            aspectRatio: 1.05,
            child: _RestaurantImage(url: item.image, fill: true),
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: card,
              borderRadius: BorderRadius.circular(22),
              border: Border.all(color: line),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Text(
                        item.name,
                        style: TextStyle(
                          color: text,
                          fontSize: 22,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 10, vertical: 7),
                      decoration: BoxDecoration(
                        color: _restaurantRed.withValues(alpha: .12),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        item.categoryName,
                        style: const TextStyle(
                          color: _restaurantRed,
                          fontSize: 11,
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  item.restaurantName,
                  style: TextStyle(
                    color: muted,
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Text(
                      '₹${item.sellingPrice.toStringAsFixed(0)}',
                      style: TextStyle(
                        color: dark ? _restaurantRed : AppTheme.danger,
                        fontSize: 26,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    if (item.hasDiscount) ...[
                      const SizedBox(width: 10),
                      Text(
                        '₹${item.price.toStringAsFixed(0)}',
                        style: TextStyle(
                          color: muted,
                          decoration: TextDecoration.lineThrough,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  item.description.trim().isEmpty
                      ? 'Freshly prepared by ${item.restaurantName}.'
                      : item.description,
                  style: TextStyle(
                    color: muted,
                    height: 1.45,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: card,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: line),
            ),
            child: Row(
              children: [
                _FoodBenefit(
                  icon: Icons.delivery_dining_rounded,
                  title: 'Delivery',
                  value: 'Zone based',
                  text: text,
                  muted: muted,
                ),
                const SizedBox(width: 12),
                _FoodBenefit(
                  icon: Icons.verified_rounded,
                  title: 'Restaurant',
                  value: 'Admin managed',
                  text: text,
                  muted: muted,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _FoodBenefit extends StatelessWidget {
  const _FoodBenefit({
    required this.icon,
    required this.title,
    required this.value,
    required this.text,
    required this.muted,
  });

  final IconData icon;
  final String title;
  final String value;
  final Color text;
  final Color muted;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Row(
        children: [
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: _restaurantRed.withValues(alpha: .12),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: _restaurantRed, size: 19),
          ),
          const SizedBox(width: 9),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(color: text, fontWeight: FontWeight.w900)),
                Text(value,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        color: muted,
                        fontSize: 11,
                        fontWeight: FontWeight.w700)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _RestaurantCartBar extends StatelessWidget {
  const _RestaurantCartBar({
    required this.count,
    required this.total,
    required this.onCheckout,
  });

  final int count;
  final double total;
  final VoidCallback onCheckout;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _restaurantBg,
      darkSurface: _restaurantPanel,
      darkCard: _restaurantCard,
      darkLine: _restaurantLine,
      darkText: _restaurantText,
      darkMuted: _restaurantMuted,
    );
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: colors.surface,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: colors.line),
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              '$count item${count == 1 ? '' : 's'} • ₹${total.toStringAsFixed(0)}',
              style: TextStyle(color: colors.text, fontWeight: FontWeight.w900),
            ),
          ),
          FilledButton(
            onPressed: onCheckout,
            style: FilledButton.styleFrom(backgroundColor: _restaurantRed),
            child: const Text('Place COD Order'),
          ),
        ],
      ),
    );
  }
}

class _RestaurantDarkEmpty extends StatelessWidget {
  const _RestaurantDarkEmpty({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    final colors = adaptiveModuleColors(
      context,
      darkBg: _restaurantBg,
      darkSurface: _restaurantPanel,
      darkCard: _restaurantCard,
      darkLine: _restaurantLine,
      darkText: _restaurantText,
      darkMuted: _restaurantMuted,
    );
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: colors.card,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: colors.line),
      ),
      child: Text(message,
          textAlign: TextAlign.center,
          style: TextStyle(color: colors.muted, fontWeight: FontWeight.w800)),
    );
  }
}

class _RestaurantImage extends StatelessWidget {
  const _RestaurantImage({this.url, this.compact = false, this.fill = false});

  final String? url;
  final bool compact;
  final bool fill;

  @override
  Widget build(BuildContext context) {
    final value = _imageUrl(url?.trim() ?? '');
    final width = compact || fill ? double.infinity : 86.0;
    final height = compact ? 82.0 : (fill ? null : 96.0);
    return ClipRRect(
      borderRadius: BorderRadius.circular(18),
      child: Container(
        width: width,
        height: height ?? double.infinity,
        color: const Color(0xFF261412),
        child: value.isEmpty
            ? const Icon(Icons.restaurant_rounded,
                color: Color(0xFFE0444E), size: 34)
            : Image.network(
                value,
                fit: BoxFit.cover,
                loadingBuilder: (context, child, progress) {
                  if (progress == null) return child;
                  return const AppDelayedSkeletonBox(height: 96, radius: 18);
                },
                errorBuilder: (_, __, ___) => const Icon(
                  Icons.restaurant_rounded,
                  color: Color(0xFFE0444E),
                  size: 34,
                ),
              ),
      ),
    );
  }

  String _imageUrl(String value) {
    if (value.isEmpty || value.startsWith('http')) return value;
    final origin = RestaurantApiConfig.baseUrl.split('/api/').first;
    return '$origin${value.startsWith('/') ? value : '/$value'}';
  }
}

class RestaurantDetailsScreen extends StatefulWidget {
  const RestaurantDetailsScreen({
    super.key,
    required this.restaurantId,
    required this.initial,
    required this.config,
  });

  final int restaurantId;
  final RestaurantSummary initial;
  final RestaurantConfig config;

  @override
  State<RestaurantDetailsScreen> createState() =>
      _RestaurantDetailsScreenState();
}

class _RestaurantDetailsScreenState extends State<RestaurantDetailsScreen> {
  final _client = RestaurantApiClient();
  late Future<RestaurantDetails> _future;

  @override
  void initState() {
    super.initState();
    _future = _client.fetchRestaurant(widget.restaurantId);
  }

  @override
  Widget build(BuildContext context) {
    final bg = adaptiveScaffold(context, _restaurantBg);
    final text = adaptiveText(context, Theme.of(context).colorScheme.onSurface);
    final muted =
        adaptiveMuted(context, Theme.of(context).colorScheme.onSurfaceVariant);
    return Scaffold(
      backgroundColor: bg,
      appBar: AppBar(
        backgroundColor: bg,
        foregroundColor: text,
        surfaceTintColor: Colors.transparent,
        title: Text(widget.initial.name),
      ),
      bottomNavigationBar: SafeArea(
        minimum: const EdgeInsets.fromLTRB(16, 8, 16, 16),
        child: FilledButton.icon(
          onPressed: _openBookingSheet,
          icon: const Icon(Icons.event_seat_outlined),
          label: const Text('Reserve Table'),
          style: FilledButton.styleFrom(
            backgroundColor: const Color(0xFFE0444E),
            minimumSize: const Size.fromHeight(54),
          ),
        ),
      ),
      body: FutureBuilder<RestaurantDetails>(
        future: _future,
        builder: (context, snapshot) {
          final restaurant = snapshot.data?.restaurant ?? widget.initial;
          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(26),
                child: Container(
                  height: 230,
                  color: adaptiveSurface(context, const Color(0xFF261412)),
                  child: restaurant.thumbnail == null ||
                          restaurant.thumbnail!.trim().isEmpty
                      ? const Center(
                          child: Icon(Icons.restaurant_rounded,
                              color: Color(0xFFE0444E), size: 72),
                        )
                      : Image.network(
                          restaurant.thumbnail!,
                          fit: BoxFit.cover,
                          loadingBuilder: (context, child, progress) {
                            if (progress == null) return child;
                            return const AppDelayedSkeletonBox(
                                height: 230, radius: 26);
                          },
                          errorBuilder: (_, __, ___) => const Center(
                            child: Icon(Icons.restaurant_rounded,
                                color: Color(0xFFE0444E), size: 72),
                          ),
                        ),
                ),
              ),
              const SizedBox(height: 18),
              _DetailCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Text(
                            restaurant.name,
                            style: TextStyle(
                              color: text,
                              fontSize: 24,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ),
                        _RatingPill(rating: restaurant.rating),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      restaurant.cuisine,
                      style: TextStyle(
                        color: muted,
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 14),
                    _IconLine(
                      icon: Icons.location_on_outlined,
                      text: restaurant.address,
                    ),
                    const SizedBox(height: 10),
                    _IconLine(
                      icon: Icons.payments_outlined,
                      text:
                          'Approx ₹${restaurant.averageCost.toStringAsFixed(0)} for two',
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),
              const _DetailCard(
                child: Row(
                  children: [
                    Expanded(
                      child: _Benefit(
                        icon: Icons.event_available_outlined,
                        title: 'Live slots',
                        subtitle: 'Book by table capacity',
                      ),
                    ),
                    SizedBox(width: 12),
                    Expanded(
                      child: _Benefit(
                        icon: Icons.support_agent_outlined,
                        title: 'Support',
                        subtitle: 'Cancel request to admin',
                      ),
                    ),
                  ],
                ),
              ),
              if (snapshot.connectionState == ConnectionState.waiting)
                const Padding(
                  padding: EdgeInsets.only(top: 20),
                  child: AppSkeletonList(cardCount: 2),
                ),
              if (snapshot.hasError)
                Container(
                  margin: const EdgeInsets.only(top: 20),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: AppTheme.danger.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(
                      color: AppTheme.danger.withValues(alpha: 0.18),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Some restaurant details could not load.',
                        style: TextStyle(
                          fontWeight: FontWeight.w900,
                          height: 1.35,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        AppErrorState.userMessage(snapshot.error),
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          height: 1.35,
                        ),
                      ),
                      const SizedBox(height: 12),
                      OutlinedButton.icon(
                        onPressed: () {
                          setState(() {
                            _future = _client.fetchRestaurant(
                              widget.restaurantId,
                            );
                          });
                        },
                        icon: const Icon(Icons.refresh_rounded),
                        label: const Text('Retry'),
                      ),
                    ],
                  ),
                ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _openBookingSheet() async {
    final session = await MartSessionStore().load();
    if (!mounted) return;
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      backgroundColor: adaptiveSurface(context, _restaurantPanel),
      builder: (_) => _RestaurantBookingSheet(
        restaurant: widget.initial,
        session: session,
        client: _client,
        paymentMethods: widget.config.paymentMethods,
        policyPages: widget.config.cmsPages,
      ),
    );
  }
}

class _RestaurantBookingSheet extends StatefulWidget {
  const _RestaurantBookingSheet({
    required this.restaurant,
    required this.session,
    required this.client,
    required this.paymentMethods,
    required this.policyPages,
  });

  final RestaurantSummary restaurant;
  final MartCustomerSession session;
  final RestaurantApiClient client;
  final List<RestaurantPaymentMethod> paymentMethods;
  final List<RestaurantCmsPage> policyPages;

  @override
  State<_RestaurantBookingSheet> createState() =>
      _RestaurantBookingSheetState();
}

class _RestaurantBookingSheetState extends State<_RestaurantBookingSheet> {
  DateTime _date = DateTime.now();
  int _partySize = 2;
  RestaurantSlot? _slot;
  TimeOfDay _waitlistTime = const TimeOfDay(hour: 19, minute: 30);
  bool _submitting = false;
  bool _joiningWaitlist = false;
  String _paymentMethod = 'pay_at_restaurant';
  late Future<List<RestaurantSlot>> _slotsFuture;
  late final TextEditingController _name;
  late final TextEditingController _phone;
  late final TextEditingController _email;
  late final TextEditingController _note;
  late final TextEditingController _paymentReference;

  @override
  void initState() {
    super.initState();
    _name = TextEditingController(text: widget.session.name);
    _phone = TextEditingController(text: widget.session.phone);
    _email = TextEditingController(text: widget.session.email);
    _note = TextEditingController();
    _paymentReference = TextEditingController();
    if (_effectivePaymentMethods.isNotEmpty) {
      _paymentMethod = _effectivePaymentMethods.first.id;
    }
    _slotsFuture = _loadSlots();
  }

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _email.dispose();
    _note.dispose();
    _paymentReference.dispose();
    super.dispose();
  }

  List<RestaurantPaymentMethod> get _effectivePaymentMethods {
    if (widget.paymentMethods.isNotEmpty) return widget.paymentMethods;
    return const [
      RestaurantPaymentMethod(
        id: 'pay_at_restaurant',
        title: 'Pay At Restaurant',
        description: 'Pay after dining at the restaurant.',
        requiresReference: false,
        gateway: '',
        instructions: '',
      ),
    ];
  }

  RestaurantPaymentMethod get _selectedPaymentMethod {
    return _effectivePaymentMethods.firstWhere(
      (method) => method.id == _paymentMethod,
      orElse: () => _effectivePaymentMethods.first,
    );
  }

  Future<List<RestaurantSlot>> _loadSlots() {
    return widget.client.fetchSlots(
      restaurantId: widget.restaurant.id,
      date: _dateString,
      partySize: _partySize,
    );
  }

  String get _dateString {
    return '${_date.year.toString().padLeft(4, '0')}-${_date.month.toString().padLeft(2, '0')}-${_date.day.toString().padLeft(2, '0')}';
  }

  String get _waitlistTimeString {
    return '${_waitlistTime.hour.toString().padLeft(2, '0')}:${_waitlistTime.minute.toString().padLeft(2, '0')}';
  }

  List<RestaurantSlot> _uniqueSlots(List<RestaurantSlot> slots) {
    final unique = <String, RestaurantSlot>{};
    for (final slot in slots) {
      final key = slot.time.trim();
      if (key.isEmpty) continue;
      unique.putIfAbsent(key, () => slot);
    }
    final result = unique.values.toList()
      ..sort((a, b) => _slotMinutes(a.time).compareTo(_slotMinutes(b.time)));
    return result;
  }

  int _slotMinutes(String value) {
    final parts = value.split(':');
    if (parts.length < 2) return 0;
    final hour = int.tryParse(parts[0]) ?? 0;
    final minute = int.tryParse(parts[1]) ?? 0;
    return hour * 60 + minute;
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 12,
        bottom: MediaQuery.of(context).viewInsets.bottom + 16,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Theme.of(context).dividerColor,
                  borderRadius: BorderRadius.circular(99),
                ),
              ),
            ),
            const SizedBox(height: 18),
            Text(
              'Reserve ${widget.restaurant.name}',
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w900,
              ),
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _pickDate,
                    icon: const Icon(Icons.calendar_today_outlined),
                    label: Text(_dateString),
                  ),
                ),
                const SizedBox(width: 10),
                _Stepper(
                  value: _partySize,
                  onChanged: (value) {
                    setState(() {
                      _partySize = value;
                      _slot = null;
                      _slotsFuture = _loadSlots();
                    });
                  },
                ),
              ],
            ),
            const SizedBox(height: 14),
            FutureBuilder<List<RestaurantSlot>>(
              future: _slotsFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const AppSkeletonList(cardCount: 2);
                }
                final slots =
                    _uniqueSlots(snapshot.data ?? const <RestaurantSlot>[]);
                if (slots.isEmpty) {
                  return _WaitlistPrompt(
                    date: _dateString,
                    time: _waitlistTimeString,
                    partySize: _partySize,
                    loading: _joiningWaitlist,
                    onPickTime: _pickWaitlistTime,
                    onJoin: _joinWaitlist,
                  );
                }
                return _SlotPickerPanel(
                  date: _dateString,
                  partySize: _partySize,
                  slots: slots,
                  selectedSlot: _slot,
                  onSelected: (slot) => setState(() => _slot = slot),
                );
              },
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _name,
              decoration: const InputDecoration(labelText: 'Name'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _phone,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(labelText: 'Phone'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _email,
              keyboardType: TextInputType.emailAddress,
              decoration: const InputDecoration(labelText: 'Email optional'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _note,
              minLines: 2,
              maxLines: 3,
              decoration:
                  const InputDecoration(labelText: 'Special request optional'),
            ),
            const SizedBox(height: 14),
            _RestaurantPaymentSection(
              methods: _effectivePaymentMethods,
              selectedMethodId: _paymentMethod,
              referenceController: _paymentReference,
              onChanged: (value) => setState(() => _paymentMethod = value),
            ),
            const SizedBox(height: 14),
            _RestaurantPolicyCard(pages: widget.policyPages),
            const SizedBox(height: 16),
            FilledButton.icon(
              onPressed: _submitting ? null : _submit,
              icon: _submitting
                  ? const AppSkeletonBox(width: 18, height: 18, radius: 9)
                  : const Icon(Icons.check_circle_outline_rounded),
              label: const Text('Confirm Reservation'),
              style: FilledButton.styleFrom(
                backgroundColor: const Color(0xFFE0444E),
                minimumSize: const Size.fromHeight(54),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 45)),
    );
    if (picked == null) return;
    setState(() {
      _date = picked;
      _slot = null;
      _slotsFuture = _loadSlots();
    });
  }

  Future<void> _pickWaitlistTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: _waitlistTime,
    );
    if (picked == null) return;
    setState(() => _waitlistTime = picked);
  }

  Future<void> _submit() async {
    if (_slot == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select an available time slot')),
      );
      return;
    }
    if (_name.text.trim().isEmpty || _phone.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Name and phone are required')),
      );
      return;
    }
    if (_selectedPaymentMethod.requiresReference &&
        _paymentReference.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
              'Payment reference is required for ${_selectedPaymentMethod.title}'),
        ),
      );
      return;
    }
    setState(() => _submitting = true);
    try {
      final response = await widget.client.placeBooking(
        restaurantId: widget.restaurant.id,
        zoneId: ZoneStore.instance.selectedZoneId,
        guestId: widget.session.guestId,
        customerName: _name.text.trim(),
        customerPhone: _phone.text.trim(),
        customerEmail: _email.text.trim(),
        bookingDate: _dateString,
        bookingTime: _slot!.time,
        partySize: _partySize,
        paymentMethod: _paymentMethod,
        paymentReference: _paymentReference.text.trim(),
        specialRequest: _note.text.trim(),
      );
      if (!mounted) return;
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            response['message']?.toString() ??
                'Restaurant booking placed successfully',
          ),
        ),
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

  Future<void> _joinWaitlist() async {
    if (_name.text.trim().isEmpty || _phone.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Name and phone are required')),
      );
      return;
    }
    setState(() => _joiningWaitlist = true);
    try {
      final response = await widget.client.joinWaitlist(
        restaurantId: widget.restaurant.id,
        zoneId: ZoneStore.instance.selectedZoneId,
        guestId: widget.session.guestId,
        customerName: _name.text.trim(),
        customerPhone: _phone.text.trim(),
        customerEmail: _email.text.trim(),
        bookingDate: _dateString,
        bookingTime: _waitlistTimeString,
        partySize: _partySize,
        specialRequest: _note.text.trim(),
      );
      if (!mounted) return;
      Navigator.of(context).pop();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            response['message']?.toString() ??
                'Waitlist request sent successfully',
          ),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppErrorState.userMessage(error))),
      );
    } finally {
      if (mounted) setState(() => _joiningWaitlist = false);
    }
  }
}

class _WaitlistPrompt extends StatelessWidget {
  const _WaitlistPrompt({
    required this.date,
    required this.time,
    required this.partySize,
    required this.loading,
    required this.onPickTime,
    required this.onJoin,
  });

  final String date;
  final String time;
  final int partySize;
  final bool loading;
  final VoidCallback onPickTime;
  final VoidCallback onJoin;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _restaurantCard),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: adaptiveLine(context, _restaurantLine)),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFE0444E).withValues(alpha: .05),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF4F1),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Icon(Icons.schedule_rounded,
                    color: Color(0xFFE0444E)),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Text(
                  'No confirmed slots available',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            'Pick a preferred time and send a waitlist request for $date · $partySize guest(s).',
            style: const TextStyle(
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: loading ? null : onPickTime,
                  icon: const Icon(Icons.schedule_rounded),
                  label: Text('Preferred $time'),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: FilledButton.icon(
                  onPressed: loading ? null : onJoin,
                  icon: loading
                      ? const AppSkeletonBox(width: 16, height: 16, radius: 8)
                      : const Icon(Icons.hourglass_top_rounded),
                  label: const Text('Join Waitlist'),
                  style: FilledButton.styleFrom(
                    backgroundColor: const Color(0xFFE0444E),
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

class _SlotPickerPanel extends StatelessWidget {
  const _SlotPickerPanel({
    required this.date,
    required this.partySize,
    required this.slots,
    required this.selectedSlot,
    required this.onSelected,
  });

  final String date;
  final int partySize;
  final List<RestaurantSlot> slots;
  final RestaurantSlot? selectedSlot;
  final ValueChanged<RestaurantSlot> onSelected;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _restaurantCard),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: adaptiveLine(context, _restaurantLine)),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: .04),
            blurRadius: 18,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Expanded(
                child: Text(
                  'Available times',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              Text(
                '$date · $partySize guests',
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          LayoutBuilder(
            builder: (context, constraints) {
              final columns = constraints.maxWidth >= 420 ? 5 : 4;
              return GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: slots.length,
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: columns,
                  crossAxisSpacing: 8,
                  mainAxisSpacing: 8,
                  childAspectRatio: 2.15,
                ),
                itemBuilder: (context, index) {
                  final slot = slots[index];
                  final selected = selectedSlot?.time == slot.time;
                  return Material(
                    color: selected
                        ? const Color(0xFFFFE0D8)
                        : const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(13),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(13),
                      onTap: () => onSelected(slot),
                      child: Container(
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(13),
                          border: Border.all(
                            color: selected
                                ? const Color(0xFFE0444E)
                                : Theme.of(context).dividerColor,
                          ),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            if (selected) ...[
                              const Icon(Icons.check_rounded,
                                  size: 16, color: Color(0xFFE0444E)),
                              const SizedBox(width: 4),
                            ],
                            Flexible(
                              child: Text(
                                slot.time,
                                overflow: TextOverflow.ellipsis,
                                style: TextStyle(
                                  color: selected
                                      ? const Color(0xFFE0444E)
                                      : Theme.of(context).colorScheme.onSurface,
                                  fontWeight: FontWeight.w900,
                                  fontSize: 12,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                },
              );
            },
          ),
          if (selectedSlot != null) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFFFF4F1),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Text(
                'Selected ${selectedSlot!.time} for $partySize guest(s).',
                style: const TextStyle(
                  color: Color(0xFFE0444E),
                  fontWeight: FontWeight.w900,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _RestaurantPaymentSection extends StatelessWidget {
  const _RestaurantPaymentSection({
    required this.methods,
    required this.selectedMethodId,
    required this.referenceController,
    required this.onChanged,
  });

  final List<RestaurantPaymentMethod> methods;
  final String selectedMethodId;
  final TextEditingController referenceController;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    final selected = methods.firstWhere(
      (method) => method.id == selectedMethodId,
      orElse: () => methods.first,
    );
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Payment Method',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 10),
        for (final method in methods)
          _RestaurantPaymentTile(
            method: method,
            selected: method.id == selectedMethodId,
            onTap: () => onChanged(method.id),
          ),
        if (selected.instructions.trim().isNotEmpty) ...[
          const SizedBox(height: 2),
          Text(
            selected.instructions,
            style: const TextStyle(
              fontWeight: FontWeight.w700,
              height: 1.35,
            ),
          ),
        ],
        if (selected.requiresReference) ...[
          const SizedBox(height: 10),
          TextField(
            controller: referenceController,
            decoration: InputDecoration(
              labelText: selected.id == 'bank_transfer'
                  ? 'Bank / UPI reference'
                  : 'Payment reference',
              prefixIcon: const Icon(Icons.receipt_long_outlined),
            ),
          ),
        ],
      ],
    );
  }
}

class _RestaurantPaymentTile extends StatelessWidget {
  const _RestaurantPaymentTile({
    required this.method,
    required this.selected,
    required this.onTap,
  });

  final RestaurantPaymentMethod method;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: adaptiveSurface(context, _restaurantCard),
        borderRadius: BorderRadius.circular(18),
        child: InkWell(
          borderRadius: BorderRadius.circular(18),
          onTap: onTap,
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: selected
                  ? const Color(0xFFE0444E).withValues(alpha: .07)
                  : adaptiveSurface(context, _restaurantCard),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: selected ? const Color(0xFFE0444E) : Theme.of(context).dividerColor,
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
                      ? const Color(0xFFE0444E)
                      : Theme.of(context).colorScheme.onSurfaceVariant,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        method.title,
                        style: const TextStyle(
                          fontWeight: FontWeight.w900,
                        ),
                      ),
                      if (method.description.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          method.description,
                          style: const TextStyle(),
                        ),
                      ],
                    ],
                  ),
                ),
                if (method.requiresReference)
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFECE7),
                      borderRadius: BorderRadius.circular(99),
                    ),
                    child: const Text(
                      'Ref',
                      style: TextStyle(
                        color: Color(0xFFE0444E),
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                      ),
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

class _RestaurantPolicyCard extends StatelessWidget {
  const _RestaurantPolicyCard({required this.pages});

  final List<RestaurantCmsPage> pages;

  @override
  Widget build(BuildContext context) {
    final visible = pages
        .where((page) =>
            {
              'terms-conditions',
              'cancellation-policy',
            }.contains(page.slug) &&
            page.content.trim().isNotEmpty)
        .toList();
    const fallback =
        'Reservations and waitlist requests are reviewed by the restaurant/admin. Cancellation requests do not cancel immediately until admin confirms.';
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _restaurantCard),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: adaptiveLine(context, _restaurantLine)),
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
              color: const Color(0xFFE0444E).withValues(alpha: .1),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(
              Icons.policy_outlined,
              color: Color(0xFFE0444E),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Before you reserve',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 6),
                if (visible.isEmpty)
                  const Text(
                    fallback,
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      height: 1.35,
                    ),
                  )
                else
                  for (final page in visible.take(2))
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

class _Stepper extends StatelessWidget {
  const _Stepper({required this.value, required this.onChanged});

  final int value;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 48,
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _restaurantCard),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: adaptiveLine(context, _restaurantLine)),
      ),
      child: Row(
        children: [
          IconButton(
            onPressed: value <= 1 ? null : () => onChanged(value - 1),
            icon: const Icon(Icons.remove_rounded),
          ),
          Text(
            '$value',
            style: const TextStyle(fontWeight: FontWeight.w900),
          ),
          IconButton(
            onPressed: value >= 12 ? null : () => onChanged(value + 1),
            icon: const Icon(Icons.add_rounded),
          ),
        ],
      ),
    );
  }
}

class RestaurantBookingsScreen extends StatefulWidget {
  const RestaurantBookingsScreen({super.key, this.showAppBar = true});

  final bool showAppBar;

  @override
  State<RestaurantBookingsScreen> createState() =>
      _RestaurantBookingsScreenState();
}

class _RestaurantBookingsScreenState extends State<RestaurantBookingsScreen> {
  final _client = RestaurantApiClient();
  late Future<_RestaurantActivityData> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<_RestaurantActivityData> _load() async {
    final session = await MartSessionStore().load();
    if (!session.isLoggedIn) return const _RestaurantActivityData();
    final results = await Future.wait([
      _client.fetchBookings(session.guestId),
      _client.fetchWaitlist(session.guestId),
    ]);
    return _RestaurantActivityData(
      bookings: results[0] as List<RestaurantBooking>,
      waitlist: results[1] as List<RestaurantWaitlistRequest>,
    );
  }

  @override
  Widget build(BuildContext context) {
    final content = FutureBuilder<_RestaurantActivityData>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const AppSkeletonPage(
            layout: AppSkeletonLayout.detail,
          );
        }
        final data = snapshot.data ?? const _RestaurantActivityData();
        if (data.bookings.isEmpty && data.waitlist.isEmpty) {
          return const _EmptyState(
            title: 'No restaurant bookings yet',
            message:
                'Your table reservations and waitlist requests will appear here.',
            icon: Icons.restaurant_outlined,
          );
        }
        return RefreshIndicator(
          onRefresh: () async => setState(() => _future = _load()),
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (data.waitlist.isNotEmpty) ...[
                const Text(
                  'Waitlist',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 10),
                for (final item in data.waitlist) ...[
                  _RestaurantWaitlistCard(request: item),
                  const SizedBox(height: 12),
                ],
                const SizedBox(height: 8),
              ],
              if (data.bookings.isNotEmpty) ...[
                const Text(
                  'Bookings',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 10),
                for (final booking in data.bookings) ...[
                  _RestaurantBookingCard(booking: booking),
                  const SizedBox(height: 12),
                ],
              ],
            ],
          ),
        );
      },
    );
    if (!widget.showAppBar) return content;
    return Scaffold(
      appBar: AppBar(title: const Text('Restaurant Bookings')),
      body: content,
    );
  }
}

class _RestaurantActivityData {
  const _RestaurantActivityData({
    this.bookings = const <RestaurantBooking>[],
    this.waitlist = const <RestaurantWaitlistRequest>[],
  });

  final List<RestaurantBooking> bookings;
  final List<RestaurantWaitlistRequest> waitlist;
}

class _RestaurantWaitlistCard extends StatelessWidget {
  const _RestaurantWaitlistCard({required this.request});

  final RestaurantWaitlistRequest request;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _restaurantCard),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: adaptiveLine(context, _restaurantLine)),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: const Color(0xFFFFF4F1),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(
              Icons.hourglass_top_rounded,
              color: Color(0xFFE0444E),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  request.restaurantName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${request.bookingDate} at ${request.bookingTime} · ${request.partySize} guests',
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (request.adminNote.trim().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(
                    request.adminNote,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ],
            ),
          ),
          _StatusBadge(status: request.status),
        ],
      ),
    );
  }
}

class _RestaurantBookingCard extends StatelessWidget {
  const _RestaurantBookingCard({required this.booking});

  final RestaurantBooking booking;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _restaurantCard),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: adaptiveLine(context, _restaurantLine)),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: const Color(0xFFFFECE7),
              borderRadius: BorderRadius.circular(16),
            ),
            child:
                const Icon(Icons.restaurant_rounded, color: Color(0xFFE0444E)),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  booking.restaurantName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontWeight: FontWeight.w900,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${booking.bookingDate} at ${booking.bookingTime} · ${booking.partySize} guests',
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
          _StatusBadge(status: booking.status),
        ],
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: _color.withValues(alpha: .12),
        borderRadius: BorderRadius.circular(99),
      ),
      child: Text(
        status.replaceAll('_', ' '),
        style: TextStyle(
          color: _color,
          fontSize: 11,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }

  Color get _color {
    switch (status) {
      case 'confirmed':
      case 'completed':
        return AppTheme.success;
      case 'cancel_requested':
      case 'cancelled':
      case 'rejected':
        return AppTheme.danger;
      default:
        return AppTheme.primary;
    }
  }
}

class _DetailCard extends StatelessWidget {
  const _DetailCard({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: adaptiveSurface(context, _restaurantCard),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: adaptiveLine(context, _restaurantLine)),
      ),
      child: child,
    );
  }
}

class _RatingPill extends StatelessWidget {
  const _RatingPill({required this.rating});

  final double rating;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: AppTheme.success,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.star_rounded, color: Colors.white, size: 15),
          const SizedBox(width: 3),
          Text(
            rating.toStringAsFixed(1),
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _IconLine extends StatelessWidget {
  const _IconLine({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, color: const Color(0xFFE0444E), size: 18),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: TextStyle(
              color: adaptiveText(
                  context, Theme.of(context).colorScheme.onSurface),
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ],
    );
  }
}

class _Benefit extends StatelessWidget {
  const _Benefit({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: adaptiveScaffold(context, _restaurantBg),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        children: [
          Icon(icon, color: const Color(0xFFE0444E)),
          const SizedBox(height: 8),
          Text(
            title,
            textAlign: TextAlign.center,
            style: TextStyle(
              color: adaptiveText(
                  context, Theme.of(context).colorScheme.onSurface),
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: TextStyle(
              color: adaptiveMuted(
                  context, Theme.of(context).colorScheme.onSurfaceVariant),
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _RestaurantVersionNotice extends StatelessWidget {
  const _RestaurantVersionNotice({
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
        color: const Color(0xFFFFEEF0),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFFFC5CB)),
      ),
      child: Row(
        children: [
          const Icon(Icons.system_update_alt_rounded, color: Color(0xFFE0444E)),
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

class _EmptyState extends StatelessWidget {
  const _EmptyState({
    required this.title,
    required this.message,
    this.icon = Icons.cloud_off_outlined,
    this.onRetry,
  });

  final String title;
  final String message;
  final IconData icon;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return AppActivityEmptyState(
      icon: icon,
      title: title,
      detail: message,
      accent: const Color(0xFFE0444E),
      action: onRetry == null
          ? null
          : FilledButton(onPressed: onRetry, child: const Text('Retry')),
    );
  }
}
