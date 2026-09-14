import 'package:flutter/material.dart';

import '../domain/ecommerce_models.dart';

class EcommerceOrderSuccessScreen extends StatelessWidget {
  const EcommerceOrderSuccessScreen({super.key, required this.result});

  final EcommerceOrderResult result;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(28),
            child: Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(28),
                border: Border.all(color: Theme.of(context).dividerColor),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF6C5CE7).withValues(alpha: .1),
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
                      color: Color(0xFF6C5CE7),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.check_rounded,
                        color: Colors.white, size: 54),
                  ),
                  const SizedBox(height: 22),
                  const Text('Order Placed',
                      style:
                          TextStyle(fontSize: 26, fontWeight: FontWeight.w900)),
                  const SizedBox(height: 8),
                  Text(result.message,
                      textAlign: TextAlign.center,
                      style: const TextStyle(height: 1.35)),
                  const SizedBox(height: 18),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                        color: const Color(0xFFF0EEFF),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: Theme.of(context).dividerColor)),
                    child: Text(result.orderNumber,
                        style: const TextStyle(
                            fontWeight: FontWeight.w900, fontSize: 18)),
                  ),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: FilledButton(
                      style: FilledButton.styleFrom(
                          backgroundColor: const Color(0xFF6C5CE7)),
                      onPressed: () => Navigator.of(context).pop(true),
                      child: const Text('Back To Ecommerce'),
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
