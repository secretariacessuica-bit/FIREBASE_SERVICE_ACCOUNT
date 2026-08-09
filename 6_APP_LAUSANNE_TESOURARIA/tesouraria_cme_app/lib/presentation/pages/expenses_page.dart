import 'package:flutter/material.dart';
import '../../core/theme.dart';
import '../widgets/app_sidebar_drawer.dart';

class ExpenseModel {
  final String id;
  final String description;
  final String category;
  final String supplier;
  final double amount;
  final String date;

  ExpenseModel({
    required this.id,
    required this.description,
    required this.category,
    required this.supplier,
    required this.amount,
    required this.date,
  });
}

class ExpensesPage extends StatefulWidget {
  const ExpensesPage({super.key});

  @override
  State<ExpensesPage> createState() => _ExpensesPageState();
}

class _ExpensesPageState extends State<ExpensesPage> {
  final List<ExpenseModel> _expenses = [
    ExpenseModel(
      id: '1',
      description: 'Aluguel do Templo - Agosto/2026',
      category: 'Aluguel & Local',
      supplier: 'Régie Immobilière Lausanne',
      amount: 1800.00,
      date: '01/08/2026',
    ),
    ExpenseModel(
      id: '2',
      description: 'Conta de Energia Eletricidade',
      category: 'Utilidades',
      supplier: 'Services Industriels de Lausanne (SIL)',
      amount: 245.50,
      date: '03/08/2026',
    ),
    ExpenseModel(
      id: '3',
      description: 'Manutenção de Equipamento de Som',
      category: 'Manutenção & Equipamento',
      supplier: 'AudioTech SA',
      amount: 130.00,
      date: '05/08/2026',
    ),
  ];

