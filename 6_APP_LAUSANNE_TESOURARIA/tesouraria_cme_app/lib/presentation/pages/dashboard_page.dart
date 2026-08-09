import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../services/fechamento_api_service.dart';
import '../../services/auth_api_service.dart';
import 'login_page.dart';
import 'placeholder_page.dart';
import 'closing_detail_page.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../blocs/history_bloc.dart';
import '../widgets/app_sidebar_drawer.dart';
import '../widgets/dashboard_summary_cards.dart';
import '../../domain/service_closing_history_models.dart';
import '../../core/monetary_utils.dart';
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
      backgroundColor: const Color(0xFFFAFAFA),
      drawer: isDesktop ? null : const AppSidebarDrawer(activeRoute: 'dashboard'),
      appBar: isDesktop
          ? null
          : AppBar(
              backgroundColor: Colors.white,
              foregroundColor: const Color(0xFF0F172A),
              elevation: 0,
              shape: const Border(bottom: BorderSide(color: Color(0xFFE5E7EB), width: 1)),
              title: const Text(
                'Visão geral',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
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
              padding: const EdgeInsets.all(24),
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
    
    // Dynamic formatted date in Portuguese, lowercase (ex: domingo, 9 de agosto de 2026)
    String dateStr = DateFormat("EEEE, d 'de' MMMM 'de' yyyy", 'pt_BR').format(DateTime.now()).toLowerCase();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Greeting & Header
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Bom dia, $_userName!',
              style: TextStyle(
                fontSize: isDesktop ? 24 : 20,
                fontWeight: FontWeight.bold,
                color: const Color(0xFF0F172A),
                letterSpacing: -0.5,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              dateStr,
              style: TextStyle(
                fontSize: isDesktop ? 13 : 12,
                color: const Color(0xFF64748B),
              ),
            ),
          ],
        ),
        const SizedBox(height: 32),

        // Summary container
        DashboardSummaryCards(
          entradas: totalEntradas,
          saidas: 0.0,
          saldo: totalEntradas,
        ),
        const SizedBox(height: 32),

        // Layout principal
        if (isDesktop)
          _buildDesktopLayout(context, historico)
        else
          _buildMobileLayout(context, historico),
      ],
    );
  }

  Widget _buildDesktopLayout(BuildContext context, List<ServiceClosingSummary> history) {
    final monthlyData = _getMonthlyData(history);
    final hasEnoughData = history.isNotEmpty;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Left Column (Chart)
        Expanded(
          flex: 3,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "EVOLUÇÃO MENSAL",
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF64748B),
                        letterSpacing: 1.0,
                      ),
                    ),
                    const SizedBox(height: 24),
                    if (hasEnoughData)
                      SizedBox(
                        height: 220,
                        child: CustomPaint(
                          painter: LineChartPainter(monthlyData),
                          child: Container(),
                        ),
                      )
                    else
                      const SizedBox(
                        height: 220,
                        child: Center(
                          child: Text(
                            "Aguardando dados históricos suficientes para evolução mensal.",
                            style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(width: 24),

        // Right Column (Pendências & Últimos Movimentos)
        Expanded(
          flex: 2,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Pendências
              _buildPendenciasSection(),
              const SizedBox(height: 24),
              // Últimos Movimentos
              _buildMovimentosSection(context, history, isDesktop: true),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildMobileLayout(BuildContext context, List<ServiceClosingSummary> history) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _buildPendenciasSection(),
        const SizedBox(height: 32),
        _buildMovimentosSection(context, history, isDesktop: false),
      ],
    );
  }

  Widget _buildPendenciasSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'PENDÊNCIAS',
          style: TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.bold,
            color: Color(0xFF64748B),
            letterSpacing: 1.0,
          ),
        ),
        const SizedBox(height: 12),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: const Text(
            'Nenhuma pendência no momento.',
            style: TextStyle(color: Color(0xFF64748B), fontSize: 13),
          ),
        ),
      ],
    );
  }

  Widget _buildMovimentosSection(BuildContext context, List<ServiceClosingSummary> history, {required bool isDesktop}) {
    final recentItems = history.take(3).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              isDesktop ? 'ÚLTIMOS MOVIMENTOS' : 'ATIVIDADE RECENTE',
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.bold,
                color: Color(0xFF64748B),
                letterSpacing: 1.0,
              ),
            ),
            TextButton(
              onPressed: () {
                Navigator.of(context).pushReplacement(MaterialPageRoute(
                  builder: (_) => const PlaceholderPage(
                    title: 'Movimentos',
                    route: 'movimentos',
                    icon: Icons.receipt_long_rounded,
                    description: 'Visualize entradas e saídas\nde cada culto.',
                  ),
                ));
              },
              style: TextButton.styleFrom(
                padding: EdgeInsets.zero,
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: Text(
                isDesktop ? 'Ver todos' : 'Ver movimentos',
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF1E3A8A),
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        if (recentItems.isEmpty)
          Container(
            padding: const EdgeInsets.symmetric(vertical: 24),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: const Center(
              child: Text(
                'Nenhum movimento registrado.',
                style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
              ),
            ),
          )
        else
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: recentItems.length,
              separatorBuilder: (context, index) => const Divider(height: 1, color: Color(0xFFE2E8F0)),
              itemBuilder: (context, index) {
                final item = recentItems[index];
                
                // Formata data curta "09 ago"
                String shortDate = item.serviceDate;
                try {
                  final parts = item.serviceDate.split('/');
                  if (parts.length == 3) {
                    final day = parts[0];
                    final month = int.tryParse(parts[1]) ?? 1;
                    const months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
                    shortDate = "$day ${months[month - 1]}".toUpperCase();
                  }
                } catch (_) {}

                return InkWell(
                  onTap: () async {
                    final shouldReload = await Navigator.of(context).push(MaterialPageRoute(
                      builder: (_) => ClosingDetailPage(closingId: item.id),
                    ));
                    if (shouldReload == true && context.mounted) {
                      context.read<HistoryBloc>().add(LoadHistoryEvent());
                    }
                  },
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    child: Row(
                      children: [
                        // Short date block
                        SizedBox(
                          width: 54,
                          child: Text(
                            shortDate,
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF64748B),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        
                        // Details column
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Fechamento do culto',
                                style: TextStyle(
                                  fontSize: 13,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF0F172A),
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                item.mainTreasurer,
                                style: const TextStyle(
                                  fontSize: 11,
                                  color: Color(0xFF64748B),
                                ),
                              ),
                            ],
                          ),
                        ),
                        
                        // Monetary amount
                        Text(
                          "+ CHF ${BigDecimalConverter.format(item.physicalTotal)}",
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF1E7E34),
                            fontFamily: 'monospace',
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
      ],
    );
  }

  List<MonthlyFinancialData> _getMonthlyData(List<ServiceClosingSummary> history) {
    final Map<String, double> monthlyEntries = {};
    
    for (var item in history) {
      try {
        final cleanDate = item.serviceDate.trim();
        final parts = cleanDate.split('/');
        if (parts.length == 3) {
          int month = int.tryParse(parts[1]) ?? 1;
          final monthAbbrev = _getMonthAbbrev(month);
          monthlyEntries[monthAbbrev] = (monthlyEntries[monthAbbrev] ?? 0.0) + item.physicalTotal;
        }
      } catch (_) {}
    }
    
    final List<String> last6Months = [];
    final now = DateTime.now();
    for (int i = 5; i >= 0; i--) {
      final mDate = DateTime(now.year, now.month - i, 1);
      last6Months.add(_getMonthAbbrev(mDate.month));
    }
    
    return last6Months.map((m) {
      double entries = monthlyEntries[m] ?? 0.0;
      return MonthlyFinancialData(m, entries, 0.0, entries);
    }).toList();
  }

  String _getMonthAbbrev(int month) {
    if (month >= 1 && month <= 12) {
      // Return local Portuguese-style abbrevs
      const ptMonths = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
      return ptMonths[month - 1];
    }
    return '';
  }
}

