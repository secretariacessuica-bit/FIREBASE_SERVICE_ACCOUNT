class BigDecimalConverter {
  static int toRappen(double decimal) {
    return (decimal * 100).round();
  }

  static double fromRappen(int rappen) {
    return rappen / 100.0;
  }

  static String format(double amount) {
    final parts = amount.toStringAsFixed(2).split('.');
    final integerPart = parts[0].replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => "${m[1]}'",
    );
    return "$integerPart.${parts[1]}";
  }
}
