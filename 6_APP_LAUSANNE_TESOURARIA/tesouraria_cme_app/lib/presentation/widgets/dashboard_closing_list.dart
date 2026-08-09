import 'package:flutter/material.dart';
import '../../domain/service_closing_history_models.dart';
import '../../core/theme.dart';
import '../pages/closing_detail_page.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../blocs/history_bloc.dart';

class DashboardClosingList extends StatelessWidget {
  final List<ServiceClosingSummary> history;

  const DashboardClosingList({
    super.key,
    required this.history,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Padding(
            padding: EdgeInsets.all(20),
            child: Text(
              'Últimos fechamentos',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Colors.black87,
              ),
            ),
          ),
          const Divider(height: 1),
          if (history.isEmpty)
            const Padding(
              padding: EdgeInsets.all(40),
              child: Center(
                child: Text('Nenhum fechamento registrado.'),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: history.length > 5 ? 5 : history.length, // Max 5 items
              separatorBuilder: (context, index) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final item = history[index];
                return ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                  title: Text(item.serviceDate.isNotEmpty && item.serviceDate != '-' ? 'Culto: ${item.serviceDate}' : 'Culto sem data'),
                  subtitle: Text('Tesoureiro: ${item.mainTreasurer}'),
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        'CHF ${item.physicalTotal.toStringAsFixed(2)}',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                      const SizedBox(width: 16),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppTheme.mathGreen.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: const Text(
                          'Fechado',
                          style: TextStyle(color: AppTheme.mathGreen, fontSize: 12, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  onTap: () async {
                    final shouldReload = await Navigator.of(context).push(MaterialPageRoute(
                      builder: (_) => ClosingDetailPage(closingId: item.id),
                    ));
                    if (shouldReload == true && context.mounted) {
                      context.read<HistoryBloc>().add(LoadHistoryEvent());
                    }
                  },
                );
              },
            ),
          const Divider(height: 1),
          Padding(
            padding: const EdgeInsets.all(16),
            child: TextButton(
              onPressed: () {
                // Future: Navigate to full history page
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Em breve: Listagem completa de fechamentos')),
                );
              },
              child: const Text('Ver todos os fechamentos →'),
            ),
          ),
        ],
      ),
    );
  }
}