class MonthlyFinancialData {
  final String monthAbbrev;
  final double entries;
  final double expenses;
  final double balance;

  MonthlyFinancialData(this.monthAbbrev, this.entries, this.expenses, this.balance);
}

class LineChartPainter extends CustomPainter {
  final List<MonthlyFinancialData> data;
  LineChartPainter(this.data);

  @override
  void paint(Canvas canvas, Size size) {
    if (data.isEmpty) return;

    final paintLineEntries = Paint()
      ..color = const Color(0xFF1E7E34) // green
      ..strokeWidth = 2
      ..style = PaintingStyle.stroke;

    final paintPointEntries = Paint()
      ..color = const Color(0xFF1E7E34)
      ..style = PaintingStyle.fill;

    final paintLineSaldo = Paint()
      ..color = const Color(0xFF1E3A8A) // deep blue
      ..strokeWidth = 2
      ..style = PaintingStyle.stroke;

    final paintPointSaldo = Paint()
      ..color = const Color(0xFF1E3A8A)
      ..style = PaintingStyle.fill;

    final paintGrid = Paint()
      ..color = const Color(0xFFF1F5F9)
      ..strokeWidth = 1;

    // Find max value to scale chart appropriately
    double maxVal = 1000.0;
    for (var d in data) {
      if (d.entries > maxVal) maxVal = d.entries;
    }
    maxVal = ((maxVal / 1000).ceil() * 1000).toDouble();

    // Draw horizontal grid lines and scale text labels
    final double stepY = size.height / 4;
    for (int i = 0; i <= 4; i++) {
      double y = stepY * i;
      canvas.drawLine(Offset(0, y), Offset(size.width, y), paintGrid);
      
      double val = maxVal - (maxVal / 4 * i);
      final textSpan = TextSpan(
        text: val >= 1000 ? "${(val / 1000).toStringAsFixed(0)}k" : val.toStringAsFixed(0),
        style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 9, fontFamily: 'monospace'),
      );
      final textPainter = TextPainter(
        text: textSpan,
        textDirection: ui.TextDirection.ltr,
      )..layout();
      textPainter.paint(canvas, Offset(-24, y - 6));
    }

