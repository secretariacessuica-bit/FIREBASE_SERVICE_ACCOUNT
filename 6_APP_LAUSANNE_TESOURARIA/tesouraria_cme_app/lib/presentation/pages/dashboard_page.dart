import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../services/fechamento_api_service.dart';
import '../../services/auth_api_service.dart';
import 'login_page.dart';
import 'wizard_page.dart';
import '../../core/theme.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../blocs/history_bloc.dart';
import '../widgets/app_sidebar_drawer.dart';
import '../widgets/dashboard_summary_cards.dart';
import '../widgets/dashboard_closing_list.dart';
import 'package:shared_preferences/shared_preferences.dart';

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

class DashboardView extends StatefulWidget {
  const DashboardView({super.key});

  @override
  State<DashboardView> createState() => _DashboardViewState();
}

class _DashboardViewState extends State<DashboardView> {
  String _userName = 'Tesoureiro';

  @override
  void initState() {
    super.initState();
    _loadUserName();
  }

  Future<void> _loadUserName() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('jwt_token');
    if (token != null) {
      // In a real app we might decode the JWT here to get the name, 
      // but for now we just show a generic greeting or if we have it saved.
      // E.g. we might have saved 'username'.
      final savedUser = prefs.getString('username');
      if (savedUser != null && savedUser.isNotEmpty) {
        setState(() {
          _userName = savedUser.substring(0, 1).toUpperCase() + savedUser.substring(1);
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      drawer: const AppSidebarDrawer(activeRoute: 'dashboard'),
      appBar: AppBar(
        backgroundColor: AppTheme.primaryGreen,
        foregroundColor: Colors.white,
        title: const Text(
          'Tesouraria CME Lausanne',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
        ),
      ),
      body: BlocConsumer<HistoryBloc, HistoryState>(
        listener: (context, state) {
          if (state is HistoryError && state.isUnauthorized) {
            AuthApiService().logout().then((_) {
              if (context.mounted) {
                Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const LoginPage()));
              }
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
            
            // Calculate total entradas from loaded history
            double totalEntradas = historico.fold(0, (sum, item) => sum + item.physicalTotal);

            return SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 1200),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Header
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Bom dia, $_userName!',
                                style: const TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.black87,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                DateFormat("EEEE, d 'de' MMMM 'de' yyyy", 'pt_BR').format(DateTime.now()),
                                style: TextStyle(
                                  fontSize: 14,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                            ],
                          ),
                          ElevatedButton.icon(
                            onPressed: () async {
                              final shouldReload = await Navigator.of(context).push(
                                MaterialPageRoute(builder: (_) => const WizardPage()),
                              );
                              if (shouldReload == true && context.mounted) {
                                context.read<HistoryBloc>().add(LoadHistoryEvent());
                              }
                            },
                            icon: const Icon(Icons.add, size: 20),
                            label: const Text('Novo Fechamento'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppTheme.primaryGreen,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              textStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 32),
                      
                      // Summary Cards
                      DashboardSummaryCards(
                        entradas: totalEntradas,
                        saidas: 0,
                        saldo: totalEntradas, // saldo = entradas - saidas
                      ),
                      
                      const SizedBox(height: 32),
                      
                      // Main Content
                      LayoutBuilder(
                        builder: (context, constraints) {
                          if (constraints.maxWidth > 800) {
                            return Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(flex: 4, child: _buildResumoEntradas(totalEntradas)),
                                const SizedBox(width: 24),
                                Expanded(flex: 7, child: DashboardClosingList(history: historico)),
                              ],
                            );
                          } else {
                            return Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                _buildResumoEntradas(totalEntradas),
                                const SizedBox(height: 24),
                                DashboardClosingList(history: historico),
                              ],
                            );
                          }
                        },
                      ),
                      
                      const SizedBox(height: 32),
                      
                      // Pendências
                      const Text(
                        'Pendências',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.black87,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: Row(
                          children: [
                            Icon(Icons.check_circle_outline, color: Colors.grey.shade400, size: 24),
                            const SizedBox(width: 12),
                            Text(
                              'Nenhuma pendência no momento.',
                              style: TextStyle(color: Colors.grey.shade600),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 48), // bottom padding
                    ],
                  ),
                ),
              ),
            );
          }
          return const Center(child: Text("Erro ao carregar histórico."));
        },
      ),
    );
  }

  Widget _buildResumoEntradas(double totalEntradas) {
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
          const Text(
            'Resumo das entradas',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 16),
          const Divider(height: 1),
          const SizedBox(height: 16),
          _buildResumoRow('Dízimos', 'Dados não disponíveis'),
          const SizedBox(height: 12),
          _buildResumoRow('Ofertas', 'Dados não disponíveis'),
          const SizedBox(height: 12),
          _buildResumoRow('Votos', 'Dados não disponíveis'),
          const SizedBox(height: 16),
          const Divider(height: 1),
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Total',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              Text(
                'CHF ${totalEntradas.toStringAsFixed(2)}',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildResumoRow(String tipo, String valor) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            Container(
              width: 8,
              height: 8,
              decoration: BoxDecoration(
                color: Colors.grey.shade300, // placeholder color
                shape: BoxShape.circle,
              ),
            ),
            const SizedBox(width: 8),
            Text(tipo, style: const TextStyle(color: Colors.black87)),
          ],
        ),
        Text(valor, style: TextStyle(color: Colors.grey.shade500, fontSize: 12)),
      ],
    );
  }
}
