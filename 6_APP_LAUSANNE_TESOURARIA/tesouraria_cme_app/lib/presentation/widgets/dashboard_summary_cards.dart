import 'package:flutter/material.dart';

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
    return LayoutBuilder(
      builder: (context, constraints) {
        if (constraints.maxWidth > 800) {
          // Desktop/Tablet landscape
          return Row(
            children: [
              Expanded(child: _buildCard('ENTRADAS', entradas, context)),
              const SizedBox(width: 16),
              Expanded(child: _buildCard('SAÍDAS', saidas, context, isExpense: true)),
              const SizedBox(width: 16),
              Expanded(child: _buildCard('SALDO DO MÊS', saldo, context, isTotal: true)),
            ],
          );
        } else {
          // Mobile/Tablet portrait
          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildCard('ENTRADAS', entradas, context),
              const SizedBox(height: 10),
              _buildCard('SAÍDAS', saidas, context, isExpense: true),
              const SizedBox(height: 10),
              _buildCard('SALDO DO MÊS', saldo, context, isTotal: true),
            ],
          );
        }
      },
    );
  }

  Widget _buildCard(String title, double amount, BuildContext context, {bool isExpense = false, bool isTotal = false}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF6B7280),
                  letterSpacing: 0.5,
                ),
              ),
              if (isExpense || isTotal)
                const Text(
                  'Em breve',
                  style: TextStyle(
                    fontSize: 10,
                    color: Color(0xFF9CA3AF),
                    fontWeight: FontWeight.w500,
                  ),
                ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            'CHF ${amount.toStringAsFixed(2)}',
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              color: Color(0xFF111827),
            ),
          ),
        ],
      ),
    );
  }
}
