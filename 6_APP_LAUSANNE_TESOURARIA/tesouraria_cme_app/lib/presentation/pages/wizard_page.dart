import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';
import '../blocs/service_closing_bloc.dart';
import '../blocs/service_closing_events_states.dart';
import '../../domain/envelope.dart';
import '../../core/monetary_utils.dart';
import '../../core/theme.dart';
import '../../services/draft_service.dart';
import '../widgets/app_sidebar_drawer.dart';
import 'dashboard_page.dart';

enum ClosingPhase { setup, counting, review }

class WizardPage extends StatefulWidget {
  const WizardPage({super.key});

  @override
  State<WizardPage> createState() => _WizardPageState();
}

class _WizardPageState extends State<WizardPage> {
  final TextEditingController _coTreasurerController = TextEditingController();
  final TextEditingController _memberNameController = TextEditingController();
  
  ClosingPhase _phase = ClosingPhase.setup;
  EnvelopeType _selectedType = EnvelopeType.dizimo;
  DateTime _selectedDate = DateTime.now();
  String _keyboardBuffer = '0';
  String? _validationError;
  late final ServiceClosingBloc _bloc;
  final DraftService _draftService = DraftService();

  @override
  void initState() {
    super.initState();
    _bloc = ServiceClosingBloc()..add(LoadMembersEvent());
    _checkForDraft();
  }

