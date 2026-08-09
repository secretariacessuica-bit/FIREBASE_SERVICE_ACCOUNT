import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:intl/intl.dart';
import '../blocs/service_closing_bloc.dart';
import '../blocs/service_closing_events_states.dart';
import '../../domain/envelope.dart';
import '../../core/monetary_utils.dart';
import '../../core/theme.dart';
import '../../services/draft_service.dart';
import '../../services/fechamento_api_service.dart';
import '../widgets/app_sidebar_drawer.dart';
import 'dashboard_page.dart';
import 'package:shared_preferences/shared_preferences.dart';

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
  Timer? _syncTimer;

  @override
  void initState() {
    super.initState();
    _bloc = ServiceClosingBloc()..add(LoadMembersEvent());
    _checkForDraft();
  }

  Future<String> _getCurrentUserName() async {
    final prefs = await SharedPreferences.getInstance();
    final savedUser = prefs.getString('username') ?? "Tesoureiro";
    return savedUser.substring(0, 1).toUpperCase() + savedUser.substring(1);
  }

  Future<void> _checkForDraft() async {
    // Check server draft first to sync multi-device
    final serverDraft = await FechamentoApiService().getDraftFromServer();
    
    if (serverDraft != null && mounted) {
      final currentUserName = await _getCurrentUserName();
      
      // Auto-assign as co-treasurer if this user is not the main treasurer
      ServiceClosingState joinedDraft = serverDraft;
      if (serverDraft.mainTreasurer != currentUserName) {
        String newCoTreasurer = serverDraft.coTreasurer ?? "";
        if (!newCoTreasurer.contains(currentUserName)) {
          newCoTreasurer = newCoTreasurer.isEmpty 
              ? currentUserName 
              : "$newCoTreasurer, $currentUserName";
        }
        joinedDraft = serverDraft.copyWith(coTreasurer: newCoTreasurer);
        _bloc.add(InitializeClosingContextEvent(
          joinedDraft.date ?? DateTime.now(),
          joinedDraft.mainTreasurer,
          joinedDraft.coTreasurer ?? '',
        ));
      }
      if (!mounted) return;
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (dlgContext) => AlertDialog(
          title: const Text("Contagem Coletiva"),
          content: Text("Existe uma contagem ativa iniciada por ${joinedDraft.mainTreasurer} para o culto de ${joinedDraft.date != null ? DateFormat('dd/MM').format(joinedDraft.date!) : ''}. Deseja participar dela?"),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(dlgContext);
                _checkLocalDraftFallback();
              },
              child: const Text("IGNORAR"),
            ),
            ElevatedButton(
              onPressed: () {
                _bloc.add(RestoreDraftEvent(joinedDraft));
                setState(() {
                  _selectedDate = joinedDraft.date ?? DateTime.now();
                  _coTreasurerController.text = joinedDraft.coTreasurer ?? "";
                  _phase = ClosingPhase.counting;
                });
                Navigator.pop(dlgContext);
                _startSyncTimer();
              },
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1E3A8A), foregroundColor: Colors.white),
              child: const Text("PARTICIPAR"),
            ),
          ],
        ),
      );
      return;
    }

    _checkLocalDraftFallback();
  }

  Future<void> _checkLocalDraftFallback() async {
    final draft = await _draftService.loadDraft();
    if (draft != null && mounted) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (dlgContext) => AlertDialog(
          title: const Text("Contagem em Andamento"),
          content: const Text("Encontramos uma contagem local que não foi finalizada. Deseja retomá-la de onde parou?"),
          actions: [
            TextButton(
              onPressed: () {
                _draftService.clearDraft();
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
                  _phase = ClosingPhase.counting;
                });
                Navigator.pop(dlgContext);
                _startSyncTimer();
              },
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1E3A8A), foregroundColor: Colors.white),
              child: const Text("RETOMAR"),
            ),
          ],
        ),
      );
    }
  }

  void _startSyncTimer() {
    _syncTimer?.cancel();
    _syncTimer = Timer.periodic(const Duration(seconds: 5), (timer) async {
      final serverDraft = await FechamentoApiService().getDraftFromServer();
      if (serverDraft != null && mounted && _phase == ClosingPhase.counting) {
        // Only restore if the list or physical total changed on the server to prevent UI stutter
        if (serverDraft.identifiedEntries.length != _bloc.state.identifiedEntries.length ||
            serverDraft.anonymousEntries.length != _bloc.state.anonymousEntries.length ||
            serverDraft.physicalTotal != _bloc.state.physicalTotal ||
            serverDraft.coTreasurer != _bloc.state.coTreasurer ||
            serverDraft.mainTreasurer != _bloc.state.mainTreasurer) {
          _bloc.add(RestoreDraftEvent(serverDraft));
          setState(() {
            _selectedDate = serverDraft.date ?? DateTime.now();
            _coTreasurerController.text = serverDraft.coTreasurer ?? "";
          });
        }
      }
    });
  }

  @override
  void dispose() {
    _syncTimer?.cancel();
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
    final screenWidth = MediaQuery.of(context).size.width;
    final isDesktop = screenWidth > 800;

    Widget bodyContent = BlocBuilder<ServiceClosingBloc, ServiceClosingState>(
      builder: (context, state) {
        switch (_phase) {
          case ClosingPhase.setup:
            return _buildSetupPhase(context, state, isDesktop);
          case ClosingPhase.counting:
            return _buildCountingPhase(context, state);
          case ClosingPhase.review:
            return _buildReviewPhase(context, state);
        }
      },
    );

    // If we are on desktop and in the setup phase, show sidebar side-by-side
    if (isDesktop && _phase == ClosingPhase.setup) {
      bodyContent = Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const AppSidebarDrawer(activeRoute: 'fechamento', permanent: true),
          Expanded(child: bodyContent),
        ],
      );
    }

    return BlocProvider.value(
      value: _bloc,
      child: Scaffold(
        backgroundColor: const Color(0xFFFAFAFA),
        appBar: (isDesktop && _phase == ClosingPhase.setup)
            ? null // Hide AppBar on desktop setup phase to match dashboard
            : AppBar(
                backgroundColor: Colors.white,
                foregroundColor: const Color(0xFF0F172A),
                elevation: 0,
                shape: const Border(bottom: BorderSide(color: Color(0xFFE5E7EB), width: 1)),
                title: Text(
                  _getAppBarTitle(),
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                ),
                leading: _phase != ClosingPhase.setup
                    ? IconButton(
                        icon: const Icon(Icons.arrow_back),
                        onPressed: () {
                          setState(() {
                            if (_phase == ClosingPhase.review) {
                              _phase = ClosingPhase.counting;
                              _startSyncTimer();
                            } else if (_phase == ClosingPhase.counting) {
                              _syncTimer?.cancel();
                              _phase = ClosingPhase.setup;
                            }
                          });
                        },
                      )
                    : null,
              ),
        drawer: (isDesktop || _phase != ClosingPhase.setup)
            ? null
            : const AppSidebarDrawer(activeRoute: 'fechamento'),
        body: bodyContent,
        bottomNavigationBar: _phase == ClosingPhase.counting
            ? BottomNavigationBar(
                currentIndex: _selectedType.index,
                onTap: (index) {
                  setState(() {
                    _selectedType = EnvelopeType.values[index];
                    _validationError = null;
                  });
                },
                selectedItemColor: const Color(0xFF1E3A8A),
                unselectedItemColor: const Color(0xFF9CA3AF),
                showUnselectedLabels: true,
                items: const [
                  BottomNavigationBarItem(
                    icon: Icon(Icons.person_pin_circle_outlined),
                    label: 'Dízimo',
                  ),
                  BottomNavigationBarItem(
                    icon: Icon(Icons.volunteer_activism_outlined),
                    label: 'Oferta',
                  ),
                  BottomNavigationBarItem(
                    icon: Icon(Icons.star_outline_rounded),
                    label: 'Voto',
                  ),
                ],
              )
            : null,
      ),
    );
  }

  String _getAppBarTitle() {
    switch (_phase) {
      case ClosingPhase.setup: return "Novo fechamento";
      case ClosingPhase.counting: return "PDV - Modo Contagem";
      case ClosingPhase.review: return "Revisão e Fechamento";
    }
  }

  Widget _buildSetupPhase(BuildContext context, ServiceClosingState state, bool isDesktop) {
    // Format date: e.g. "Domingo, 09 de agosto de 2026"
    String formattedDate = DateFormat("EEEE, dd 'de' MMMM 'de' yyyy", 'pt_BR').format(_selectedDate);
    if (formattedDate.isNotEmpty) {
      formattedDate = formattedDate.substring(0, 1).toUpperCase() + formattedDate.substring(1);
    }

    return Container(
      color: const Color(0xFFFAFAFA),
      width: double.infinity,
      height: double.infinity,
      child: Center(
        child: SingleChildScrollView(
          child: Container(
            constraints: const BoxConstraints(maxWidth: 600),
            padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 48.0),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                // CONTAGEM DE CULTO
                Text(
                  "CONTAGEM DE CULTO",
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: isDesktop ? 13 : 11,
                    fontWeight: FontWeight.w600,
                    color: const Color(0xFF64748B),
                    letterSpacing: 1.5,
                  ),
                ),
                const SizedBox(height: 16),
                
                // Date text
                Text(
                  formattedDate,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: isDesktop ? 36 : 26,
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF0F172A),
                    letterSpacing: -0.5,
                  ),
                ),
                
                // Space between date and button
                SizedBox(height: isDesktop ? 100 : 80),
                
                // INICIAR button
                Container(
                  width: isDesktop ? 360 : double.infinity,
                  height: 60,
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(8),
                    gradient: const LinearGradient(
                      colors: [
                        Color(0xFF0A2E6B), // dark navy
                        Color(0xFF0C53D4), // royal blue
                      ],
                      begin: Alignment.centerLeft,
                      end: Alignment.centerRight,
                    ),
                  ),
                  child: Material(
                    color: Colors.transparent,
                    child: InkWell(
                      borderRadius: BorderRadius.circular(8),
                      onTap: () async {
                        final currentUserName = await _getCurrentUserName();
                        if (context.mounted) {
                          context.read<ServiceClosingBloc>().add(
                            InitializeClosingContextEvent(_selectedDate, currentUserName, '')
                          );
                          setState(() => _phase = ClosingPhase.counting);
                          _startSyncTimer();
                        }
                      },
                      child: const Center(
                        child: Text(
                          "INICIAR",
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 1.0,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
                
                // Space between button and text
                const SizedBox(height: 48),
                
                // Explanatory text below
                Container(
                  constraints: const BoxConstraints(maxWidth: 380),
                  child: const Text(
                    "Ao iniciar, você poderá registrar os valores de dízimos, ofertas e votos e finalizar o fechamento.",
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 13,
                      color: Color(0xFF64748B),
                      height: 1.5,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildCountingPhase(BuildContext context, ServiceClosingState state) {
    return Center(
      child: SingleChildScrollView(
        child: Container(
          constraints: const BoxConstraints(maxWidth: 400),
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            mainAxisSize: MainAxisSize.min,
            children: [
              Align(
                alignment: Alignment.centerRight,
                child: Padding(
                  padding: const EdgeInsets.only(bottom: 12.0),
                  child: Text(
                    "Total: CHF ${BigDecimalConverter.fromRappen(state.identifiedTotal + state.anonymousTotal).toStringAsFixed(2)}", 
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF111827)),
                  ),
                ),
              ),
              if (_selectedType == EnvelopeType.dizimo)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12.0),
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
                      controller.addListener(() {
                        if (_memberNameController.text != controller.text) {
                          _memberNameController.text = controller.text;
                        }
                      });
                      
                      _memberNameController.addListener(() {
                        if (_memberNameController.text.isEmpty && controller.text.isNotEmpty) {
                          controller.clear();
                        }
                      });

                      return TextField(
                        controller: controller,
                        focusNode: focusNode,
                        decoration: InputDecoration(
                          labelText: "Contribuinte (Opcional)",
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
                padding: const EdgeInsets.symmetric(vertical: 18, horizontal: 16),
                decoration: BoxDecoration(color: const Color(0xFF111827), borderRadius: BorderRadius.circular(8)),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text("Valor", style: TextStyle(color: Color(0xFF9CA3AF), fontSize: 14)),
                    Text(
                      "CHF ${_getDecimalAmountFromBuffer().toStringAsFixed(2)}", 
                      style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold, fontFamily: 'monospace'),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 3,
                  childAspectRatio: 1.8,
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
                      backgroundColor: isBackspace ? AppTheme.excludeRed : const Color(0xFFF3F4F6),
                      foregroundColor: isBackspace ? Colors.white : const Color(0xFF111827),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                      elevation: 0,
                    ),
                    child: Text(key, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                  );
                },
              ),
              const SizedBox(height: 12),
              ElevatedButton(
                onPressed: () => _registerEntry(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF1E3A8A),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 18),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  elevation: 0,
                ),
                child: const Text("REGISTRAR", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, letterSpacing: 0.5)),
              ),
              const SizedBox(height: 8),
              TextButton(
                onPressed: () {
                  _syncTimer?.cancel();
                  setState(() => _phase = ClosingPhase.review);
                },
                child: const Text("Ir para revisão →", style: TextStyle(color: Color(0xFF4B5563), fontSize: 13, fontWeight: FontWeight.w600)),
              ),
              const SizedBox(height: 8),
            ],
          ),
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

    if (memberName.isNotEmpty) {
      final entryId = DateTime.now().microsecondsSinceEpoch.toString();
      final envelope = Envelope(id: entryId, memberName: memberName, type: _selectedType, amount: rappen);
      context.read<ServiceClosingBloc>().add(AddEnvelopeEvent(envelope));
      
      ScaffoldMessenger.of(context).clearSnackBars();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: const Text("Lançamento identificado salvo!"),
        duration: const Duration(milliseconds: 1200),
        behavior: SnackBarBehavior.floating,
        action: SnackBarAction(label: "DESFAZER", textColor: Colors.yellow, onPressed: () => context.read<ServiceClosingBloc>().add(UndoAddedEntryEvent(entryId))),
      ));
    } else {
      // Anonymous
      final entry = AnonymousEntry(
        id: DateTime.now().microsecondsSinceEpoch.toString(),
        type: _selectedType,
        amount: rappen,
      );
      context.read<ServiceClosingBloc>().add(AddAnonymousOfferingEvent(entry));
      
      ScaffoldMessenger.of(context).clearSnackBars();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text("${_selectedType == EnvelopeType.dizimo ? 'Dízimo' : _selectedType == EnvelopeType.voto ? 'Voto' : 'Oferta'} anônima somada!"),
        duration: const Duration(milliseconds: 1200),
        behavior: SnackBarBehavior.floating,
        action: SnackBarAction(label: "DESFAZER", textColor: Colors.yellow, onPressed: () => context.read<ServiceClosingBloc>().add(UndoAnonymousOfferingEvent(entry.id))),
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
          const Text("Revisão e Matemática do Caixa", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF111827))),
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
                      const Text("Lançamentos Identificados", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF4B5563))),
                      const SizedBox(height: 8),
                      Expanded(
                        child: Container(
                          decoration: BoxDecoration(border: Border.all(color: const Color(0xFFE5E7EB)), borderRadius: BorderRadius.circular(8), color: Colors.white),
                          child: state.identifiedEntries.isEmpty
                            ? const Center(child: Text("Nenhum lançamento identificado.", style: TextStyle(color: Color(0xFF9CA3AF), fontSize: 13)))
                            : ListView.builder(
                                itemCount: state.identifiedEntries.length,
                                itemBuilder: (context, index) {
                                  final env = state.identifiedEntries[state.identifiedEntries.length - 1 - index];
                                  return ListTile(
                                    leading: const Icon(Icons.mail_outline_rounded, color: Color(0xFF1E3A8A)),
                                    title: Text(env.memberName, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                                    subtitle: Text(env.type.name.toUpperCase(), style: const TextStyle(fontSize: 10)),
                                    trailing: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Text('CHF ${BigDecimalConverter.fromRappen(env.amount).toStringAsFixed(2)}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                        IconButton(icon: const Icon(Icons.delete_outline_rounded, color: AppTheme.excludeRed), onPressed: () => context.read<ServiceClosingBloc>().add(RemoveEnvelopeEvent(env.id))),
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
                        decoration: BoxDecoration(
                          color: const Color(0xFFF9FAFB), 
                          border: Border.all(color: const Color(0xFFE5E7EB)), 
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Column(
                          children: [
                            _buildCategoryReview(context, state, EnvelopeType.dizimo, "DÍZIMO"),
                            _buildCategoryReview(context, state, EnvelopeType.oferta, "OFERTA"),
                            _buildCategoryReview(context, state, EnvelopeType.voto, "VOTO"),
                            const Divider(thickness: 1, color: Color(0xFFE5E7EB)),
                            _mathRow("TOTAL REGISTRADO", state.registeredTotal, isBold: true),
                            const SizedBox(height: 12),
                            _mathRow("Total físico contado", state.physicalTotal),
                            const SizedBox(height: 12),
                            ElevatedButton.icon(
                              onPressed: () => _showPhysicalTotalDialog(context, state),
                              icon: const Icon(Icons.calculate_outlined, size: 16),
                              label: const Text("Informar Total Físico"),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFFF3F4F6),
                                foregroundColor: const Color(0xFF1E3A8A),
                                elevation: 0,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                              ),
                            ),
                            const SizedBox(height: 12),
                            _mathRow("DIFERENÇA", state.difference, isBold: true, color: state.difference == 0 ? Colors.green.shade700 : AppTheme.excludeRed),
                            if (state.difference == 0 && state.physicalTotal > 0)
                              const Align(alignment: Alignment.centerRight, child: Icon(Icons.check_circle, color: Colors.green, size: 24)),
                            if (state.error != null) Padding(padding: const EdgeInsets.only(top: 8.0), child: Text(state.error!, style: const TextStyle(color: AppTheme.excludeRed, fontWeight: FontWeight.bold, fontSize: 12))),
                            if (state.difference != 0) const Padding(padding: EdgeInsets.only(top: 6.0), child: Text("A diferença deve ser zero para fechar.", style: TextStyle(color: AppTheme.excludeRed, fontWeight: FontWeight.bold, fontSize: 11))),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                      // Co-Treasurer Input at Final Review
                      TextField(
                        controller: _coTreasurerController,
                        decoration: const InputDecoration(
                          labelText: "Co-Tesoureiro",
                          border: OutlineInputBorder(),
                          fillColor: Colors.white,
                          filled: true,
                          contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                        ),
                        style: const TextStyle(fontSize: 13),
                      ),
                      const Spacer(),
                      ElevatedButton(
                        onPressed: (state.error == null && state.difference == 0 && state.physicalTotal > 0) ? () {
                          context.read<ServiceClosingBloc>().add(
                            InitializeClosingContextEvent(state.date ?? DateTime.now(), state.mainTreasurer, _coTreasurerController.text)
                          );
                          context.read<ServiceClosingBloc>().add(SubmitClosingEvent());
                          Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const DashboardScreen()));
                        } : null,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF1E3A8A), 
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 20),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          elevation: 0,
                        ),
                        child: const Text("ENVIAR FECHAMENTO", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
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
                          style: ElevatedButton.styleFrom(
                            backgroundColor: key == '⌫' ? AppTheme.excludeRed : Colors.grey.shade200, 
                            foregroundColor: key == '⌫' ? Colors.white : Colors.black87, 
                            padding: EdgeInsets.zero,
                            elevation: 0,
                          ),
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
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF1E3A8A), foregroundColor: Colors.white),
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
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF111827))),
          const SizedBox(height: 2),
          _mathRow("Identificado", ident),
          _mathRow("Anônimo", anon),
          const Divider(color: Color(0xFFE5E7EB)),
          _mathRow("Subtotal", ident + anon, isBold: true),
        ],
      ),
    );
  }

  Widget _mathRow(String label, int amountRappen, {bool isBold = false, Color color = Colors.black87}) {
    double amount = BigDecimalConverter.fromRappen(amountRappen);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: TextStyle(fontWeight: isBold ? FontWeight.bold : FontWeight.normal, fontSize: 13, color: color)),
          Text("CHF ${amount.toStringAsFixed(2)}", style: TextStyle(fontWeight: isBold ? FontWeight.bold : FontWeight.normal, fontSize: 13, color: color)),
        ],
      ),
    );
  }
}
