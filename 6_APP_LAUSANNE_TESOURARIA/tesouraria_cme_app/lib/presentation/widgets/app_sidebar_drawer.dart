import 'package:flutter/material.dart';
import '../../core/theme.dart';
import '../../services/auth_api_service.dart';
import '../pages/login_page.dart';
import '../pages/dashboard_page.dart';
import '../pages/wizard_page.dart';
import '../pages/history_page.dart';
import '../pages/members_page.dart';
import '../pages/reports_page.dart';
import '../pages/movements_page.dart';
import '../pages/expenses_page.dart';
import '../pages/settings_page.dart';

class AppSidebarDrawer extends StatelessWidget {
  final String activeRoute;
  final bool permanent;

  const AppSidebarDrawer({
    super.key,
    this.activeRoute = 'dashboard',
    this.permanent = false,
  });

  @override
  Widget build(BuildContext context) {
    final content = Container(
      color: AppTheme.darkSidebar,
      child: Column(
        children: [
          // Header do App
          Padding(
            padding: const EdgeInsets.only(top: 48, left: 24, right: 24, bottom: 32),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.church, color: Colors.white, size: 24),
                ),
                const SizedBox(width: 12),
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'CME LAUSANNE',
                      style: TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                        letterSpacing: 0.5,
                      ),
                    ),
                    Text(
                      'TESOURARIA',
                      style: TextStyle(
                        color: Color(0xFF9CA3AF),
                        fontSize: 11,
                        letterSpacing: 1.0,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // Itens de Menu
          Expanded(
            child: ListView(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              children: [
                _buildMenuItem(
                  context: context,
                  icon: Icons.grid_view_rounded,
                  title: 'Visão geral',
                  isActive: activeRoute == 'dashboard',
                  onTap: () {
                    if (!permanent) Navigator.pop(context);
                    Navigator.of(context).pushReplacement(
                      MaterialPageRoute(builder: (_) => const DashboardScreen()),
                    );
                  },
                ),
                _buildMenuItem(
                  context: context,
                  icon: Icons.add_circle_outline_rounded,
                  title: 'Novo fechamento',
                  isActive: activeRoute == 'fechamento',
                  onTap: () {
                    if (!permanent) Navigator.pop(context);
                    Navigator.of(context).pushReplacement(
                      MaterialPageRoute(builder: (_) => const WizardPage()),
                    );
                  },
                ),
                _buildMenuItem(
                  context: context,
                  icon: Icons.receipt_long_rounded,
                  title: 'Movimentos',
                  isActive: activeRoute == 'movimentos',
                  onTap: () {
                    if (!permanent) Navigator.pop(context);
                    Navigator.of(context).pushReplacement(MaterialPageRoute(
                      builder: (_) => const MovementsPage(),
                    ));
                  },
                ),
                _buildMenuItem(
                  context: context,
                  icon: Icons.people_outline_rounded,
                  title: 'Contribuintes',
                  isActive: activeRoute == 'contribuintes',
                  onTap: () {
                    if (!permanent) Navigator.pop(context);
                    Navigator.of(context).pushReplacement(MaterialPageRoute(
                      builder: (_) => const MembersPage(),
                    ));
                  },
                ),
                _buildMenuItem(
                  context: context,
                  icon: Icons.account_balance_wallet_outlined,
                  title: 'Despesas',
                  isActive: activeRoute == 'despesas',
                  onTap: () {
                    if (!permanent) Navigator.pop(context);
                    Navigator.of(context).pushReplacement(MaterialPageRoute(
                      builder: (_) => const ExpensesPage(),
                    ));
                  },
                ),
                _buildMenuItem(
                  context: context,
                  icon: Icons.point_of_sale_rounded,
                  title: 'Fechamentos',
                  isActive: activeRoute == 'fechamentos',
                  onTap: () {
                    if (!permanent) Navigator.pop(context);
                    Navigator.of(context).pushReplacement(MaterialPageRoute(
                      builder: (_) => const HistoryPage(),
                    ));
                  },
                ),
                _buildMenuItem(
                  context: context,
                  icon: Icons.bar_chart_rounded,
                  title: 'Relatórios',
                  isActive: activeRoute == 'relatorios',
                  onTap: () {
                    if (!permanent) Navigator.pop(context);
                    Navigator.of(context).pushReplacement(MaterialPageRoute(
                      builder: (_) => const ReportsPage(),
                    ));
                  },
                ),
                _buildMenuItem(
                  context: context,
                  icon: Icons.settings_outlined,
                  title: 'Configurações',
                  isActive: activeRoute == 'configuracoes',
                  onTap: () {
                    if (!permanent) Navigator.pop(context);
                    Navigator.of(context).pushReplacement(MaterialPageRoute(
                      builder: (_) => const SettingsPage(),
                    ));
                  },
                ),
              ],
            ),
          ),

          // Perfil e Logout no Footer
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.05),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    backgroundColor: Colors.white.withValues(alpha: 0.2),
                    child: const Text('A', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Admilson Silva',
                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                          overflow: TextOverflow.ellipsis,
                        ),
                        Text(
                          'Tesoureiro',
                          style: TextStyle(color: Color(0xFF9CA3AF), fontSize: 11),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.logout_rounded, color: Color(0xFF9CA3AF), size: 20),
                    onPressed: () async {
                      await AuthApiService().logout();
                      if (!context.mounted) return;
                      Navigator.of(context).pushAndRemoveUntil(
                        MaterialPageRoute(builder: (_) => const LoginPage()),
                        (route) => false,
                      );
                    },
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );

    if (permanent) {
      return SizedBox(
        width: 260,
        child: content,
      );
    }

    return Drawer(
      child: content,
    );
  }

  Widget _buildMenuItem({
    required BuildContext context,
    required IconData icon,
    required String title,
    required bool isActive,
    VoidCallback? onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 4),
      decoration: BoxDecoration(
        color: isActive ? const Color(0xFF1E293B) : Colors.transparent,
        borderRadius: BorderRadius.circular(8),
        border: isActive ? const Border(left: BorderSide(color: Color(0xFF1E7E34), width: 3)) : null,
      ),
      child: ListTile(
        dense: true,
        leading: Icon(
          icon,
          color: isActive ? Colors.white : const Color(0xFF9CA3AF),
          size: 20,
        ),
        title: Text(
          title,
          style: TextStyle(
            color: isActive ? Colors.white : const Color(0xFFD1D5DB),
            fontWeight: isActive ? FontWeight.bold : FontWeight.w500,
            fontSize: 14,
          ),
        ),
        onTap: onTap,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
    );
  }
}