  Future<void> _checkForDraft() async {
    final draft = await _draftService.loadDraft();
    if (draft != null && mounted) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (dlgContext) => AlertDialog(
          title: const Text("Contagem em Andamento"),
          content: const Text("Encontramos uma contagem que não foi finalizada. Deseja retomá-la de onde parou?"),
          actions: [
            TextButton(
              onPressed: () {
                _draftService.clearDraft(); // Descarta o rascunho
                Navigator.pop(dlgContext);
              },
              child: const Text("DESCARTAR", style: TextStyle(color: AppTheme.excludeRed)),
            ),
            ElevatedButton(
              onPressed: () {
                _bloc.add(RestoreDraftEvent(draft));
                setState(() {
                  _selectedDate = draft.date ?? DateTime.now();
                  _coTreasurerController.text = draft.coTreasurer ?? "";
                  _phase = ClosingPhase.counting; // Retoma direto para a contagem
                });
                Navigator.pop(dlgContext);
              },
              child: const Text("RETOMAR"),
            ),
          ],
        ),
      );
    }
  }

  @override
  void dispose() {
    _coTreasurerController.dispose();
    _memberNameController.dispose();
    _bloc.close();
    super.dispose();
  }

  void _onKeyPress(String val) {
    setState(() {
      _validationError = null;
      if (val == '⌫') {
        if (_keyboardBuffer.length > 1) {
          _keyboardBuffer = _keyboardBuffer.substring(0, _keyboardBuffer.length - 1);
        } else {
          _keyboardBuffer = '0';
        }
      } else if (val == '00') {
        if (_keyboardBuffer != '0') {
          _keyboardBuffer += '00';
        }
      } else {
        if (_keyboardBuffer == '0') {
          _keyboardBuffer = val;
        } else {
          _keyboardBuffer += val;
        }
      }
    });
  }

  int _getAmountFromBuffer() {
    return int.tryParse(_keyboardBuffer) ?? 0;
  }

  double _getDecimalAmountFromBuffer() {
    return BigDecimalConverter.fromRappen(_getAmountFromBuffer());
  }

  @override
  Widget build(BuildContext context) {
    return BlocProvider.value(
      value: _bloc,
      child: Scaffold(
        appBar: AppBar(
          title: Text(_getAppBarTitle()),
          leading: _phase != ClosingPhase.setup ? IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: () {
              setState(() {
                if (_phase == ClosingPhase.review) {
                  _phase = ClosingPhase.counting;
                } else if (_phase == ClosingPhase.counting) {
                  _phase = ClosingPhase.setup;
                }
              });
            },
          ) : null,
        ),
        drawer: _phase == ClosingPhase.setup ? const AppSidebarDrawer(activeRoute: 'fechamento') : null,
        body: BlocBuilder<ServiceClosingBloc, ServiceClosingState>(
          builder: (context, state) {
            switch (_phase) {
              case ClosingPhase.setup:
                return _buildSetupPhase(context, state);
              case ClosingPhase.counting:
                return _buildCountingPhase(context, state);
              case ClosingPhase.review:
                return _buildReviewPhase(context, state);
            }
          },
        ),
      ),
    );
  }

  String _getAppBarTitle() {
    switch (_phase) {
      case ClosingPhase.setup: return "Configurar Sessão";
      case ClosingPhase.counting: return "PDV - Modo Contagem";
      case ClosingPhase.review: return "Revisão e Fechamento";
    }
  }

  Widget _buildSetupPhase(BuildContext context, ServiceClosingState state) {
    return Center(
      child: Container(
        constraints: const BoxConstraints(maxWidth: 500),
        padding: const EdgeInsets.all(24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text("Configurações Iniciais", style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold), textAlign: TextAlign.center),
            const SizedBox(height: 32),
            ListTile(
              title: const Text("Data do Culto"),
              subtitle: Text(DateFormat('dd/MM/yyyy').format(_selectedDate)),
              trailing: const Icon(Icons.calendar_today),
              shape: RoundedRectangleBorder(side: BorderSide(color: Colors.grey.shade300), borderRadius: BorderRadius.circular(8)),
              onTap: () async {
                DateTime? picked = await showDatePicker(
                  context: context,
                  initialDate: _selectedDate,
                  firstDate: DateTime(2020),
                  lastDate: DateTime(2030),
                );
                if (picked != null) {
                  setState(() {
                    _selectedDate = picked;
                  });
                }
              },
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _coTreasurerController,
              decoration: const InputDecoration(labelText: "Co-Tesoureiro", border: OutlineInputBorder()),
            ),
            const SizedBox(height: 24),
            const SizedBox(height: 48),
            ElevatedButton(
              onPressed: () {
                context.read<ServiceClosingBloc>().add(InitializeClosingContextEvent(_selectedDate, _coTreasurerController.text));
                setState(() => _phase = ClosingPhase.counting);
              },
              style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 20)),
              child: const Text("INICIAR CONTAGEM", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            )
          ],
        ),
      ),
    );
  }

  Widget _buildCountingPhase(BuildContext context, ServiceClosingState state) {
    return Center(
      child: Container(
        constraints: const BoxConstraints(maxWidth: 400),
        padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                SegmentedButton<EnvelopeType>(
                  segments: EnvelopeType.values.map((type) {
                    return ButtonSegment<EnvelopeType>(
                      value: type,
                      label: Text(type.name.toUpperCase(), style: const TextStyle(fontSize: 12)),
                    );
                  }).toList(),
                  selected: {_selectedType},
                  onSelectionChanged: (Set<EnvelopeType> newSelection) {
                    setState(() => _selectedType = newSelection.first);
                  },
                  style: SegmentedButton.styleFrom(
                    backgroundColor: Colors.white,
                    selectedBackgroundColor: AppTheme.institutionalBlue.withValues(alpha: 0.1),
                    selectedForegroundColor: AppTheme.institutionalBlue,
                    padding: const EdgeInsets.symmetric(horizontal: 4),
                  ),
                ),
                const SizedBox(height: 8),
                Align(
                  alignment: Alignment.centerRight,
                  child: Text(
                    "Total: CHF ${BigDecimalConverter.fromRappen(state.identifiedTotal + state.anonymousTotal).toStringAsFixed(2)}", 
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            if (_selectedType == EnvelopeType.dizimo)
              Padding(
                padding: const EdgeInsets.only(bottom: 16.0),
                child: Autocomplete<String>(
                  optionsBuilder: (TextEditingValue textEditingValue) {
                    if (textEditingValue.text.isEmpty) {
                      return const Iterable<String>.empty();
                    }
                    final currentText = textEditingValue.text.toLowerCase();
                    return state.knownMembers.where((String option) {
                      return option.toLowerCase().contains(currentText);
                    });
                  },
                  onSelected: (String selection) {
                    _memberNameController.text = selection;
                    if (_validationError != null) setState(() => _validationError = null);
                  },
                  fieldViewBuilder: (context, controller, focusNode, onFieldSubmitted) {
                    // Sincroniza o controller interno do Autocomplete com o nosso
                    controller.addListener(() {
                      if (_memberNameController.text != controller.text) {
                        _memberNameController.text = controller.text;
                      }
                    });
                    
                    // Se a gente esvaziar o nosso externamente, esvazia o do autocomplete
                    _memberNameController.addListener(() {
                      if (_memberNameController.text.isEmpty && controller.text.isNotEmpty) {
                        controller.clear();
                      }
                    });

                    return TextField(
                      controller: controller,
                      focusNode: focusNode,
                      decoration: InputDecoration(
                        labelText: "Contribuinte (Obrigatório)",
                        prefixIcon: const Icon(Icons.person),
                        errorText: _validationError,
                        border: const OutlineInputBorder(),
                      ),
                      onChanged: (_) {
                        if (_validationError != null) setState(() => _validationError = null);
                      },
                    );
                  },
                ),
              ),
            Container(
              padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 16),
              decoration: BoxDecoration(color: Colors.black87, borderRadius: BorderRadius.circular(8)),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text("Valor", style: TextStyle(color: Colors.grey, fontSize: 16)),
                  Text("CHF ${_getDecimalAmountFromBuffer().toStringAsFixed(2)}", 
                    style: const TextStyle(color: Colors.white, fontSize: 40, fontWeight: FontWeight.bold, fontFamily: 'monospace')),
                ],
              ),
            ),
            const SizedBox(height: 16),
            Expanded(
              child: GridView.builder(
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 3,
                  childAspectRatio: 1.6,
                  mainAxisSpacing: 8,
                  crossAxisSpacing: 8,
                ),
                itemCount: 12,
                itemBuilder: (context, index) {
                  final keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '00', '0', '⌫'];
                  String key = keys[index];
                  bool isBackspace = key == '⌫';
                  return ElevatedButton(
                    onPressed: () => _onKeyPress(key),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: isBackspace ? AppTheme.excludeRed : Colors.grey.shade200,
                      foregroundColor: isBackspace ? Colors.white : Colors.black87,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                    ),
                    child: Text(key, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
                  );
                },
              ),
            ),
            ElevatedButton(
              onPressed: () => _registerEntry(context),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.mathGreen,
                padding: const EdgeInsets.symmetric(vertical: 24),
              ),
              child: const Text("REGISTRAR", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 20)),
            ),
            const SizedBox(height: 16),
            TextButton(
              onPressed: () => setState(() => _phase = ClosingPhase.review),
              child: const Text("Finalizar contagem", style: TextStyle(color: Colors.grey, fontSize: 16)),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  void _registerEntry(BuildContext context) {
    final memberName = _memberNameController.text.trim();
    final int rappen = _getAmountFromBuffer();
    
    if (rappen <= 0) {
      setState(() => _validationError = "Valor inválido.");
      return;
    }

    if (_selectedType == EnvelopeType.dizimo && memberName.isEmpty) {
      setState(() => _validationError = "Nome obrigatório para dízimo.");
      return;
    }

    if (memberName.isNotEmpty) {
      final entryId = DateTime.now().microsecondsSinceEpoch.toString();
      final envelope = Envelope(id: entryId, memberName: memberName, type: _selectedType, amount: rappen);
      context.read<ServiceClosingBloc>().add(AddEnvelopeEvent(envelope));
      
      ScaffoldMessenger.of(context).clearSnackBars();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text("Lançamento identificado salvo!"),
        duration: const Duration(seconds: 3),
        action: SnackBarAction(label: "DESFAZER", onPressed: () => context.read<ServiceClosingBloc>().add(UndoAddedEntryEvent(entryId))),
      ));
    } else {
      // Oferta anônima
      final entry = AnonymousEntry(
        id: DateTime.now().microsecondsSinceEpoch.toString(),
        type: _selectedType,
        amount: rappen,
      );
      context.read<ServiceClosingBloc>().add(AddAnonymousOfferingEvent(entry));
      
      ScaffoldMessenger.of(context).clearSnackBars();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text("Oferta anônima somada ao caixa!"),
        duration: const Duration(seconds: 3),
        action: SnackBarAction(label: "DESFAZER", onPressed: () => context.read<ServiceClosingBloc>().add(UndoAnonymousOfferingEvent(entry.id))),
      ));
    }

    _memberNameController.clear();
    setState(() {
      _keyboardBuffer = '0';
      _validationError = null;
    });
  }

  Widget _buildReviewPhase(BuildContext context, ServiceClosingState state) {
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text("Revisão e Matemática do Caixa", style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          Expanded(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  flex: 3,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Text("Lançamentos Identificados", style: TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      Expanded(
                        child: Container(
                          decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade300), borderRadius: BorderRadius.circular(8)),
                          child: state.identifiedEntries.isEmpty
                            ? const Center(child: Text("Nenhum lançamento identificado."))
                            : ListView.builder(
                                itemCount: state.identifiedEntries.length,
                                itemBuilder: (context, index) {
                                  final env = state.identifiedEntries[state.identifiedEntries.length - 1 - index];
                                  return ListTile(
                                    leading: const Icon(Icons.mail),
                                    title: Text(env.memberName),
                                    subtitle: Text(env.type.name.toUpperCase()),
                                    trailing: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Text('CHF ${BigDecimalConverter.fromRappen(env.amount).toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.bold)),
                                        IconButton(icon: const Icon(Icons.delete, color: AppTheme.excludeRed), onPressed: () => context.read<ServiceClosingBloc>().add(RemoveEnvelopeEvent(env.id))),
                                      ],
                                    ),
                                  );
                                },
                              ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  flex: 2,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(color: AppTheme.mathGreen.withValues(alpha: 0.1), border: Border.all(color: AppTheme.mathGreen), borderRadius: BorderRadius.circular(8)),
                        child: Column(
                          children: [
                            _buildCategoryReview(context, state, EnvelopeType.dizimo, "DÍZIMO"),
                            _buildCategoryReview(context, state, EnvelopeType.oferta, "OFERTA"),
                            _buildCategoryReview(context, state, EnvelopeType.voto, "VOTO"),
                            const Divider(thickness: 2),
                            _mathRow("TOTAL REGISTRADO", state.registeredTotal, isBold: true),
                            const SizedBox(height: 16),
                            _mathRow("Total físico contado", state.physicalTotal),
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () => _showPhysicalTotalDialog(context, state),
                              icon: const Icon(Icons.calculate),
                              label: const Text("Informar Total Físico da Mesa"),
                            ),
                            const SizedBox(height: 16),
                            _mathRow("DIFERENÇA", state.difference, isBold: true, color: state.difference == 0 ? Colors.black87 : AppTheme.excludeRed),
                            if (state.difference == 0 && state.physicalTotal > 0)
                              const Align(alignment: Alignment.centerRight, child: Text("✓", style: TextStyle(color: Colors.green, fontSize: 24, fontWeight: FontWeight.bold))),
                            if (state.error != null) Padding(padding: const EdgeInsets.only(top: 8.0), child: Text(state.error!, style: const TextStyle(color: AppTheme.excludeRed, fontWeight: FontWeight.bold))),
                            if (state.difference != 0) const Padding(padding: EdgeInsets.only(top: 8.0), child: Text("A diferença deve ser zero para fechar o caixa.", style: TextStyle(color: AppTheme.excludeRed, fontWeight: FontWeight.bold))),
                          ],
                        ),
                      ),
                      const Spacer(),
                      ElevatedButton(
                        onPressed: (state.error == null && state.difference == 0 && state.physicalTotal > 0) ? () {
                          context.read<ServiceClosingBloc>().add(SubmitClosingEvent());
                          // Na vida real, haveria um BlocListener escutando o sucesso.
                          Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const DashboardScreen()));
                        } : null,
                        style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 24)),
                        child: const Text("ENVIAR FECHAMENTO", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      )
                    ],
                  ),
                )
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _showPhysicalTotalDialog(BuildContext context, ServiceClosingState state) {
    String localBuffer = '0';
    showDialog(
      context: context,
      builder: (dlgContext) {
        return StatefulBuilder(
          builder: (context, setDlgState) {
            void dlgKeyPress(String val) {
              setDlgState(() {
                if (val == '⌫') {
                  if (localBuffer.length > 1) {
                    localBuffer = localBuffer.substring(0, localBuffer.length - 1);
                  } else {
                    localBuffer = '0';
                  }
                } else if (val == '00') {
                  if (localBuffer != '0') {
                    localBuffer += '00';
                  }
                } else {
                  if (localBuffer == '0') {
                    localBuffer = val;
                  } else {
                    localBuffer += val;
                  }
                }
              });
            }

            double amount = BigDecimalConverter.fromRappen(int.tryParse(localBuffer) ?? 0);

            return AlertDialog(
              title: const Text("Total Físico (Dinheiro na mesa)"),
              content: SizedBox(
                width: 300,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(color: Colors.black87, borderRadius: BorderRadius.circular(8)),
                      child: Center(
                        child: Text("CHF ${amount.toStringAsFixed(2)}", style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold, fontFamily: 'monospace')),
                      ),
                    ),
                    const SizedBox(height: 16),
                    GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 3, childAspectRatio: 1.5, mainAxisSpacing: 8, crossAxisSpacing: 8),
                      itemCount: 12,
                      itemBuilder: (context, index) {
                        final keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '00', '0', '⌫'];
                        String key = keys[index];
                        return ElevatedButton(
                          onPressed: () => dlgKeyPress(key),
                          style: ElevatedButton.styleFrom(backgroundColor: key == '⌫' ? AppTheme.excludeRed : Colors.grey.shade200, foregroundColor: key == '⌫' ? Colors.white : Colors.black87, padding: EdgeInsets.zero),
                          child: Text(key, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        );
                      },
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(onPressed: () => Navigator.pop(dlgContext), child: const Text("CANCELAR")),
                ElevatedButton(
                  onPressed: () {
                    context.read<ServiceClosingBloc>().add(SetPhysicalTotalEvent(int.tryParse(localBuffer) ?? 0));
                    Navigator.pop(dlgContext);
                  },
                  child: const Text("SALVAR TOTAL"),
                ),
              ],
            );
          }
        );
      }
    );
  }

  Widget _buildCategoryReview(BuildContext context, ServiceClosingState state, EnvelopeType type, String title) {
    int ident = state.identifiedTotalBy(type);
    int anon = state.anonymousTotalBy(type);
    if (ident == 0 && anon == 0) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.only(bottom: 16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
          _mathRow("Identificado", ident),
          _mathRow("Não identificado", anon),
          const Divider(),
          _mathRow("Subtotal", ident + anon, isBold: true),
        ],
      ),
    );
  }

  Widget _mathRow(String label, int amountRappen, {bool isBold = false, Color color = Colors.black87}) {
    double amount = BigDecimalConverter.fromRappen(amountRappen);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontWeight: isBold ? FontWeight.bold : FontWeight.normal, fontSize: 15, color: color)),
          Text("CHF ${amount.toStringAsFixed(2)}", style: TextStyle(fontWeight: isBold ? FontWeight.bold : FontWeight.normal, fontSize: 15, color: color)),
        ],
      ),
    );
  }
}
