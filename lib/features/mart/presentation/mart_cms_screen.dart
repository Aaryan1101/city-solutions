import 'package:flutter/material.dart';

import '../domain/mart_i18n.dart';
import '../domain/mart_models.dart';

class MartCmsScreen extends StatelessWidget {
  const MartCmsScreen({
    super.key,
    required this.pages,
  });

  final List<MartCmsPage> pages;

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder<String>(
      valueListenable: MartLocaleController.locale,
      builder: (context, locale, _) => DefaultTabController(
        length: pages.length,
        child: Scaffold(
          backgroundColor: Theme.of(context).scaffoldBackgroundColor,
          appBar: AppBar(
            title: Text(MartI18n.text('policies_info', locale)),
            bottom: TabBar(
              isScrollable: true,
              tabs: pages
                  .map((page) => Tab(text: _localizedTitle(page, locale)))
                  .toList(),
            ),
          ),
          body: TabBarView(
            children: pages
                .map(
                  (page) => SingleChildScrollView(
                    padding: const EdgeInsets.all(16),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: Theme.of(context).colorScheme.surface,
                        borderRadius: BorderRadius.circular(8),
                        border:
                            Border.all(color: Theme.of(context).dividerColor),
                      ),
                      child: Text(
                        page.content.trim().isEmpty
                            ? _emptyState(page, locale)
                            : page.content,
                        style: const TextStyle(
                          height: 1.5,
                        ),
                      ),
                    ),
                  ),
                )
                .toList(),
          ),
        ),
      ),
    );
  }

  String _localizedTitle(MartCmsPage page, String locale) {
    return switch (page.slug) {
      'about-us' => MartI18n.text('about_us', locale),
      'terms-conditions' => MartI18n.text('terms_conditions', locale),
      'privacy-policy' => MartI18n.text('privacy_policy', locale),
      'refund-policy' => MartI18n.text('refund_policy', locale),
      'shipping-policy' => MartI18n.text('shipping_policy', locale),
      'support' => MartI18n.text('support', locale),
      _ => page.title,
    };
  }

  String _emptyState(MartCmsPage page, String locale) {
    return '${_localizedTitle(page, locale)} will appear here after admin updates.';
  }
}
