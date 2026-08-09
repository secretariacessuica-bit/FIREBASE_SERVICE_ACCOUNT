import re

file_path = r'c:\Users\Wande\Documents\ia\6_APP_LAUSANNE_TESOURARIA\tesouraria_cme_app\lib\presentation\pages\wizard_page.dart'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

start_pattern = r'  Widget _buildReviewPhase\(BuildContext context, ServiceClosingState state\) \{'
end_pattern = r'  Widget _mathRow\(String label, int amountRappen, \{bool isBold = false, Color color = Colors\.black87\}\) \{[\s\S]*?    \);\n  \}'

match_start = re.search(start_pattern, content)
match_end = re.search(end_pattern, content)

if match_start and match_end:
    start_idx = match_start.start()
    end_idx = match_end.end()
    
    new_code = '''  Widget _buildReviewPhase(BuildContext context, ServiceClosingState state) {
    final screenWidth = MediaQuery.of(context).size.width;
    final isDesktop = screenWidth > 800;

    String formattedDate = DateFormat("EEEE, d 'de' MMMM 'de' yyyy", 'pt_BR').format(state.date ?? DateTime.now());
    if (formattedDate.isNotEmpty) {
      formattedDate = formattedDate.substring(0, 1).toUpperCase() + formattedDate.substring(1);
    }

    Widget leftColumn = Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text("Lançamentos Identificados", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF111827))),
            Text("${state.identifiedEntries.length}", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF111827))),
          ],
        ),
        const SizedBox(height: 12),
        Container(
          decoration: BoxDecoration(border: Border.all(color: const Color(0xFFE5E7EB)), borderRadius: BorderRadius.circular(8), color: Colors.white),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                child: Row(
                  children: const [
                    Expanded(flex: 2, child: Text("Contribuinte", style: TextStyle(fontSize: 12, color: Color(0xFF6B7280), fontWeight: FontWeight.w600))),
                    Expanded(child: Text("Categoria", style: TextStyle(fontSize: 12, color: Color(0xFF6B7280), fontWeight: FontWeight.w600))),
                    Expanded(child: Text("Valor", textAlign: TextAlign.right, style: TextStyle(fontSize: 12, color: Color(0xFF6B7280), fontWeight: FontWeight.w600))),
                  ],
                ),
              ),
              const Divider(height: 1, color: Color(0xFFE5E7EB)),
              if (state.identifiedEntries.isEmpty)
                const Padding(padding: EdgeInsets.all(24.0), child: Center(child: Text("Nenhum lançamento identificado.", style: TextStyle(color: Color(0xFF9CA3AF), fontSize: 13))))
              else
                ...state.identifiedEntries.reversed.take(6).map((env) {
                  return Column(
                    children: [
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        child: Row(
                          children: [
                            Expanded(flex: 2, child: Text(env.memberName, style: const TextStyle(fontSize: 13, color: Color(0xFF111827)))),
                            Expanded(child: Text(env.type.name.toUpperCase(), style: const TextStyle(fontSize: 13, color: Color(0xFF4B5563)))),
                            Expanded(
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.end,
                                children: [
                                  Text('CHF ${BigDecimalConverter.fromRappen(env.amount).toStringAsFixed(2)}', style: const TextStyle(fontSize: 13, color: Color(0xFF111827))),
                                  const SizedBox(width: 8),
                                  InkWell(
                                    onTap: () => context.read<ServiceClosingBloc>().add(RemoveEnvelopeEvent(env.id)),
                                    child: const Icon(Icons.delete_outline, size: 18, color: AppTheme.excludeRed),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Divider(height: 1, color: Color(0xFFF3F4F6)),
                    ],
                  );
                }),
              if (state.identifiedEntries.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  child: Center(
                    child: InkWell(
                      onTap: () {
                        // TODO: Implement "Ver todos" dialog se necessário
                      },
                      child: const Text("Ver todos os lançamentos  >", style: TextStyle(color: Color(0xFF1E3A8A), fontSize: 13, fontWeight: FontWeight.w600)),
                    ),
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 24),
        const Text("Lançamentos Anônimos por Categoria", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF111827))),
        const SizedBox(height: 12),
        Container(
          decoration: BoxDecoration(border: Border.all(color: const Color(0xFFE5E7EB)), borderRadius: BorderRadius.circular(8), color: Colors.white),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                child: Row(
                  children: const [
                    Expanded(child: Text("Categoria", style: TextStyle(fontSize: 12, color: Color(0xFF6B7280), fontWeight: FontWeight.w600))),
                    Expanded(child: Text("Quantidade", textAlign: TextAlign.center, style: TextStyle(fontSize: 12, color: Color(0xFF6B7280), fontWeight: FontWeight.w600))),
                    Expanded(child: Text("Valor", textAlign: TextAlign.right, style: TextStyle(fontSize: 12, color: Color(0xFF6B7280), fontWeight: FontWeight.w600))),
                  ],
                ),
              ),
              const Divider(height: 1, color: Color(0xFFE5E7EB)),
              ...[EnvelopeType.dizimo, EnvelopeType.oferta, EnvelopeType.voto].map((type) {
                int count = state.anonymousEntries.where((e) => e.type == type).length;
                int amount = state.anonymousTotalBy(type);
                return Column(
                  children: [
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      child: Row(
                        children: [
                          Expanded(child: Text(type.name.toUpperCase(), style: const TextStyle(fontSize: 13, color: Color(0xFF111827)))),
                          Expanded(child: Text("$count", textAlign: TextAlign.center, style: const TextStyle(fontSize: 13, color: Color(0xFF4B5563)))),
                          Expanded(child: Text('CHF ${BigDecimalConverter.fromRappen(amount).toStringAsFixed(2)}', textAlign: TextAlign.right, style: const TextStyle(fontSize: 13, color: Color(0xFF111827)))),
                        ],
                      ),
                    ),
                    if (type != EnvelopeType.voto)
                      const Divider(height: 1, color: Color(0xFFF3F4F6)),
                  ],
                );
              }),
            ],
          ),
        ),
      ],
    );

    Widget rightColumn = Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text("Resumo da contagem", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF111827))),
        const SizedBox(height: 12),
        Container(
          decoration: BoxDecoration(border: Border.all(color: const Color(0xFFE5E7EB)), borderRadius: BorderRadius.circular(8), color: Colors.white),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    _buildCategoryReview(context, state, EnvelopeType.dizimo, "DÍZIMO"),
                    _buildCategoryReview(context, state, EnvelopeType.oferta, "OFERTA"),
                    _buildCategoryReview(context, state, EnvelopeType.voto, "VOTO", isLast: true),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                decoration: const BoxDecoration(
                  color: Color(0xFFEFF6FF),
                  borderRadius: BorderRadius.only(bottomLeft: Radius.circular(8), bottomRight: Radius.circular(8)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text("TOTAL REGISTRADO", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1E3A8A))),
                    Text("CHF ${BigDecimalConverter.fromRappen(state.registeredTotal).toStringAsFixed(2)}", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1E3A8A))),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),
        const Text("Conferência do caixa", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF111827))),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(border: Border.all(color: const Color(0xFFE5E7EB)), borderRadius: BorderRadius.circular(8), color: Colors.white),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _mathRow("Total registrado", state.registeredTotal),
              const SizedBox(height: 6),
              _mathRow("Total físico", state.physicalTotal),
              const SizedBox(height: 16),
              _mathRow(
                "Diferença", 
                state.difference, 
                isBold: true, 
                color: state.difference == 0 ? const Color(0xFF1E3A8A) : AppTheme.excludeRed
              ),
              if (state.difference != 0)
                const Padding(
                  padding: EdgeInsets.only(top: 8.0, bottom: 16.0),
                  child: Text("A diferença deve ser zero para finalizar o fechamento.", style: TextStyle(color: AppTheme.excludeRed, fontSize: 11)),
                )
              else
                const SizedBox(height: 16),
              OutlinedButton.icon(
                onPressed: () => _showPhysicalTotalDialog(context, state, isDesktop),
                icon: const Icon(Icons.calculate_outlined, size: 18),
                label: const Text("INFORMAR TOTAL FÍSICO"),
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFF1E3A8A),
                  side: const BorderSide(color: Color(0xFF1E3A8A)),
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),
        DropdownButtonFormField<String>(
          value: _coTreasurerController.text.isEmpty || !state.knownMembers.contains(_coTreasurerController.text) 
                 ? null : _coTreasurerController.text,
          decoration: InputDecoration(
            labelText: "Co-tesoureiro",
            hintText: "Selecione o co-tesoureiro",
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE5E7EB))),
            enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: const BorderSide(color: Color(0xFFE5E7EB))),
            filled: true,
            fillColor: Colors.white,
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          ),
          items: state.knownMembers.map((String member) {
            return DropdownMenuItem<String>(value: member, child: Text(member, style: const TextStyle(fontSize: 14)));
          }).toList(),
          onChanged: (val) {
            if (val != null) {
              setState(() => _coTreasurerController.text = val);
            }
          },
        ),
        const SizedBox(height: 24),
        ElevatedButton(
          onPressed: (state.error == null && !state.isSubmitting && state.difference == 0 && state.physicalTotal > 0 && _coTreasurerController.text.isNotEmpty) ? () {
            _syncTimer?.cancel();
            context.read<ServiceClosingBloc>().add(
              InitializeClosingContextEvent(state.date ?? DateTime.now(), state.mainTreasurer, _coTreasurerController.text)
            );
            context.read<ServiceClosingBloc>().add(SubmitClosingEvent());
          } : null,
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF1E3A8A), 
            foregroundColor: Colors.white,
            disabledBackgroundColor: const Color(0xFFE5E7EB),
            disabledForegroundColor: const Color(0xFF9CA3AF),
            padding: const EdgeInsets.symmetric(vertical: 20),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            elevation: 0,
          ),
          child: state.isSubmitting
              ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
              : const Text("ENVIAR FECHAMENTO", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
        ),
        if (state.error != null)
          Padding(
            padding: const EdgeInsets.only(top: 8.0),
            child: Text(state.error!, textAlign: TextAlign.center, style: const TextStyle(color: AppTheme.excludeRed, fontSize: 12)),
          ),
      ],
    );

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text("Revisão e fechamento", style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF111827))),
          const SizedBox(height: 4),
          Text(formattedDate, style: const TextStyle(fontSize: 14, color: Color(0xFF4B5563))),
          const SizedBox(height: 4),
          const Text("Confira os lançamentos e o total físico antes de finalizar o fechamento.", style: TextStyle(fontSize: 14, color: Color(0xFF4B5563))),
          const SizedBox(height: 32),
          if (isDesktop)
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(flex: 3, child: leftColumn),
                const SizedBox(width: 32),
                Expanded(flex: 2, child: rightColumn),
              ],
            )
          else
            Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                rightColumn,
                const SizedBox(height: 32),
                leftColumn,
              ],
            ),
        ],
      ),
    );
  }

  void _showPhysicalTotalDialog(BuildContext context, ServiceClosingState state, bool isDesktop) {
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

            return Focus(
              autofocus: true,
              onKeyEvent: (node, event) {
                if (event is KeyDownEvent) {
                  final logicalKey = event.logicalKey;
                  if (logicalKey == LogicalKeyboardKey.digit0 || logicalKey == LogicalKeyboardKey.numpad0) {
                    dlgKeyPress('0');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.digit1 || logicalKey == LogicalKeyboardKey.numpad1) {
                    dlgKeyPress('1');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.digit2 || logicalKey == LogicalKeyboardKey.numpad2) {
                    dlgKeyPress('2');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.digit3 || logicalKey == LogicalKeyboardKey.numpad3) {
                    dlgKeyPress('3');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.digit4 || logicalKey == LogicalKeyboardKey.numpad4) {
                    dlgKeyPress('4');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.digit5 || logicalKey == LogicalKeyboardKey.numpad5) {
                    dlgKeyPress('5');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.digit6 || logicalKey == LogicalKeyboardKey.numpad6) {
                    dlgKeyPress('6');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.digit7 || logicalKey == LogicalKeyboardKey.numpad7) {
                    dlgKeyPress('7');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.digit8 || logicalKey == LogicalKeyboardKey.numpad8) {
                    dlgKeyPress('8');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.digit9 || logicalKey == LogicalKeyboardKey.numpad9) {
                    dlgKeyPress('9');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.backspace || logicalKey == LogicalKeyboardKey.delete) {
                    dlgKeyPress('⌫');
                    return KeyEventResult.handled;
                  } else if (logicalKey == LogicalKeyboardKey.enter) {
                    context.read<ServiceClosingBloc>().add(SetPhysicalTotalEvent(int.tryParse(localBuffer) ?? 0));
                    Navigator.pop(dlgContext);
                    return KeyEventResult.handled;
                  }
                }
                return KeyEventResult.ignored;
              },
              child: Dialog(
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: Container(
                  width: isDesktop ? 360 : MediaQuery.of(context).size.width * 0.95,
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text("Total físico", style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF111827))),
                          IconButton(
                            icon: const Icon(Icons.close, color: Color(0xFF6B7280)),
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                            onPressed: () => Navigator.pop(dlgContext),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      const Text("Dinheiro contado na mesa", style: TextStyle(fontSize: 14, color: Color(0xFF6B7280))),
                      const SizedBox(height: 24),
                      Container(
                        padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
                        decoration: BoxDecoration(color: const Color(0xFF0B1931), borderRadius: BorderRadius.circular(8)),
                        child: Center(
                          child: Text("CHF ${amount.toStringAsFixed(2)}", style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.bold, fontFamily: 'monospace')),
                        ),
                      ),
                      const SizedBox(height: 24),
                      GridView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 3, 
                          childAspectRatio: 1.6, 
                          mainAxisSpacing: 12, 
                          crossAxisSpacing: 12
                        ),
                        itemCount: 12,
                        itemBuilder: (context, index) {
                          final keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '00', '0', '⌫'];
                          String key = keys[index];
                          bool isBackspace = key == '⌫';
                          return ElevatedButton(
                            onPressed: () => dlgKeyPress(key),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: isBackspace ? const Color(0xFFFEE2E2) : const Color(0xFFF8FAFC),
                              foregroundColor: isBackspace ? const Color(0xFFDC2626) : const Color(0xFF0F172A),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              elevation: 0,
                              padding: EdgeInsets.zero,
                            ),
                            child: isBackspace
                                ? const Icon(Icons.backspace_outlined, size: 22)
                                : Text(key, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                          );
                        },
                      ),
                      const SizedBox(height: 24),
                      Row(
                        children: [
                          Expanded(
                            child: TextButton(
                              onPressed: () => Navigator.pop(dlgContext),
                              style: TextButton.styleFrom(
                                foregroundColor: const Color(0xFF1E3A8A),
                                padding: const EdgeInsets.symmetric(vertical: 16),
                              ),
                              child: const Text("CANCELAR", style: TextStyle(fontWeight: FontWeight.bold)),
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: ElevatedButton(
                              onPressed: () {
                                context.read<ServiceClosingBloc>().add(SetPhysicalTotalEvent(int.tryParse(localBuffer) ?? 0));
                                Navigator.pop(dlgContext);
                              },
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF1E3A8A),
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 16),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                elevation: 0,
                              ),
                              child: const Text("SALVAR TOTAL", style: TextStyle(fontWeight: FontWeight.bold)),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            );
          }
        );
      }
    );
  }

  Widget _buildCategoryReview(BuildContext context, ServiceClosingState state, EnvelopeType type, String title, {bool isLast = false}) {
    return Padding(
      padding: EdgeInsets.only(bottom: isLast ? 0 : 16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF111827))),
          const SizedBox(height: 8),
          _mathRow("Identificado", state.identifiedTotalBy(type)),
          const SizedBox(height: 4),
          _mathRow("Anônimo", state.anonymousTotalBy(type)),
          const SizedBox(height: 8),
          _mathRow("Subtotal", state.identifiedTotalBy(type) + state.anonymousTotalBy(type), isBold: true),
          if (!isLast)
            const Padding(
              padding: EdgeInsets.only(top: 16.0),
              child: Divider(height: 1, color: Color(0xFFE5E7EB)),
            ),
        ],
      ),
    );
  }

  Widget _mathRow(String label, int amountRappen, {bool isBold = false, Color color = const Color(0xFF4B5563)}) {
    double amount = BigDecimalConverter.fromRappen(amountRappen);
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: TextStyle(fontWeight: isBold ? FontWeight.bold : FontWeight.normal, fontSize: 13, color: color)),
        Text("CHF ${amount.toStringAsFixed(2)}", style: TextStyle(fontWeight: isBold ? FontWeight.bold : FontWeight.normal, fontSize: 13, color: color)),
      ],
    );
  }'''
    
    modified_content = content[:start_idx] + new_code + content[end_idx:]
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(modified_content)
    print('Successfully modified wizard_page.dart')
else:
    print('Could not find start or end pattern')