  void _showAddExpenseDialog() {
    final descController = TextEditingController();
    final supplierController = TextEditingController();
    final amountController = TextEditingController();
    String category = 'Utilidades';

    showDialog(
      context: context,
      builder: (dlgContext) {
        return StatefulBuilder(
          builder: (context, setDlgState) {
            return Dialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              child: Container(
                width: 420,
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Registrar Nova Despesa',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close, color: Color(0xFF6B7280)),
                          onPressed: () => Navigator.pop(dlgContext),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: descController,
                      decoration: const InputDecoration(
                        labelText: 'Descrição da despesa',
                        hintText: 'Ex: Conta de água, compra de material...',
                        border: OutlineInputBorder(),
                      ),
                      style: const TextStyle(fontSize: 13),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: supplierController,
                      decoration: const InputDecoration(
                        labelText: 'Fornecedor / Beneficiário',
                        hintText: 'Ex: SIL, Empresa X...',
                        border: OutlineInputBorder(),
                      ),
                      style: const TextStyle(fontSize: 13),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
                      initialValue: category,
                      decoration: const InputDecoration(
                        labelText: 'Categoria',
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'Aluguel & Local', child: Text('Aluguel & Local')),
                        DropdownMenuItem(value: 'Utilidades', child: Text('Utilidades (Água/Luz/Net)')),
                        DropdownMenuItem(value: 'Manutenção & Equipamento', child: Text('Manutenção & Equipamentos')),
                        DropdownMenuItem(value: 'Eventos & Ministério', child: Text('Eventos & Ministério')),
                        DropdownMenuItem(value: 'Outros', child: Text('Outros')),
                      ],
                      onChanged: (val) {
                        if (val != null) setDlgState(() => category = val);
                      },
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: amountController,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(
                        labelText: 'Valor (CHF)',
                        prefixText: 'CHF ',
                        border: OutlineInputBorder(),
                      ),
                      style: const TextStyle(fontSize: 13),
                    ),
                    const SizedBox(height: 24),
                    Row(
                      children: [
                        Expanded(
                          child: TextButton(
                            onPressed: () => Navigator.pop(dlgContext),
                            child: const Text('CANCELAR'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: () {
                              final desc = descController.text.trim();
                              final supplier = supplierController.text.trim();
                              final amount = double.tryParse(amountController.text.replaceAll(',', '.')) ?? 0;

                              if (desc.isNotEmpty && amount > 0) {
                                final now = DateTime.now();
                                final dateStr = '${now.day.toString().padLeft(2, '0')}/${now.month.toString().padLeft(2, '0')}/${now.year}';
                                setState(() {
                                  _expenses.insert(
                                    0,
                                    ExpenseModel(
                                      id: DateTime.now().millisecondsSinceEpoch.toString(),
                                      description: desc,
                                      category: category,
                                      supplier: supplier.isEmpty ? 'N/I' : supplier,
                                      amount: amount,
                                      date: dateStr,
                                    ),
                                  );
                                });
                                Navigator.pop(dlgContext);
                              }
                            },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF1E3A8A),
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(vertical: 14),
                            ),
                            child: const Text('SALVAR DESPESA'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final screenWidth = MediaQuery.of(context).size.width;
    final isDesktop = screenWidth > 800;

    final totalDespesas = _expenses.fold(0.0, (sum, item) => sum + item.amount);

    return Scaffold(
      backgroundColor: const Color(0xFFFAFAFA),
      drawer: isDesktop ? null : const AppSidebarDrawer(activeRoute: 'despesas'),
      appBar: isDesktop
          ? null
          : AppBar(
              backgroundColor: Colors.white,
              foregroundColor: const Color(0xFF0F172A),
              elevation: 0,
              shape: const Border(bottom: BorderSide(color: Color(0xFFE5E7EB), width: 1)),
              title: const Text(
                'Gestão de Despesas',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
              ),
            ),
      body: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (isDesktop) const AppSidebarDrawer(activeRoute: 'despesas', permanent: true),
          Expanded(
            child: SingleChildScrollView(
              padding: EdgeInsets.all(isDesktop ? 32.0 : 16.0),
              child: Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 1100),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _buildHeader(isDesktop),
                      const SizedBox(height: 24),
                      _buildSummaryCard(totalDespesas, _expenses.length),
                      const SizedBox(height: 24),
                      _buildExpensesTable(isDesktop),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader(bool isDesktop) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'CONTROLE FINANCEIRO',
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: Color(0xFF64748B),
                letterSpacing: 1.5,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Despesas da Congregação',
              style: TextStyle(
                fontSize: isDesktop ? 24 : 20,
                fontWeight: FontWeight.bold,
                color: const Color(0xFF0F172A),
                letterSpacing: -0.5,
              ),
            ),
            const SizedBox(height: 4),
            const Text(
              'Registro e acompanhamento das despesas operacionais da igreja.',
              style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
            ),
          ],
        ),
        ElevatedButton.icon(
          onPressed: _showAddExpenseDialog,
          icon: const Icon(Icons.add_rounded, size: 18),
          label: const Text('Nova Despesa'),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF1E3A8A),
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            elevation: 0,
          ),
        ),
      ],
    );
  }

  Widget _buildSummaryCard(double total, int totalCount) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppTheme.excludeRed.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.account_balance_wallet_outlined, color: AppTheme.excludeRed, size: 28),
          ),
          const SizedBox(width: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'CHF ${total.toStringAsFixed(2)}',
                style: const TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF0F172A),
                  fontFamily: 'monospace',
                ),
              ),
              const SizedBox(height: 2),
              Text(
                'Total acumulado em despesas ($totalCount registros)',
                style: const TextStyle(fontSize: 13, color: Color(0xFF64748B)),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildExpensesTable(bool isDesktop) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
            decoration: const BoxDecoration(
              color: Color(0xFFF8FAFC),
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(8),
                topRight: Radius.circular(8),
              ),
              border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
            ),
            child: const Row(
              children: [
                SizedBox(
                  width: 90,
                  child: Text('DATA', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                ),
                Expanded(
                  flex: 2,
                  child: Text('DESCRIÇÃO / FORNECEDOR', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                ),
                Expanded(
                  child: Text('CATEGORIA', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                ),
                SizedBox(
                  width: 120,
                  child: Text('VALOR', textAlign: TextAlign.right, style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF64748B))),
                ),
                SizedBox(width: 48),
              ],
            ),
          ),
          if (_expenses.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 48),
              child: Center(
                child: Text('Nenhuma despesa registrada.', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13)),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _expenses.length,
              separatorBuilder: (_, __) => const Divider(height: 1, color: Color(0xFFE2E8F0)),
              itemBuilder: (context, index) {
                final item = _expenses[index];
                return Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                  child: Row(
                    children: [
                      SizedBox(
                        width: 90,
                        child: Text(item.date, style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                      ),
                      Expanded(
                        flex: 2,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(item.description, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: Color(0xFF0F172A))),
                            Text('Fornecedor: ${item.supplier}', style: const TextStyle(fontSize: 11, color: Color(0xFF64748B))),
                          ],
                        ),
                      ),
                      Expanded(
                        child: Text(item.category, style: const TextStyle(fontSize: 12, color: Color(0xFF475569))),
                      ),
                      SizedBox(
                        width: 120,
                        child: Text(
                          '- CHF ${item.amount.toStringAsFixed(2)}',
                          textAlign: TextAlign.right,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            fontFamily: 'monospace',
                            color: AppTheme.excludeRed,
                          ),
                        ),
                      ),
                      SizedBox(
                        width: 48,
                        child: IconButton(
                          icon: const Icon(Icons.delete_outline, size: 18, color: AppTheme.excludeRed),
                          onPressed: () {
                            setState(() {
                              _expenses.removeWhere((e) => e.id == item.id);
                            });
                          },
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
        ],
      ),
    );
  }
}
