import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../services/fechamento_api_service.dart';
import '../../services/auth_api_service.dart';
import 'login_page.dart';
import 'wizard_page.dart';
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
    final screenWidth = MediaQuery.of(context).size.width;
    final isDesktop = screenWidth > 800;

    return Scaffold(
      backgroundColor: const Color(0xFFF9FAFB),
      drawer: isDesktop ? null : const AppSidebarDrawer(activeRoute: 'dashboard'),
      appBar: isDesktop
          ? null
          : AppBar(
              backgroundColor: Colors.white,
              foregroundColor: const Color(0xFF111827),
              elevation: 0,
              shape: const Border(bottom: BorderSide(color: Color(0xFFE5E7EB), width: 1)),
              title: const Text(
                'CME Lausanne',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF111827)),
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
            final content = _buildMainContent(context, state, isDesktop);

            if (isDesktop) {
              return Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const AppSidebarDrawer(activeRoute: 'dashboard', permanent: true),
                  Expanded(
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 32),
                      child: Center(
                        child: ConstrainedBox(
                          constraints: const BoxConstraints(maxWidth: 1100),
                          child: content,
                        ),
                      ),
                    ),
                  ),
                ],
              );
            }

            return SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: content,
            );
          }
          return const Center(child: Text("Erro ao carregar histórico."));
        },
      ),
    );
  }

  Widget _buildMainContent(BuildContext context, HistoryLoaded state, bool isDesktop) {
    final historico = state.history;
    double totalEntradas = historico.fold(0, (sum, item) => sum + item.physicalTotal);
    final dateStr = DateFormat("EEEE, d 'de' MMMM 'de' yyyy", 'pt_BR').format(DateTime.now());

    Widget actionButton() {
      return ElevatedButton.icon(
        onPressed: () async {
          final shouldReload = await Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const WizardPage()),
          );
          if (shouldReload == true && context.mounted) {
            context.read<HistoryBloc>().add(LoadHistoryEvent());
          }
        },
        icon: const Icon(Icons.add, size: 18, color: Colors.white),
        label: const Text('Novo Fechamento'),
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFF137333),
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
          textStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Header responsivo
        if (isDesktop)
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
                      fontSize: 22,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF111827),
                      letterSpacing: -0.5,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    dateStr,
                    style: const TextStyle(
                      fontSize: 13,
                      color: Color(0xFF6B7280),
                    ),
                  ),
                ],
              ),
              actionButton(),
            ],
          )
        else
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Bom dia, $_userName!',
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF111827),
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                dateStr,
                style: const TextStyle(
                  fontSize: 12,
                  color: Color(0xFF6B7280),
                ),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: actionButton(),
              ),
            ],
          ),
        
        const SizedBox(height: 24),

        // Summary Cards (Entradas, Saídas, Saldo)
        DashboardSummaryCards(
          entradas: totalEntradas,
          saidas: 0,
          saldo: totalEntradas,
        ),

        const SizedBox(height: 24),

        // Conteúdo Principal
        LayoutBuilder(
          builder: (context, constraints) {
            if (constraints.maxWidth > 750) {
              return Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(flex: 4, child: _buildResumoEntradas(totalEntradas)),
                  const SizedBox(width: 20),
                  Expanded(flex: 7, child: DashboardClosingList(history: historico)),
                ],
              );
            } else {
              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _buildResumoEntradas(totalEntradas),
                  const SizedBox(height: 16),
                  DashboardClosingList(history: historico),
                ],
              );
            }
          },
        ),

        const SizedBox(height: 24),

        // Seção de Pendências
        const Text(
          'Pendências',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: Color(0xFF111827),
          ),
        ),
        const SizedBox(height: 10),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: const Color(0xFFE5E7EB)),
          ),
          child: const Row(
            children: [
              Icon(Icons.check_circle_outline, color: Color(0xFF9CA3AF), size: 20),
              SizedBox(width: 10),
              Text(
                'Nenhuma pendência no momento.',
                style: TextStyle(color: Color(0xFF6B7280), fontSize: 13),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildResumoEntradas(double totalEntradas) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Resumo das entradas',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.bold,
              color: Color(0xFF111827),
            ),
          ),
          const SizedBox(height: 12),
          const Divider(height: 1, color: Color(0xFFE5E7EB)),
          const SizedBox(height: 12),
          _buildResumoRow('Dízimos', 'Dados não disponíveis'),
          const SizedBox(height: 10),
          _buildResumoRow('Ofertas', 'Dados não disponíveis'),
          const SizedBox(height: 10),
          _buildResumoRow('Votos', 'Dados não disponíveis'),
          const SizedBox(height: 12),
          const Divider(height: 1, color: Color(0xFFE5E7EB)),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Total',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF111827)),
              ),
              Text(
                'CHF ${totalEntradas.toStringAsFixed(2)}',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF111827)),
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
              width: 6,
              height: 6,
              decoration: const BoxDecoration(
                color: Color(0xFFD1D5DB),
                shape: BoxShape.circle,
              ),
            ),
            const SizedBox(width: 8),
            Text(tipo, style: const TextStyle(color: Color(0xFF374151), fontSize: 13)),
          ],
        ),
        Text(valor, style: const TextStyle(color: Color(0xFF9CA3AF), fontSize: 11)),
      ],
    );
  }
}
