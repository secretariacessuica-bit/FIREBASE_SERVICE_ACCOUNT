import 'package:flutter/material.dart';
import '../../services/fechamento_api_service.dart';
import '../../services/auth_api_service.dart';
import 'login_page.dart';
import 'wizard_page.dart';
import 'closing_detail_page.dart';
import '../../core/theme.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../blocs/history_bloc.dart';
import '../widgets/app_sidebar_drawer.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => HistoryBloc(FechamentoApiService())..add(LoadHistoryEvent()),
      child: const DashboardView(),
    );
  }
}

class DashboardView extends StatelessWidget {
  const DashboardView({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      drawer: const AppSidebarDrawer(activeRoute: 'dashboard'),
      appBar: AppBar(
        backgroundColor: AppTheme.primaryGreen,
        foregroundColor: Colors.white,
        title: const Text(
          'Tesouraria CME Lausanne',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: ElevatedButton.icon(
              onPressed: () async {
                final shouldReload = await Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const WizardPage()),
                );
                if (shouldReload == true && context.mounted) {
                  context.read<HistoryBloc>().add(LoadHistoryEvent());
                }
              },
              icon: const Icon(Icons.add, size: 18),
              label: const Text('Novo Fechamento'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: AppTheme.primaryGreen,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                textStyle: const TextStyle(fontWeight: FontWeight.bold),
              ),
            ),
          ),
        ],
      ),
      body: BlocConsumer<HistoryBloc, HistoryState>(
        listener: (context, state) {
          if (state is HistoryError && state.isUnauthorized) {
            AuthApiService().logout().then((_) {
              Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const LoginPage()));
            });
          } else if (state is HistoryError) {
            ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(state.message)));
          }
        },
        builder: (context, state) {
          if (state is HistoryLoading || state is HistoryInitial) {
            return const Center(child: CircularProgressIndicator());
          } else if (state is HistoryLoaded) {
            final historico = state.history;
            if (historico.isEmpty) {
              return const Center(child: Text("Nenhum fechamento encontrado."));
            }
            return Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 800),
                child: ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: historico.length,
                  itemBuilder: (context, index) {
                    final item = historico[index];
                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: ListTile(
                        leading: const Icon(Icons.history, color: AppTheme.institutionalBlue),
                        title: Text(item.serviceDate.isNotEmpty && item.serviceDate != '-' ? 'Culto: ${item.serviceDate}' : 'Culto sem data'),
                        subtitle: Text('Tesoureiro: ${item.mainTreasurer}'),
                        trailing: Text('CHF ${item.physicalTotal.toStringAsFixed(2)}', 
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                        onTap: () async {
                          final shouldReload = await Navigator.of(context).push(MaterialPageRoute(
                            builder: (_) => ClosingDetailPage(closingId: item.id),
                          ));
                          if (shouldReload == true && context.mounted) {
                            context.read<HistoryBloc>().add(LoadHistoryEvent());
                          }
                        },
                      ),
                    );
                  },
                ),
              ),
            );
          }
          return const Center(child: Text("Erro ao carregar histórico."));
        },
      ),
    );
  }
}


