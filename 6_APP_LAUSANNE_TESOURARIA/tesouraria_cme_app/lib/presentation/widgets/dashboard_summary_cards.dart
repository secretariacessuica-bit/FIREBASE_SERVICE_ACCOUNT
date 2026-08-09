import 'package:flutter/material.dart';
import '../../core/monetary_utils.dart';

class DashboardSummaryCards extends StatelessWidget {
  final double entradas;
  final double saidas;
  final double saldo;

  const DashboardSummaryCards({
    super.key,
    required this.entradas,
    required this.saidas,
    required this.saldo,
  });

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final isDesktop = screenWidth > 800;

    if (isDesktop) {
      // Desktop / Tablet landscape: Single elegant container with vertical dividers
      return Container(
        padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 32),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: const Color(0xFFE2E8F0)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.02),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            Expanded(
              child: _buildDesktopSection(
                title: 'ENTRADAS',
                amount: entradas,
                amountColor: const Color(0xFF1E7E34), // Controlled green for positive
              ),
            ),
            Container(
              height: 48,
              width: 1,
              color: const Color(0xFFE2E8F0),
            ),
            Expanded(
              child: _buildDesktopSection(
                title: 'SAÍDAS',
                amount: saidas,
                amountColor: const Color(0xFF0F172A), // Navy
              ),
            ),
            Container(
              height: 48,
              width: 1,
              color: const Color(0xFFE2E8F0),
            ),
            Expanded(
              child: _buildDesktopSection(
                title: 'SALDO DO MÊS',
                amount: saldo,
                amountColor: const Color(0xFF1E3A8A), // Deep financial blue
              ),
            ),
          ],
        ),
      );
    } else {
      // Mobile text-only summary layout
      return Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Padding(
            padding: EdgeInsets.only(bottom: 12.0),
            child: Text(
              'RESUMO DO MÊS',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                color: Color(0xFF64748B),
                letterSpacing: 1.0,
              ),
            ),
          ),
          _buildMobileRow('Entradas', entradas, amountColor: const Color(0xFF1E7E34)),
          const SizedBox(height: 8),
          _buildMobileRow('Saídas', saidas, amountColor: const Color(0xFF0F172A)),
          const SizedBox(height: 8),
          _buildMobileRow('Saldo', saldo, amountColor: const Color(0xFF1E3A8A), isBold: true),
        ],
      );
    }
  }

  Widget _buildDesktopSection({
    required String title,
    required double amount,
    required Color amountColor,
  }) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: Color(0xFF64748B),
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            "CHF ${BigDecimalConverter.format(amount)}",
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: amountColor,
              fontFamily: 'monospace',
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMobileRow(
    String label,
    double amount, {
    required Color amountColor,
    bool isBold = false,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            color: const Color(0xFF0F172A),
            fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
            fontSize: 14,
          ),
        ),
        Text(
          "CHF ${BigDecimalConverter.format(amount)}",
          style: TextStyle(
            color: amountColor,
            fontWeight: FontWeight.bold,
            fontSize: 14,
            fontFamily: 'monospace',
          ),
        ),
      ],
    );
  }
}
