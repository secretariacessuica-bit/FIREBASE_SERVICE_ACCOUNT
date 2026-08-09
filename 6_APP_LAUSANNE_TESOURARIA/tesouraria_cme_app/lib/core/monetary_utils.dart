class BigDecimalConverter {
  static int toRappen(double decimal) {
    return (decimal * 100).round();
  }

  static double fromRappen(int rappen) {
    return rappen / 100.0;
  }
}