    // Draw month names below the chart
    final double stepX = size.width / (data.length - 1);
    for (int i = 0; i < data.length; i++) {
      double x = stepX * i;
      final textSpan = TextSpan(
        text: data[i].monthAbbrev,
        style: const TextStyle(color: Color(0xFF64748B), fontSize: 10, fontWeight: FontWeight.bold),
      );
      final textPainter = TextPainter(
        text: textSpan,
        textDirection: ui.TextDirection.ltr,
      )..layout();
      textPainter.paint(canvas, Offset(x - 10, size.height + 8));
    }

    // Plot lines
    final Path pathEntries = Path();
    final Path pathSaldo = Path();

    for (int i = 0; i < data.length; i++) {
      double x = stepX * i;
      double yEntries = size.height - (data[i].entries / maxVal * size.height);
      double ySaldo = size.height - (data[i].balance / maxVal * size.height);

      if (i == 0) {
        pathEntries.moveTo(x, yEntries);
        pathSaldo.moveTo(x, ySaldo);
      } else {
        pathEntries.lineTo(x, yEntries);
        pathSaldo.lineTo(x, ySaldo);
      }
    }

    canvas.drawPath(pathEntries, paintLineEntries);
    canvas.drawPath(pathSaldo, paintLineSaldo);

    // Plot data points
    for (int i = 0; i < data.length; i++) {
      double x = stepX * i;
      double yEntries = size.height - (data[i].entries / maxVal * size.height);
      double ySaldo = size.height - (data[i].balance / maxVal * size.height);

      canvas.drawCircle(Offset(x, yEntries), 3.5, paintPointEntries);
      canvas.drawCircle(Offset(x, ySaldo), 3.5, paintPointSaldo);
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => true;
}
