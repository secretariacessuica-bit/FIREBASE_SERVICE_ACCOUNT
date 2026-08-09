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
              const SizedBox(height: 12),
              _buildCard('SAÍDAS', saidas, context, isExpense: true),
              const SizedBox(height: 12),
              _buildCard('SALDO DO MÊS', saldo, context, isTotal: true),
            ],
          );
        }
      },
    );
  }

  Widget _buildCard(String title, double amount, BuildContext context, {bool isExpense = false, bool isTotal = false}) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.bold,
              color: Colors.grey.shade600,
              letterSpacing: 1.1,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            'CHF ${amount.toStringAsFixed(2)}',
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 16),
          if (isExpense || isTotal)
            Text(
              'Em breve',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey.shade400,
              ),
            )
          else
            const SizedBox(height: 14), // placeholder to match height
        ],
      ),
    );
  }
}
