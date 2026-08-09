import 'package:flutter_test/flutter_test.dart';
import 'package:tesouraria_cme_app/core/monetary_utils.dart';

void main() {
  group('Monetary Conversion (Rappen ↔ Decimal)', () {
    test('Converts CHF 0.01 correctly', () {
      expect(BigDecimalConverter.toRappen(0.01), equals(1));
      expect(BigDecimalConverter.fromRappen(1), equals(0.01));
    });

    test('Converts CHF 0.10 correctly', () {
      expect(BigDecimalConverter.toRappen(0.10), equals(10));
      expect(BigDecimalConverter.fromRappen(10), equals(0.10));
    });

    test('Converts CHF 0.29 correctly', () {
      expect(BigDecimalConverter.toRappen(0.29), equals(29));
      expect(BigDecimalConverter.fromRappen(29), equals(0.29));
    });

    test('Converts CHF 120.50 correctly', () {
      expect(BigDecimalConverter.toRappen(120.50), equals(12050));
      expect(BigDecimalConverter.fromRappen(12050), equals(120.50));
    });

    test('Converts CHF 9999.99 correctly', () {
      expect(BigDecimalConverter.toRappen(9999.99), equals(999999));
      expect(BigDecimalConverter.fromRappen(999999), equals(9999.99));
    });
  });
}
