import 'package:flutter_test/flutter_test.dart';

import 'package:city_solutions/main.dart';

void main() {
  testWidgets('shows splash screen', (WidgetTester tester) async {
    await tester.pumpWidget(const CitySolutionsApp());

    expect(find.text('City Solution'), findsOneWidget);
    expect(find.text('Smart solutions, better city'), findsOneWidget);
  });
}
