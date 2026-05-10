import 'package:flutter_test/flutter_test.dart';
import 'package:pharmacy_client/main.dart';

void main() {
  testWidgets('renders pharmacy client app shell', (tester) async {
    await tester.pumpWidget(const PharmacyClientApp());
    await tester.pump();

    expect(find.text('الرئيسية'), findsOneWidget);
    expect(find.text('المنتجات'), findsOneWidget);
    expect(find.text('السلة'), findsOneWidget);
  });
}
